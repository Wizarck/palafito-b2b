<?php
/**
 * Configuración de PDF para estados personalizados de Palafito.
 *
 * @package Palafito_WC_Extensions
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Configura automáticamente los PDF para los estados personalizados de Palafito.
 */
class Palafito_PDF_Configuration {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Configurar PDF cuando se activa el plugin.
		add_action( 'admin_init', array( $this, 'configure_pdf_settings' ) );

		// Agregar filtros para personalizar la generación de documentos.
		add_filter( 'wpo_wcpdf_document_is_allowed', array( $this, 'customize_document_generation' ), 10, 2 );
		add_filter( 'wpo_wcpdf_document_types_for_email', array( $this, 'customize_email_attachments' ), 10, 3 );
	}

	/**
	 * Configura automáticamente los ajustes de PDF para los estados personalizados.
	 */
	public function configure_pdf_settings() {
		// Solo configurar una vez.
		if ( get_option( 'palafito_pdf_configured' ) ) {
			return;
		}

		// Configurar Packing Slip (Albarán) para estados personalizados.
		$this->configure_packing_slip_settings();

		// Configurar Invoice (Factura) para estados personalizados.
		$this->configure_invoice_settings();

		// Marcar como configurado.
		update_option( 'palafito_pdf_configured', true );
	}

	/**
	 * Configura los ajustes del Packing Slip (Albarán).
	 */
	private function configure_packing_slip_settings() {
		$packing_slip_settings = get_option( 'wpo_wcpdf_documents_settings_packing-slip', array() );

		// Habilitar Packing Slip.
		$packing_slip_settings['enabled'] = 1;

		// Configurar para que se genere solo en estados específicos.
		// Deshabilitar para todos los estados excepto los personalizados.
		$all_statuses      = wc_get_order_statuses();
		$disabled_statuses = array();

		foreach ( $all_statuses as $status_key => $status_label ) {
			$status_slug = str_replace( 'wc-', '', $status_key );
			// Solo permitir en estados personalizados de Palafito.
			if ( ! in_array( $status_slug, array( 'entregado', 'facturado' ), true ) ) {
				$disabled_statuses[] = $status_key;
			}
		}

		$packing_slip_settings['disable_for_statuses'] = $disabled_statuses;

		// Configurar adjuntos de email (opcional).
		// Puedes descomentar y modificar según necesites.

		/*
		$packing_slip_settings['attach_to_email_ids'] = array(
			'customer_completed_order' => 1, // Email de pedido completado.
		);
		*/

		// Guardar configuración.
		update_option( 'wpo_wcpdf_documents_settings_packing-slip', $packing_slip_settings );
	}

	/**
	 * Configura los ajustes de Invoice (Factura).
	 */
	private function configure_invoice_settings() {
		$invoice_settings = get_option( 'wpo_wcpdf_documents_settings_invoice', array() );

		// Habilitar Invoice.
		$invoice_settings['enabled'] = 1;

		// Configurar para que se genere solo en estados específicos.
		$all_statuses      = wc_get_order_statuses();
		$disabled_statuses = array();

		foreach ( $all_statuses as $status_key => $status_label ) {
			$status_slug = str_replace( 'wc-', '', $status_key );
			// Solo permitir en estado "Facturado".
			if ( $status_slug !== 'facturado' ) {
				$disabled_statuses[] = $status_key;
			}
		}

		$invoice_settings['disable_for_statuses'] = $disabled_statuses;

		// Configurar adjuntos de email (opcional).

		/*
		$invoice_settings['attach_to_email_ids'] = array(
			'customer_completed_order' => 1,
		);
		*/

		// Guardar configuración.
		update_option( 'wpo_wcpdf_documents_settings_invoice', $invoice_settings );
	}

	/**
	 * Personaliza la generación de documentos según el estado del pedido.
	 *
	 * @param bool   $allowed Si el documento está permitido.
	 * @param object $document El objeto del documento.
	 * @return bool
	 */
	public function customize_document_generation( $allowed, $document ) {
		if ( ! $document->order ) {
			return $allowed;
		}

		$order_status  = $document->order->get_status();
		$document_type = $document->get_type();

		// Packing Slip (Albarán) - solo para estados "Entregado" y "Facturado".
		if ( 'packing-slip' === $document_type ) {
			if ( ! in_array( $order_status, array( 'entregado', 'facturado' ), true ) ) {
				return false;
			}
		}

		// Invoice (Factura) - solo para estado "Facturado".
		if ( 'invoice' === $document_type ) {
			if ( $order_status !== 'facturado' ) {
				return false;
			}
		}

		return $allowed;
	}

	/**
	 * Personaliza los adjuntos de email según el estado del pedido.
	 *
	 * @param array  $document_types Tipos de documentos a adjuntar.
	 * @param string $email_id ID del email.
	 * @param object $order Objeto del pedido.
	 * @return array
	 */
	public function customize_email_attachments( $document_types, $email_id, $order ) {
		if ( ! $order ) {
			return $document_types;
		}

		$order_status = $order->get_status();

		// Solo adjuntar documentos según el estado del pedido.
		foreach ( $document_types as $output_format => $types ) {
			foreach ( $types as $key => $document_type ) {
				// Packing Slip - solo para estados "Entregado" y "Facturado".
				if ( 'packing-slip' === $document_type ) {
					if ( ! in_array( $order_status, array( 'entregado', 'facturado' ), true ) ) {
						unset( $document_types[ $output_format ][ $key ] );
					}
				}

				// Invoice - solo para estado "Facturado".
				if ( 'invoice' === $document_type ) {
					if ( $order_status !== 'facturado' ) {
						unset( $document_types[ $output_format ][ $key ] );
					}
				}
			}
		}

		return $document_types;
	}
}

// Inicializar la configuración de PDF.
new Palafito_PDF_Configuration();
