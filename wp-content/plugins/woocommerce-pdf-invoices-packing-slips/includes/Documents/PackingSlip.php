<?php
namespace WPO\IPS\Documents;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( '\\WPO\\IPS\\Documents\\PackingSlip' ) ) :

/**
 * Packing Slip Document
 */

class PackingSlip extends OrderDocumentMethods {

	/**
	 * Init/load the order object.
	 *
	 * @param  int|object|WC_Order $order Order to init.
	 */
	public function __construct( $order = 0 ) {
		// set properties
		$this->type  = 'packing-slip';
		$this->title = __( 'Packing Slip', 'woocommerce-pdf-invoices-packing-slips' );
		$this->icon  = WPO_WCPDF()->plugin_url() . "/assets/images/packing-slip.svg";

		// call parent constructor
		parent::__construct( $order );

		// output formats (placed after parent construct to override the abstract default)
		$this->output_formats = apply_filters( 'wpo_wcpdf_document_output_formats', array( 'pdf' ), $this );
	}

	/**
	 * Get the document title
	 *
	 * @return string
	 */
	public function get_title() {
		// override/not using $this->title to allow for language switching!
		$title = __( 'Packing Slip', 'woocommerce-pdf-invoices-packing-slips' );
		$title = apply_filters_deprecated( "wpo_wcpdf_{$this->slug}_title", array( $title, $this ), '3.8.7', 'wpo_wcpdf_document_title' ); // deprecated
		return apply_filters( 'wpo_wcpdf_document_title', $title, $this );
	}

	/**
	 * Get the document number title
	 *
	 * @return string
	 */
	public function get_number_title() {
		// override to allow for language switching!
		$title = __( 'Packing Slip Number:', 'woocommerce-pdf-invoices-packing-slips' );
		$title = apply_filters_deprecated( "wpo_wcpdf_{$this->slug}_number_title", array( $title, $this ), '3.8.7', 'wpo_wcpdf_document_number_title' ); // deprecated
		return apply_filters( 'wpo_wcpdf_document_number_title', $title, $this );
	}

	/**
	 * Get the document date title
	 *
	 * @return string
	 */
	public function get_date_title() {
		// override to allow for language switching!
		$title = __( 'Packing Slip Date:', 'woocommerce-pdf-invoices-packing-slips' );
		$title = apply_filters_deprecated( "wpo_wcpdf_{$this->slug}_date_title", array( $title, $this ), '3.8.7', 'wpo_wcpdf_document_date_title' ); // deprecated
		return apply_filters( 'wpo_wcpdf_document_date_title', $title, $this );
	}

	public function get_filename( $context = 'download', $args = array() ) {
		$order_count = isset($args['order_ids']) ? count($args['order_ids']) : 1;

		$name = _n( 'packing-slip', 'packing-slips', $order_count, 'woocommerce-pdf-invoices-packing-slips' );

		if ( $order_count == 1 ) {
			if ( isset( $this->settings['display_number'] ) ) {
				$suffix = (string) $this->get_number();
			} else {
				if ( empty( $this->order ) && isset( $args['order_ids'] ) ) {
					$order = wc_get_order( $args['order_ids'][0] );
					$suffix = is_callable( array( $order, 'get_order_number' ) ) ? $order->get_order_number() : '';
				} else {
					$suffix = is_callable( array( $this->order, 'get_order_number' ) ) ? $this->order->get_order_number() : '';
				}
			}
		} else {
			$suffix = date_i18n( 'Y-m-d' ); // 2024-12-31
		}

		// get filename
		$output_format = ! empty( $args['output'] ) ? esc_attr( $args['output'] ) : 'pdf';
		$filename      = $name . '-' . $suffix . wcpdf_get_document_output_format_extension( $output_format );

		// Filter filename
		$order_ids = isset( $args['order_ids'] ) ? $args['order_ids'] : array( $this->order_id );
		$filename  = apply_filters( 'wpo_wcpdf_filename', $filename, $this->get_type(), $order_ids, $context, $args );

		// sanitize filename (after filters to prevent human errors)!
		return sanitize_file_name( $filename );
	}

	public function init_settings() {
		// Register settings.
		$page = $option_group = $option_name = 'wpo_wcpdf_documents_settings_packing-slip';

		$settings_fields = array(
			array(
				'type'			=> 'section',
				'id'			=> 'packing_slip',
				'title'			=> '',
				'callback'		=> 'section',
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'enabled',
				'title'			=> __( 'Enable', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback'		=> 'checkbox',
				'section'		=> 'packing_slip',
				'args'			=> array(
					'option_name'		=> $option_name,
					'id'				=> 'enabled',
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'display_billing_address',
				'title'			=> __( 'Display billing address', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback'		=> 'select',
				'section'		=> 'packing_slip',
				'args'			=> array(
					'option_name'	=> $option_name,
					'id'			=> 'display_billing_address',
					'options' 		=> array(
						''				=> __( 'No' , 'woocommerce-pdf-invoices-packing-slips' ),
						'when_different'=> __( 'Only when different from shipping address' , 'woocommerce-pdf-invoices-packing-slips' ),
						'always'		=> __( 'Always' , 'woocommerce-pdf-invoices-packing-slips' ),
					),
					// 'description'	=> __( 'Display billing address (in addition to the default shipping address) if different from shipping address', 'woocommerce-pdf-invoices-packing-slips' ),
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'display_email',
				'title'			=> __( 'Display email address', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback'		=> 'checkbox',
				'section'		=> 'packing_slip',
				'args'			=> array(
					'option_name'	=> $option_name,
					'id'			=> 'display_email',
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'display_phone',
				'title'			=> __( 'Display phone number', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback'		=> 'checkbox',
				'section'		=> 'packing_slip',
				'args'			=> array(
					'option_name'	=> $option_name,
					'id'			=> 'display_phone',
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'display_customer_notes',
				'title'			=> __( 'Display customer notes', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback'		=> 'checkbox',
				'section'		=> 'packing_slip',
				'args'			=> array(
					'option_name'		=> $option_name,
					'id'				=> 'display_customer_notes',
					'store_unchecked'	=> true,
					'default'			=> 1,
				)
			),
		);

		// Configuración para adjuntar packing slips a emails (funcionalidad habilitada para Palafito).
		$email_settings = array(
			array(
				'type'			=> 'setting',
				'id'			=> 'attach_to_email_ids',
				'title'			=> __( 'Attach to:', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback'		=> 'multiple_checkboxes',
				'section'		=> 'packing_slip',
				'args'			=> array(
					'option_name'	  => $option_name,
					'id'			  => 'attach_to_email_ids',
					'fields_callback' => array( $this, 'get_wc_emails' ),
					/* translators: directory path */
					'description'	  => ! WPO_WCPDF()->file_system->is_writable( WPO_WCPDF()->main->get_tmp_path( 'attachments' ) ) ? '<span class="wpo-warning">' . sprintf( __( 'It looks like the temp folder (<code>%s</code>) is not writable, check the permissions for this folder! Without having write access to this folder, the plugin will not be able to email packing slips.', 'woocommerce-pdf-invoices-packing-slips' ), WPO_WCPDF()->main->get_tmp_path( 'attachments' ) ).'</span>':'',
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'disable_for_statuses',
				'title'			=> __( 'Disable for:', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback'		=> 'select',
				'section'		=> 'packing_slip',
				'args'			=> array(
					'option_name'      => $option_name,
					'id'               => 'disable_for_statuses',
					'options_callback' => 'wc_get_order_statuses',
					'multiple'         => true,
					'enhanced_select'  => true,
					'placeholder'      => __( 'Select one or more statuses', 'woocommerce-pdf-invoices-packing-slips' ),
				)
			),
		);
		$settings_fields = WPO_WCPDF()->settings->move_setting_after_id( $settings_fields, $email_settings, 'enabled' );

		// Configuración adicional de packing slip (funcionalidades Pro habilitadas para Palafito).
		$additional_settings = array(
			array(
				'type'			=> 'setting',
				'id'			=> 'display_date',
				'title'			=> __( 'Display packing slip date', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback'		=> 'checkbox',
				'section'		=> 'packing_slip',
				'args'			=> array(
					'option_name'	=> $option_name,
					'id'			=> 'display_date',
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'display_number',
				'title'			=> __( 'Display packing slip number', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback'		=> 'select',
				'section'		=> 'packing_slip',
				'args'			=> array(
					'option_name'	=> $option_name,
					'id'			=> 'display_number',
					'options' 		=> array(
						''					=> __( 'No' , 'woocommerce-pdf-invoices-packing-slips' ),
						'packing_slip_number'=> __( 'Packing Slip Number' , 'woocommerce-pdf-invoices-packing-slips' ),
						'order_number'		=> __( 'Order Number' , 'woocommerce-pdf-invoices-packing-slips' ),
					),
					'description'	=> sprintf(
						'<strong>%s</strong> %s <a href="https://docs.wpovernight.com/woocommerce-pdf-invoices-packing-slips/invoice-numbers-explained/#why-is-the-pdf-invoice-number-different-from-the-woocommerce-order-number">%s</a>',
						__( 'Warning!', 'woocommerce-pdf-invoices-packing-slips' ),
						__( 'Using the Order Number as packing slip number is not recommended as this may lead to gaps in the packing slip number sequence (even when order numbers are sequential).', 'woocommerce-pdf-invoices-packing-slips' ),
						__( 'More information', 'woocommerce-pdf-invoices-packing-slips' )
					),
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'next_packing_slip_number',
				'title'			=> __( 'Next packing slip number (without prefix/suffix etc.)', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback'		=> 'next_number_edit',
				'section'		=> 'packing_slip',
				'args'			=> array(
					'store_callback' => array( $this, 'get_sequential_number_store' ),
					'size'           => '10',
					'description'    => __( 'This is the number that will be used for the next document. By default, numbering starts from 1 and increases for every new document. Note that if you override this and set it lower than the current/highest number, this could create duplicate numbers!', 'woocommerce-pdf-invoices-packing-slips' ),
				)
			),
			array(
				'type'     => 'setting',
				'id'       => 'number_format',
				'title'    => __( 'Number format', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'multiple_text_input',
				'section'  => 'packing_slip',
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
								strtolower( __( 'Packing Slip', 'woocommerce-pdf-invoices-packing-slips' ) ), '<strong>[packing_slip_year]</strong>', '<strong>[packing_slip_month]</strong>'
							) . ' ' . __( 'Check the Docs article below to see all the available placeholders for prefix/suffix.', 'woocommerce-pdf-invoices-packing-slips' ),
						),
						'suffix'  => array(
							'label'       => __( 'Suffix' , 'woocommerce-pdf-invoices-packing-slips' ),
							'size'        => 20,
							'description' => __( 'If set, this value will be used as number suffix.' , 'woocommerce-pdf-invoices-packing-slips' ) . ' ' . sprintf(
								/* translators: 1. document type, 2-3 placeholders */
								__( 'You can use the %1$s year and/or month with the %2$s or %3$s placeholders respectively.', 'woocommerce-pdf-invoices-packing-slips' ),
								strtolower( __( 'Packing Slip', 'woocommerce-pdf-invoices-packing-slips' ) ), '<strong>[packing_slip_year]</strong>', '<strong>[packing_slip_month]</strong>'
							) . ' ' . __( 'Check the Docs article below to see all the available placeholders for prefix/suffix.', 'woocommerce-pdf-invoices-packing-slips' ),
						),
						'padding' => array(
							'label'       => __( 'Padding' , 'woocommerce-pdf-invoices-packing-slips' ),
							'size'        => 20,
							'type'        => 'number',
							/* translators: document type */
							'description' => sprintf( __( 'Enter the number of digits you want to use as padding. For instance, enter <code>6</code> to display the %s number <code>123</code> as <code>000123</code>, filling it with zeros until the number set as padding is reached.' , 'woocommerce-pdf-invoices-packing-slips' ), strtolower( __( 'Packing Slip', 'woocommerce-pdf-invoices-packing-slips' ) ) ),
						),
					),
					/* translators: document type */
					'description' => __( 'For more information about setting up the number format and see the available placeholders for the prefix and suffix, check this article:', 'woocommerce-pdf-invoices-packing-slips' ) . sprintf( ' <a href="https://docs.wpovernight.com/woocommerce-pdf-invoices-packing-slips/number-format-explained/" target="_blank">%s</a>', __( 'Number format explained', 'woocommerce-pdf-invoices-packing-slips') ) . '.<br><br>'. sprintf( __( '<strong>Note</strong>: Changes made to the number format will only be reflected on new orders. Also, if you have already created a custom %s number format with a filter, the above settings will be ignored.', 'woocommerce-pdf-invoices-packing-slips' ), strtolower( __( 'Packing Slip', 'woocommerce-pdf-invoices-packing-slips' ) ) ),
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'reset_number_yearly',
				'title'			=> __( 'Reset packing slip number yearly', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback'		=> 'checkbox',
				'section'		=> 'packing_slip',
				'args'			=> array(
					'option_name'	=> $option_name,
					'id'			=> 'reset_number_yearly',
				)
			),
		);
		$settings_fields = WPO_WCPDF()->settings->move_setting_after_id( $settings_fields, $additional_settings, 'display_customer_notes' );

		// Legacy filter to allow plugins to alter settings fields.
		$settings_fields = apply_filters( 'wpo_wcpdf_settings_fields_documents_packing_slip', $settings_fields, $page, $option_group, $option_name );

		// Allow plugins to alter settings fields.
		$settings_fields = apply_filters( "wpo_wcpdf_settings_fields_documents_{$this->type}_pdf", $settings_fields, $page, $option_group, $option_name, $this );

		if ( ! empty( $settings_fields ) ) {
			WPO_WCPDF()->settings->add_settings_fields( $settings_fields, $page, $option_group, $option_name );
		}
	}

	/**
	 * Get the settings categories.
	 *
	 * @param string $output_format
	 *
	 * @return array
	 */
	public function get_settings_categories( string $output_format ): array {
		if ( ! in_array( $output_format, $this->output_formats, true ) ) {
			return array();
		}

		$settings_categories = array(
			'pdf' => array(
				'general'          => array(
					'title'   => __( 'General', 'woocommerce-pdf-invoices-packing-slips' ),
					'members' => array(
						'enabled',
						'attach_to_email_ids',
					),
				),
				'document_details' => array(
					'title'   => __( 'Document details', 'woocommerce-pdf-invoices-packing-slips' ),
					'members' => array(
						'display_email',
						'display_phone',
						'display_customer_notes',
						'display_billing_address',
					),
				),
			),
		);

		return apply_filters( 'wpo_wcpdf_document_settings_categories', $settings_categories[ $output_format ], $output_format, $this );
	}

}

endif; // class_exists
