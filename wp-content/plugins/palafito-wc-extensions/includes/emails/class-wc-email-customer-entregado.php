<?php
/**
 * Customer Entregado Email
 *
 * An email sent to the customer when the order is marked as "Entregado".
 *
 * @class       WC_Email_Customer_Entregado
 * @extends     WC_Email
 * @package     Palafito_WC_Extensions
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Email_Customer_Entregado' ) ) :

	/**
	 * Customer Entregado Email
	 *
	 * @class       WC_Email_Customer_Entregado
	 * @extends     WC_Email
	 */
	class WC_Email_Customer_Entregado extends WC_Email {

		/**
		 * Constructor
		 */
		public function __construct() {

			$this->id             = 'customer_entregado';
			$this->customer_email = true;
			$this->title          = __( 'Pedido entregado', 'palafito-wc-extensions' );
			$this->description    = __( 'Este email se envía al cliente cuando el pedido se marca como "Entregado".', 'palafito-wc-extensions' );

			$this->template_html  = 'emails/customer-entregado.php';
			$this->template_plain = 'emails/plain/customer-entregado.php';
			$this->template_base  = plugin_dir_path( dirname( __DIR__ ) ) . 'templates/';

			$this->subject = __( 'Tu pedido #{order_number} ha sido entregado', 'palafito-wc-extensions' );
			$this->heading = __( '¡Tu pedido ha sido entregado!', 'palafito-wc-extensions' );

			// Triggers for this email.
			add_action( 'woocommerce_order_status_entregado_notification', array( $this, 'trigger' ), 10, 2 );

			// Call parent constructor.
			parent::__construct();
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param int            $order_id The order ID.
		 * @param WC_Order|false $order Order object.
		 */
		public function trigger( $order_id, $order = false ) {
			$this->setup_locale();

			if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
				$order = wc_get_order( $order_id );
			}

			if ( is_a( $order, 'WC_Order' ) ) {
				$this->object                         = $order;
				$this->recipient                      = $this->object->get_billing_email();
				$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
				$this->placeholders['{order_number}'] = $this->object->get_order_number();
			}

			if ( $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_locale();
		}

		/**
		 * Get content html.
		 *
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
				array(
					'order'              => $this->object,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => false,
					'plain_text'         => false,
					'email'              => $this,
				),
				'',
				$this->template_base
			);
		}

		/**
		 * Get content plain.
		 *
		 * @return string
		 */
		public function get_content_plain() {
			return wc_get_template_html(
				$this->template_plain,
				array(
					'order'              => $this->object,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => false,
					'plain_text'         => true,
					'email'              => $this,
				),
				'',
				$this->template_base
			);
		}

		/**
		 * Initialise settings form fields.
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'            => array(
					'title'   => __( 'Enable/Disable', 'palafito-wc-extensions' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable this email notification', 'palafito-wc-extensions' ),
					'default' => 'yes',
				),
				'subject'            => array(
					'title'       => __( 'Subject', 'palafito-wc-extensions' ),
					'type'        => 'text',
					/* translators: %s: placeholders available for translation */
					'description' => sprintf( __( 'Available placeholders: %s', 'palafito-wc-extensions' ), '<code>{order_date}, {order_number}</code>' ),
					'placeholder' => $this->get_default_subject(),
					'default'     => '',
				),
				'heading'            => array(
					'title'       => __( 'Email heading', 'palafito-wc-extensions' ),
					'type'        => 'text',
					/* translators: %s: placeholders available for translation */
					'description' => sprintf( __( 'Available placeholders: %s', 'palafito-wc-extensions' ), '<code>{order_date}, {order_number}</code>' ),
					'placeholder' => $this->get_default_heading(),
					'default'     => '',
				),
				'additional_content' => array(
					'title'       => __( 'Additional content', 'palafito-wc-extensions' ),
					'description' => __( 'Text to appear below the main email content.', 'palafito-wc-extensions' ),
					'css'         => 'width:400px; height: 75px;',
					'placeholder' => __( 'N/A', 'palafito-wc-extensions' ),
					'type'        => 'textarea',
					'default'     => $this->get_default_additional_content(),
				),
				'email_type'         => array(
					'title'       => __( 'Email type', 'palafito-wc-extensions' ),
					'type'        => 'select',
					'description' => __( 'Choose which format of email to send.', 'palafito-wc-extensions' ),
					'default'     => 'html',
					'class'       => 'email_type wc-enhanced-select',
					'options'     => $this->get_email_type_options(),
				),
			);
		}
	}

endif;

return new WC_Email_Customer_Entregado();
