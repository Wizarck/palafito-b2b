<?php
namespace WPO\WC\PDF_Invoices\Documents;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( '\\WPO\\WC\\PDF_Invoices\\Documents\\Proforma' ) ) :

/**
 * Proforma Invoice Document
 * 
 * @class  \WPO\WC\PDF_Invoices\Documents\Proforma
 */

class Proforma extends Pro_Document {

	/**
	 * @var string
	 */
	public $type;

	/**
	 * @var string
	 */
	public $title;

	/**
	 * @var string
	 */
	public $icon;

	/**
	 * Init/load the order object.
	 *
	 * @param  int|object|WC_Order $order Order to init.
	 */
	public function __construct( $order = 0 ) {
		// set properties
		$this->type  = 'proforma';
		$this->title = __( 'Proforma Invoice', 'wpo_wcpdf_pro' );
		$this->icon  = WPO_WCPDF_Pro()->plugin_url() . '/assets/images/proforma.svg';

		// Call parent constructor
		parent::__construct( $order );
		
		// Determine numbering system (main invoice number or separate document sequence)
		add_filter( 'wpo_wcpdf_document_sequential_number_store', array( $this, 'get_number_sequence' ), 1, 2 );
	}

	public function get_title() {
		// override/not using $this->title to allow for language switching!
		$title = __( 'Proforma Invoice', 'wpo_wcpdf_pro' );
		$title = apply_filters_deprecated( "wpo_wcpdf_{$this->slug}_title", array( $title, $this ), '2.15.11', 'wpo_wcpdf_document_title' ); // deprecated
		return apply_filters( 'wpo_wcpdf_document_title', $title, $this );
	}

	public function get_number_title() {
		// override to allow for language switching!
		$title = __( 'Proforma Invoice Number:', 'wpo_wcpdf_pro' );
		$title = apply_filters_deprecated( "wpo_wcpdf_{$this->slug}_number_title", array( $title, $this ), '2.15.11', 'wpo_wcpdf_document_number_title' ); // deprecated
		return apply_filters( 'wpo_wcpdf_document_number_title', $title, $this );
	}

	public function get_date_title() {
		// override to allow for language switching!
		$title = __( 'Proforma Invoice Date:', 'wpo_wcpdf_pro' );
		$title = apply_filters_deprecated( "wpo_wcpdf_{$this->slug}_date_title", array( $title, $this ), '2.15.11', 'wpo_wcpdf_document_date_title' ); // deprecated
		return apply_filters( 'wpo_wcpdf_document_date_title', $title, $this );
	}
	
	/**
	 * Get the shipping address title
	 *
	 * @return string
	 */
	public function get_shipping_address_title(): string {
		// override to allow for language switching!
		return apply_filters( 'wpo_wcpdf_document_shipping_address_title', __( 'Ship To:', 'wpo_wcpdf_pro' ), $this );
	}

	public function get_filename( $context = 'download', $args = array() ) {
		$order_ids   = $args['order_ids'] ?? array( $this->order_id );
		$order_count = count( $order_ids );
		$name        = _n( 'proforma-invoice', 'proforma-invoices', $order_count, 'wpo_wcpdf_pro' );

		if ( 1 === $order_count ) {
			if ( isset( $this->settings['display_number'] ) ) {
				$suffix = (string) $this->get_number();
			} else {
				if ( empty( $this->order ) ) {
					$order  = wc_get_order( $order_ids[0] );
					$suffix = method_exists( $order, 'get_order_number' ) ? $order->get_order_number() : '';
				} else {
					$suffix = method_exists( $this->order, 'get_order_number' ) ? $this->order->get_order_number() : '';
				}
			}
		} else {
			$suffix = date( 'Y-m-d' ); // 2020-11-11
		}

		$filename = $name . '-' . $suffix . '.pdf';

		// Filter filename
		$order_ids = isset( $args['order_ids'] ) ? $args['order_ids'] : array( $this->order_id );
		$filename  = apply_filters( 'wpo_wcpdf_filename', $filename, $this->get_type(), $order_ids, $context, $args );

		// sanitize filename (after filters to prevent human errors)!
		return sanitize_file_name( $filename );
	}


	/**
	 * Initialise settings
	 */
	public function init_settings() {
		// Register settings.
		$page = $option_group = $option_name = 'wpo_wcpdf_documents_settings_proforma';

		$settings_fields = array(
			array(
				'type'     => 'section',
				'id'       => 'proforma',
				'title'    => '',
				'callback' => 'section',
			),
			array(
				'type'     => 'setting',
				'id'       => 'enabled',
				'title'    => __( 'Enable', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'checkbox',
				'section'  => 'proforma',
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'enabled',
				)
			),
			array(
				'type'     => 'setting',
				'id'       => 'attach_to_email_ids',
				'title'    => __( 'Attach to:', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'multiple_checkboxes',
				'section'  => 'proforma',
				'args'     => array(
					'option_name'     => $option_name,
					'id'              => 'attach_to_email_ids',
					'fields_callback' => array( $this, 'get_wc_emails' ),
					'description'     => ! is_writable( WPO_WCPDF()->main->get_tmp_path( 'attachments' ) ) ? '<span class="wpo-warning">' . sprintf( __( 'It looks like the temp folder (<code>%s</code>) is not writable, check the permissions for this folder! Without having write access to this folder, the plugin will not be able to email invoices.', 'woocommerce-pdf-invoices-packing-slips' ), WPO_WCPDF()->main->get_tmp_path( 'attachments' ) ).'</span>' : '',
				)
			),
			array(
				'type'     => 'setting',
				'id'       => 'disable_for_statuses',
				'title'    => __( 'Disable for:', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'select',
				'section'  => 'proforma',
				'args'     => array(
					'option_name'      => $option_name,
					'id'               => 'disable_for_statuses',
					'options_callback' => 'wc_get_order_statuses',
					'multiple'         => true,
					'enhanced_select'  => true,
					'placeholder'      => __( 'Select one or more statuses', 'woocommerce-pdf-invoices-packing-slips' ),
				)
			),
			array(
				'type'     => 'setting',
				'id'       => 'display_shipping_address',
				'title'    => __( 'Display shipping address', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'select',
				'section'  => 'proforma',
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'display_shipping_address',
					'options'     => array(
						''               => __( 'No' , 'woocommerce-pdf-invoices-packing-slips' ),
						'when_different' => __( 'Only when different from billing address' , 'woocommerce-pdf-invoices-packing-slips' ),
						'always'         => __( 'Always' , 'woocommerce-pdf-invoices-packing-slips' ),
					),
				)
			),
			array(
				'type'     => 'setting',
				'id'       => 'display_email',
				'title'    => __( 'Display email address', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'checkbox',
				'section'  => 'proforma',
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'display_email',
				)
			),
			array(
				'type'     => 'setting',
				'id'       => 'display_phone',
				'title'    => __( 'Display phone number', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'checkbox',
				'section'  => 'proforma',
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'display_phone',
				)
			),
			array(
				'type'     => 'setting',
				'id'       => 'display_customer_notes',
				'title'    => __( 'Display customer notes', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'checkbox',
				'section'  => 'proforma',
				'args'     => array(
					'option_name'     => $option_name,
					'id'              => 'display_customer_notes',
					'store_unchecked' => true,
					'default'         => 1,
				)
			),
			array(
				'type'     => 'setting',
				'id'       => 'display_date',
				'title'    => __( 'Display proforma invoice date', 'wpo_wcpdf_pro' ),
				'callback' => 'select',
				'section'  => 'proforma',
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'display_date',
					'options'     => array(
						''           => __( 'No' , 'woocommerce-pdf-invoices-packing-slips' ),
						'1'          => __( 'Proforma Invoice Date' , 'wpo_wcpdf_pro' ),
						'order_date' => __( 'Order Date' , 'woocommerce-pdf-invoices-packing-slips' ),
					),
				)
			),
			array(
				'type'     => 'setting',
				'id'       => 'number_sequence',
				'title'    => __( 'Number sequence', 'wpo_wcpdf_pro' ),
				'callback' => 'radio_button',
				'section'  => 'proforma',
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'number_sequence',
					'options'     => array(
						'invoice_number'  => __( 'Main invoice numbering' , 'wpo_wcpdf_pro' ),
						'proforma_number' => __( 'Separate proforma numbering' , 'wpo_wcpdf_pro' ),
					),
					'default'     => 'proforma_number',
				)
			),
			array(
				'type'     => 'setting',
				'id'       => 'display_number',
				'title'    => __( 'Display proforma invoice number', 'wpo_wcpdf_pro' ),
				'callback' => 'select',
				'section'  => 'proforma',
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'display_number',
					'options'     => array(
						''                        => __( 'No', 'wpo_wcpdf_pro' ),
						'proforma_invoice_number' => __( 'Proforma Invoice Number', 'wpo_wcpdf_pro' ),
						'order_number'            => __( 'Order Number', 'wpo_wcpdf_pro' ),
					),
					'description' => sprintf(
						'<strong>%s</strong> %s <a href="https://docs.wpovernight.com/woocommerce-pdf-invoices-packing-slips/invoice-numbers-explained/#why-is-the-pdf-invoice-number-different-from-the-woocommerce-order-number">%s</a>',
						__( 'Warning!', 'wpo_wcpdf_pro' ),
						__( 'Using the Order Number as proforma invoice number is not recommended as this may lead to gaps in the proforma invoice number sequence (even when order numbers are sequential).', 'wpo_wcpdf_pro' ),
						__( 'More information', 'wpo_wcpdf_pro' )
					),
				)
			),
			array(
				'type'     => 'setting',
				'id'       => 'next_proforma_number',
				'title'    => __( 'Next proforma invoice number (without prefix/suffix etc.)', 'wpo_wcpdf_pro' ),
				'callback' => 'next_number_edit',
				'section'  => 'proforma',
				'args'     => array(
					'store_callback' => array( $this, 'get_sequential_number_store' ),
					'size'        => '10',
					'description' => __( 'This is the number that will be used for the next document. By default, numbering starts from 1 and increases for every new document. Note that if you override this and set it lower than the current/highest number, this could create duplicate numbers!', 'woocommerce-pdf-invoices-packing-slips' ),
				)
			),
			array(
				'type'     => 'setting',
				'id'       => 'number_format',
				'title'    => __( 'Number format', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'multiple_text_input',
				'section'  => 'proforma',
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'number_format',
					'fields'      => array(
						'prefix'  => array(
							'label'       => __( 'Prefix' , 'woocommerce-pdf-invoices-packing-slips' ),
							'size'        => 20,
							'description' => __( 'If set, this value will be used as number prefix.' , 'woocommerce-pdf-invoices-packing-slips' ) . ' ' . sprintf(
								/* translators: 1. document type, 2-3 placeholders */
								__( 'You can use the %1$s year and/or month with the %2$s or %3$s placeholders respectively.', 'woocommerce-pdf-invoices-packing-slips' ),
								strtolower( __( 'Proforma Invoice', 'wpo_wcpdf_pro' ) ), '<strong>[proforma_year]</strong>', '<strong>[proforma_month]</strong>'
							) . ' ' . __( 'Check the Docs article below to see all the available placeholders for prefix/suffix.', 'woocommerce-pdf-invoices-packing-slips' ),
						),
						'suffix'  => array(
							'label'       => __( 'Suffix' , 'woocommerce-pdf-invoices-packing-slips' ),
							'size'        => 20,
							'description' => __( 'If set, this value will be used as number suffix.' , 'woocommerce-pdf-invoices-packing-slips' ) . ' ' . sprintf(
								/* translators: 1. document type, 2-3 placeholders */
								__( 'You can use the %1$s year and/or month with the %2$s or %3$s placeholders respectively.', 'woocommerce-pdf-invoices-packing-slips' ),
								strtolower( __( 'Proforma Invoice', 'wpo_wcpdf_pro' ) ), '<strong>[proforma_year]</strong>', '<strong>[proforma_month]</strong>'
							) . ' ' . __( 'Check the Docs article below to see all the available placeholders for prefix/suffix.', 'woocommerce-pdf-invoices-packing-slips' ),
						),
						'padding' => array(
							'label'       => __( 'Padding' , 'woocommerce-pdf-invoices-packing-slips' ),
							'size'        => 20,
							'type'        => 'number',
							/* translators: document type */
							'description' => sprintf( __( 'Enter the number of digits you want to use as padding. For instance, enter <code>6</code> to display the %s number <code>123</code> as <code>000123</code>, filling it with zeros until the number set as padding is reached.' , 'woocommerce-pdf-invoices-packing-slips' ), strtolower( __( 'Proforma Invoice', 'wpo_wcpdf_pro' ) ) ),
						),
					),
					/* translators: document type */
					'description' => __( 'For more information about setting up the number format and see the available placeholders for the prefix and suffix, check this article:', 'woocommerce-pdf-invoices-packing-slips' ) . sprintf( ' <a href="https://docs.wpovernight.com/woocommerce-pdf-invoices-packing-slips/number-format-explained/" target="_blank">%s</a>', __( 'Number format explained', 'woocommerce-pdf-invoices-packing-slips') ) . '.<br><br>'. sprintf( __( '<strong>Note</strong>: Changes made to the number format will only be reflected on new orders. Also, if you have already created a custom %s number format with a filter, the above settings will be ignored.', 'woocommerce-pdf-invoices-packing-slips' ), strtolower( __( 'Proforma Invoice', 'wpo_wcpdf_pro' ) ) ),
				)
			),
			array(
				'type'     => 'setting',
				'id'       => 'reset_number_yearly',
				'title'    => __( 'Reset proforma invoice number yearly', 'wpo_wcpdf_pro' ),
				'callback' => 'checkbox',
				'section'  => 'proforma',
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'reset_number_yearly',
				)
			),
			array(
				'type'     => 'setting',
				'id'       => 'my_account_buttons',
				'title'    => __( 'Allow My Account download', 'wpo_wcpdf_pro' ),
				'callback' => 'select',
				'section'  => 'proforma',
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'my_account_buttons',
					'options'     => array(
						'no_invoice' => __( 'Only when the final invoice is not available' , 'wpo_wcpdf_pro' ),
						'available'  => __( 'Only when a proforma invoice is already created/emailed' , 'wpo_wcpdf_pro' ),
						'custom'     => __( 'Only for specific order statuses (define below)' , 'woocommerce-pdf-invoices-packing-slips' ),
						'always'     => __( 'Always' , 'woocommerce-pdf-invoices-packing-slips' ),
						'never'      => __( 'Never' , 'woocommerce-pdf-invoices-packing-slips' ),
					),
					'default'     => 'no_invoice',
					'custom'      => array(
						'type' => 'multiple_checkboxes',
						'args' => array(
							'option_name'     => $option_name,
							'id'              => 'my_account_restrict',
							'fields_callback' => array( $this, 'get_wc_order_status_list' ),
						),
					),
				)
			),
			array(
				'type'     => 'setting',
				'id'       => 'disable_free',
				'title'    => __( 'Disable for free orders', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'checkbox',
				'section'  => 'proforma',
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'disable_free',
					'description' => sprintf(__( "Disable document when the order total is %s", 'woocommerce-pdf-invoices-packing-slips' ), function_exists('wc_price') ? wc_price( 0 ) : 0 ),
				)
			),
			array(
				'type'     => 'setting',
				'id'       => 'use_latest_settings',
				'title'    => __( 'Always use most current settings', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'checkbox',
				'section'  => 'proforma',
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'use_latest_settings',
					'description' => __( "When enabled, the document will always reflect the most current settings (such as footer text, document name, etc.) rather than using historical settings.", 'woocommerce-pdf-invoices-packing-slips' )
					                . "<br>"
					                . __( "<strong>Caution:</strong> enabling this will also mean that if you change your company name or address in the future, previously generated documents will also be affected.", 'woocommerce-pdf-invoices-packing-slips' ),
				)
			),
		);

		// allow plugins to alter settings fields
		$settings_fields = apply_filters( 'wpo_wcpdf_settings_fields_documents_proforma', $settings_fields, $page, $option_group, $option_name );
		WPO_WCPDF()->settings->add_settings_fields( $settings_fields, $page, $option_group, $option_name );
		return;

	}

}

endif; // class_exists

return new Proforma();