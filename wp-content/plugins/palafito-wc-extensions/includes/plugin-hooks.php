<?php
/**
 * Plugin activation and deactivation hooks.
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
	// Crear tablas personalizadas si es necesario.
	// Configurar opciones por defecto.
	// Limpiar cachés.

	// Log the hook execution for debugging.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'Palafito WC Extensions: Hook executed - palafito_wc_extensions_activate' );
	}
}

/**
 * Función de desactivación del plugin.
 */
function palafito_wc_extensions_deactivate() {
	// Limpiar datos temporales.
	// No eliminar datos permanentes aquí.

	// Log the hook execution for debugging.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'Palafito WC Extensions: Hook executed - palafito_wc_extensions_deactivate' );
	}
}

// Registrar hooks de activación/desactivación.
register_activation_hook( PALAFITO_WC_EXTENSIONS_PLUGIN_FILE, 'palafito_wc_extensions_activate' );
register_deactivation_hook( PALAFITO_WC_EXTENSIONS_PLUGIN_FILE, 'palafito_wc_extensions_deactivate' );

// Cargar configuración de PDF si el plugin está disponible.
if ( class_exists( 'WPO_WCPDF' ) ) {
	require_once PALAFITO_WC_EXTENSIONS_PLUGIN_DIR . 'includes/class-palafito-pdf-configuration.php';
}

// Hooks para cambio de estado de pedidos y envío automático de emails.
add_action( 'woocommerce_order_status_changed', 'palafito_wc_extensions_handle_order_status_change', 10, 3 );

/**
 * Manejar cambios de estado de pedidos y enviar emails automáticos.
 *
 * @param int    $order_id   Order ID.
 * @param string $old_status Old status.
 * @param string $new_status New status.
 */
function palafito_wc_extensions_handle_order_status_change( $order_id, $old_status, $new_status ) {
	// Solo procesar si el plugin de PDF está disponible.
	if ( ! class_exists( 'WPO_WCPDF' ) ) {
		return;
	}

	// Obtener la instancia del plugin.
	global $palafito_wc_extensions;

	if ( ! isset( $palafito_wc_extensions ) ) {
		return;
	}

	// Enviar email automático cuando el estado cambie a "Entregado".
	if ( 'entregado' === $new_status ) {
		// Trigger custom email action with correct arguments.
		do_action( 'woocommerce_order_status_entregado', $order_id, $old_status, $new_status );

		// Log the action for debugging.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "Palafito WC Extensions: Order {$order_id} status changed to 'entregado', triggering email action." );
		}
	}

	// Enviar email automático cuando el estado cambie a "Facturado".
	if ( 'facturado' === $new_status ) {
		// Trigger custom email action with correct arguments.
		do_action( 'woocommerce_order_status_facturado', $order_id, $old_status, $new_status );

		// Log the action for debugging.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "Palafito WC Extensions: Order {$order_id} status changed to 'facturado', triggering email action." );
		}
	}
}
