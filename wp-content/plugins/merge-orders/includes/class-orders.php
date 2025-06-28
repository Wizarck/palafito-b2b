<?php

namespace Vibe\Merge_Orders;

use InvalidArgumentException;
use WC_Order;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Handles registering custom order status and provides utilities through static methods
 *
 * @since 1.0.0
 */
class Orders {

	const MERGED_INTO_KEY = '_vibe_merge_orders_merged_into';

	/**
	 * Creates an instance and sets up hooks
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'register_custom_order_status' ) );
		add_filter( 'wc_order_statuses', array( __CLASS__, 'add_order_status' ) );
	}

	/**
	 * Register the custom post status applied to orders that have been merged into another
	 */
	public static function register_custom_order_status() {
		register_post_status( 'wc-merged', array(
			'label'                     => _x( 'Merged', 'Order status', 'merge-orders' ),
			'internal'                  => true,
			'public'                    => false,
			'exclude_from_search'       => true,
			/**
			 * Modify whether or not to show orders with this status in the all list
			 *
			 * @param bool $show Whether or not to show in the admin all list
			 *
			 * @since 1.0.0
			 */
			'show_in_admin_all_list'    => apply_filters( Merge_Orders::hook_prefix( 'show_merged_in_admin_all_list' ), true ),
			'show_in_admin_status_list' => true,
			/* translators: %s: The total number of merged orders */
			'label_count'               => _n_noop( 'Merged <span class="count">(%s)</span>', 'Merged <span class="count">(%s)</span>', 'merge-orders' ),
		) );
	}

	/**
	 * Add our custom status to the list of WooCommerce order statuses
	 *
	 * @param array $statuses The order statuses
	 *
	 * @return array The order statuses with ours included
	 */
	public static function add_order_status( array $statuses ) {
		$statuses['wc-merged'] = _x( 'Merged', 'Order status', 'merge-orders' );

		return $statuses;
	}

	/**
	 * Get an array of post statuses that make an order invalid for merging
	 *
	 * @return string[] The invalid merge statuses
	 */
	public static function invalid_merge_statuses() {
		/**
		 * Add or remove invalid merge statuses
		 *
		 * @param string[] $invalid_merge_statuses The invalid merge statuses
		 *
		 * @since 1.0.0
		 */
		return apply_filters( Merge_Orders::hook_prefix('invalid_merge_statuses' ), array(
			'auto-draft',
			'wc-merged',
			'trash',
		) );
	}

	/**
	 * Returns true if the given order can be merged by the current user
	 *
	 * @param int $order_id The ID of the order to check
	 *
	 * @return bool True if the order can be merged and false otherwise
	 */
	public static function can_merge( $order_id ) {
		$order        = wc_get_order( $order_id );
		$is_mergeable = ! in_array( $order->get_status(), static::invalid_merge_statuses() );
		$user_can     = current_user_can( 'edit_shop_orders', $order_id );

		$can_merge = $is_mergeable && $user_can;

		/**
		 * Allow overriding whether or not a particular order can be merged
		 *
		 * @since 1.0.0
		 *
		 * @param bool $can_merge Whether or not the order can be merged
		 * @param WC_Order $order The order that is being checked
		 */
		return apply_filters( Merge_Orders::hook_prefix( 'can_merge' ), $can_merge, $order );
	}

	/**
	 * Merges orders
	 *
	 * @param int $target_order_id The ID of the order to merge into
	 * @param array $order_ids An array of order IDs to be merged into the target order
	 *
	 * @return array The result of the merge containing a success flag and a potential error message
	 */
	public static function merge( $target_order_id, array $order_ids ) {
		$target = wc_get_order( $target_order_id );
		$orders = array_map( 'wc_get_order', $order_ids );

		try {
			$handler = new Merge_Handler( $target, $orders );
			$result  = $handler->merge();
			$result  = array(
				'success' => $result
			);
		} catch ( InvalidArgumentException $e ) {
			return array(
				'success' => false,
				'error'   => $e->getMessage(),
			);
		}

		return $result;
	}

	/**
	 * Checks whether or not the given order has been merged into before
	 *
	 * @param int $order_id The ID of the order to check
	 *
	 * @return bool True if the given order ID has been merged into before, false otherwise
	 */
	public static function has_been_merged_into( $order_id ) {
		$order = wc_get_order( $order_id );

		return $order->get_meta( self::MERGED_INTO_KEY ) === 'yes';
	}
}
