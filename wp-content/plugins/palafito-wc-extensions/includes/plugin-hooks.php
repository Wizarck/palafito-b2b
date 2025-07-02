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
	// ALWAYS log for debugging - we need to see what's happening.
	error_log( '=== PALAFITO DEBUG: Hook fired ===' );
	error_log( 'Order ID: ' . ( $order ? $order->get_id() : 'NULL' ) );
	error_log( 'Admin Instance: ' . ( $admin_instance ? 'EXISTS' : 'NULL' ) );
	error_log( 'Form Data Keys: ' . ( is_array( $form_data ) ? implode( ', ', array_keys( $form_data ) ) : 'NOT ARRAY' ) );

	// Validate inputs.
	if ( ! $admin_instance || ! $order || ! is_array( $form_data ) ) {
		error_log( 'PALAFITO DEBUG: Validation failed - exiting early' );
		return;
	}

	// Get packing slip document.
	$packing_slip = wcpdf_get_document( 'packing-slip', $order );
	if ( empty( $packing_slip ) ) {
		error_log( 'PALAFITO DEBUG: No packing slip document found' );
		return;
	}

	error_log( 'PALAFITO DEBUG: Packing slip document exists, slug: ' . $packing_slip->slug );

	// Look for packing slip fields in form data.
	$packing_slip_fields = array();
	foreach ( $form_data as $key => $value ) {
		if ( strpos( $key, 'packing-slip' ) !== false || strpos( $key, 'packing_slip' ) !== false ) {
			$packing_slip_fields[ $key ] = $value;
		}
	}

	error_log( 'PALAFITO DEBUG: Packing slip fields found: ' . print_r( $packing_slip_fields, true ) );

	// Process packing slip form data.
	$document_data = $admin_instance->process_order_document_form_data( $form_data, $packing_slip->slug );

	error_log( 'PALAFITO DEBUG: Processed document data: ' . print_r( $document_data, true ) );

	// Only save if we have data to save.
	if ( ! empty( $document_data ) ) {
		error_log( 'PALAFITO DEBUG: Attempting to save packing slip data...' );
		$packing_slip->set_data( $document_data, $order );
		$packing_slip->save();
		error_log( 'PALAFITO DEBUG: Packing slip data saved successfully!' );

		// Verify the meta was actually saved.
		$saved_date = $order->get_meta( '_wcpdf_packing-slip_date', true );
		error_log( 'PALAFITO DEBUG: Saved meta _wcpdf_packing-slip_date: ' . $saved_date );
	} else {
		error_log( 'PALAFITO DEBUG: No document data to save - form data may not contain packing slip fields' );
	}

	// EMERGENCY TEST: Force set the meta field directly to see if that works.
	$test_timestamp = time();
	$order->update_meta_data( '_wcpdf_packing-slip_date', $test_timestamp );
	$order->save_meta_data();
	error_log( 'PALAFITO DEBUG: EMERGENCY TEST - Force set _wcpdf_packing-slip_date to: ' . $test_timestamp );

	// Verify it was saved.
	$verification = $order->get_meta( '_wcpdf_packing-slip_date', true );
	error_log( 'PALAFITO DEBUG: EMERGENCY TEST - Verification read: ' . $verification );

	error_log( '=== PALAFITO DEBUG: Hook completed ===' );
}
