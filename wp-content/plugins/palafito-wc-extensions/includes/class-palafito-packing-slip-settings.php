<?php
/**
 * Reorganización de opciones del Packing Slip para Palafito.
 *
 * @package Palafito_WC_Extensions
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reorganiza las opciones del Packing Slip para que coincidan exactamente con la estructura de la factura.
 */
class Palafito_Packing_Slip_Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Hook para reemplazar completamente las opciones del packing slip.
		add_filter( 'wpo_wcpdf_settings_fields_documents_packing_slip', array( $this, 'replace_packing_slip_settings' ), 999, 1 );

		// Hook para agregar "Pedido entregado" a la lista de emails.
		add_filter( 'wpo_wcpdf_wc_emails', array( $this, 'add_entregado_email_to_list' ) );

		// Hook para agregar JavaScript al admin.
		add_action( 'admin_footer', array( $this, 'add_packing_slip_js' ) );
	}

	/**
	 * Reemplaza completamente las opciones del packing slip para que coincidan exactamente con la factura.
	 *
	 * @param array $settings_fields Campos de configuración actuales.
	 * @return array
	 */
	public function replace_packing_slip_settings( $settings_fields ) {
		// Solo reorganizar si el plugin PDF está disponible.
		if ( ! class_exists( 'WPO_WCPDF' ) ) {
			return $settings_fields;
		}

		$option_name = 'wpo_wcpdf_documents_settings_packing-slip';

		// Reemplazar completamente con el orden exacto de la factura.
		$new_settings_fields = array(
			array(
				'type'     => 'section',
				'id'       => 'packing_slip',
				'title'    => '',
				'callback' => 'section',
			),
			array(
				'type'     => 'setting',
				'id'       => 'enabled',
				'title'    => __( 'Enable', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'checkbox',
				'section'  => 'packing_slip',
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'enabled',
				),
			),
			array(
				'type'     => 'setting',
				'id'       => 'attach_to_email_ids',
				'title'    => __( 'Attach to:', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'multiple_checkboxes',
				'section'  => 'packing_slip',
				'args'     => array(
					'option_name'     => $option_name,
					'id'              => 'attach_to_email_ids',
					'fields_callback' => array( $this, 'get_wc_emails' ),
					/* translators: directory path */
					'description'     => ! WPO_WCPDF()->file_system->is_writable( WPO_WCPDF()->main->get_tmp_path( 'attachments' ) ) ? '<span class="wpo-warning">' . sprintf( __( 'It looks like the temp folder (<code>%s</code>) is not writable, check the permissions for this folder! Without having write access to this folder, the plugin will not be able to email packing slips.', 'woocommerce-pdf-invoices-packing-slips' ), WPO_WCPDF()->main->get_tmp_path( 'attachments' ) ) . '</span>' : '',
				),
			),
			array(
				'type'     => 'setting',
				'id'       => 'disable_for_statuses',
				'title'    => __( 'Disable for:', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'select',
				'section'  => 'packing_slip',
				'args'     => array(
					'option_name'      => $option_name,
					'id'               => 'disable_for_statuses',
					'options_callback' => 'wc_get_order_statuses',
					'multiple'         => true,
					'enhanced_select'  => true,
					'placeholder'      => __( 'Select one or more statuses', 'woocommerce-pdf-invoices-packing-slips' ),
				),
			),
			array(
				'type'     => 'setting',
				'id'       => 'display_billing_address',
				'title'    => __( 'Display billing address', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'select',
				'section'  => 'packing_slip',
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'display_billing_address',
					'options'     => array(
						''               => __( 'No', 'woocommerce-pdf-invoices-packing-slips' ),
						'when_different' => __( 'Only when different from shipping address', 'woocommerce-pdf-invoices-packing-slips' ),
						'always'         => __( 'Always', 'woocommerce-pdf-invoices-packing-slips' ),
					),
				),
			),
			array(
				'type'     => 'setting',
				'id'       => 'display_email',
				'title'    => __( 'Display email address', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'checkbox',
				'section'  => 'packing_slip',
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'display_email',
				),
			),
			array(
				'type'     => 'setting',
				'id'       => 'display_phone',
				'title'    => __( 'Display phone number', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'checkbox',
				'section'  => 'packing_slip',
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'display_phone',
				),
			),
			array(
				'type'     => 'setting',
				'id'       => 'display_customer_notes',
				'title'    => __( 'Display customer notes', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'checkbox',
				'section'  => 'packing_slip',
				'args'     => array(
					'option_name'     => $option_name,
					'id'              => 'display_customer_notes',
					'store_unchecked' => true,
					'default'         => 1,
				),
			),
			array(
				'type'     => 'setting',
				'id'       => 'display_date',
				'title'    => __( 'Display packing slip date', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'select',
				'section'  => 'packing_slip',
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'display_date',
					'options'     => array(
						''              => __( 'No', 'woocommerce-pdf-invoices-packing-slips' ),
						'document_date' => __( 'Packing Slip Date', 'woocommerce-pdf-invoices-packing-slips' ),
						'order_date'    => __( 'Order Date', 'woocommerce-pdf-invoices-packing-slips' ),
					),
				),
			),
			array(
				'type'     => 'setting',
				'id'       => 'display_number',
				'title'    => __( 'Display packing slip number', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'select',
				'section'  => 'packing_slip',
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'display_number',
					'options'     => array(
						''                    => __( 'No', 'woocommerce-pdf-invoices-packing-slips' ),
						'packing_slip_number' => __( 'Packing Slip Number', 'woocommerce-pdf-invoices-packing-slips' ),
						'order_number'        => __( 'Order Number', 'woocommerce-pdf-invoices-packing-slips' ),
					),
					'description' => sprintf(
						'<strong>%s</strong> %s <a href="https://docs.wpovernight.com/woocommerce-pdf-invoices-packing-slips/invoice-numbers-explained/#why-is-the-pdf-invoice-number-different-from-the-woocommerce-order-number">%s</a>',
						__( 'Warning!', 'woocommerce-pdf-invoices-packing-slips' ),
						__( 'Using the Order Number as packing slip number is not recommended as this may lead to gaps in the packing slip number sequence (even when order numbers are sequential).', 'woocommerce-pdf-invoices-packing-slips' ),
						__( 'More information', 'woocommerce-pdf-invoices-packing-slips' )
					),
				),
			),
			array(
				'type'     => 'setting',
				'id'       => 'use_order_number',
				'title'    => __( 'Use order number', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'checkbox',
				'section'  => 'packing_slip',
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'use_order_number',
					'default'     => 1,
					'description' => __( 'When checked, the packing slip number will use the order number instead of a sequential number. The number format (prefix/suffix) will still be applied.', 'woocommerce-pdf-invoices-packing-slips' ),
				),
			),
			array(
				'type'     => 'setting',
				'id'       => 'next_packing_slip_number',
				'title'    => __( 'Next packing slip number (without prefix/suffix etc.)', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'next_number_edit',
				'section'  => 'packing_slip',
				'args'     => array(
					'store_callback' => array( $this, 'get_sequential_number_store' ),
					'size'           => '10',
					'description'    => __( 'This is the number that will be used for the next document. By default, numbering starts from 1 and increases for every new document. Note that if you override this and set it lower than the current/highest number, this could create duplicate numbers!', 'woocommerce-pdf-invoices-packing-slips' ),
					'disabled_when'  => array(
						'field' => 'use_order_number',
						'value' => 1,
					),
				),
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
							'label'       => __( 'Prefix', 'woocommerce-pdf-invoices-packing-slips' ),
							'size'        => 20,
							'description' => __( 'If set, this value will be used as number prefix.', 'woocommerce-pdf-invoices-packing-slips' ) . ' ' . sprintf(
								/* translators: 1. document type, 2-3 placeholders */
								__( 'You can use the %1$s year and/or month with the %2$s or %3$s placeholders respectively.', 'woocommerce-pdf-invoices-packing-slips' ),
								strtolower( __( 'Packing Slip', 'woocommerce-pdf-invoices-packing-slips' ) ),
								'<strong>[packing_slip_year]</strong>',
								'<strong>[packing_slip_month]</strong>'
							) . ' ' . __( 'Check the Docs article below to see all the available placeholders for prefix/suffix.', 'woocommerce-pdf-invoices-packing-slips' ),
						),
						'suffix'  => array(
							'label'       => __( 'Suffix', 'woocommerce-pdf-invoices-packing-slips' ),
							'size'        => 20,
							'description' => __( 'If set, this value will be used as number suffix.', 'woocommerce-pdf-invoices-packing-slips' ) . ' ' . sprintf(
								/* translators: 1. document type, 2-3 placeholders */
								__( 'You can use the %1$s year and/or month with the %2$s or %3$s placeholders respectively.', 'woocommerce-pdf-invoices-packing-slips' ),
								strtolower( __( 'Packing Slip', 'woocommerce-pdf-invoices-packing-slips' ) ),
								'<strong>[packing_slip_year]</strong>',
								'<strong>[packing_slip_month]</strong>'
							) . ' ' . __( 'Check the Docs article below to see all the available placeholders for prefix/suffix.', 'woocommerce-pdf-invoices-packing-slips' ),
						),
						'padding' => array(
							'label'       => __( 'Padding', 'woocommerce-pdf-invoices-packing-slips' ),
							'size'        => 20,
							'type'        => 'number',
							/* translators: document type */
							'description' => sprintf( __( 'Enter the number of digits you want to use as padding. For instance, enter <code>6</code> to display the %s number <code>123</code> as <code>000123</code>, filling it with zeros until the number set as padding is reached.', 'woocommerce-pdf-invoices-packing-slips' ), strtolower( __( 'Packing Slip', 'woocommerce-pdf-invoices-packing-slips' ) ) ),
						),
					),
					/* translators: document type */
					'description' => __( 'For more information about setting up the number format and see the available placeholders for the prefix and suffix, check this article:', 'woocommerce-pdf-invoices-packing-slips' ) . sprintf( ' <a href="https://docs.wpovernight.com/woocommerce-pdf-invoices-packing-slips/number-format-explained/" target="_blank">%s</a>', __( 'Number format explained', 'woocommerce-pdf-invoices-packing-slips' ) ) . '.<br><br>' . sprintf( __( '<strong>Note</strong>: Changes made to the number format will only be reflected on new orders. Also, if you have already created a custom %s number format with a filter, the above settings will be ignored.', 'woocommerce-pdf-invoices-packing-slips' ), strtolower( __( 'Packing Slip', 'woocommerce-pdf-invoices-packing-slips' ) ) ),
				),
			),
			array(
				'type'     => 'setting',
				'id'       => 'reset_number_yearly',
				'title'    => __( 'Reset packing slip number yearly', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'checkbox',
				'section'  => 'packing_slip',
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'reset_number_yearly',
				),
			),
			array(
				'type'     => 'setting',
				'id'       => 'my_account_buttons',
				'title'    => __( 'Allow My Account packing slip download', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'select',
				'section'  => 'packing_slip',
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'my_account_buttons',
					'options'     => array(
						'available' => __( 'Only when a packing slip is already created/emailed', 'woocommerce-pdf-invoices-packing-slips' ),
						'custom'    => __( 'Only for specific order statuses (define below)', 'woocommerce-pdf-invoices-packing-slips' ),
						'always'    => __( 'Always', 'woocommerce-pdf-invoices-packing-slips' ),
						'never'     => __( 'Never', 'woocommerce-pdf-invoices-packing-slips' ),
					),
					'custom'      => array(
						'type' => 'multiple_checkboxes',
						'args' => array(
							'option_name'     => $option_name,
							'id'              => 'my_account_restrict',
							'fields_callback' => array( $this, 'get_wc_order_status_list' ),
						),
					),
				),
			),
			array(
				'type'     => 'setting',
				'id'       => 'packing_slip_number_column',
				'title'    => __( 'Enable packing slip number column in the orders list', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'checkbox',
				'section'  => 'packing_slip',
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'packing_slip_number_column',
				),
			),
			array(
				'type'     => 'setting',
				'id'       => 'packing_slip_date_column',
				'title'    => __( 'Enable packing slip date column in the orders list', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'checkbox',
				'section'  => 'packing_slip',
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'packing_slip_date_column',
				),
			),
			array(
				'type'     => 'setting',
				'id'       => 'mark_printed',
				'title'    => __( 'Mark as printed', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'select',
				'section'  => 'packing_slip',
				'args'     => array(
					'option_name'     => $option_name,
					'id'              => 'mark_printed',
					'options'         => array_merge(
						array(
							'manually' => __( 'Manually', 'woocommerce-pdf-invoices-packing-slips' ),
						),
						apply_filters(
							'wpo_wcpdf_document_triggers',
							array(
								'single'           => __( 'On single order action', 'woocommerce-pdf-invoices-packing-slips' ),
								'bulk'             => __( 'On bulk order action', 'woocommerce-pdf-invoices-packing-slips' ),
								'my_account'       => __( 'On my account', 'woocommerce-pdf-invoices-packing-slips' ),
								'email_attachment' => __( 'On email attachment', 'woocommerce-pdf-invoices-packing-slips' ),
								'document_data'    => __( 'On order document data (number and/or date set manually)', 'woocommerce-pdf-invoices-packing-slips' ),
							)
						)
					),
					'multiple'        => true,
					'enhanced_select' => true,
					'description'     => __( 'Allows you to mark the document as printed, manually (in the order page) or automatically (based on the document creation context you have selected).', 'woocommerce-pdf-invoices-packing-slips' ),
				),
			),
			array(
				'type'     => 'setting',
				'id'       => 'unmark_printed',
				'title'    => __( 'Unmark as printed', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'checkbox',
				'section'  => 'packing_slip',
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'unmark_printed',
					'description' => __( 'Adds a link in the order page to allow to remove the printed mark.', 'woocommerce-pdf-invoices-packing-slips' ),
				),
			),
		);

		return $new_settings_fields;
	}

	/**
	 * Get WooCommerce emails for the attach to dropdown.
	 *
	 * @return array
	 */
	public function get_wc_emails() {
		$emails    = array();
		$wc_emails = WC()->mailer()->get_emails();
		if ( ! empty( $wc_emails ) ) {
			foreach ( $wc_emails as $email ) {
				$emails[ $email->id ] = $email->get_title();
			}
		}
		return $emails;
	}

	/**
	 * Get WooCommerce order status list.
	 *
	 * @return array
	 */
	public function get_wc_order_status_list() {
		return wc_get_order_statuses();
	}

	/**
	 * Get sequential number store callback.
	 *
	 * @return array
	 */
	public function get_sequential_number_store() {
		return array( 'WPO_WCPDF', 'get_sequential_number_store' );
	}

	/**
	 * Add "Pedido entregado" to the list of emails.
	 *
	 * @param array $emails List of emails.
	 * @return array
	 */
	public function add_entregado_email_to_list( $emails ) {
		$emails['entregado'] = __( 'Pedido entregado', 'woocommerce-pdf-invoices-packing-slips' );
		return $emails;
	}

	/**
	 * Add JavaScript to the admin footer.
	 */
	public function add_packing_slip_js() {
		// Only add JavaScript on the PDF settings page.
		if ( ! isset( $_GET['page'] ) || 'wpo_wcpdf_options_page' !== $_GET['page'] ) {
			return;
		}

		// Verify nonce for security.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wpo_wcpdf_settings' ) ) {
			return;
		}

		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Function to toggle next packing slip number field.
			function toggleNextPackingSlipNumber() {
				var useOrderNumber = $('#wpo_wcpdf_documents_settings_packing-slip_use_order_number');
				var nextNumberField = $('#wpo_wcpdf_documents_settings_packing-slip_next_packing_slip_number');
				
				if (useOrderNumber.is(':checked')) {
					nextNumberField.prop('disabled', true);
					nextNumberField.closest('.form-field').addClass('disabled');
				} else {
					nextNumberField.prop('disabled', false);
					nextNumberField.closest('.form-field').removeClass('disabled');
				}
			}

			// Initial state.
			toggleNextPackingSlipNumber();

			// Listen for changes on the checkbox.
			$('#wpo_wcpdf_documents_settings_packing-slip_use_order_number').on('change', function() {
				toggleNextPackingSlipNumber();
			});
		});
		</script>
		<style>
		.form-field.disabled {
			opacity: 0.6;
		}
		</style>
		<?php
	}
}

// Inicializar la reorganización de opciones del packing slip.
new Palafito_Packing_Slip_Settings();
