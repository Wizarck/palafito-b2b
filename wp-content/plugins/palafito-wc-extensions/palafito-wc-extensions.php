<?php
/**
 * Plugin Name: Palafito WC Extensions
 * Plugin URI: https://palafito.com
 * Description: Extensiones personalizadas para WooCommerce en Palafito B2B.
 * Version: 1.0.0
 * Author: Palafito Team
 * Author URI: https://palafito.com
 * Text Domain: palafito-wc-extensions
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 *
 * @package Palafito_WC_Extensions
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'PALAFITO_WC_EXTENSIONS_VERSION', '1.0.0' );
define( 'PALAFITO_WC_EXTENSIONS_PLUGIN_FILE', __FILE__ );
define( 'PALAFITO_WC_EXTENSIONS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PALAFITO_WC_EXTENSIONS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load plugin hooks first.
require_once PALAFITO_WC_EXTENSIONS_PLUGIN_DIR . 'includes/plugin-hooks.php';

// Load the main plugin class.
require_once PALAFITO_WC_EXTENSIONS_PLUGIN_DIR . 'class-palafito-wc-extensions.php';

/**
 * Initialize the plugin on the 'init' hook to avoid early loading issues.
 */
function palafito_wc_extensions_init() {
	// Only initialize if WooCommerce is active.
	if ( class_exists( 'WooCommerce' ) ) {
		new Palafito_WC_Extensions();
	}
}

// Inicializa el plugin de forma segura después de que todos los plugins estén cargados.
add_action( 'plugins_loaded', 'palafito_wc_extensions_init', 20 );

// Declarar compatibilidad con HPOS (High Performance Order Storage) de WooCommerce.
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				plugin_basename( __FILE__ ),
				true
			);
		}
	}
);
