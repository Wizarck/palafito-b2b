<?php
/**
 * Plugin Hooks for Palafito WC Extensions
 *
 * Handles custom hooks, status changes, and email triggers for the Palafito B2B workflow.
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

// Los emails se envían automáticamente por WooCommerce cuando se registran los estados personalizados.
// No necesitamos disparar manualmente las acciones.

/**
 * Manejar cambios de estado de pedidos (solo logs para debugging).
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

// Register custom emails for Entregado and Facturado.
add_filter(
	'woocommerce_email_classes',
	function ( $email_classes ) {
		require_once __DIR__ . '/emails/class-wc-email-customer-entregado.php';
		require_once __DIR__ . '/emails/class-wc-email-customer-facturado.php';
		$email_classes['WC_Email_Customer_Entregado'] = new WC_Email_Customer_Entregado();
		$email_classes['WC_Email_Customer_Facturado'] = new WC_Email_Customer_Facturado();
		return $email_classes;
	}
);

// Forzar la generación del albarán (packing slip) al cambiar a Entregado.
add_action(
	'woocommerce_order_status_entregado',
	function ( $order_id, $order = null ) {
		if ( ! $order ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order ) {
			return;
		}
		if ( function_exists( 'wcpdf_get_document' ) ) {
			$packing_slip = wcpdf_get_document( 'packing-slip', $order, true );
			if ( $packing_slip && $packing_slip->is_allowed() ) {
				// Generar y guardar SIEMPRE número y fecha (sobrescribe manuales).
				$packing_slip->initiate_date();
				$packing_slip->initiate_number();
				$packing_slip->save();
				// Nota de pedido para trazabilidad.
				$order->add_order_note( __( 'Número y fecha de albarán generados automáticamente al cambiar a Entregado.', 'palafito-wc-extensions' ) );
				$order->save();
			}
		}
	},
	10,
	2
);
