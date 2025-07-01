<?php
/**
 * Email: Customer Facturado
 *
 * Handles the email sent to the customer when an order is marked as Facturado.
 *
 * @package Palafito_WC_Extensions
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Email class for Facturado status.
 *
 * Handles email notifications for the custom "Facturado" order status.
 *
 * @since 1.0.0
 */
class WC_Email_Customer_Facturado extends WC_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'customer_facturado';
		$this->customer_email = true;
		$this->title          = __( 'Pedido facturado (Palafito)', 'palafito-wc-extensions' );
		$this->description    = __( 'Este email se envía al cliente cuando su pedido cambia al estado Facturado.', 'palafito-wc-extensions' );
		$this->template_html  = 'emails/customer-facturado.php';
		$this->template_plain = 'emails/plain/customer-facturado.php';
		$this->template_base  = WC()->template_path() . 'emails/';

		// Triggers for this email.
		add_action( 'woocommerce_order_status_facturado', array( $this, 'trigger' ), 10, 2 );

		// Call parent constructor.
		parent::__construct();
	}

	/**
	 * Trigger the email.
	 *
	 * @param int      $order_id Order ID.
	 * @param WC_Order $order    Order object.
	 */
	public function trigger( $order_id, $order = false ) {
		if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order ) {
			return;
		}

		$this->object    = $order;
		$this->recipient = $this->get_recipient_from_order( $order );

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

	/**
	 * Get default subject.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return __( 'Tu pedido ha sido facturado - {site_title}', 'palafito-wc-extensions' );
	}

	/**
	 * Get default heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( '¡Tu pedido ha sido facturado!', 'palafito-wc-extensions' );
	}

	/**
	 * Get HTML content.
	 *
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html(
			$this->template_html,
			array(
				'order'         => $this->object,
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => false,
				'plain_text'    => false,
				'email'         => $this,
			),
			'',
			plugin_dir_path( __FILE__ ) . '../../templates/'
		);
	}

	/**
	 * Get plain content.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html(
			$this->template_plain,
			array(
				'order'         => $this->object,
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => false,
				'plain_text'    => true,
				'email'         => $this,
			),
			'',
			plugin_dir_path( __FILE__ ) . '../../templates/'
		);
	}

	/**
	 * Get recipient from order.
	 *
	 * @param WC_Order $order Order object.
	 * @return string
	 */
	protected function get_recipient_from_order( $order ) {
		// Use WooCommerce logic for recipient(s), allow comma-separated emails.
		$recipient         = $order->get_billing_email();
		$custom_recipients = $this->get_option( 'recipient', '' );
		if ( ! empty( $custom_recipients ) ) {
			$recipient = $custom_recipients;
		}
		return $recipient;
	}

	/**
	 * Get attachments for the email.
	 *
	 * @return array
	 */
	// Eliminado: los adjuntos se gestionan por el filtro global en Palafito_Email_Attachments.
}
