<?php
/**
 * Checkout Customizations for B2B
 *
 * @package Palafito_WC_Extensions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Palafito Checkout Customizations Class
 */
class Palafito_Checkout_Customizations {

	/**
	 * Initialize the class.
	 */
	public function __construct() {
		add_filter( 'woocommerce_checkout_fields', array( $this, 'customize_checkout_fields' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_custom_fields' ) );
	}

	/**
	 * Customize checkout fields for B2B.
	 *
	 * @param array $fields Checkout fields.
	 * @return array Modified checkout fields.
	 */
	public function customize_checkout_fields( $fields ) {
		// Make last name fields optional for B2B.
		if ( isset( $fields['billing']['billing_last_name'] ) ) {
			$fields['billing']['billing_last_name']['required'] = false;
		}

		if ( isset( $fields['shipping']['shipping_last_name'] ) ) {
			$fields['shipping']['shipping_last_name']['required'] = false;
		}

		return $fields;
	}

	/**
	 * Save custom fields to order.
	 *
	 * @param int $order_id Order ID.
	 */
	public function save_custom_fields( $order_id ) {
		// Verify nonce for security.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ?? '' ) ), 'woocommerce-process_checkout' ) ) {
			return;
		}

		// Save any custom fields here if needed in the future.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "Palafito WC Extensions: Custom fields saved for order {$order_id}" );
		}
	}
} 