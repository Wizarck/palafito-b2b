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

		// Modificar la lógica del botón Complete para que aparezca solo en estados específicos.
		add_filter( 'woocommerce_admin_order_actions', array( $this, 'modify_complete_button_visibility' ), 20, 2 );
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
		// Obtener el referer de forma segura para la redirección.
		$referer = isset( $_SERVER['REQUEST_URI'] ) ? rawurlencode( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) : '';

		// Botón para cambiar a Entregado si está en procesando.
		if ( 'processing' === $order_status ) {
			$actions['palafito_mark_entregado'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=entregado&order_id=' . $order_id . '&wp_http_referer=' . $referer ), 'woocommerce-mark-order-status' ),
				'name'   => __( 'Entregado', 'palafito-wc-extensions' ),
				'action' => 'palafito_mark_entregado',
			);
		}

		// Botón para cambiar a Facturado si está en entregado.
		if ( 'entregado' === $order_status ) {
			$actions['palafito_mark_facturado'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=facturado&order_id=' . $order_id . '&wp_http_referer=' . $referer ), 'woocommerce-mark-order-status' ),
				'name'   => __( 'Facturado', 'palafito-wc-extensions' ),
				'action' => 'palafito_mark_facturado',
			);
		}

		return $actions;
	}

	/**
	 * Modifica la visibilidad del botón Complete para que aparezca solo en estados específicos.
	 *
	 * @param array    $actions Acciones disponibles.
	 * @param WC_Order $order Objeto del pedido.
	 * @return array
	 */
	public function modify_complete_button_visibility( $actions, $order ) {
		$order_status = $order->get_status();

		// Ocultar el botón Complete si está en processing (ya que tenemos nuestro botón Entregado).
		if ( 'processing' === $order_status && isset( $actions['complete'] ) ) {
			unset( $actions['complete'] );
		}

		// Mostrar el botón Complete si está en facturado (para completar el pedido).
		if ( 'facturado' === $order_status && ! isset( $actions['complete'] ) ) {
			$order_id            = $order->get_id();
			$actions['complete'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=completed&order_id=' . $order_id ), 'woocommerce-mark-order-status' ),
				'name'   => __( 'Complete', 'woocommerce' ),
				'action' => 'complete',
			);
		}

		return $actions;
	}
}

// Inicializar las acciones solo si el plugin PDF está disponible.
if ( class_exists( 'WPO_WCPDF' ) ) {
	new Palafito_Admin_PDF_Actions();
}
