<?php
/**
 * Clase para personalizaciones del checkout de WooCommerce.
 *
 * @package Palafito_WC_Extensions
 * @since 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase Palafito_Checkout_Customizations.
 *
 * Maneja todas las personalizaciones relacionadas con el checkout de WooCommerce.
 */
class Palafito_Checkout_Customizations {

	/**
	 * Constructor de la clase.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Inicializar hooks de WordPress.
	 */
	private function init_hooks() {
		// Hooks de checkout.
		add_filter( 'woocommerce_checkout_fields', array( $this, 'customize_checkout_fields' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_custom_checkout_fields' ) );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_custom_fields_in_admin' ) );
		add_action( 'woocommerce_order_details_after_customer_details', array( $this, 'display_custom_fields_in_order' ) );

		// Validación de campos.
		add_action( 'woocommerce_checkout_process', array( $this, 'validate_custom_fields' ) );
	}

	/**
	 * Personalizar campos del checkout.
	 *
	 * @param array $fields Campos del checkout.
	 * @return array
	 */
	public function customize_checkout_fields( $fields ) {
		// Agregar campo RFC para facturación.
		$fields['billing']['billing_rfc'] = array(
			'label'       => esc_html__( 'RFC', 'palafito-wc-extensions' ),
			'placeholder' => esc_attr__( 'Ingresa tu RFC', 'palafito-wc-extensions' ),
			'required'    => true,
			'class'       => array( 'form-row-wide' ),
			'clear'       => true,
			'priority'    => 25,
		);

		// Hacer el teléfono obligatorio.
		if ( isset( $fields['billing']['billing_phone'] ) ) {
			$fields['billing']['billing_phone']['required'] = true;
		}

		// Agregar campo de razón social.
		$fields['billing']['billing_business_name'] = array(
			'label'       => esc_html__( 'Razón Social', 'palafito-wc-extensions' ),
			'placeholder' => esc_attr__( 'Nombre de la empresa', 'palafito-wc-extensions' ),
			'required'    => true,
			'class'       => array( 'form-row-wide' ),
			'clear'       => true,
			'priority'    => 20,
		);

		return $fields;
	}

	/**
	 * Guardar campos personalizados del checkout.
	 *
	 * @param int $order_id ID del pedido.
	 */
	public function save_custom_checkout_fields( $order_id ) {
		// Verificar nonce para seguridad.
		if ( ! isset( $_POST['woocommerce-process-checkout-nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) ), 'woocommerce-process_checkout' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// Guardar RFC.
		if ( isset( $_POST['billing_rfc'] ) ) {
			$rfc = sanitize_text_field( wp_unslash( $_POST['billing_rfc'] ) );
			$order->update_meta_data( '_billing_rfc', $rfc );
		}

		// Guardar razón social.
		if ( isset( $_POST['billing_business_name'] ) ) {
			$business_name = sanitize_text_field( wp_unslash( $_POST['billing_business_name'] ) );
			$order->update_meta_data( '_billing_business_name', $business_name );
		}

		$order->save();
	}

	/**
	 * Mostrar campos personalizados en el admin.
	 *
	 * @param WC_Order $order Objeto del pedido.
	 */
	public function display_custom_fields_in_admin( $order ) {
		$rfc           = $order->get_meta( '_billing_rfc' );
		$business_name = $order->get_meta( '_billing_business_name' );

		if ( $rfc ) {
			echo '<p><strong>' . esc_html__( 'RFC:', 'palafito-wc-extensions' ) . '</strong> ' . esc_html( $rfc ) . '</p>';
		}

		if ( $business_name ) {
			echo '<p><strong>' . esc_html__( 'Razón Social:', 'palafito-wc-extensions' ) . '</strong> ' . esc_html( $business_name ) . '</p>';
		}
	}

	/**
	 * Mostrar campos personalizados en el pedido.
	 *
	 * @param WC_Order $order Objeto del pedido.
	 */
	public function display_custom_fields_in_order( $order ) {
		$rfc           = $order->get_meta( '_billing_rfc' );
		$business_name = $order->get_meta( '_billing_business_name' );

		if ( $rfc || $business_name ) {
			echo '<section class="palafito-custom-fields">';
			echo '<h2>' . esc_html__( 'Información Adicional', 'palafito-wc-extensions' ) . '</h2>';

			if ( $rfc ) {
				echo '<p><strong>' . esc_html__( 'RFC:', 'palafito-wc-extensions' ) . '</strong> ' . esc_html( $rfc ) . '</p>';
			}

			if ( $business_name ) {
				echo '<p><strong>' . esc_html__( 'Razón Social:', 'palafito-wc-extensions' ) . '</strong> ' . esc_html( $business_name ) . '</p>';
			}

			echo '</section>';
		}
	}

	/**
	 * Validar campos personalizados.
	 */
	public function validate_custom_fields() {
		// Verificar nonce.
		if ( ! isset( $_POST['woocommerce-process-checkout-nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) ), 'woocommerce-process_checkout' ) ) {
			wc_add_notice( esc_html__( 'Error de seguridad. Por favor, intenta de nuevo.', 'palafito-wc-extensions' ), 'error' );
			return;
		}

		// Validar RFC.
		if ( isset( $_POST['billing_rfc'] ) ) {
			$rfc = sanitize_text_field( wp_unslash( $_POST['billing_rfc'] ) );
			if ( empty( $rfc ) ) {
				wc_add_notice( esc_html__( 'El RFC es obligatorio.', 'palafito-wc-extensions' ), 'error' );
			} elseif ( ! $this->validate_rfc_format( $rfc ) ) {
				wc_add_notice( esc_html__( 'El formato del RFC no es válido.', 'palafito-wc-extensions' ), 'error' );
			}
		}

		// Validar razón social.
		if ( isset( $_POST['billing_business_name'] ) ) {
			$business_name = sanitize_text_field( wp_unslash( $_POST['billing_business_name'] ) );
			if ( empty( $business_name ) ) {
				wc_add_notice( esc_html__( 'La razón social es obligatoria.', 'palafito-wc-extensions' ), 'error' );
			}
		}
	}

	/**
	 * Validar formato de RFC.
	 *
	 * @param string $rfc RFC a validar.
	 * @return bool
	 */
	private function validate_rfc_format( $rfc ) {
		// Validación básica de RFC mexicano.
		$pattern = '/^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/';
		return preg_match( $pattern, strtoupper( $rfc ) );
	}
}
