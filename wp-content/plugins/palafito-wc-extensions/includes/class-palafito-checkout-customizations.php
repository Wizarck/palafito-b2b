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
		add_action( 'woocommerce_thankyou', array( $this, 'auto_update_order_status' ), 10, 1 );
		add_filter(
			'woocommerce_my_account_my_orders_actions',
			function ( $actions, $order ) {
				// Elimina la acción de cancelar.
				if ( isset( $actions['cancel'] ) ) {
					unset( $actions['cancel'] );
				}
				// Elimina la acción de pagar.
				if ( isset( $actions['pay'] ) ) {
					unset( $actions['pay'] );
				}
				return $actions;
			},
			10,
			2
		);
	}

	/**
	 * Customize checkout fields for B2B.
	 *
	 * @param array $fields Checkout fields.
	 * @return array Modified checkout fields.
	 */
	public function customize_checkout_fields( $fields ) {
		// Hacer todos los campos de facturación opcionales en el checkout.
		if ( isset( $fields['billing'] ) ) {
			foreach ( $fields['billing'] as $key => &$field ) {
				$field['required'] = false;
			}
		}
		// Make last name fields optional for B2B.
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
		if ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ) {
			error_log( 'Palafito WC Extensions: Custom fields saved for order ' . $order_id );
		}
	}

	/**
	 * Automatiza la transición de estado tras el checkout.
	 *
	 * @param int $order_id Order ID.
	 */
	public function auto_update_order_status( $order_id ) {
		if ( ! $order_id ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$payment_method = $order->get_payment_method();
		if ( 'cod' === $payment_method ) {
			if ( 'on-hold' !== $order->get_status() ) {
				$order->update_status( 'on-hold', __( 'Transición automática: Pago mensual.', 'palafito-wc-extensions' ) );
			}
		} elseif ( 'pending' === $order->get_status() ) {
			$order->update_status( 'processing', __( 'Transición automática: Pago por tarjeta.', 'palafito-wc-extensions' ) );
		}
	}
}
