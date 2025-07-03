<?php
/**
 * Plugin Hooks for Palafito WC Extensions
 *
 * Handles custom hooks and status changes for the Palafito B2B workflow.
 *
 * @package Palafito_WC_Extensions
 * @since 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Función de activación del plugin.
 */
function palafito_wc_extensions_activate() {
	// Log the hook execution for debugging.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'Palafito WC Extensions: Hook executed - palafito_wc_extensions_activate' );
	}
}

/**
 * Función de desactivación del plugin.
 */
function palafito_wc_extensions_deactivate() {
	// Log the hook execution for debugging.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'Palafito WC Extensions: Hook executed - palafito_wc_extensions_deactivate' );
	}
}

// Registrar hooks de activación/desactivación.
register_activation_hook( PALAFITO_WC_EXTENSIONS_PLUGIN_FILE, 'palafito_wc_extensions_activate' );
register_deactivation_hook( PALAFITO_WC_EXTENSIONS_PLUGIN_FILE, 'palafito_wc_extensions_deactivate' );

// Hooks para cambio de estado de pedidos (solo logs para debugging).
add_action( 'woocommerce_order_status_changed', 'palafito_wc_extensions_handle_order_status_change', 10, 3 );

/**
 * Manejar cambios de estado de pedidos (solo logs para debugging).
 *
 * @param int    $order_id   Order ID.
 * @param string $old_status Old status.
 * @param string $new_status New status.
 */
function palafito_wc_extensions_handle_order_status_change( $order_id, $old_status, $new_status ) {
	// Log status changes for debugging.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		if ( 'entregado' === $new_status ) {
			error_log( "Palafito WC Extensions: Order {$order_id} status changed to 'entregado'." );
		}
		if ( 'facturado' === $new_status ) {
			error_log( "Palafito WC Extensions: Order {$order_id} status changed to 'facturado'." );
		}
	}
}

// Eliminado: Registro de emails personalizados - funcionalidad manejada por el plugin PRO.
// Eliminado: Forzar generación de albarán - funcionalidad manejada por el plugin PRO.
// Eliminado: Adjuntos de email - funcionalidad manejada por el plugin PRO.

/**
 * Save packing slip data when order is saved.
 *
 * Uses a more reliable hook that fires for all order saves, not just invoice contexts.
 * The 'wpo_wcpdf_on_save_invoice_order_data' hook is unreliable for packing slip data.
 *
 * @param int $order_id Order ID being saved.
 * @param WP_Post $order Order post object.
 */
add_action( 'woocommerce_process_shop_order_meta', 'palafito_save_packing_slip_data_on_order_save', 36, 2 );

/**
 * Save packing slip data during order save operations.
 *
 * @param int          $order_id   Order ID.
 * @param WP_Post|null $order_post Order post object (unused).
 */
function palafito_save_packing_slip_data_on_order_save( $order_id, $order_post = null ) {
	// Validate inputs.
	if ( empty( $order_id ) || empty( $_POST ) ) {
		return;
	}

	// Get order object.
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}

	// Debug logging.
	error_log( '=== PALAFITO DEBUG: Order save hook fired ===' );
	error_log( 'Order ID: ' . $order_id );

	// Check nonce for security.
	if ( empty( $_POST['woocommerce_meta_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
		error_log( 'PALAFITO DEBUG: Nonce verification failed' );
		return;
	}

	// Check user permissions.
	if ( ! current_user_can( 'edit_shop_orders' ) && ! current_user_can( 'manage_woocommerce' ) ) {
		error_log( 'PALAFITO DEBUG: User lacks permissions' );
		return;
	}

	// Look for packing slip date field in POST data.
	$packing_slip_date_field   = null;
	$packing_slip_number_field = null;

	// Check for both possible field name formats.
	$possible_date_keys = array(
		'_wcpdf_packing-slip_date',
		'_wcpdf_packing_slip_date',
	);

	$possible_number_keys = array(
		'_wcpdf_packing-slip_number',
		'_wcpdf_packing_slip_number',
	);

	foreach ( $possible_date_keys as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			$packing_slip_date_field = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			error_log( 'PALAFITO DEBUG: Found packing slip date field with key: ' . $key );
			break;
		}
	}

	foreach ( $possible_number_keys as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			$packing_slip_number_field = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			error_log( 'PALAFITO DEBUG: Found packing slip number field with key: ' . $key );
			break;
		}
	}

	// Log what we found in POST data.
	$packing_slip_keys = array();
	foreach ( $_POST as $key => $value ) {
		if ( strpos( $key, 'packing' ) !== false || strpos( $key, 'slip' ) !== false ) {
			$packing_slip_keys[] = $key;
		}
	}
	error_log( 'PALAFITO DEBUG: All packing slip related keys in POST: ' . implode( ', ', $packing_slip_keys ) );

	// If we found packing slip date data, process it.
	if ( ! empty( $packing_slip_date_field ) && is_array( $packing_slip_date_field ) ) {
		error_log( 'PALAFITO DEBUG: Processing packing slip date field: ' . print_r( $packing_slip_date_field, true ) );

		// Get packing slip document.
		$packing_slip = wcpdf_get_document( 'packing-slip', $order );
		if ( ! $packing_slip ) {
			error_log( 'PALAFITO DEBUG: Could not get packing slip document' );
			return;
		}

		// Process date from form field.
		$date_data = array();
		if ( ! empty( $packing_slip_date_field['date'] ) ) {
			$date   = sanitize_text_field( $packing_slip_date_field['date'] );
			$hour   = ! empty( $packing_slip_date_field['hour'] ) ? sanitize_text_field( $packing_slip_date_field['hour'] ) : '00';
			$minute = ! empty( $packing_slip_date_field['minute'] ) ? sanitize_text_field( $packing_slip_date_field['minute'] ) : '00';

			// Create properly formatted date string.
			$date        = gmdate( 'Y-m-d', strtotime( $date ) );
			$hour        = sprintf( '%02d', intval( $hour ) );
			$minute      = sprintf( '%02d', intval( $minute ) );
			$date_string = "{$date} {$hour}:{$minute}:00";

			$date_data['date'] = $date_string;
			error_log( 'PALAFITO DEBUG: Processed date string: ' . $date_string );
		}

		// Process number if provided.
		if ( ! empty( $packing_slip_number_field ) ) {
			$date_data['number'] = absint( $packing_slip_number_field );
			error_log( 'PALAFITO DEBUG: Processed number: ' . $date_data['number'] );
		}

		// Save the data if we have any.
		if ( ! empty( $date_data ) ) {
			error_log( 'PALAFITO DEBUG: Attempting to save packing slip data...' );

			try {
				$packing_slip->set_data( $date_data, $order );
				$packing_slip->save();
				error_log( 'PALAFITO DEBUG: Packing slip data saved successfully!' );

				// Verify the meta was actually saved.
				$saved_date = $order->get_meta( '_wcpdf_packing-slip_date', true );
				error_log( 'PALAFITO DEBUG: Verified saved meta _wcpdf_packing-slip_date: ' . $saved_date );

			} catch ( Exception $e ) {
				error_log( 'PALAFITO DEBUG: Error saving packing slip data: ' . $e->getMessage() );
			}
		}
	} else {
		error_log( 'PALAFITO DEBUG: No packing slip date field found in POST data' );
	}

	error_log( '=== PALAFITO DEBUG: Order save hook completed ===' );
}

/**
 * Sincronización bidireccional entre _wcpdf_packing-slip_date y _entregado_date
 * 
 * Asegura que ambos campos estén siempre sincronizados cuando uno de ellos cambia.
 */

// Hook para sincronizar cuando se actualiza _wcpdf_packing-slip_date
add_action( 'updated_post_meta', 'palafito_sync_packing_slip_to_entregado', 10, 4 );
add_action( 'added_post_meta', 'palafito_sync_packing_slip_to_entregado', 10, 4 );

/**
 * Sincronizar fecha de albarán a fecha de entrega
 *
 * @param int    $meta_id    Meta ID
 * @param int    $post_id    Post ID
 * @param string $meta_key   Meta key
 * @param mixed  $meta_value Meta value
 */
function palafito_sync_packing_slip_to_entregado( $meta_id, $post_id, $meta_key, $meta_value ) {
	// Solo procesar si es la fecha del albarán
	if ( '_wcpdf_packing-slip_date' !== $meta_key ) {
		return;
	}

	// Verificar que es un pedido de WooCommerce
	$order = wc_get_order( $post_id );
	if ( ! $order ) {
		return;
	}

	// Evitar bucle infinito con flag temporal
	if ( get_transient( "palafito_syncing_{$post_id}" ) ) {
		return;
	}

	// Debug logging
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( "PALAFITO SYNC: Updating _entregado_date for order {$post_id} with value: {$meta_value}" );
	}

	// Establecer flag temporal para evitar bucle
	set_transient( "palafito_syncing_{$post_id}", true, 30 );

	try {
		// Actualizar _entregado_date con el mismo valor
		$order->update_meta_data( '_entregado_date', $meta_value );
		$order->save_meta_data();

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "PALAFITO SYNC: Successfully synced _entregado_date for order {$post_id}" );
		}
	} catch ( Exception $e ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "PALAFITO SYNC ERROR: Failed to sync order {$post_id}: " . $e->getMessage() );
		}
	} finally {
		// Limpiar flag temporal
		delete_transient( "palafito_syncing_{$post_id}" );
	}
}

// Hook para sincronizar cuando se actualiza _entregado_date
add_action( 'updated_post_meta', 'palafito_sync_entregado_to_packing_slip', 10, 4 );
add_action( 'added_post_meta', 'palafito_sync_entregado_to_packing_slip', 10, 4 );

/**
 * Sincronizar fecha de entrega a fecha de albarán
 *
 * @param int    $meta_id    Meta ID
 * @param int    $post_id    Post ID
 * @param string $meta_key   Meta key
 * @param mixed  $meta_value Meta value
 */
function palafito_sync_entregado_to_packing_slip( $meta_id, $post_id, $meta_key, $meta_value ) {
	// Solo procesar si es la fecha de entrega
	if ( '_entregado_date' !== $meta_key ) {
		return;
	}

	// Verificar que es un pedido de WooCommerce
	$order = wc_get_order( $post_id );
	if ( ! $order ) {
		return;
	}

	// Evitar bucle infinito con flag temporal
	if ( get_transient( "palafito_syncing_{$post_id}" ) ) {
		return;
	}

	// Debug logging
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( "PALAFITO SYNC: Updating _wcpdf_packing-slip_date for order {$post_id} with value: {$meta_value}" );
	}

	// Establecer flag temporal para evitar bucle
	set_transient( "palafito_syncing_{$post_id}", true, 30 );

	try {
		// Verificar si el plugin PDF está disponible y obtener el documento
		if ( function_exists( 'wcpdf_get_document' ) ) {
			$packing_slip = wcpdf_get_document( 'packing-slip', $order );
			
			if ( $packing_slip ) {
				// Usar la API del plugin PDF para actualizar la fecha
				$date_data = array(
					'date' => $meta_value
				);
				$packing_slip->set_data( $date_data, $order );
				$packing_slip->save();
			} else {
				// Fallback: actualizar directamente el meta
				$order->update_meta_data( '_wcpdf_packing-slip_date', $meta_value );
				$order->save_meta_data();
			}
		} else {
			// Fallback: actualizar directamente el meta
			$order->update_meta_data( '_wcpdf_packing-slip_date', $meta_value );
			$order->save_meta_data();
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "PALAFITO SYNC: Successfully synced _wcpdf_packing-slip_date for order {$post_id}" );
		}
	} catch ( Exception $e ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "PALAFITO SYNC ERROR: Failed to sync order {$post_id}: " . $e->getMessage() );
		}
	} finally {
		// Limpiar flag temporal
		delete_transient( "palafito_syncing_{$post_id}" );
	}
}
