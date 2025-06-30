<?php
/**
 * Checkout Customizations Class
 *
 * @package Palafito_WC_Extensions
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles checkout customizations for B2B functionality.
 */
class Palafito_Checkout_Customizations {

	/**
	 * Initialize the class.
	 */
	public function __construct() {
		add_action( 'woocommerce_checkout_fields', array( $this, 'customize_checkout_fields' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_custom_fields' ) );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'add_b2b_fields' ) );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'make_last_name_optional' ) );
	}

	/**
	 * Customize checkout fields for B2B.
	 *
	 * @param array $fields Checkout fields.
	 * @return array Modified checkout fields.
	 */
	public function customize_checkout_fields( $fields ) {
		// Add basic B2B fields.
		if ( isset( $fields['billing'] ) ) {
			$fields['billing']['billing_company_type'] = array(
				'label'       => __( 'Company Type', 'palafito-wc-extensions' ),
				'placeholder' => __( 'Select company type', 'palafito-wc-extensions' ),
				'required'    => true,
				'class'       => array( 'form-row-wide' ),
				'clear'       => true,
				'priority'    => 25,
				'type'        => 'select',
				'options'     => array(
					''           => __( 'Select...', 'palafito-wc-extensions' ),
					'wholesale'  => __( 'Wholesale', 'palafito-wc-extensions' ),
					'retail'     => __( 'Retail', 'palafito-wc-extensions' ),
					'distributor' => __( 'Distributor', 'palafito-wc-extensions' ),
				),
			);
		}

		return $fields;
	}

	/**
	 * Add B2B specific fields.
	 *
	 * @param array $fields Checkout fields.
	 * @return array Modified checkout fields.
	 */
	public function add_b2b_fields( $fields ) {
		if ( isset( $fields['billing'] ) ) {
			$fields['billing']['billing_business_name'] = array(
				'label'       => __( 'Business Name', 'palafito-wc-extensions' ),
				'placeholder' => __( 'Enter your business name', 'palafito-wc-extensions' ),
				'required'    => true,
				'class'       => array( 'form-row-wide' ),
				'clear'       => true,
				'priority'    => 30,
			);
		}

		return $fields;
	}

	/**
	 * Save custom fields to order.
	 *
	 * @param int $order_id Order ID.
	 */
	public function save_custom_fields( $order_id ) {
		if ( ! empty( $_POST['billing_company_type'] ) ) {
			update_post_meta( $order_id, '_billing_company_type', sanitize_text_field( wp_unslash( $_POST['billing_company_type'] ) ) );
		}

		if ( ! empty( $_POST['billing_business_name'] ) ) {
			update_post_meta( $order_id, '_billing_business_name', sanitize_text_field( wp_unslash( $_POST['billing_business_name'] ) ) );
		}
	}

	/**
	 * Make last name optional.
	 *
	 * @param array $fields Checkout fields.
	 * @return array Modified checkout fields.
	 */
	public function make_last_name_optional( $fields ) {
		// Make billing last name optional.
		if ( isset( $fields['billing'] ) ) {
			$fields['billing']['billing_last_name']['required'] = false;
		}

		// Make shipping last name optional.
		if ( isset( $fields['shipping'] ) ) {
			$fields['shipping']['shipping_last_name']['required'] = false;
		}

		return $fields;
	}
} 