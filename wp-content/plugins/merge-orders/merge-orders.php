<?php
/**
 * Plugin Name: WooCommerce Merge Orders
 * Plugin URI: https://woocommerce.com/products/merge-orders
 * Description: Merge multiple orders into one order for processing together.
 * Version: 1.3.11
 * Author: Vibe Agency
 * Author URI: https://vibeagency.uk
 * Developer: Vibe Agency
 * Developer URI: https://vibeagency.uk
 * Text Domain: merge-orders
 * Domain path: /languages
 *
 * Woo: 6749042:c5b4f8a8be8336ef9be254ff09ee5ad5
 * WC requires at least: 8.3
 * WC tested up to: 8.8
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

use Vibe\Merge_Orders\Merge_Orders;

define( 'VIBE_MERGE_ORDERS_VERSION', '1.3.11' );

// Autoloader for all classes
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

// Protect against conflicts and loading multiple copies of plugin
if ( ! function_exists( 'vibe_merge_orders' ) ) {
	// HPOS compatibility
	add_action( 'before_woocommerce_init', function() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	} );

	/**
	 * Returns the singleton instance of the main plugin class
	 *
	 * @return Merge_Orders The singleton
	 */
	function vibe_merge_orders() {
		return Merge_Orders::instance();
	}

	// Initialise the plugin
	vibe_merge_orders();
}
