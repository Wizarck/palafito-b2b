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

		// Auto-generate packing slip number when order is processing.
		add_action( 'woocommerce_order_status_processing', array( $this, 'auto_generate_packing_slip_number' ) );

		// Set delivery date when order is "entregado".
		add_action( 'woocommerce_order_status_entregado', array( $this, 'set_delivery_date' ) );

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

		// Use the same structure as invoice in the PRO.
		$data = array(
			'number'           => array(
				'label' => __( 'Packing slip number:', 'woocommerce-pdf-invoices-packing-slips' ),
			),
			'date'             => array(
				'label' => __( 'Packing slip date:', 'woocommerce-pdf-invoices-packing-slips' ),
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

		// Output using the same method as invoice.
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
	 * Auto-generate packing slip number when order is processing.
	 *
	 * @param int $order_id Order ID.
	 */
	public function auto_generate_packing_slip_number( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$packing_slip = wcpdf_get_document( 'packing-slip', $order );
		if ( ! $packing_slip ) {
			return;
		}

		// Check if packing slip number already exists.
		if ( $packing_slip->exists() && ! empty( $packing_slip->get_number() ) ) {
			return;
		}

		// Get packing slip settings.
		$settings         = get_option( 'wpo_wcpdf_documents_settings_packing-slip', array() );
		$use_order_number = ! empty( $settings['use_order_number'] );

		// Determine the base number based on settings.
		if ( $use_order_number ) {
			// Use order number as base.
			$base_number = $order->get_order_number();
		} else {
			// Use sequential number.
			$base_number = $this->get_next_sequential_number();
		}

		// Apply number format if configured.
		$formatted_number = $this->apply_number_format( $base_number, $order, $settings );

		// Create a number object like the PRO does.
		$number_data = array(
			'number'           => $base_number,
			'formatted_number' => $formatted_number,
		);

		// Set the number using the PRO method.
		$packing_slip->set_number( $number_data );
		$packing_slip->save();

		// Add order note.
		$order->add_order_note(
			sprintf(
				/* translators: %s: formatted number */
				__( 'Packing slip number generated automatically: %s', 'palafito-wc-extensions' ),
				$formatted_number
			)
		);
	}

	/**
	 * Get next sequential number for packing slip.
	 *
	 * @return int
	 */
	private function get_next_sequential_number() {
		$settings    = get_option( 'wpo_wcpdf_documents_settings_packing-slip', array() );
		$next_number = ! empty( $settings['next_packing_slip_number'] ) ? (int) $settings['next_packing_slip_number'] : 1;

		// Increment the number for next use.
		$settings['next_packing_slip_number'] = $next_number + 1;
		update_option( 'wpo_wcpdf_documents_settings_packing-slip', $settings );

		return $next_number;
	}

	/**
	 * Apply number format to the base number.
	 *
	 * @param string   $base_number Base number.
	 * @param WC_Order $order Order object.
	 * @param array    $settings Packing slip settings.
	 * @return string
	 */
	private function apply_number_format( $base_number, $order, $settings ) {
		$formatted_number = $base_number;

		// Get number format settings.
		$number_format = ! empty( $settings['number_format'] ) ? $settings['number_format'] : array();

		if ( ! empty( $number_format ) ) {
			$prefix  = ! empty( $number_format['prefix'] ) ? $number_format['prefix'] : '';
			$suffix  = ! empty( $number_format['suffix'] ) ? $number_format['suffix'] : '';
			$padding = ! empty( $number_format['padding'] ) ? (int) $number_format['padding'] : 0;

			// Apply padding if configured.
			if ( $padding > 0 ) {
				$formatted_number = str_pad( $base_number, $padding, '0', STR_PAD_LEFT );
			}

			// Apply prefix and suffix.
			$formatted_number = $prefix . $formatted_number . $suffix;

			// Replace placeholders.
			$formatted_number = $this->replace_placeholders( $formatted_number, $order );
		}

		return $formatted_number;
	}

	/**
	 * Replace placeholders in the formatted number.
	 *
	 * @param string   $formatted_number Formatted number with placeholders.
	 * @param WC_Order $order Order object.
	 * @return string
	 */
	private function replace_placeholders( $formatted_number, $order ) {
		$replacements = array(
			'[packing_slip_year]'  => gmdate( 'Y' ),
			'[packing_slip_month]' => gmdate( 'm' ),
			'[order_number]'       => $order->get_order_number(),
			'[order_date]'         => $order->get_date_created()->format( 'Y-m-d' ),
			'[order_date_i18n]'    => $order->get_date_created()->date_i18n( 'Y-m-d' ),
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $formatted_number );
	}

	/**
	 * Set delivery date when order is "entregado".
	 *
	 * @param int $order_id Order ID.
	 */
	public function set_delivery_date( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$packing_slip = wcpdf_get_document( 'packing-slip', $order );
		if ( ! $packing_slip ) {
			return;
		}

		// Check if delivery date already exists.
		if ( $packing_slip->exists() && ! empty( $packing_slip->get_date() ) ) {
			return;
		}

		// Set current date as delivery date using the PRO method.
		$current_date = time();
		$packing_slip->set_date( $current_date );
		$packing_slip->save();

		// Add order note.
		$order->add_order_note(
			sprintf(
				/* translators: %s: delivery date */
				__( 'Fecha de entrega establecida automáticamente: %s', 'palafito-wc-extensions' ),
				date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $current_date )
			)
		);
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

		$order = $document->order;
		if ( ! $order ) {
			return;
		}

		// Add packing slip number if it exists.
		if ( $document->exists() && ! empty( $document->get_number() ) ) {
			?>
			<div class="packing-slip-number">
				<strong><?php esc_html_e( 'Número de albarán:', 'palafito-wc-extensions' ); ?></strong>
				<?php echo esc_html( $document->get_number()->get_formatted() ); ?>
			</div>
			<?php
		}

		// Add delivery date if it exists.
		if ( $document->exists() && ! empty( $document->get_date() ) ) {
			?>
			<div class="delivery-date">
				<strong><?php esc_html_e( 'Fecha de entrega:', 'palafito-wc-extensions' ); ?></strong>
				<?php echo esc_html( $document->get_date()->date_i18n( get_option( 'date_format' ) ) ); ?>
			</div>
			<?php
		}
	}
}
