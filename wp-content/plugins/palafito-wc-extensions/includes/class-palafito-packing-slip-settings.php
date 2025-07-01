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
 * Reorganiza las opciones del Packing Slip para que coincidan con la estructura de la factura.
 */
class Palafito_Packing_Slip_Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Hook para reorganizar las opciones del packing slip.
		add_filter( 'wpo_wcpdf_settings_fields_documents_packing_slip', array( $this, 'reorganize_packing_slip_settings' ), 20, 1 );

		// Hook para agregar las opciones faltantes al packing slip.
		add_filter( 'wpo_wcpdf_settings_fields_documents_packing_slip', array( $this, 'add_missing_packing_slip_options' ), 30, 1 );
	}

	/**
	 * Reorganiza las opciones del packing slip para que coincidan con la estructura de la factura.
	 *
	 * @param array $settings_fields Campos de configuración actuales.
	 * @return array
	 */
	public function reorganize_packing_slip_settings( $settings_fields ) {
		// Solo reorganizar si el plugin PDF está disponible.
		if ( ! class_exists( 'WPO_WCPDF' ) ) {
			return $settings_fields;
		}

		// Buscar y extraer las opciones que necesitamos reorganizar.
		$reorganized_fields = array();
		$remaining_fields   = array();

		// Primera pasada: extraer las opciones en el orden correcto (igual que factura).
		foreach ( $settings_fields as $field ) {
			if ( ! isset( $field['id'] ) ) {
				$remaining_fields[] = $field;
				continue;
			}

			// Orden exacto como en factura:
			// General: enabled, attach_to_email_ids, disable_for_statuses, my_account_buttons
			// Document details: display_email, display_phone, display_customer_notes, display_billing_address, display_number, next_packing_slip_number, number_format, display_date
			// Admin: packing_slip_number_column, packing_slip_date_column
			// Advanced: next_packing_slip_number, reset_number_yearly, mark_printed, unmark_printed

			switch ( $field['id'] ) {
				// General section (como en factura).
				case 'enabled':
					$reorganized_fields[0] = $field;
					break;
				case 'attach_to_email_ids':
					$reorganized_fields[1] = $field;
					break;
				case 'disable_for_statuses':
					$reorganized_fields[2] = $field;
					break;
				case 'my_account_buttons':
					$reorganized_fields[3] = $field;
					break;

				// Document details section (como en factura).
				case 'display_email':
					$reorganized_fields[4] = $field;
					break;
				case 'display_phone':
					$reorganized_fields[5] = $field;
					break;
				case 'display_customer_notes':
					$reorganized_fields[6] = $field;
					break;
				case 'display_billing_address':
					$reorganized_fields[7] = $field;
					break;
				case 'display_number':
					$reorganized_fields[8] = $field;
					break;
				case 'next_packing_slip_number':
					$reorganized_fields[9] = $field;
					break;
				case 'number_format':
					$reorganized_fields[10] = $field;
					break;
				case 'display_date':
					$reorganized_fields[11] = $field;
					break;

				// Admin section (como en factura).
				case 'packing_slip_number_column':
					$reorganized_fields[12] = $field;
					break;
				case 'packing_slip_date_column':
					$reorganized_fields[13] = $field;
					break;

				// Advanced section (como en factura).
				case 'reset_number_yearly':
					$reorganized_fields[14] = $field;
					break;
				case 'mark_printed':
					$reorganized_fields[15] = $field;
					break;
				case 'unmark_printed':
					$reorganized_fields[16] = $field;
					break;

				default:
					$remaining_fields[] = $field;
					break;
			}
		}

		// Ordenar por índice y agregar los campos restantes.
		ksort( $reorganized_fields );
		$final_fields = array_merge( $reorganized_fields, $remaining_fields );

		return $final_fields;
	}

	/**
	 * Agrega las opciones faltantes al packing slip para que coincida con la factura.
	 *
	 * @param array $settings_fields Campos de configuración actuales.
	 * @return array
	 */
	public function add_missing_packing_slip_options( $settings_fields ) {
		$option_name = 'wpo_wcpdf_documents_settings_packing-slip';

		// Verificar si ya existen las opciones para no duplicarlas.
		$existing_ids = array();
		foreach ( $settings_fields as $field ) {
			if ( isset( $field['id'] ) ) {
				$existing_ids[] = $field['id'];
			}
		}

		// Agregar opciones faltantes que están en factura pero no en packing slip.
		$missing_options = array();

		// disable_for_statuses (General section).
		if ( ! in_array( 'disable_for_statuses', $existing_ids, true ) ) {
			$missing_options[] = array(
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
			);
		}

		// display_number (Document details section).
		if ( ! in_array( 'display_number', $existing_ids, true ) ) {
			$missing_options[] = array(
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
				),
			);
		}

		// next_packing_slip_number (Document details section).
		if ( ! in_array( 'next_packing_slip_number', $existing_ids, true ) ) {
			$missing_options[] = array(
				'type'     => 'setting',
				'id'       => 'next_packing_slip_number',
				'title'    => __( 'Next packing slip number', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'next_number_edit',
				'section'  => 'packing_slip',
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'next_packing_slip_number',
					'size'        => '10',
					'description' => __( 'This is the number that will be used for the next document. By default, numbering starts from 1 and increases for every new document. Note that if you override this and set it to a number that has already been used, this could create duplicate numbers!', 'woocommerce-pdf-invoices-packing-slips' ),
				),
			);
		}

		// number_format (Document details section).
		if ( ! in_array( 'number_format', $existing_ids, true ) ) {
			$missing_options[] = array(
				'type'     => 'setting',
				'id'       => 'number_format',
				'title'    => __( 'Number format', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback' => 'text_input',
				'section'  => 'packing_slip',
				'args'     => array(
					'option_name' => $option_name,
					'id'          => 'number_format',
					'size'        => '20',
					'description' => __( 'Available placeholders: {order_number}, {order_date}, {order_date_i18n}, {document_date}, {document_date_i18n}, {Y}, {y}, {m}, {n}, {j}, {d}.<br><strong>Note:</strong> The {document_date} and {document_date_i18n} placeholders will use the document date (if set) or fall back to the order date.', 'woocommerce-pdf-invoices-packing-slips' ),
				),
			);
		}

		// display_date (Document details section).
		if ( ! in_array( 'display_date', $existing_ids, true ) ) {
			$missing_options[] = array(
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
			);
		}

		// Agregar las opciones faltantes al final.
		return array_merge( $settings_fields, $missing_options );
	}
}

// Inicializar la reorganización de opciones del packing slip.
new Palafito_Packing_Slip_Settings();
