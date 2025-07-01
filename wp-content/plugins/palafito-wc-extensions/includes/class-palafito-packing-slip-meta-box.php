<?php
/**
 * Packing Slip Meta Box Extension
 *
 * Extends the PRO functionality to include packing slip in the meta box
 * with the same structure as invoice, using PRO meta keys and logic.
 *
 * @package Palafito_WC_Extensions
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Palafito_Packing_Slip_Meta_Box
 *
 * Extends the PRO meta box functionality to include packing slip editing.
 */
class Palafito_Packing_Slip_Meta_Box {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize hooks.
	 */
	public function init() {
		// Enable packing slip if not already enabled.
		add_action( 'init', array( $this, 'enable_packing_slip_if_needed' ) );

		// Add packing slip to the existing PDF meta box.
		add_action( 'wpo_wcpdf_meta_box_start', array( $this, 'add_packing_slip_to_meta_box' ), 10, 2 );

		// Add delivery date column to orders list.
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_delivery_date_column' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'delivery_date_column_data' ), 10, 2 );
		add_filter( 'manage_edit-shop_order_sortable_columns', array( $this, 'make_delivery_date_sortable' ) );
		add_action( 'pre_get_posts', array( $this, 'sort_by_delivery_date' ) );

		// Desactivado temporalmente para debug:
		// add_action( 'wpo_wcpdf_after_document_label', array( $this, 'add_packing_slip_fields_to_template' ), 10, 2 ); // Hook desactivado temporalmente para debug.
		add_action( 'wpo_wcpdf_after_document_label', array( $this, 'add_packing_slip_fields_to_template' ), 10, 2 );
	}

	/**
	 * Enable packing slip if not already enabled.
	 */
	public function enable_packing_slip_if_needed() {
		// Only run once and only if PDF plugin is available.
		if ( ! class_exists( 'WPO_WCPDF' ) ) {
			return;
		}

		$option_name = 'wpo_wcpdf_documents_settings_packing-slip';
		$settings    = get_option( $option_name, array() );

		// Enable packing slip if not already enabled.
		if ( empty( $settings['enabled'] ) ) {
			$settings['enabled'] = 1;
			update_option( $option_name, $settings );
		}
	}

	/**
	 * Add packing slip to the existing PDF meta box.
	 *
	 * @param WC_Order $order Order object.
	 * @param object   $admin Admin object.
	 */
	public function add_packing_slip_to_meta_box( $order, $admin ) {
		$packing_slip = wcpdf_get_document( 'packing-slip', $order );
		if ( ! $packing_slip ) {
			return;
		}
		// Refuerzo: si el packing slip existe pero no tiene número o fecha, los genero.
		if ( $packing_slip->exists() && ( ! $packing_slip->get_number() || ! $packing_slip->get_date() ) ) {
			$packing_slip->initiate_date();
			$packing_slip->initiate_number();
			$packing_slip->save();
		}
		// Use the same structure as invoice in the PRO.
		$data = array(
			'number'           => array(
				'label' => __( 'Número de albarán:', 'palafito-wc-extensions' ),
			),
			'date'             => array(
				'label' => __( 'Fecha de entrega:', 'palafito-wc-extensions' ),
			),
			'display_date'     => array(
				'label' => __( 'Packing slip display date:', 'woocommerce-pdf-invoices-packing-slips' ),
			),
			'creation_trigger' => array(
				'label' => __( 'Packing slip created via:', 'woocommerce-pdf-invoices-packing-slips' ),
			),
			'notes'            => array(
				'label' => __( 'Notes (printed in the packing slip):', 'woocommerce-pdf-invoices-packing-slips' ),
			),
		);
		$admin->output_number_date_edit_fields( $packing_slip, $data );
	}

	/**
	 * Add delivery date column to orders list.
	 *
	 * @param array $columns Order columns.
	 * @return array Modified columns.
	 */
	public function add_delivery_date_column( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $column ) {
			$new_columns[ $key ] = $column;
			if ( 'order_status' === $key ) {
				$new_columns['delivery_date'] = __( 'Fecha de entrega', 'palafito-wc-extensions' );
			}
		}
		return $new_columns;
	}

	/**
	 * Display delivery date in orders list column.
	 *
	 * @param string $column Column name.
	 * @param int    $post_id Post ID.
	 */
	public function delivery_date_column_data( $column, $post_id ) {
		if ( 'delivery_date' === $column ) {
			$order = wc_get_order( $post_id );
			if ( $order ) {
				// Use the PRO meta key for packing slip date.
				$delivery_date = $order->get_meta( '_wcpdf_packing-slip_date' );
				if ( $delivery_date ) {
					echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $delivery_date ) ) );
				} else {
					echo '<span class="na">—</span>';
				}
			}
		}
	}

	/**
	 * Make delivery date column sortable.
	 *
	 * @param array $columns Sortable columns.
	 * @return array Modified columns.
	 */
	public function make_delivery_date_sortable( $columns ) {
		$columns['delivery_date'] = 'delivery_date';
		return $columns;
	}

	/**
	 * Sort orders by delivery date.
	 *
	 * @param WP_Query $query Query object.
	 */
	public function sort_by_delivery_date( $query ) {
		if ( ! is_admin() ) {
			return;
		}

		$orderby = $query->get( 'orderby' );
		if ( 'delivery_date' === $orderby ) {
			// Use the PRO meta key for packing slip date.
			$query->set( 'meta_key', '_wcpdf_packing-slip_date' );
			$query->set( 'orderby', 'meta_value' );
		}
	}

	/**
	 * Add packing slip fields to template.
	 *
	 * @param string $document_type Document type.
	 * @param object $document Document object.
	 */
	public function add_packing_slip_fields_to_template( $document_type, $document ) {
		if ( 'packing-slip' !== $document_type ) {
			return;
		}
		// Mostrar número de albarán usando el helper estándar del documento.
		if ( method_exists( $document, 'get_number' ) && $document->get_number() ) {
			?>
			<div class="packing-slip-number">
				<strong><?php esc_html_e( 'Número de albarán:', 'palafito-wc-extensions' ); ?></strong>
				<?php $document->number( $document_type ); ?>
			</div>
			<?php
		}
		// Mostrar fecha de entrega usando el helper estándar del documento.
		if ( method_exists( $document, 'get_date' ) && $document->get_date() ) {
			?>
			<div class="delivery-date">
				<strong><?php esc_html_e( 'Fecha de entrega:', 'palafito-wc-extensions' ); ?></strong>
				<?php $document->date( $document_type ); ?>
			</div>
			<?php
		}
	}
}
