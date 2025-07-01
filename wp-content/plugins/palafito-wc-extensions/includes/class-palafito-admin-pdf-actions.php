<?php
/**
 * Acciones de PDF en la tabla de pedidos para Palafito.
 *
 * @package Palafito_WC_Extensions
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maneja las acciones de PDF en la tabla de pedidos.
 */
class Palafito_Admin_PDF_Actions {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Agregar acciones personalizadas de cambio de estado.
		add_filter( 'woocommerce_admin_order_actions', array( $this, 'add_custom_status_actions' ), 30, 2 );

		// Modificar la lógica de WooCommerce para incluir nuestros estados personalizados.
		add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', array( $this, 'add_custom_statuses_to_payment_complete' ) );
		add_filter( 'woocommerce_order_is_paid_statuses', array( $this, 'add_custom_statuses_to_paid_statuses' ) );

		// Modificar las acciones después de que WooCommerce las genere.
		add_filter( 'woocommerce_admin_order_actions', array( $this, 'modify_complete_button_visibility' ), 40, 2 );
	}

	/**
	 * Agrega acciones personalizadas de cambio de estado.
	 *
	 * @param array    $actions Acciones disponibles.
	 * @param WC_Order $order Objeto del pedido.
	 * @return array
	 */
	public function add_custom_status_actions( $actions, $order ) {
		$order_status = $order->get_status();
		$order_id     = $order->get_id();

		// Botón para cambiar a Entregado si está en procesando.
		if ( 'processing' === $order_status ) {
			$actions['palafito_mark_entregado'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=entregado&order_id=' . $order_id ), 'woocommerce-mark-order-status' ),
				'name'   => __( 'Entregado', 'palafito-wc-extensions' ),
				'action' => 'palafito-mark-entregado',
			);
		}
		// Botón para cambiar a Facturado si está en entregado.
		if ( 'entregado' === $order_status ) {
			$actions['palafito_mark_facturado'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=facturado&order_id=' . $order_id ), 'woocommerce-mark-order-status' ),
				'name'   => __( 'Facturado', 'palafito-wc-extensions' ),
				'action' => 'palafito-mark-facturado',
			);
		}

		return $actions;
	}

	/**
	 * Agrega nuestros estados personalizados a la lista de estados para completar el pago.
	 *
	 * @param array $statuses Lista de estados para completar el pago.
	 * @return array
	 */
	public function add_custom_statuses_to_payment_complete( $statuses ) {
		$statuses[] = 'entregado';
		return $statuses;
	}

	/**
	 * Agrega nuestros estados personalizados a la lista de estados pagados.
	 *
	 * @param array $statuses Lista de estados pagados.
	 * @return array
	 */
	public function add_custom_statuses_to_paid_statuses( $statuses ) {
		$statuses[] = 'entregado';
		return $statuses;
	}

	/**
	 * Modifica la visibilidad del botón Complete después de que WooCommerce las genere.
	 *
	 * @param array    $actions Acciones disponibles.
	 * @param WC_Order $order Objeto del pedido.
	 * @return array
	 */
	public function modify_complete_button_visibility( $actions, $order ) {
		$order_status = $order->get_status();
		$order_id     = $order->get_id();

		// Si el pedido está en estado de procesamiento, ocultar el botón Complete.
		if ( 'processing' === $order_status ) {
			unset( $actions['complete'] );
		}

		return $actions;
	}
}

// Inicializar las acciones solo si el plugin PDF está disponible.
if ( class_exists( 'WPO_WCPDF' ) ) {
	new Palafito_Admin_PDF_Actions();
}
