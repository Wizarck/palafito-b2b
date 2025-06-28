<?php

namespace Vibe\Merge_Orders;

use DateTime;
use WC_Data_Exception;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * AJAX request handlers
 *
 * @since 1.0.0
 */
class AJAX {

	/**
	 * Creates an instance and sets up the AJAX actions
	 */
	public function __construct() {
		$action = Merge_Orders::hook_prefix( 'popup_orders' );
		add_action( "wp_ajax_{$action}", array( __CLASS__, 'get_popup' ) );

		$action = Merge_Orders::hook_prefix( 'get_orders' );
		add_action( "wp_ajax_{$action}", array( __CLASS__, 'get_orders' ) );

		$action = Merge_Orders::hook_prefix( 'merge_order' );
		add_action( "wp_ajax_{$action}", array( __CLASS__, 'merge_orders' ) );
	}

	/**
	 * Handles an AJAX request to fetch orders HTML in the merging modal
	 *
	 * Sends a JSON response containing a success flag and the HTML to be used for the modal
	 */
	public static function get_popup() {
		$response = array(
			'success' => false
		);

		$nonce = isset( $_REQUEST['nonce'] ) ? wc_clean( $_REQUEST['nonce'] ) : false;

		if ( ! empty( $nonce ) && wp_verify_nonce( $nonce, Merge_Orders::hook_prefix( 'popup-nonce' ) ) ) {
			$order_id = isset( $_REQUEST['order_id'] ) ? absint( $_REQUEST['order_id'] ) : false;

			if ( Orders::can_merge( $order_id ) ) {
				$response['html']    = Admin::get_merging_popup( $order_id );
				$response['success'] = ! empty( $response['html'] );
			}
		}

		wp_send_json( $response );
	}

	/**
	 * Handles an AJAX request to fetch the orders to be used in the order selector in the merging modal
	 *
	 * Sends a JSON response containing a success flag, the results of the search, and pagination data
	 */
	public static function get_orders() {
		$response = array(
			'success' => false,
		);

		$nonce = isset( $_REQUEST['nonce'] ) ? wc_clean( $_REQUEST['nonce'] ) : false;

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, Merge_Orders::hook_prefix( 'get-orders-nonce') ) ) {
			$response['error'] = __( 'Invalid request, please refresh the page and try again', 'merge-orders' );

			wp_send_json( $response ); // die
		}

		$search                 = isset( $_GET['search'] ) ? wc_clean( $_GET['search'] ) : '';
		$amount                 = isset( $_GET['amount'] ) ? absint( $_GET['amount'] ) : 20;
		$page                   = isset( $_GET['page'] ) ? absint( $_GET['page'] ) : 1;
		$target_id              = isset( $_GET['target_id'] ) ? absint( $_GET['target_id'] ) : 0;

		$orders = static::search_orders( $search, $amount, $page, $target_id );

		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );

		// Provide default formats in case they're unset
		$date_format = $date_format ? $date_format : 'F j, Y';
		$time_format = $time_format ? $time_format : 'g:i a';

		foreach ( $orders as $order ) {
			$order_id = $order['ID'];
			$wc_order = wc_get_order( $order_id );
			unset( $order['ID'] );

			$order['date'] = DateTime::createFromFormat( 'Y-m-d H:i:s', $order['date'] )->format( "{$date_format} @ {$time_format}" );
			$order['date'] = "({$order['date']})";

			$order['status'] = '[' . str_replace( 'wc-', '', $order['status'] ) . ']';
			$label           = "#{$wc_order->get_order_number()} " . implode( ' ', $order );
			$row_content = <<<HTML
<tr class="order-{$order_id}">
	<td>
		{$label}
		<a href="{$wc_order->get_edit_order_url()}" target="_blank" class="dashicons dashicons-external" title="View order"></a>
	</td>
	<td>
		<a href="#!" class="remove-order-from-merge dashicons dashicons-no-alt" title="Remove"></a>
	</td>
</tr>
HTML;

			$response['results'][] = array(
				'id'          => $order_id,
				'text'        => $label,
				'row_content' => $row_content,
			);
		}

		$response['pagination']['more'] = ! count( $orders ) < $amount;

		wp_send_json( $response );
	}

	protected static function search_orders( $search_string, $quantity, $page, $exclude_id ) {
		global $wpdb;

		$optimised_search = Admin::use_optimised_search();
		$invalid_merge_statuses = Orders::invalid_merge_statuses();

		// Escape the merge statuses separately before using them in "IN" clause
		$invalid_merge_statuses = array_map( function( $v ) {
			return "'" . esc_sql( $v ) . "'";
		}, $invalid_merge_statuses );

		// This is hacky, but necessary to convince the code sniffer we have escaped the values
		$wpdb->invalid_merge_statuses = implode( ', ', $invalid_merge_statuses );

		if ( $optimised_search ) {
			// With optimised search we just search for order ID which saves concatenating non-indexed columns
			if ( Admin::is_using_hpos() ) {
				$orders = $wpdb->get_results( $wpdb->prepare(
					"SELECT orders.ID, address.first_name AS first_name, address.last_name AS last_name, orders.date_created_gmt AS date, orders.status AS status
					FROM {$wpdb->prefix}wc_orders AS orders 
				         LEFT JOIN {$wpdb->prefix}wc_order_addresses AS address
				            ON address.order_id = orders.id AND address.address_type = 'billing'
					WHERE
					  orders.ID != %d
					  AND orders.status NOT IN ({$wpdb->invalid_merge_statuses})
					  AND orders.type = 'shop_order'
					  AND orders.ID LIKE %s
					  ORDER BY orders.ID DESC
					  LIMIT %d OFFSET %d",
					$exclude_id,
					'%' . $search_string . '%',
					$quantity,
					$quantity * ( $page - 1 )
				), ARRAY_A );
			} else {
				$orders = $wpdb->get_results( $wpdb->prepare(
					"SELECT orders.ID, first_names.meta_value AS first_name, last_names.meta_value AS last_name, orders.post_date AS date, orders.post_status AS status
					FROM {$wpdb->posts} AS orders
						 LEFT JOIN {$wpdb->postmeta} AS first_names
							ON first_names.post_id = orders.ID AND first_names.meta_key = '_billing_first_name'
						 LEFT JOIN {$wpdb->postmeta} AS last_names
							ON last_names.post_id = orders.ID AND last_names.meta_key = '_billing_last_name'
					WHERE
					  orders.ID != %d
					  AND orders.post_status NOT IN ({$wpdb->invalid_merge_statuses})
					  AND orders.post_type = 'shop_order'
					  AND orders.ID LIKE %s
					  ORDER BY orders.ID DESC
					  LIMIT %d OFFSET %d",
					$exclude_id,
					'%' . $search_string . '%',
					$quantity,
					$quantity * ( $page - 1 )
				), ARRAY_A );
			}
		} else {
			if ( Admin::is_using_hpos() ) {
				$orders = $wpdb->get_results( $wpdb->prepare(
					"SELECT orders.ID, address.first_name AS first_name, address.last_name AS last_name, orders.date_created_gmt AS date, orders.status AS status
					FROM {$wpdb->prefix}wc_orders AS orders 
				         LEFT JOIN {$wpdb->prefix}wc_order_addresses AS address
				            ON address.order_id = orders.id AND address.address_type = 'billing'
					WHERE
					  orders.ID != %d
					  AND orders.status NOT IN ({$wpdb->invalid_merge_statuses})
					  AND orders.type = 'shop_order'
					  AND CONCAT_WS(' ', CONCAT( '#', orders.ID ), address.first_name, address.last_name, orders.date_created_gmt, orders.status) LIKE %s
					  ORDER BY orders.ID DESC
					  LIMIT %d OFFSET %d",
					$exclude_id,
					'%' . $search_string . '%',
					$quantity,
					$quantity * ( $page - 1 )
				), ARRAY_A );
			} else {
				$orders = $wpdb->get_results( $wpdb->prepare(
					"SELECT orders.ID, first_names.meta_value AS first_name, last_names.meta_value AS last_name, orders.post_date AS date, orders.post_status AS status
					FROM {$wpdb->posts} AS orders
						 LEFT JOIN {$wpdb->postmeta} AS first_names
							ON first_names.post_id = orders.ID AND first_names.meta_key = '_billing_first_name'
						 LEFT JOIN {$wpdb->postmeta} AS last_names
							ON last_names.post_id = orders.ID AND last_names.meta_key = '_billing_last_name'
					WHERE
					  orders.ID != %d
					  AND orders.post_status NOT IN ({$wpdb->invalid_merge_statuses})
					  AND orders.post_type = 'shop_order'
					  AND CONCAT_WS(' ', CONCAT( '#', orders.ID ), first_names.meta_value, last_names.meta_value, orders.post_date, orders.post_status) LIKE %s
					  ORDER BY orders.ID DESC
					  LIMIT %d OFFSET %d",
					$exclude_id,
					'%' . $search_string . '%',
					$quantity,
					$quantity * ( $page - 1 )
				), ARRAY_A );
			}
		}

		return $orders;
	}

	/**
	 * Handles an AJAX request to merge orders
	 *
	 * Sends a JSON response containing a success flag
	 */
	public static function merge_orders() {
		Merge_Orders::logger()->log( 'Merge requested by AJAX' );

		$response = array(
			'success' => false,
		);

		$nonce = isset( $_REQUEST['nonce'] ) ? wc_clean( $_REQUEST['nonce'] ) : false;

		if ( ! empty( $nonce ) && wp_verify_nonce( $nonce, Merge_Orders::hook_prefix( 'merging-nonce' ) ) ) {
			$target_id = isset( $_REQUEST['target_id'] ) ? absint( $_REQUEST['target_id'] ) : false;

			if ( Orders::can_merge( $target_id ) ) {
				try {
					$order_ids = isset( $_REQUEST['order_ids'] ) ? wc_clean( $_REQUEST['order_ids'] ) : '';
					$order_ids = explode( '|', $order_ids );

					Merge_Orders::logger()->log( 'Requesting merge of orders ' . implode( ', ', $order_ids ) . " into target order {$target_id}" );

					$result = Orders::merge( $target_id, $order_ids );

					$response['success'] = $result['success'];
					$response['error'] = isset( $result['error'] ) ? $result['error'] : '';

				} catch ( WC_Data_Exception $e ) {
					$response['success'] = false;
					$response['error']   = __( 'Error occurred merging orders', 'merge-orders' );

					Merge_Orders::logger()->log( 'Exception triggered during merge: ' . $e->getMessage(), Logger::LOG_ERROR );
				}
			} else {
				Merge_Orders::logger()->log( "Target order {$target_id} is not mergeable", Logger::LOG_ERROR );
			}
		} else {
			Merge_Orders::logger()->log( 'AJAX nonce verification failed', Logger::LOG_ERROR );
		}

		Merge_Orders::logger()->log( 'AJAX response to return: ' . print_r( $response, true ) );

		wp_send_json( $response );
	}
}
