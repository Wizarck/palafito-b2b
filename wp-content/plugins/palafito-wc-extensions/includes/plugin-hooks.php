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
 * Uses a more reliable hook that fires for all order saves, not just invoice contexts.
 * The 'wpo_wcpdf_on_save_invoice_order_data' hook is unreliable for packing slip data.
 *
 * @param int $order_id Order ID being saved.
 * @param WP_Post $order Order post object.
 */
/**
 * REMOVED: Custom packing slip data save function
 *
 * This functionality has been centralized in the PDF plugins to avoid duplicities:
 * - woocommerce-pdf-invoices-packing-slips (base functionality)
 * - woocommerce-pdf-ips-pro (Pro features including packing slip date management)
 *
 * The PDF plugins now handle all packing slip date logic centrally.
 */

/**
 * REMOVED: Bidirectional synchronization between _wcpdf_packing-slip_date and _entregado_date
 *
 * NEW APPROACH: Only _wcpdf_packing-slip_date is used as the single source of truth
 * No more synchronization with _entregado_date field - this eliminates complexity
 * and ensures data consistency as per requirements.
 */

/**
 * Set date format to d-m-Y for PDF documents
 *
 * @param string $format The date format.
 * @param object $document The PDF document object.
 * @return string Modified date format.
 */
function palafito_set_pdf_date_format( $format, $document ) {
	// Use d-m-Y format for all PDF documents as per requirements.
	return 'd-m-Y';
}
add_filter( 'wpo_wcpdf_date_format', 'palafito_set_pdf_date_format', 10, 2 );
