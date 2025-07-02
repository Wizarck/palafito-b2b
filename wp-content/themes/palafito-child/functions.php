<?php
/**
 * Functions for Palafito Child Theme
 *
 * Contains theme setup and customizations.
 *
 * @package Palafito_Child
 * @since 1.0.0
 */

/**
 * Main theme class for Palafito Child.
 *
 * Handles theme setup and customizations.
 *
 * @since 1.0.0
 */
class Palafito_Child_Theme {
	/**
	 * Constructor.
	 *
	 * Sets up theme hooks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Theme setup actions.
	}
}

new Palafito_Child_Theme();

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove cross-sell products from cart page.
 */
function palafito_remove_cross_sell_from_cart() {
	remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
}
add_action( 'init', 'palafito_remove_cross_sell_from_cart' );
