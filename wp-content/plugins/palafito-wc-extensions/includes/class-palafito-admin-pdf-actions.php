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
		// Agregar acciones personalizadas de PDF.
		add_filter( 'woocommerce_admin_order_actions', array( $this, 'add_custom_pdf_actions' ), 30, 2 );

		// Manejar las acciones AJAX personalizadas.
		add_action( 'wp_ajax_palafito_download_packing_slip', array( $this, 'handle_download_packing_slip' ) );
		add_action( 'wp_ajax_palafito_download_invoice', array( $this, 'handle_download_invoice' ) );
	}



	/**
	 * Agrega acciones personalizadas de PDF con mejor control.
	 *
	 * @param array    $actions Acciones disponibles.
	 * @param WC_Order $order Objeto del pedido.
	 * @return array
	 */
	public function add_custom_pdf_actions( $actions, $order ) {
		$order_status = $order->get_status();
		$order_id     = $order->get_id();

		// Agregar acción de Packing Slip solo si el pedido está en "Entregado" o "Facturado".
		if ( in_array( $order_status, array( 'entregado', 'facturado' ), true ) ) {
			$actions['palafito_packing_slip'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=palafito_download_packing_slip&order_id=' . $order_id ), 'palafito_download_packing_slip' ),
				'name'   => __( 'Descargar Albarán', 'palafito-wc-extensions' ),
				'action' => 'palafito-packing-slip',
			);
		}

		// Agregar acción de Invoice solo si el pedido está en "Facturado".
		if ( 'facturado' === $order_status ) {
			$actions['palafito_invoice'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=palafito_download_invoice&order_id=' . $order_id ), 'palafito_download_invoice' ),
				'name'   => __( 'Descargar Factura', 'palafito-wc-extensions' ),
				'action' => 'palafito-invoice',
			);
		}

		return $actions;
	}

	/**
	 * Maneja la descarga del Packing Slip (Albarán).
	 */
	public function handle_download_packing_slip() {
		// Verificar nonce.
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'palafito_download_packing_slip' ) ) {
			wp_die( esc_html__( 'Acceso no autorizado.', 'palafito-wc-extensions' ) );
		}

		// Verificar permisos.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'No tienes permisos para realizar esta acción.', 'palafito-wc-extensions' ) );
		}

		$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
		$order    = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_die( esc_html__( 'Pedido no encontrado.', 'palafito-wc-extensions' ) );
		}

		// Verificar que el pedido esté en estado correcto.
		$order_status = $order->get_status();
		if ( ! in_array( $order_status, array( 'entregado', 'facturado' ), true ) ) {
			wp_die( esc_html__( 'El albarán solo se puede descargar para pedidos entregados o facturados.', 'palafito-wc-extensions' ) );
		}

		// Generar y descargar el Packing Slip usando el plugin PDF.
		if ( class_exists( 'WPO_WCPDF' ) ) {
			$document = wcpdf_get_document( 'packing-slip', $order );
			if ( $document ) {
				$document->output_pdf( 'download' );
				exit;
			}
		}

		wp_die( esc_html__( 'Error al generar el albarán.', 'palafito-wc-extensions' ) );
	}

	/**
	 * Maneja la descarga de la Invoice (Factura).
	 */
	public function handle_download_invoice() {
		// Verificar nonce.
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'palafito_download_invoice' ) ) {
			wp_die( esc_html__( 'Acceso no autorizado.', 'palafito-wc-extensions' ) );
		}

		// Verificar permisos.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'No tienes permisos para realizar esta acción.', 'palafito-wc-extensions' ) );
		}

		$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
		$order    = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_die( esc_html__( 'Pedido no encontrado.', 'palafito-wc-extensions' ) );
		}

		// Verificar que el pedido esté en estado correcto.
		$order_status = $order->get_status();
		if ( 'facturado' !== $order_status ) {
			wp_die( esc_html__( 'La factura solo se puede descargar para pedidos facturados.', 'palafito-wc-extensions' ) );
		}

		// Generar y descargar la Invoice usando el plugin PDF.
		if ( class_exists( 'WPO_WCPDF' ) ) {
			$document = wcpdf_get_document( 'invoice', $order );
			if ( $document ) {
				$document->output_pdf( 'download' );
				exit;
			}
		}

		wp_die( esc_html__( 'Error al generar la factura.', 'palafito-wc-extensions' ) );
	}
}

// Inicializar las acciones de PDF solo si el plugin PDF está disponible.
if ( class_exists( 'WPO_WCPDF' ) ) {
	new Palafito_Admin_PDF_Actions();
}
