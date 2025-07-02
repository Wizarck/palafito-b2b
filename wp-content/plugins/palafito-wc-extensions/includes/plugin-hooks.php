<?php
/**
 * Plugin Hooks for Palafito WC Extensions
 *
 * Handles custom hooks and status changes for the Palafito B2B workflow.
 *
 * @package Palafito_WC_Extensions
 * @since 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Función de activación del plugin.
 */
function palafito_wc_extensions_activate() {
	// Log the hook execution for debugging.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'Palafito WC Extensions: Hook executed - palafito_wc_extensions_activate' );
	}
}

/**
 * Función de desactivación del plugin.
 */
function palafito_wc_extensions_deactivate() {
	// Log the hook execution for debugging.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'Palafito WC Extensions: Hook executed - palafito_wc_extensions_deactivate' );
	}
}

// Registrar hooks de activación/desactivación.
register_activation_hook( PALAFITO_WC_EXTENSIONS_PLUGIN_FILE, 'palafito_wc_extensions_activate' );
register_deactivation_hook( PALAFITO_WC_EXTENSIONS_PLUGIN_FILE, 'palafito_wc_extensions_deactivate' );

// Hooks para cambio de estado de pedidos (solo logs para debugging).
add_action( 'woocommerce_order_status_changed', 'palafito_wc_extensions_handle_order_status_change', 10, 3 );

/**
 * Manejar cambios de estado de pedidos (solo logs para debugging).
 *
 * @param int    $order_id   Order ID.
 * @param string $old_status Old status.
 * @param string $new_status New status.
 */
function palafito_wc_extensions_handle_order_status_change( $order_id, $old_status, $new_status ) {
	// Log status changes for debugging.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		if ( 'entregado' === $new_status ) {
			error_log( "Palafito WC Extensions: Order {$order_id} status changed to 'entregado'." );
		}
		if ( 'facturado' === $new_status ) {
			error_log( "Palafito WC Extensions: Order {$order_id} status changed to 'facturado'." );
		}
	}
}

// Eliminado: Registro de emails personalizados - funcionalidad manejada por el plugin PRO.
// Eliminado: Forzar generación de albarán - funcionalidad manejada por el plugin PRO.
// Eliminado: Adjuntos de email - funcionalidad manejada por el plugin PRO.

/**
 * Save packing slip data when order is saved.
 *
 * The base PDF plugin only saves invoice data during regular order saves.
 * This hook ensures packing slip data is also saved during order save operations.
 *
 * @param array  $form_data     Form data from the order save.
 * @param object $order         WooCommerce order object.
 * @param object $admin_instance PDF plugin admin instance.
 */
add_action( 'wpo_wcpdf_on_save_invoice_order_data', 'palafito_save_packing_slip_data_on_order_save', 10, 3 );

/**
 * Save packing slip data during order save operations.
 *
 * @param array  $form_data     Form data.
 * @param object $order         WooCommerce order object.
 * @param object $admin_instance PDF plugin admin instance.
 */
function palafito_save_packing_slip_data_on_order_save( $form_data, $order, $admin_instance ) {
	// Validate inputs.
	if ( ! $admin_instance || ! $order || ! is_array( $form_data ) ) {
		return;
	}

	// Log the hook execution for debugging.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'Palafito WC Extensions: Saving packing slip data for order ' . $order->get_id() );
	}

	// Get packing slip document.
	$packing_slip = wcpdf_get_document( 'packing-slip', $order );
	if ( empty( $packing_slip ) ) {
		return;
	}

	// Process packing slip form data.
	$document_data = $admin_instance->process_order_document_form_data( $form_data, $packing_slip->slug );

	// Only save if we have data to save.
	if ( ! empty( $document_data ) ) {
		$packing_slip->set_data( $document_data, $order );
		$packing_slip->save();

		// Log successful save for debugging.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Palafito WC Extensions: Packing slip data saved for order ' . $order->get_id() );
		}
	}
}
