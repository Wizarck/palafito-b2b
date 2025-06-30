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

	// Log solo en desarrollo.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '[Palafito WC Extensions] Plugin activado' );
	}
}

/**
 * Función de desactivación del plugin.
 */
function palafito_wc_extensions_deactivate() {
	// Limpiar datos temporales.
	// No eliminar datos permanentes aquí.

	// Log solo en desarrollo.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '[Palafito WC Extensions] Plugin desactivado' );
	}
}

// Registrar hooks de activación/desactivación.
register_activation_hook( PALAFITO_WC_EXTENSIONS_PLUGIN_FILE, 'palafito_wc_extensions_activate' );
register_deactivation_hook( PALAFITO_WC_EXTENSIONS_PLUGIN_FILE, 'palafito_wc_extensions_deactivate' );
