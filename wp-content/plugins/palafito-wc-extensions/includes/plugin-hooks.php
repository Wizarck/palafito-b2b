<?php
/**
 * Plugin Hooks for Palafito WC Extensions
 *
 * Handles custom hooks, status changes, and email triggers for the Palafito B2B workflow.
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
	// Crear tablas personalizadas si es necesario.
	// Configurar opciones por defecto.
	// Limpiar cachés.

	// Log the hook execution for debugging.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'Palafito WC Extensions: Hook executed - palafito_wc_extensions_activate' );
	}
}

/**
 * Función de desactivación del plugin.
 */
function palafito_wc_extensions_deactivate() {
	// Limpiar datos temporales.
	// No eliminar datos permanentes aquí.

	// Log the hook execution for debugging.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'Palafito WC Extensions: Hook executed - palafito_wc_extensions_deactivate' );
	}
}

// Registrar hooks de activación/desactivación.
register_activation_hook( PALAFITO_WC_EXTENSIONS_PLUGIN_FILE, 'palafito_wc_extensions_activate' );
register_deactivation_hook( PALAFITO_WC_EXTENSIONS_PLUGIN_FILE, 'palafito_wc_extensions_deactivate' );

// Cargar configuración de PDF si el plugin está disponible.
if ( class_exists( 'WPO_WCPDF' ) ) {
	require_once PALAFITO_WC_EXTENSIONS_PLUGIN_DIR . 'includes/class-palafito-pdf-configuration.php';
}

// Hooks para cambio de estado de pedidos y envío automático de emails.
add_action( 'woocommerce_order_status_changed', 'palafito_wc_extensions_handle_order_status_change', 10, 3 );

// Los emails se envían automáticamente por WooCommerce cuando se registran los estados personalizados.
// No necesitamos disparar manualmente las acciones.

/**
 * Manejar cambios de estado de pedidos (solo logs para debugging).
 *
 * @param int    $order_id   Order ID.
 * @param string $old_status Old status.
 * @param string $new_status New status.
 */
function palafito_wc_extensions_handle_order_status_change( $order_id, $old_status, $new_status ) {
	// Solo procesar si el plugin de PDF está disponible.
	if ( ! class_exists( 'WPO_WCPDF' ) ) {
		return;
	}

	// Obtener la instancia del plugin.
	global $palafito_wc_extensions;

	if ( ! isset( $palafito_wc_extensions ) ) {
		return;
	}

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

// Register custom emails for Entregado and Facturado.
add_filter(
	'woocommerce_email_classes',
	function ( $email_classes ) {
		require_once __DIR__ . '/emails/class-wc-email-customer-entregado.php';
		require_once __DIR__ . '/emails/class-wc-email-customer-facturado.php';
		$email_classes['WC_Email_Customer_Entregado'] = new WC_Email_Customer_Entregado();
		$email_classes['WC_Email_Customer_Facturado'] = new WC_Email_Customer_Facturado();
		return $email_classes;
	}
);

// Forzar la generación del albarán (packing slip) al cambiar a Entregado.
add_action(
	'woocommerce_order_status_entregado',
	function ( $order_id, $order = null ) {
		if ( ! $order ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order ) {
			return;
		}
		if ( function_exists( 'wcpdf_get_document' ) ) {
			$packing_slip = wcpdf_get_document( 'packing-slip', $order, true );
			if ( $packing_slip && $packing_slip->is_allowed() ) {
				// Borrar packing slip previo para evitar caché interna.
				if ( $packing_slip->exists() ) {
					$packing_slip->delete();
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( '[PALAFITO] Packing slip previo borrado para pedido ' . $order->get_id() );
					}
				}
				// Forzar recarga y generación nativa tras borrar.
				$packing_slip = wcpdf_get_document( 'packing-slip', $order, true );
				// Obtener settings del albarán.
				$settings = get_option( 'wpo_wcpdf_documents_settings_packing-slip', array() );
				$use_order_number = ! empty( $settings['use_order_number'] );
				if ( $use_order_number ) {
					$base_number = $order->get_order_number();
				} else {
					// Lógica secuencial (por defecto del plugin).
					$base_number = $packing_slip->get_number() ? $packing_slip->get_number() : $order->get_id();
				}
				// Formatear número según configuración.
				$formatted_number = $base_number;
				if ( ! empty( $settings['number_format'] ) && is_array( $settings['number_format'] ) ) {
					$prefix  = ! empty( $settings['number_format']['prefix'] ) ? $settings['number_format']['prefix'] : '';
					$suffix  = ! empty( $settings['number_format']['suffix'] ) ? $settings['number_format']['suffix'] : '';
					$padding = ! empty( $settings['number_format']['padding'] ) ? (int) $settings['number_format']['padding'] : 0;
					if ( $padding > 0 ) {
						$formatted_number = str_pad( $base_number, $padding, '0', STR_PAD_LEFT );
					}
					$formatted_number = $prefix . $formatted_number . $suffix;
				}
				$number_data = array(
					'number'           => $base_number,
					'formatted_number' => $formatted_number,
				);
				$packing_slip->set_number( $number_data );
				$packing_slip->initiate_date();
				$packing_slip->save();
				// translators: %s: formatted number.
				$order->add_order_note( sprintf( __( 'Número y fecha de albarán generados automáticamente al cambiar a Entregado. Número: %s', 'palafito-wc-extensions' ), $formatted_number ) );
				$order->save();
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '[PALAFITO] Hook entregado ejecutado para pedido ' . $order->get_id() );
					error_log( '[PALAFITO] Packing slip número: ' . print_r( $number_data, true ) );
					error_log( '[PALAFITO] Packing slip fecha: ' . print_r( $packing_slip->get_date(), true ) );
				}
				// Obtener la ruta del PDF de forma compatible.
				$path = null;
				if ( method_exists( $packing_slip, 'get_pdf_path' ) ) {
					$path = $packing_slip->get_pdf_path();
				} elseif ( method_exists( $packing_slip, 'get_pdf' ) ) {
					$path = $packing_slip->get_pdf( 'path' );
				}
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '[PALAFITO] Packing slip PDF path: ' . print_r( $path, true ) );
				}
				if ( $path && file_exists( $path ) ) {
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( '[PALAFITO] Packing slip adjuntado al email para pedido ' . $order->get_id() . ': ' . $path );
					}
				} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '[PALAFITO] Packing slip NO adjuntado (no existe archivo) para pedido ' . $order->get_id() );
				}
			}
		}
	},
	10,
	2
);

add_filter(
	'wpo_wcpdf_email_attachments',
	function ( $attachments, $status, $order ) {
		if ( ! $order ) {
			return $attachments;
		}
		if ( function_exists( 'wcpdf_get_document' ) ) {
			$packing_slip = wcpdf_get_document( 'packing-slip', $order, true );
			if ( $packing_slip && $packing_slip->is_allowed() ) {
				// Borrar packing slip previo para evitar caché interna.
				if ( $packing_slip->exists() ) {
					$packing_slip->delete();
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( '[PALAFITO] Packing slip previo borrado antes de adjuntar para pedido ' . $order->get_id() );
					}
				}
				// Forzar recarga de datos y generación.
				$packing_slip = wcpdf_get_document( 'packing-slip', $order, true );
				$settings = get_option( 'wpo_wcpdf_documents_settings_packing-slip', array() );
				$use_order_number = ! empty( $settings['use_order_number'] );
				if ( $use_order_number ) {
					$base_number = $order->get_order_number();
				} else {
					$base_number = $packing_slip->get_number() ? $packing_slip->get_number() : $order->get_id();
				}
				$formatted_number = $base_number;
				if ( ! empty( $settings['number_format'] ) && is_array( $settings['number_format'] ) ) {
					$prefix  = ! empty( $settings['number_format']['prefix'] ) ? $settings['number_format']['prefix'] : '';
					$suffix  = ! empty( $settings['number_format']['suffix'] ) ? $settings['number_format']['suffix'] : '';
					$padding = ! empty( $settings['number_format']['padding'] ) ? (int) $settings['number_format']['padding'] : 0;
					if ( $padding > 0 ) {
						$formatted_number = str_pad( $base_number, $padding, '0', STR_PAD_LEFT );
					}
					$formatted_number = $prefix . $formatted_number . $suffix;
				}
				$number_data = array(
					'number'           => $base_number,
					'formatted_number' => $formatted_number,
				);
				$packing_slip->set_number( $number_data );
				$packing_slip->initiate_date();
				$packing_slip->save();
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '[PALAFITO] Packing slip número: ' . print_r( $number_data, true ) );
					error_log( '[PALAFITO] Packing slip fecha: ' . print_r( $packing_slip->get_date(), true ) );
				}
				$path = null;
				if ( method_exists( $packing_slip, 'get_pdf_path' ) ) {
					$path = $packing_slip->get_pdf_path();
				} elseif ( method_exists( $packing_slip, 'get_pdf' ) ) {
					$path = $packing_slip->get_pdf( 'path' );
				}
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '[PALAFITO] Packing slip PDF path: ' . print_r( $path, true ) );
				}
				if ( $path && file_exists( $path ) ) {
					$attachments[] = $path;
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( '[PALAFITO] Packing slip adjuntado al email para pedido ' . $order->get_id() . ': ' . $path );
					}
				} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '[PALAFITO] Packing slip NO adjuntado (no existe archivo) para pedido ' . $order->get_id() );
				}
			}
		}
		return $attachments;
	},
	10,
	3
);
