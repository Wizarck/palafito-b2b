<?php
/**
 * Email Attachments Handler for Palafito WC Extensions
 *
 * @package Palafito_WC_Extensions
 * @since 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Palafito_Email_Attachments
 *
 * Handles automatic email attachments for custom order statuses.
 */
class Palafito_Email_Attachments {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Hook into WooCommerce email attachments.
		add_filter( 'woocommerce_email_attachments', array( $this, 'attach_documents_to_email' ), 100, 4 );

		// Hook into the custom email attachment filter.
		add_filter( 'wpo_wcpdf_attach_documents', array( $this, 'add_custom_email_attachments' ), 10, 1 );

		// Add custom email IDs for our custom order statuses.
		add_filter( 'woocommerce_email_actions', array( $this, 'add_custom_email_actions' ), 10, 1 );

		// Hook into order status change actions to send custom emails.
		add_action( 'woocommerce_order_status_entregado', array( $this, 'send_entregado_email' ), 10, 2 );
		add_action( 'woocommerce_order_status_facturado', array( $this, 'send_facturado_email' ), 10, 2 );
	}

	/**
	 * Attach documents to WooCommerce emails.
	 *
	 * @param array    $attachments Array of attachment file paths.
	 * @param string   $email_id    Email ID.
	 * @param WC_Order $order       Order object.
	 * @param WC_Email $email       Email object.
	 * @return array
	 */
	public function attach_documents_to_email( $attachments, $email_id, $order, $email = null ) {
		// Unused parameter for compatibility with WooCommerce email filter.
		unset( $email );
		// Check if all variables are properly set.
		if ( ! is_object( $order ) || ! isset( $email_id ) ) {
			return $attachments;
		}

		// Skip if not a valid order.
		if ( ! ( $order instanceof WC_Order || is_subclass_of( $order, 'WC_Abstract_Order' ) ) ) {
			return $attachments;
		}

		// Skip certain email types.
		if ( in_array( $email_id, array( 'no_stock', 'low_stock', 'backorder', 'customer_new_account', 'customer_reset_password' ), true ) ) {
			return $attachments;
		}

		// Get order status.
		$order_status = $order->get_status();

		// Attach packing slip for "Entregado" status.
		if ( 'entregado' === $order_status && $this->should_attach_packing_slip( $email_id ) ) {
			$packing_slip_attachment = $this->get_packing_slip_attachment( $order );
			if ( $packing_slip_attachment ) {
				$attachments[] = $packing_slip_attachment;
			}
		}

		// Attach invoice for "Facturado" status.
		if ( 'facturado' === $order_status && $this->should_attach_invoice( $email_id ) ) {
			$invoice_attachment = $this->get_invoice_attachment( $order );
			if ( $invoice_attachment ) {
				$attachments[] = $invoice_attachment;
			}
		}

		return $attachments;
	}

	/**
	 * Add custom email attachments to the PDF plugin configuration.
	 *
	 * @param array $attach_documents Current attachment configuration.
	 * @return array
	 */
	public function add_custom_email_attachments( $attach_documents ) {
		// Add packing slip to custom email IDs.
		if ( isset( $attach_documents['pdf']['packing-slip'] ) ) {
			$attach_documents['pdf']['packing-slip'][] = 'custom_entregado_email';
		}

		// Add invoice to custom email IDs.
		if ( isset( $attach_documents['pdf']['invoice'] ) ) {
			$attach_documents['pdf']['invoice'][] = 'custom_facturado_email';
		}

		return $attach_documents;
	}

	/**
	 * Add custom email actions for our custom order statuses.
	 *
	 * @param array $email_actions Current email actions.
	 * @return array
	 */
	public function add_custom_email_actions( $email_actions ) {
		$email_actions[] = 'woocommerce_order_status_entregado';
		$email_actions[] = 'woocommerce_order_status_facturado';

		return $email_actions;
	}

	/**
	 * Check if packing slip should be attached to this email.
	 *
	 * @param string $email_id Email ID.
	 * @return bool
	 */
	private function should_attach_packing_slip( $email_id ) {
		// Attach to customer completed order email and our custom emails.
		$allowed_emails = array(
			'customer_completed_order',
			'custom_entregado_email',
		);

		return in_array( $email_id, $allowed_emails, true );
	}

	/**
	 * Check if invoice should be attached to this email.
	 *
	 * @param string $email_id Email ID.
	 * @return bool
	 */
	private function should_attach_invoice( $email_id ) {
		// Attach to customer completed order email and our custom emails.
		$allowed_emails = array(
			'customer_completed_order',
			'custom_facturado_email',
		);

		return in_array( $email_id, $allowed_emails, true );
	}

	/**
	 * Get packing slip attachment for an order.
	 *
	 * @param WC_Order $order Order object.
	 * @return string|false Attachment file path or false on failure.
	 */
	private function get_packing_slip_attachment( $order ) {
		// Check if PDF plugin is active.
		if ( ! function_exists( 'WPO_WCPDF' ) ) {
			return false;
		}

		try {
			// Get the packing slip document.
			$document = wcpdf_get_document( 'packing-slip', array( $order->get_id() ), true );

			if ( ! $document ) {
				return false;
			}

			// Get the PDF file.
			$attachment = wcpdf_get_document_file( $document, 'pdf' );

			return $attachment ? $attachment : false;

		} catch ( Exception $e ) {
			// Log error if debug is enabled.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Palafito WC Extensions: Error generating packing slip attachment: ' . $e->getMessage() );
			}
			return false;
		}
	}

	/**
	 * Get invoice attachment for an order.
	 *
	 * @param WC_Order $order Order object.
	 * @return string|false Attachment file path or false on failure.
	 */
	private function get_invoice_attachment( $order ) {
		// Check if PDF plugin is active.
		if ( ! function_exists( 'WPO_WCPDF' ) ) {
			return false;
		}

		try {
			// Get the invoice document.
			$document = wcpdf_get_document( 'invoice', array( $order->get_id() ), true );

			if ( ! $document ) {
				return false;
			}

			// Get the PDF file.
			$attachment = wcpdf_get_document_file( $document, 'pdf' );

			return $attachment ? $attachment : false;

		} catch ( Exception $e ) {
			// Log error if debug is enabled.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Palafito WC Extensions: Error generating invoice attachment: ' . $e->getMessage() );
			}
			return false;
		}
	}

	/**
	 * Send custom email when order status changes to "Entregado".
	 *
	 * @param int      $order_id   Order ID.
	 * @param string   $old_status Old status.
	 * @param string   $new_status New status.
	 * @param WC_Order $order      Order object.
	 */
	public function send_entregado_email( $order_id, $old_status, $new_status, $order ) {
		if ( 'entregado' === $new_status ) {
			$this->send_custom_order_email( $order, 'entregado' );
		}
	}

	/**
	 * Send custom email when order status changes to "Facturado".
	 *
	 * @param int      $order_id   Order ID.
	 * @param string   $old_status Old status.
	 * @param string   $new_status New status.
	 * @param WC_Order $order      Order object.
	 */
	public function send_facturado_email( $order_id, $old_status, $new_status, $order ) {
		if ( 'facturado' === $new_status ) {
			$this->send_custom_order_email( $order, 'facturado' );
		}
	}

	/**
	 * Send custom order email with appropriate attachments.
	 *
	 * @param WC_Order $order       Order object.
	 * @param string   $status_type Status type ('entregado' or 'facturado').
	 */
	private function send_custom_order_email( $order, $status_type ) {
		// Get customer email.
		$customer_email = $order->get_billing_email();

		if ( ! $customer_email ) {
			return;
		}

		// Prepare email content.
		$subject = $this->get_email_subject( $status_type, $order );
		$message = $this->get_email_message( $status_type, $order );
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		// Prepare attachments.
		$attachments = array();

		if ( 'entregado' === $status_type ) {
			$packing_slip = $this->get_packing_slip_attachment( $order );
			if ( $packing_slip ) {
				$attachments[] = $packing_slip;
			}
		} elseif ( 'facturado' === $status_type ) {
			$invoice = $this->get_invoice_attachment( $order );
			if ( $invoice ) {
				$attachments[] = $invoice;
			}
		}

		// Send email.
		$sent = wp_mail( $customer_email, $subject, $message, $headers, $attachments );

		// Log the email sending.
		if ( $sent ) {
			$order->add_order_note(
				sprintf(
					/* translators: %s: Status type */
					__( 'Email de %s enviado al cliente con adjuntos.', 'palafito-wc-extensions' ),
					ucfirst( $status_type )
				)
			);
		} else {
			$order->add_order_note(
				sprintf(
					/* translators: %s: Status type */
					__( 'Error al enviar email de %s al cliente.', 'palafito-wc-extensions' ),
					ucfirst( $status_type )
				)
			);
		}
	}

	/**
	 * Get email subject for custom status.
	 *
	 * @param string   $status_type Status type.
	 * @param WC_Order $order       Order object.
	 * @return string
	 */
	private function get_email_subject( $status_type, $order ) {
		$order_number = $order->get_order_number();

		if ( 'entregado' === $status_type ) {
			return sprintf(
				/* translators: %s: Order number */
				__( 'Su pedido #%s ha sido entregado - Palafito', 'palafito-wc-extensions' ),
				$order_number
			);
		} elseif ( 'facturado' === $status_type ) {
			return sprintf(
				/* translators: %s: Order number */
				__( 'Factura disponible para su pedido #%s - Palafito', 'palafito-wc-extensions' ),
				$order_number
			);
		}

		return '';
	}

	/**
	 * Get email message for custom status.
	 *
	 * @param string   $status_type Status type.
	 * @param WC_Order $order       Order object.
	 * @return string
	 */
	private function get_email_message( $status_type, $order ) {
		$order_number  = $order->get_order_number();
		$customer_name = $order->get_billing_first_name();

		if ( 'entregado' === $status_type ) {
			return sprintf(
				'<h2>%s</h2>
				<p>%s</p>
				<p>%s</p>
				<p>%s</p>',
				__( 'Pedido Entregado', 'palafito-wc-extensions' ),
				sprintf(
					/* translators: %s: Customer name */
					__( 'Estimado/a %s,', 'palafito-wc-extensions' ),
					$customer_name
				),
				sprintf(
					/* translators: %s: Order number */
					__( 'Su pedido #%s ha sido entregado exitosamente.', 'palafito-wc-extensions' ),
					$order_number
				),
				__( 'Adjunto encontrará el albarán de entrega para su referencia.', 'palafito-wc-extensions' )
			);
		} elseif ( 'facturado' === $status_type ) {
			return sprintf(
				'<h2>%s</h2>
				<p>%s</p>
				<p>%s</p>
				<p>%s</p>',
				__( 'Factura Disponible', 'palafito-wc-extensions' ),
				sprintf(
					/* translators: %s: Customer name */
					__( 'Estimado/a %s,', 'palafito-wc-extensions' ),
					$customer_name
				),
				sprintf(
					/* translators: %s: Order number */
					__( 'La factura para su pedido #%s ya está disponible.', 'palafito-wc-extensions' ),
					$order_number
				),
				__( 'Adjunto encontrará la factura correspondiente.', 'palafito-wc-extensions' )
			);
		}

		return '';
	}
}
