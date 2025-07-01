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

		// Customize the list of available emails for PDF attachments.
		add_filter( 'wpo_wcpdf_wc_emails', array( $this, 'customize_wc_emails_list' ), 10, 1 );
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
	public function attach_documents_to_email( $attachments, $email_id, $order, $email ) {
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

		// Adjuntar packing slip solo si el email es el nativo de WooCommerce para 'entregado' y está configurado en el plugin PDF.
		if ( 'entregado' === $order_status && 'customer_entregado' === $email_id ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Palafito WC Extensions: Processing packing slip attachment for order ' . $order->get_id() );
			}
			if ( function_exists( 'wcpdf_get_document' ) ) {
				$packing_slip = wcpdf_get_document( 'packing-slip', $order );
				if ( $packing_slip && $packing_slip->exists() ) {
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( 'Palafito WC Extensions: Packing slip exists for order ' . $order->get_id() );
					}
					$settings = get_option( 'wpo_wcpdf_documents_settings_packing-slip', array() );
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( 'Palafito WC Extensions: Packing slip settings: ' . print_r( $settings, true ) );
					}
					if ( ! empty( $settings['attach_to_email_ids'] ) && in_array( 'customer_entregado', (array) $settings['attach_to_email_ids'], true ) ) {
						if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
							error_log( 'Palafito WC Extensions: Adding packing slip to attachments for order ' . $order->get_id() );
						}
						$attachments[] = $packing_slip->get_pdf( 'path' );
					} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( 'Palafito WC Extensions: Packing slip not configured for customer_entregado email' );
					}
				} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Palafito WC Extensions: Packing slip does not exist for order ' . $order->get_id() );
				}
			} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Palafito WC Extensions: wcpdf_get_document function not available' );
			}
		}

		// Adjuntar invoice solo si el email es el nativo de WooCommerce para 'facturado' y está configurado en el plugin PDF.
		if ( 'facturado' === $order_status && 'customer_facturado' === $email_id ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "Palafito WC Extensions: Processing invoice attachment for order {$order->get_id()}" );
			}
			if ( function_exists( 'wcpdf_get_document' ) ) {
				$invoice = wcpdf_get_document( 'invoice', $order );
				if ( $invoice && $invoice->exists() ) {
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( "Palafito WC Extensions: Invoice exists for order {$order->get_id()}" );
					}
					$settings = get_option( 'wpo_wcpdf_documents_settings_invoice', array() );
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( 'Palafito WC Extensions: Invoice settings: ' . print_r( $settings, true ) );
					}
					if ( ! empty( $settings['attach_to_email_ids'] ) && in_array( 'customer_facturado', (array) $settings['attach_to_email_ids'], true ) ) {
						if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
							error_log( "Palafito WC Extensions: Adding invoice to attachments for order {$order->get_id()}" );
						}
						$attachments[] = $invoice->get_pdf( 'path' );
					} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
							error_log( 'Palafito WC Extensions: Invoice not configured for customer_facturado email' );
					}
				} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( "Palafito WC Extensions: Invoice doesn't exist for order {$order->get_id()}" );
				}
			} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Palafito WC Extensions: wcpdf_get_document function not available for invoice' );
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
		// Add packing slip to custom email IDs if needed (legacy, for compatibility).
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
	 * Customize the list of available emails for PDF attachments.
	 *
	 * @param array $emails Current list of emails.
	 * @return array
	 */
	public function customize_wc_emails_list( $emails ) {
		$emails['customer_entregado'] = __( 'Pedido entregado', 'woocommerce-pdf-invoices-packing-slips' );
		$emails['customer_facturado'] = __( 'Pedido facturado', 'woocommerce-pdf-invoices-packing-slips' );
		$emails                       = array_unique( $emails, SORT_STRING );
		asort( $emails );
		return $emails;
	}
}
