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
		// Filtrar las acciones de PDF según el estado del pedido (prioridad alta para ejecutar después del plugin PDF).
		add_filter( 'woocommerce_admin_order_actions', array( $this, 'filter_pdf_actions' ), 100, 2 );

		// Agregar acciones personalizadas de PDF.
		add_filter( 'woocommerce_admin_order_actions', array( $this, 'add_custom_pdf_actions' ), 110, 2 );

		// Manejar las acciones AJAX personalizadas.
		add_action( 'wp_ajax_palafito_download_packing_slip', array( $this, 'handle_download_packing_slip' ) );
		add_action( 'wp_ajax_palafito_download_invoice', array( $this, 'handle_download_invoice' ) );

		// Filtro adicional para asegurar que se ejecute después de que el plugin PDF agregue sus acciones.
		add_filter( 'woocommerce_admin_order_actions', array( $this, 'final_filter_pdf_actions' ), 999, 2 );
	}

	/**
	 * Filtra las acciones de PDF existentes según el estado del pedido.
	 *
	 * @param array    $actions Acciones disponibles.
	 * @param WC_Order $order Objeto del pedido.
	 * @return array
	 */
	public function filter_pdf_actions( $actions, $order ) {
		$order_status = $order->get_status();

		// Remover acciones de PDF que no deberían aparecer según el estado.
		foreach ( $actions as $key => $action ) {
			// Remover acción de Packing Slip si el pedido no está en estado "Entregado" o "Facturado".
			if ( ( 'wpo_wcpdf_packing-slip' === $key || 'wcpdf-packing-slip' === $key ) && ! in_array( $order_status, array( 'entregado', 'facturado' ), true ) ) {
				unset( $actions[ $key ] );
			}

			// Remover acción de Invoice si el pedido no está en estado "Facturado".
			if ( ( 'wpo_wcpdf_invoice' === $key || 'wcpdf-invoice' === $key ) && 'facturado' !== $order_status ) {
				unset( $actions[ $key ] );
			}
		}

		return $actions;
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

	/**
	 * Filtro final para asegurar que las acciones de PDF se filtren correctamente.
	 * Se ejecuta con prioridad muy alta para asegurar que se ejecute después del plugin PDF.
	 *
	 * @param array    $actions Acciones disponibles.
	 * @param WC_Order $order Objeto del pedido.
	 * @return array
	 */
	public function final_filter_pdf_actions( $actions, $order ) {
		$order_status = $order->get_status();

		// Lista de claves de acciones de PDF que debemos filtrar.
		$pdf_action_keys = array(
			'wpo_wcpdf_packing-slip',
			'wcpdf-packing-slip',
			'wpo_wcpdf_invoice',
			'wcpdf-invoice',
			'wpo_wcpdf_packing_slip',
			'wpo_wcpdf_invoice',
		);

		// Remover acciones de PDF según el estado del pedido.
		foreach ( $actions as $key => $action ) {
			// Si es una acción de PDF de packing slip y el pedido no está en estado correcto.
			if ( in_array( $key, array( 'wpo_wcpdf_packing-slip', 'wcpdf-packing-slip', 'wpo_wcpdf_packing_slip' ), true ) ) {
				if ( ! in_array( $order_status, array( 'entregado', 'facturado' ), true ) ) {
					unset( $actions[ $key ] );
				}
			}

			// Si es una acción de PDF de invoice y el pedido no está en estado correcto.
			if ( in_array( $key, array( 'wpo_wcpdf_invoice', 'wcpdf-invoice', 'wpo_wcpdf_invoice' ), true ) ) {
				if ( 'facturado' !== $order_status ) {
					unset( $actions[ $key ] );
				}
			}
		}

		return $actions;
	}
}

// Inicializar las acciones de PDF solo si el plugin PDF está disponible.
if ( class_exists( 'WPO_WCPDF' ) ) {
	new Palafito_Admin_PDF_Actions();
}
