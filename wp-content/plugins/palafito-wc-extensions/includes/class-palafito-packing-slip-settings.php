<?php
/**
 * Sincronización de configuración del Packing Slip para Palafito.
 *
 * @package Palafito_WC_Extensions
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sincroniza la configuración del checkbox "usar número de pedido" con display_number.
 *
 * @package Palafito_WC_Extensions
 */
class Palafito_Packing_Slip_Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Hook para sincronizar use_order_number y display_number al guardar opciones.
		add_filter( 'pre_update_option_wpo_wcpdf_documents_settings_packing-slip', array( $this, 'sync_use_order_number_and_display_number' ), 10, 2 );
	}

	/**
	 * Sincroniza use_order_number y display_number para evitar desincronización.
	 *
	 * @param array $new_value Nuevo valor de la opción.
	 * @param array $old_value Valor anterior de la opción.
	 * @return array
	 */
	public function sync_use_order_number_and_display_number( $new_value, $old_value ) {
		if ( isset( $new_value['use_order_number'] ) && 1 === $new_value['use_order_number'] ) {
			$new_value['display_number'] = 'order_number';
		} elseif ( isset( $new_value['display_number'] ) && 'order_number' === $new_value['display_number'] ) {
			$new_value['use_order_number'] = 1;
		} else {
			// Si ninguno está activo, asegúrate de que ambos estén desactivados.
			$new_value['use_order_number'] = 0;
			if ( empty( $new_value['display_number'] ) || 'order_number' === $new_value['display_number'] ) {
				$new_value['display_number'] = 'packing_slip_number';
			}
		}
		return $new_value;
	}
}

// Inicializar la sincronización de configuración del packing slip.
new Palafito_Packing_Slip_Settings();
