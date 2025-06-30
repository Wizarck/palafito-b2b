<?php
/**
 * Bootstrap file for PHPUnit tests.
 *
 * @package Palafito_WC_Extensions
 * @since 1.0.0
 */

// Load PHPUnit Polyfills first (required for WordPress tests).
require_once dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/vendor/autoload.php';

// Load WordPress test environment.
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL;
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	// Load WooCommerce first from the standard plugin path.
	$wc_path = dirname( dirname( dirname( __DIR__ ) ) ) . '/plugins/woocommerce/woocommerce.php';
	if ( file_exists( $wc_path ) ) {
		require $wc_path;
	}

	// Load our plugin.
	$plugin_path = dirname( __DIR__ ) . '/palafito-wc-extensions.php';
	if ( file_exists( $plugin_path ) ) {
		require $plugin_path;
		// Load plugin classes directly for tests.
		$plugin_dir = dirname( __DIR__ );
		$class_files = array(
			$plugin_dir . '/includes/class-checkout-customizations.php',
		);
		foreach ( $class_files as $file ) {
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	}
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
