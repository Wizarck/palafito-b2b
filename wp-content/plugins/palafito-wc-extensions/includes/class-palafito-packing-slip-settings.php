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
		add_filter( 'wpo_wcpdf_settings_fields_documents_packing_slip', array( $this, 'reorganize_packing_slip_settings' ), 20, 4 );
	}

	/**
	 * Reorganiza las opciones del packing slip para que coincidan con la estructura de la factura.
	 *
	 * @param array  $settings_fields Campos de configuración actuales.
	 * @param string $page Página de configuración.
	 * @param string $option_group Grupo de opciones.
	 * @param string $option_name Nombre de la opción.
	 * @return array
	 */
	public function reorganize_packing_slip_settings( $settings_fields, $page, $option_group, $option_name ) {
		// Solo reorganizar si el plugin PDF está disponible.
		if ( ! class_exists( 'WPO_WCPDF' ) ) {
			return $settings_fields;
		}

		// Buscar y extraer las opciones que necesitamos reorganizar.
		$reorganized_fields = array();
		$remaining_fields   = array();

		// Primera pasada: extraer las opciones en el orden correcto.
		foreach ( $settings_fields as $field ) {
			if ( ! isset( $field['id'] ) ) {
				$remaining_fields[] = $field;
				continue;
			}

			// Orden deseado (como en factura):
			// 1. enabled
			// 2. attach_to_email_ids
			// 3. disable_for_statuses
			// 4. display_billing_address (equivalente a display_shipping_address en factura)
			// 5. display_email
			// 6. display_phone
			// 7. display_customer_notes
			// 8. display_date
			// 9. display_number
			// 10. next_packing_slip_number
			// 11. number_format
			// 12. my_account_buttons
			// 13. reset_number_yearly
			// 14. subtract_refunded_qty
			// 15. hide_virtual_downloadable_products

			switch ( $field['id'] ) {
				case 'enabled':
					$reorganized_fields[0] = $field;
					break;
				case 'attach_to_email_ids':
					$reorganized_fields[1] = $field;
					break;
				case 'disable_for_statuses':
					$reorganized_fields[2] = $field;
					break;
				case 'display_billing_address':
					$reorganized_fields[3] = $field;
					break;
				case 'display_email':
					$reorganized_fields[4] = $field;
					break;
				case 'display_phone':
					$reorganized_fields[5] = $field;
					break;
				case 'display_customer_notes':
					$reorganized_fields[6] = $field;
					break;
				case 'display_date':
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
				case 'my_account_buttons':
					$reorganized_fields[11] = $field;
					break;
				case 'reset_number_yearly':
					$reorganized_fields[12] = $field;
					break;
				case 'subtract_refunded_qty':
					$reorganized_fields[13] = $field;
					break;
				case 'hide_virtual_downloadable_products':
					$reorganized_fields[14] = $field;
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
}

// Inicializar la reorganización de opciones del packing slip.
new Palafito_Packing_Slip_Settings();
