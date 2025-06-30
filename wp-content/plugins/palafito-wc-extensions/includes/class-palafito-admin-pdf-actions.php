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

		// Handler AJAX para cambiar estado a entregado o facturado.
		add_action( 'wp_ajax_palafito_mark_order_status', array( $this, 'handle_mark_order_status' ) );
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
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=palafito_mark_order_status&status=entregado&order_id=' . $order_id ), 'palafito_mark_order_status' ),
				'name'   => __( 'Entregado', 'palafito-wc-extensions' ),
				'action' => 'palafito-mark-entregado',
			);
		}
		// Botón para cambiar a Facturado si está en entregado.
		if ( 'entregado' === $order_status ) {
			$actions['palafito_mark_facturado'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=palafito_mark_order_status&status=facturado&order_id=' . $order_id ), 'palafito_mark_order_status' ),
				'name'   => __( 'Facturado', 'palafito-wc-extensions' ),
				'action' => 'palafito-mark-facturado',
			);
		}

		return $actions;
	}

	/**
	 * Handler AJAX para cambiar el estado del pedido a entregado o facturado.
	 */
	public function handle_mark_order_status() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'No tienes permisos para cambiar el estado del pedido.', 'palafito-wc-extensions' ) ) );
		}

		$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'palafito_mark_order_status' ) ) {
			wp_send_json_error( array( 'message' => __( 'Nonce inválido.', 'palafito-wc-extensions' ) ) );
		}

		$order_id = isset( $_REQUEST['order_id'] ) ? absint( $_REQUEST['order_id'] ) : 0;
		$status   = isset( $_REQUEST['status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ) : '';

		if ( ! $order_id || ! in_array( $status, array( 'entregado', 'facturado' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Datos inválidos.', 'palafito-wc-extensions' ) ) );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Pedido no encontrado.', 'palafito-wc-extensions' ) ) );
		}

		$order->update_status( $status, __( 'Cambio de estado vía acción rápida.', 'palafito-wc-extensions' ) );
		wp_send_json_success( array( 'message' => __( 'Estado actualizado correctamente.', 'palafito-wc-extensions' ) ) );
	}
}

// Inicializar las acciones solo si el plugin PDF está disponible.
if ( class_exists( 'WPO_WCPDF' ) ) {
	new Palafito_Admin_PDF_Actions();
}
