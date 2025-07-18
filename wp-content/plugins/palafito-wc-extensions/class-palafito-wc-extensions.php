<?php
/**
 * Funcionalidad principal y clases para Palafito WC Extensions.
 *
 * @package Palafito_WC_Extensions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
final class Palafito_WC_Extensions {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init_hooks();
		$this->load_classes();
		$this->init_components();
	}

	/**
	 * Initialize plugin hooks.
	 */
	private function init_hooks() {
		// Registrar nuevos estados personalizados de pedido.
		add_filter( 'woocommerce_register_shop_order_post_statuses', array( __CLASS__, 'register_custom_order_statuses' ) );
		add_filter( 'wc_order_statuses', array( __CLASS__, 'add_custom_order_statuses_to_list' ) );

		// Acciones masivas para interfaz clásica (edit-shop_order).
		add_filter( 'bulk_actions-edit-shop_order', array( __CLASS__, 'add_custom_order_statuses_to_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-shop_order', array( __CLASS__, 'handle_bulk_order_status_actions' ), 10, 3 );

		// Acciones masivas para nueva interfaz HPOS (woocommerce_page_wc-orders).
		add_filter( 'woocommerce_admin_order_list_bulk_actions', array( __CLASS__, 'add_custom_order_statuses_to_bulk_actions' ) );
		add_filter( 'woocommerce_admin_order_list_handle_bulk_actions', array( __CLASS__, 'handle_bulk_order_status_actions' ), 10, 3 );

		// Acciones individuales para nueva interfaz HPOS.
		add_filter( 'woocommerce_admin_order_actions', array( __CLASS__, 'add_custom_order_actions' ), 10, 2 );

		// Remover acción "Complete" de pedidos en estado "on-hold".
		add_filter( 'woocommerce_admin_order_actions', array( __CLASS__, 'remove_complete_action_from_on_hold' ), 20, 2 );

		// Registrar post status personalizados en el hook init.
		add_action( 'init', array( __CLASS__, 'register_custom_post_statuses' ), 1 );

		// Permitir que los estados personalizados sean válidos para el cambio rápido desde AJAX.
		add_filter( 'woocommerce_valid_order_statuses_for_payment', array( __CLASS__, 'add_custom_statuses_to_valid_list' ) );
		add_filter( 'woocommerce_valid_order_statuses_for_cancel', array( __CLASS__, 'add_custom_statuses_to_valid_list' ) );
		add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', array( __CLASS__, 'add_custom_statuses_to_valid_list' ) );
		add_filter( 'woocommerce_valid_order_statuses_for_refund', array( __CLASS__, 'add_custom_statuses_to_valid_list' ) );
		add_filter( 'woocommerce_valid_order_statuses_for_edit', array( __CLASS__, 'add_custom_statuses_to_valid_list' ) );

		// Permitir que los estados personalizados sean válidos para el cambio rápido desde el menú de pedidos.
		add_filter( 'woocommerce_order_statuses', array( __CLASS__, 'add_custom_statuses_to_order_statuses' ) );

		// 🎯 CRITICAL: Block WooCommerce PDF PRO automatic generation for packing slips.
		// This MUST be loaded BEFORE the PDF PRO plugin hooks (priority 5 vs 7).
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'block_automatic_packing_slip_generation' ), 5, 4 );

		// Block PRO plugin auto-generation configuration for packing slips.
		add_filter( 'wpo_wcpdf_pro_order_status_to_generate_pdfs', array( __CLASS__, 'filter_pro_auto_generation_statuses' ), 10, 2 );

		// Override document settings to block auto-generation for packing slips.
		add_filter( 'wpo_wcpdf_document_store_settings', array( __CLASS__, 'override_packing_slip_auto_generation_settings' ), 10, 2 );

		// CRITICAL: Force disable auto-generation at document settings level.
		add_filter( 'wpo_wcpdf_document_settings', array( __CLASS__, 'force_disable_packing_slip_auto_generation' ), 10, 2 );

		// Block document creation in non-entregado states.
		add_filter( 'wpo_wcpdf_document_is_allowed', array( __CLASS__, 'block_packing_slip_in_non_entregado_states' ), 5, 2 );

		// Clean packing slip auto-generation configuration.
		add_filter( 'option_wpo_wcpdf_documents_settings_packing-slip', array( __CLASS__, 'clean_packing_slip_auto_generation_option' ), 10, 1 );

		// AGGRESSIVE: Block auto-generation at the source.
		add_filter( 'wpo_wcpdf_document_auto_generate', array( __CLASS__, 'block_auto_generation_aggressively' ), 5, 3 );

		// ULTRA AGGRESSIVE: Completely disable PRO hooks for packing slips in non-allowed statuses.
		add_action( 'init', array( __CLASS__, 'ultra_aggressive_pro_packing_slip_block' ), 1 );

		// Permitir transiciones de estado personalizadas.
		// Priority 20 to ensure it runs AFTER other plugins and forces the update.
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'handle_custom_order_status_change' ), 20, 4 );

		// Active prevention: Block any attempts to set delivery date in non-entregado states.
		add_action( 'wpo_wcpdf_save_document', array( __CLASS__, 'prevent_premature_date_setting' ), 5, 2 );

		// 🎯 PACKING SLIP PDF AUTO-GENERATION HOOKS
		// 1. Manual metabox date setting
		add_action( 'updated_post_meta', array( __CLASS__, 'maybe_generate_packing_slip_on_date_change' ), 10, 4 );

		// 2. Manual PDF generation button (this is already handled by the PDF plugin itself)
		// No additional hook needed as the plugin handles manual generation correctly

		// 3. Status change to "entregado" (already handled in handle_custom_order_status_change)
		// 4. Status change to "facturado" or "completed" without existing packing slip date
		// Both handled in enhanced handle_custom_order_status_change method

		// 🔧 ENSURE PDF PLUGIN SETTINGS ARE CONFIGURED CORRECTLY
		add_action( 'init', array( __CLASS__, 'ensure_pdf_display_settings' ), 99 );

		// Disparar emails personalizados cuando cambien los estados.
		add_action( 'woocommerce_order_status_entregado', array( __CLASS__, 'trigger_entregado_email' ), 10, 2 );
		add_action( 'woocommerce_order_status_facturado', array( __CLASS__, 'trigger_facturado_email' ), 10, 2 );

		// Registrar emails personalizados.
		add_filter( 'woocommerce_email_classes', array( __CLASS__, 'add_custom_email_classes' ) );

		// Personalizar título de email "entregado" con códigos de cliente.
		add_filter( 'woocommerce_email_subject_customer_entregado', array( __CLASS__, 'customize_entregado_email_subject' ), 10, 2 );

		// Cargar estilos personalizados para colores de estados.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );

		// Hook para Kadence WooCommerce Email Designer.
		add_action( 'kadence_woomail_designer_email_details', array( $this, 'kadence_email_main_content' ), 10, 4 );

		// Hook para añadir columnas personalizadas.
		add_filter( 'manage_edit-shop_order_columns', array( __CLASS__, 'add_custom_order_columns' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( __CLASS__, 'custom_order_columns_data' ), 10, 2 );
		add_filter( 'manage_edit-shop_order_sortable_columns', array( __CLASS__, 'make_custom_order_columns_sortable' ) );

		// Hook para nueva interfaz HPOS.
		add_filter( 'woocommerce_shop_order_list_table_columns', array( __CLASS__, 'add_custom_order_columns' ) );
		add_action( 'woocommerce_shop_order_list_table_custom_column', array( __CLASS__, 'custom_order_columns_data' ), 10, 2 );
		add_filter( 'woocommerce_shop_order_list_table_sortable_columns', array( __CLASS__, 'make_custom_order_columns_sortable' ) );

		// Hook para manejar ordenación (interfaz clásica).
		add_action( 'pre_get_posts', array( __CLASS__, 'sort_orders_by_custom_columns' ) );

		// Hook para manejar ordenación (nueva interfaz HPOS).
		add_filter( 'woocommerce_order_list_table_prepare_items_query_args', array( __CLASS__, 'hpos_adjust_query_args_for_custom_columns' ) );

		// Hook para configurar columnas por defecto visibles.
		add_filter( 'default_hidden_columns', array( __CLASS__, 'set_default_hidden_columns' ), 10, 2 );

		// Hook para modificar campos de checkout.
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'modify_checkout_order_notes_field' ) );
	}

	/**
	 * Registrar los post status personalizados de pedido en WordPress.
	 */
	public static function register_custom_post_statuses() {
		register_post_status(
			'wc-entregado',
			array(
				'label'                     => _x( 'Entregado', 'Order status', 'palafito-wc-extensions' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				// translators: %s: número de pedidos con estado Entregado.
				'label_count'               => _n_noop(
					'Entregado <span class="count">(%s)</span>',
					'Entregados <span class="count">(%s)</span>',
					'palafito-wc-extensions'
				),
			)
		);
		register_post_status(
			'wc-facturado',
			array(
				'label'                     => _x( 'Facturado', 'Order status', 'palafito-wc-extensions' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				// translators: %s: número de pedidos con estado Facturado.
				'label_count'               => _n_noop(
					'Facturado <span class="count">(%s)</span>',
					'Facturados <span class="count">(%s)</span>',
					'palafito-wc-extensions'
				),
			)
		);
	}

	/**
	 * Registrar los nuevos estados personalizados de pedido.
	 *
	 * @param array $order_statuses Array de estados de pedido registrados.
	 * @return array
	 */
	public static function register_custom_order_statuses( $order_statuses ) {
		$order_statuses['wc-entregado'] = array(
			'label'                     => _x( 'Entregado', 'Order status', 'palafito-wc-extensions' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			// translators: %s: número de pedidos con estado Entregado.
			'label_count'               => _n_noop( 'Entregado <span class="count">(%s)</span>', 'Entregados <span class="count">(%s)</span>', 'palafito-wc-extensions' ),
		);
		$order_statuses['wc-facturado'] = array(
			'label'                     => _x( 'Facturado', 'Order status', 'palafito-wc-extensions' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			// translators: %s: número de pedidos con estado Facturado.
			'label_count'               => _n_noop( 'Facturado <span class="count">(%s)</span>', 'Facturados <span class="count">(%s)</span>', 'palafito-wc-extensions' ),
		);
		return $order_statuses;
	}

	/**
	 * Añadir los nuevos estados personalizados a la lista de estados de WooCommerce en el orden correcto.
	 *
	 * @param array $order_statuses Array de estados de pedido.
	 * @return array
	 */
	public static function add_custom_order_statuses_to_list( $order_statuses ) {
		$new_order_statuses = array();
		foreach ( $order_statuses as $key => $label ) {
			if ( 'wc-pending' === $key ) {
				$new_order_statuses[ $key ] = $label;
			} elseif ( 'wc-processing' === $key ) {
				$new_order_statuses[ $key ]         = $label;
				$new_order_statuses['wc-entregado'] = _x( 'Entregado', 'Order status', 'palafito-wc-extensions' );
				$new_order_statuses['wc-facturado'] = _x( 'Facturado', 'Order status', 'palafito-wc-extensions' );
			} elseif ( 'wc-completed' === $key ) {
				$new_order_statuses[ $key ] = $label;
			} else {
				$new_order_statuses[ $key ] = $label;
			}
		}
		// Si por alguna razón 'wc-completed' no está, lo agregamos al final.
		if ( ! isset( $new_order_statuses['wc-completed'] ) && isset( $order_statuses['wc-completed'] ) ) {
			$new_order_statuses['wc-completed'] = $order_statuses['wc-completed'];
		}
		return $new_order_statuses;
	}

	/**
	 * Añadir los nuevos estados personalizados a las acciones masivas del admin.
	 *
	 * @param array $bulk_actions Acciones masivas disponibles.
	 * @return array
	 */
	public static function add_custom_order_statuses_to_bulk_actions( $bulk_actions ) {
		$bulk_actions['mark_entregado'] = __( 'Cambiar a Entregado', 'palafito-wc-extensions' );
		$bulk_actions['mark_facturado'] = __( 'Cambiar a Facturado', 'palafito-wc-extensions' );
		return $bulk_actions;
	}

	/**
	 * Manejar las acciones masivas de estados personalizados.
	 *
	 * @param string $redirect_to URL de redirección.
	 * @param string $doaction Acción a realizar.
	 * @param array  $post_ids IDs de los posts.
	 * @return string
	 */
	public static function handle_bulk_order_status_actions( $redirect_to, $doaction, $post_ids ) {
		if ( 'mark_entregado' === $doaction ) {
			$processed_count = 0;
			foreach ( $post_ids as $post_id ) {
				$order = wc_get_order( $post_id );
				if ( $order ) {
					$order->update_status( 'entregado', __( 'Cambio masivo a Entregado.', 'palafito-wc-extensions' ) );
					++$processed_count;
				}
			}
			$redirect_to = add_query_arg( 'bulk_entregado', $processed_count, $redirect_to );
		} elseif ( 'mark_facturado' === $doaction ) {
			$processed_count = 0;
			foreach ( $post_ids as $post_id ) {
				$order = wc_get_order( $post_id );
				if ( $order ) {
					$order->update_status( 'facturado', __( 'Cambio masivo a Facturado.', 'palafito-wc-extensions' ) );
					++$processed_count;
				}
			}
			$redirect_to = add_query_arg( 'bulk_facturado', $processed_count, $redirect_to );
		}
		return $redirect_to;
	}

	/**
	 * Agregar estados personalizados a las listas de estados válidos de WooCommerce.
	 *
	 * @param array $statuses Lista de estados válidos.
	 * @return array
	 */
	public static function add_custom_statuses_to_valid_list( $statuses ) {
		$custom_statuses = array( 'entregado', 'facturado' );
		return array_merge( $statuses, $custom_statuses );
	}

	/**
	 * Agregar estados personalizados a la lista de estados de pedido de WooCommerce.
	 *
	 * @param array $order_statuses Lista de estados de pedido.
	 * @return array
	 */
	public static function add_custom_statuses_to_order_statuses( $order_statuses ) {
		$order_statuses['entregado'] = _x( 'Entregado', 'Order status', 'palafito-wc-extensions' );
		$order_statuses['facturado'] = _x( 'Facturado', 'Order status', 'palafito-wc-extensions' );
		return $order_statuses;
	}

	/**
	 * Añadir acciones individuales para la nueva interfaz HPOS.
	 *
	 * @param array    $actions Acciones disponibles.
	 * @param WC_Order $order   Objeto del pedido.
	 * @return array
	 */
	public static function add_custom_order_actions( $actions, $order ) {
		// Añadir acción "Entregado" si el pedido está en procesamiento.
		if ( $order->has_status( array( 'processing' ) ) ) {
			$actions['entregado'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=entregado&order_id=' . $order->get_id() ), 'woocommerce-mark-order-status' ),
				'name'   => __( 'Entregado', 'palafito-wc-extensions' ),
				'action' => 'entregado',
			);
		}

		// Añadir acción "Facturado" si el pedido está entregado.
		if ( $order->has_status( array( 'entregado' ) ) ) {
			$actions['facturado'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=facturado&order_id=' . $order->get_id() ), 'woocommerce-mark-order-status' ),
				'name'   => __( 'Facturado', 'palafito-wc-extensions' ),
				'action' => 'facturado',
			);
		}

		// Añadir acción "Completado" si el pedido está en procesamiento o facturado.
		if ( $order->has_status( array( 'processing', 'facturado' ) ) ) {
			$actions['complete'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=completed&order_id=' . $order->get_id() ), 'woocommerce-mark-order-status' ),
				'name'   => __( 'Complete', 'woocommerce' ),
				'action' => 'complete',
			);
		}

		return $actions;
	}

	/**
	 * Remover acción "Complete" de pedidos en estado "on-hold".
	 *
	 * @param array    $actions Acciones disponibles.
	 * @param WC_Order $order   Objeto del pedido.
	 * @return array
	 */
	public static function remove_complete_action_from_on_hold( $actions, $order ) {
		// Remover acción "Complete" si el pedido está en estado "on-hold".
		if ( $order->has_status( array( 'on-hold' ) ) && isset( $actions['complete'] ) ) {
			unset( $actions['complete'] );
		}

		return $actions;
	}

	/**
	 * Manejar cambios de estado personalizados.
	 *
	 * @param int      $order_id Order ID.
	 * @param string   $old_status Old status.
	 * @param string   $new_status New status.
	 * @param WC_Order $order Order object.
	 */
	public static function handle_custom_order_status_change( $order_id, $old_status, $new_status, $order ) {
		// Log status change for debugging.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "Palafito WC Extensions: Order {$order_id} status changed from {$old_status} to {$new_status}" );
		}

		// 🎯 SCENARIO 3: Status change to "entregado" - always update date and generate PDF.
		if ( 'entregado' === $new_status ) {
			// Define states that should NOT trigger date update.
			$excluded_previous_states = array( 'facturado', 'completado', 'completed' );

			// Check if the previous state should be excluded.
			if ( ! in_array( $old_status, $excluded_previous_states, true ) ) {
				// Get current value for comparison.
				$previous_value = $order->get_meta( '_wcpdf_packing-slip_date' );

				// Force update with current timestamp to ensure COMPLETE overwrite.
				$current_timestamp = current_time( 'timestamp' );

				// Method 1: Update meta field directly.
				$order->delete_meta_data( '_wcpdf_packing-slip_date' );
				$order->update_meta_data( '_wcpdf_packing-slip_date', $current_timestamp );
				$order->delete_meta_data( '_wcpdf_packing_slip_date' );
				$order->save_meta_data();

				// Method 2: Update via database as fallback.
				update_post_meta( $order_id, '_wcpdf_packing-slip_date', $current_timestamp );
				delete_post_meta( $order_id, '_wcpdf_packing_slip_date' );

				// Method 3: Update PDF document directly (to sync with metabox).
				if ( function_exists( 'wcpdf_get_document' ) ) {
					$packing_slip = wcpdf_get_document( 'packing-slip', $order );
					if ( $packing_slip ) {
						// Create WC_DateTime object for the current timestamp.
						$wc_date = new WC_DateTime();
						$wc_date->setTimestamp( $current_timestamp );

						// Set the date on the document.
						$packing_slip->set_date( $wc_date );

						// Save the document to persist the date.
						$packing_slip->save();
					}
				}

				// 🎯 AUTO-GENERATE PACKING SLIP PDF.
				self::generate_packing_slip_pdf( $order );

				// Verify the update was successful.
				$verified_value = $order->get_meta( '_wcpdf_packing-slip_date' );

				// Enhanced logging for debugging.
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '=== PALAFITO DELIVERY DATE UPDATE ===' );
					error_log( "Order {$order_id}: {$old_status} → {$new_status}" );
					error_log( 'Previous value: ' . ( $previous_value ? gmdate( 'Y-m-d H:i:s', $previous_value ) : 'EMPTY' ) );
					error_log( "New timestamp: {$current_timestamp} (" . gmdate( 'Y-m-d H:i:s', $current_timestamp ) . ')' );
					error_log( 'Meta field verified: ' . ( $verified_value ? gmdate( 'Y-m-d H:i:s', $verified_value ) : 'FAILED' ) );
					error_log( 'Meta update successful: ' . ( $verified_value == $current_timestamp ? 'YES' : 'NO' ) );

					// Check PDF document update.
					if ( function_exists( 'wcpdf_get_document' ) ) {
						$check_packing_slip = wcpdf_get_document( 'packing-slip', $order );
						if ( $check_packing_slip && $check_packing_slip->get_date() ) {
							$doc_timestamp = $check_packing_slip->get_date()->getTimestamp();
							error_log( 'PDF document date: ' . gmdate( 'Y-m-d H:i:s', $doc_timestamp ) );
							error_log( 'PDF update successful: ' . ( $doc_timestamp == $current_timestamp ? 'YES' : 'NO' ) );
						} else {
							error_log( 'PDF document date: FAILED' );
						}
					}

					error_log( 'UPDATED: Meta field + PDF document + PDF generated' );
					error_log( '=========================================' );
				}
			} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// Log when update is skipped due to excluded previous state.
				error_log( "Palafito WC Extensions: Skipped date update for Order {$order_id} - previous state '{$old_status}' is excluded" );
			}
		}

		// 🎯 SCENARIO 4: Status change to "facturado" or "completed" - generate packing slip if no date exists.
		if ( 'facturado' === $new_status || 'completed' === $new_status ) {
			// Check if packing slip date already exists.
			$existing_packing_date = $order->get_meta( '_wcpdf_packing-slip_date' );

			// If no packing slip date exists, set it now and generate PDF.
			if ( empty( $existing_packing_date ) ) {
				// Set current timestamp as packing slip date.
				$current_timestamp = current_time( 'timestamp' );

				// Update meta field directly.
				$order->delete_meta_data( '_wcpdf_packing-slip_date' );
				$order->update_meta_data( '_wcpdf_packing-slip_date', $current_timestamp );
				$order->delete_meta_data( '_wcpdf_packing_slip_date' );
				$order->save_meta_data();

				// Update via database as fallback.
				update_post_meta( $order_id, '_wcpdf_packing-slip_date', $current_timestamp );
				delete_post_meta( $order_id, '_wcpdf_packing_slip_date' );

				// Update PDF document directly.
				if ( function_exists( 'wcpdf_get_document' ) ) {
					$packing_slip = wcpdf_get_document( 'packing-slip', $order );
					if ( $packing_slip ) {
						$wc_date = new WC_DateTime();
						$wc_date->setTimestamp( $current_timestamp );
						$packing_slip->set_date( $wc_date );
						$packing_slip->save();
					}
				}

				// 🎯 AUTO-GENERATE PACKING SLIP PDF.
				self::generate_packing_slip_pdf( $order );

				// Log the creation.
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( "[PALAFITO] Created missing packing slip date and PDF for order {$order_id} on status change to {$new_status}" );
				}
			}
		}

		// Update invoice date when order status changes to "facturado" or "completed".
		// Only update if NOT coming from "facturado" or "completed" states.
		if ( 'facturado' === $new_status || 'completed' === $new_status ) {
			// Define states that should NOT trigger date update.
			$excluded_previous_states = array( 'facturado', 'completed' );

			// Check if the previous state should be excluded.
			if ( ! in_array( $old_status, $excluded_previous_states, true ) ) {
				// Get current value for comparison.
				$previous_value = $order->get_meta( '_wcpdf_invoice_date' );

				// Force update with current timestamp to ensure COMPLETE overwrite.
				$current_timestamp = current_time( 'timestamp' );

				// Method 1: Update meta field directly.
				$order->delete_meta_data( '_wcpdf_invoice_date' );
				$order->update_meta_data( '_wcpdf_invoice_date', $current_timestamp );
				$order->save_meta_data();

				// Method 2: Update via database as fallback.
				update_post_meta( $order_id, '_wcpdf_invoice_date', $current_timestamp );

				// Method 3: Update PDF document directly (to sync with metabox).
				if ( function_exists( 'wcpdf_get_document' ) ) {
					$invoice = wcpdf_get_document( 'invoice', $order );
					if ( $invoice ) {
						// Create WC_DateTime object for the current timestamp.
						$wc_date = new WC_DateTime();
						$wc_date->setTimestamp( $current_timestamp );

						// Set the date on the document.
						$invoice->set_date( $wc_date );

						// Save the document to persist the date.
						$invoice->save();
					}
				}

				// Verify the update was successful.
				$verified_value = $order->get_meta( '_wcpdf_invoice_date' );

				// Enhanced logging for debugging.
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '=== PALAFITO INVOICE DATE UPDATE ===' );
					error_log( "Order {$order_id}: {$old_status} → {$new_status}" );
					error_log( 'Previous value: ' . ( $previous_value ? gmdate( 'Y-m-d H:i:s', $previous_value ) : 'EMPTY' ) );
					error_log( "New timestamp: {$current_timestamp} (" . gmdate( 'Y-m-d H:i:s', $current_timestamp ) . ')' );
					error_log( 'Meta field verified: ' . ( $verified_value ? gmdate( 'Y-m-d H:i:s', $verified_value ) : 'FAILED' ) );
					error_log( 'Meta update successful: ' . ( $verified_value == $current_timestamp ? 'YES' : 'NO' ) );

					// Check PDF document update.
					if ( function_exists( 'wcpdf_get_document' ) ) {
						$check_invoice = wcpdf_get_document( 'invoice', $order );
						if ( $check_invoice && $check_invoice->get_date() ) {
							$doc_timestamp = $check_invoice->get_date()->getTimestamp();
							error_log( 'PDF document date: ' . gmdate( 'Y-m-d H:i:s', $doc_timestamp ) );
							error_log( 'PDF update successful: ' . ( $doc_timestamp == $current_timestamp ? 'YES' : 'NO' ) );
						} else {
							error_log( 'PDF document date: FAILED' );
						}
					}

					error_log( 'UPDATED: Meta field + PDF document' );
					error_log( '=========================================' );
				}
			} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// Log when update is skipped due to excluded previous state.
				error_log( "Palafito WC Extensions: Skipped invoice date update for Order {$order_id} - previous state '{$old_status}' is excluded" );
			}
		}
	}

	/**
	 * Prevent premature date setting for packing slips.
	 *
	 * This function prevents setting delivery dates on packing slips when the order
	 * is not yet in "entregado" status, except for manual admin actions.
	 *
	 * @param object $document PDF document object.
	 * @param object $order WooCommerce order object.
	 */
	public static function prevent_premature_date_setting( $document, $order ) {
		// Only act on packing slip documents.
		if ( $document && $document->get_type() === 'packing-slip' ) {
			$order_status = $order->get_status();

			// Define statuses where automatic date setting is allowed.
			$auto_allowed_statuses = array( 'entregado', 'facturado', 'completed' );

			// Check if this is a manual action (admin area with direct user interaction).
			$is_manual_action = is_admin() && ! wp_doing_ajax() && ! defined( 'DOING_CRON' );

			// For automatic actions, only allow in auto_allowed_statuses.
			if ( ! $is_manual_action && ! in_array( $order_status, $auto_allowed_statuses, true ) ) {
				// Clear any date that might have been set for automatic actions.
				if ( method_exists( $document, 'set_date' ) ) {
					$document->set_date( null );
				}

				// Also ensure standard meta field stays empty.
				$order->delete_meta_data( '_wcpdf_packing-slip_date' );
				$order->save_meta_data();
				delete_post_meta( $order->get_id(), '_wcpdf_packing-slip_date' );

				// ELIMINATE any legacy field.
				delete_post_meta( $order->get_id(), '_wcpdf_packing_slip_date' );

				// Log the prevention for debugging.
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( "[PALAFITO] BLOCKED DATE: Prevented automatic date setting for order {$order->get_id()} in status '{$order_status}' (not in auto-allowed list)" );
				}
			} elseif ( $is_manual_action && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// Log manual actions for debugging.
				error_log( "[PALAFITO] ALLOWED DATE: Manual date setting allowed for order {$order->get_id()} in status '{$order_status}'" );
			}
		}
	}

	/**
	 * 🎯 AUTO-GENERATE PACKING SLIP PDF when metabox date is manually changed.
	 *
	 * This function triggers when the packing slip date is manually set in the metabox.
	 * It automatically generates the PDF to ensure the document exists.
	 *
	 * @param int    $meta_id    ID of updated metadata entry.
	 * @param int    $post_id    Post ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 */
	public static function maybe_generate_packing_slip_on_date_change( $meta_id, $post_id, $meta_key, $meta_value ) {
		// Only process shop_order posts.
		if ( 'shop_order' !== get_post_type( $post_id ) ) {
			return;
		}

		// Only process packing slip date changes.
		if ( '_wcpdf_packing-slip_date' !== $meta_key && '_wcpdf_packing_slip_date' !== $meta_key ) {
			return;
		}

		// Only process when a valid date is being set (not empty).
		if ( empty( $meta_value ) ) {
			return;
		}

		// Get the order.
		$order = wc_get_order( $post_id );
		if ( ! $order ) {
			return;
		}

		// Log the trigger for debugging.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "[PALAFITO] METABOX DATE CHANGE: Detected manual date change for order {$post_id}, generating packing slip PDF..." );
		}

		// Generate the packing slip PDF.
		self::generate_packing_slip_pdf( $order );
	}

	/**
	 * 🎯 CENTRAL FUNCTION: Generate packing slip PDF and ensure it's saved.
	 *
	 * This function creates the packing slip PDF document and forces it to be generated
	 * and saved to ensure it exists for the user.
	 *
	 * @param WC_Order $order The WooCommerce order object.
	 * @return bool True if generation was successful, false otherwise.
	 */
	public static function generate_packing_slip_pdf( $order ) {
		// Verify PDF plugin is available.
		if ( ! function_exists( 'wcpdf_get_document' ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "[PALAFITO] ERROR: PDF plugin not available for order {$order->get_id()}" );
			}
			return false;
		}

		try {
			// Get the packing slip document (create if doesn't exist).
			$packing_slip = wcpdf_get_document( 'packing-slip', $order, true );

			if ( ! $packing_slip ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( "[PALAFITO] ERROR: Could not create packing slip document for order {$order->get_id()}" );
				}
				return false;
			}

			// Force generation of the PDF file.
			$pdf_file = $packing_slip->get_pdf();

			if ( $pdf_file ) {
				// Log successful generation.
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					$filename = $packing_slip->get_filename();
					error_log( "[PALAFITO] SUCCESS: Generated packing slip PDF '{$filename}' for order {$order->get_id()}" );
				}

				// Add order note about automatic generation.
				$order->add_order_note(
					sprintf(
						/* translators: 1: document name */
						__( '%s automaticamente generado por Palafito WC Extensions.', 'palafito-wc-extensions' ),
						'Albarán'
					),
					false
				);

				return true;
			} else {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( "[PALAFITO] ERROR: PDF generation failed for order {$order->get_id()}" );
				}
				return false;
			}
		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "[PALAFITO] EXCEPTION: PDF generation failed for order {$order->get_id()}: " . $e->getMessage() );
			}
			return false;
		}
	}

	/**
	 * Añadir clases de email personalizadas a WooCommerce.
	 *
	 * @param array $email_classes Array de clases de email.
	 * @return array
	 */
	public static function add_custom_email_classes( $email_classes ) {
		// Añadir email de "Entregado".
		if ( ! isset( $email_classes['WC_Email_Customer_Entregado'] ) ) {
			$email_classes['WC_Email_Customer_Entregado'] = include plugin_dir_path( __FILE__ ) . 'includes/emails/class-wc-email-customer-entregado.php';
		}

		// Añadir email de "Facturado".
		if ( ! isset( $email_classes['WC_Email_Customer_Facturado'] ) ) {
			$email_classes['WC_Email_Customer_Facturado'] = include plugin_dir_path( __FILE__ ) . 'includes/emails/class-wc-email-customer-facturado.php';
		}

		return $email_classes;
	}

	/**
	 * Disparar email de "Entregado".
	 *
	 * @param int      $order_id Order ID.
	 * @param WC_Order $order Order object.
	 */
	public static function trigger_entregado_email( $order_id, $order ) {
		if ( ! $order ) {
			$order = wc_get_order( $order_id );
		}

		if ( $order ) {
			// Disparar el email personalizado.
			do_action( 'woocommerce_order_status_entregado_notification', $order_id, $order );
		}
	}

	/**
	 * Disparar email de "Facturado".
	 *
	 * @param int      $order_id Order ID.
	 * @param WC_Order $order Order object.
	 */
	public static function trigger_facturado_email( $order_id, $order ) {
		if ( ! $order ) {
			$order = wc_get_order( $order_id );
		}

		if ( $order ) {
			// Disparar el email personalizado.
			do_action( 'woocommerce_order_status_facturado_notification', $order_id, $order );
		}
	}

	/**
	 * Load plugin classes.
	 */
	private function load_classes() {
		// Load plugin classes.
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-palafito-checkout-customizations.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/plugin-hooks.php';
		// Load PDF actions if PDF plugin is available.
		if ( class_exists( 'WPO_WCPDF' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'includes/class-palafito-packing-slip-settings.php';
		}
	}

	/**
	 * Initialize plugin components.
	 */
	private function init_components() {
		// Initialize checkout customizations.
		new Palafito_Checkout_Customizations();
		// Initialize PDF settings if PDF plugin is available.
		if ( class_exists( 'WPO_WCPDF' ) ) {
			new Palafito_Packing_Slip_Settings();
		}
	}

	/**
	 * Handle order status changes.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $old_status Old status.
	 * @param string $new_status New status.
	 */
	public function handle_order_status_change( $order_id, $old_status, $new_status ) {
		// Log status change for debugging.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "Palafito WC Extensions: Order {$order_id} status changed from {$old_status} to {$new_status}" );
		}
	}

	/**
	 * Enqueue admin styles.
	 */
	public function enqueue_admin_styles() {
		// Solo cargar en páginas de WooCommerce relacionadas con pedidos.
		$screen = get_current_screen();
		if ( $screen && ( 'edit-shop_order' === $screen->id || 'woocommerce_page_wc-orders' === $screen->id ) ) {
			wp_enqueue_style(
				'palafito-order-status-colors',
				plugin_dir_url( __FILE__ ) . 'assets/css/admin-order-status-colors.css',
				array( 'woocommerce_admin_styles' ), // Dependencia de WooCommerce para cargar después.
				'1.0.0'
			);
		}
	}

	/**
	 * Hook para Kadence WooCommerce Email Designer.
	 *
	 * @param WC_Order $order Order object.
	 * @param bool     $sent_to_admin Whether the email is sent to admin.
	 * @param bool     $plain_text Whether the email is plain text.
	 * @param WC_Email $email Email object.
	 */
	public function kadence_email_main_content( $order, $sent_to_admin, $plain_text, $email ) {
		// Solo procesar para nuestros emails personalizados.
		if ( ! in_array( $email->id, array( 'customer_entregado', 'customer_facturado' ), true ) ) {
			return;
		}
		// No imprimir nada aquí para evitar duplicidad de contenido.
	}

	/**
	 * Añadir columnas personalizadas a la tabla de pedidos.
	 *
	 * @param array $columns Array de columnas de la tabla de pedidos.
	 * @return array
	 */
	public static function add_custom_order_columns( $columns ) {
		// Verificar si las columnas del plugin PRO ya están presentes.
		$pro_invoice_number_exists = isset( $columns['invoice_number_column'] );
		$pro_invoice_date_exists   = isset( $columns['invoice_date_column'] );

		// Añadir nuestras columnas personalizadas.
		$columns['entregado_date'] = __( 'Fecha de entrega', 'palafito-wc-extensions' );
		$columns['notes']          = __( 'Notas', 'palafito-wc-extensions' );

		// Si las columnas del plugin PRO no existen, añadir las nuestras.
		if ( ! $pro_invoice_number_exists ) {
			$columns['invoice_number'] = __( 'Número de factura', 'palafito-wc-extensions' );
		}
		if ( ! $pro_invoice_date_exists ) {
			$columns['invoice_date'] = __( 'Fecha de factura', 'palafito-wc-extensions' );
		}

		// Reordenar las columnas para mantener el orden deseado.
		$reordered_columns = array();

		// Columnas que queremos al principio en orden específico.
		$priority_columns = array(
			'cb',
			'order_number',
			'order_total',
			'notes',
			'order_status',
			'wc_actions',
			'entregado_date',
		);

		// Añadir columnas prioritarias en el orden deseado.
		foreach ( $priority_columns as $column_key ) {
			if ( isset( $columns[ $column_key ] ) ) {
				$reordered_columns[ $column_key ] = $columns[ $column_key ];
			}
		}

		// Añadir columnas de factura (PRO o nuestras) después de las prioritarias.
		if ( $pro_invoice_number_exists ) {
			$reordered_columns['invoice_number_column'] = $columns['invoice_number_column'];
		} elseif ( isset( $columns['invoice_number'] ) ) {
			$reordered_columns['invoice_number'] = $columns['invoice_number'];
		}

		if ( $pro_invoice_date_exists ) {
			$reordered_columns['invoice_date_column'] = $columns['invoice_date_column'];
		} elseif ( isset( $columns['invoice_date'] ) ) {
			$reordered_columns['invoice_date'] = $columns['invoice_date'];
		}

		// Añadir el resto de columnas al final.
		foreach ( $columns as $key => $column ) {
			if ( ! isset( $reordered_columns[ $key ] ) ) {
				$reordered_columns[ $key ] = $column;
			}
		}

		return $reordered_columns;
	}

	/**
	 * Mostrar datos en las columnas personalizadas.
	 *
	 * @param string $column Columna actual.
	 * @param int    $post_id ID del pedido.
	 */
	public static function custom_order_columns_data( $column, $post_id ) {
		$order = wc_get_order( $post_id );
		if ( ! $order ) {
			return;
		}

		switch ( $column ) {
			case 'entregado_date':
				// Show delivery date: check multiple sources to match what metabox shows.

				// First try: get from PDF document (what metabox displays).
				$display_date = '';
				if ( function_exists( 'wcpdf_get_document' ) ) {
					$packing_slip = wcpdf_get_document( 'packing-slip', $order );
					if ( $packing_slip && $packing_slip->exists() && $packing_slip->get_date() ) {
						$display_date = $packing_slip->get_date()->date_i18n( 'd-m-Y' );
					}
				}

				// Fallback: check standard meta field.
				if ( empty( $display_date ) ) {
					$entregado_date = $order->get_meta( '_wcpdf_packing-slip_date' );
					if ( $entregado_date ) {
						$date         = is_numeric( $entregado_date ) ? $entregado_date : strtotime( $entregado_date );
						$display_date = date_i18n( 'd-m-Y', $date );
					}
				}

				// Final fallback: check legacy field (without dash).
				if ( empty( $display_date ) ) {
					$legacy_date = $order->get_meta( '_wcpdf_packing_slip_date' );
					if ( $legacy_date ) {
						$date         = is_numeric( $legacy_date ) ? $legacy_date : strtotime( $legacy_date );
						$display_date = date_i18n( 'd-m-Y', $date );
					}
				}

				echo ! empty( $display_date ) ? esc_html( $display_date ) : '&mdash;';
				break;

			case 'notes':
				// Mostrar nota de cliente (customer note) en vez de notas de factura.
				$customer_note = $order->get_customer_note();
				if ( $customer_note ) {
					$notes_text = wp_strip_all_tags( $customer_note );
					$notes_text = strlen( $notes_text ) > 50 ? substr( $notes_text, 0, 50 ) . '...' : $notes_text;
					echo esc_html( $notes_text );
				} else {
					echo '&mdash;';
				}
				break;

			case 'invoice_number':
				// Mostrar número de factura del plugin PDF (solo si no es columna PRO).
				$invoice_number = $order->get_meta( '_wcpdf_invoice_number' );
				if ( $invoice_number ) {
					echo esc_html( $invoice_number );
				} else {
					echo '&mdash;';
				}
				break;

			case 'invoice_date':
				// Show invoice date: check multiple sources to match what metabox shows.

				// First try: get from PDF document (what metabox displays).
				$display_date = '';
				if ( function_exists( 'wcpdf_get_document' ) ) {
					$invoice = wcpdf_get_document( 'invoice', $order );
					if ( $invoice && $invoice->exists() && $invoice->get_date() ) {
						$display_date = $invoice->get_date()->date_i18n( 'd-m-Y' );
					}
				}

				// Fallback: check standard meta field.
				if ( empty( $display_date ) ) {
					$invoice_date = $order->get_meta( '_wcpdf_invoice_date' );
					if ( $invoice_date ) {
						$date         = is_numeric( $invoice_date ) ? $invoice_date : strtotime( $invoice_date );
						$display_date = date_i18n( 'd-m-Y', $date );
					}
				}

				echo ! empty( $display_date ) ? esc_html( $display_date ) : '&mdash;';
				break;

			case 'invoice_number_column':
			case 'invoice_date_column':
				// No hacer nada para las columnas del plugin PRO, ya que ellas mismas manejan su contenido.
				break;
		}
	}

	/**
	 * Hacer las columnas personalizadas ordenables.
	 *
	 * @param array $columns Array de columnas ordenables.
	 * @return array
	 */
	public static function make_custom_order_columns_sortable( $columns ) {
		$columns['entregado_date'] = 'entregado_date';
		$columns['notes']          = 'notes';

		// Solo añadir nuestras columnas de factura si las del plugin PRO no existen.
		if ( ! isset( $columns['invoice_number_column'] ) ) {
			$columns['invoice_number'] = 'invoice_number';
		}
		if ( ! isset( $columns['invoice_date_column'] ) ) {
			$columns['invoice_date'] = 'invoice_date';
		}

		return $columns;
	}

	/**
	 * Ordenar pedidos por columnas personalizadas (interfaz clásica).
	 *
	 * @param WP_Query $query Query object.
	 */
	public static function sort_orders_by_custom_columns( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || 'shop_order' !== $query->get( 'post_type' ) ) {
			return;
		}

		$orderby = $query->get( 'orderby' );
		if ( 'entregado_date' === $orderby ) {
			// Order by _wcpdf_packing-slip_date as single source of truth.
			$query->set( 'meta_key', '_wcpdf_packing-slip_date' );
			$query->set( 'orderby', 'meta_value' );
		} elseif ( 'notes' === $orderby ) {
			$query->set( 'meta_key', '_wcpdf_invoice_notes' );
			$query->set( 'orderby', 'meta_value' );
		} elseif ( 'invoice_number' === $orderby ) {
			$query->set( 'meta_key', '_wcpdf_invoice_number' );
			$query->set( 'orderby', 'meta_value' );
		} elseif ( 'invoice_date' === $orderby ) {
			$query->set( 'meta_key', '_wcpdf_invoice_date' );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}

	/**
	 * Ajustar argumentos de consulta para la nueva interfaz HPOS.
	 *
	 * @param array $args Argumentos de consulta.
	 * @return array
	 */
	public static function hpos_adjust_query_args_for_custom_columns( $args ) {
		if ( 'entregado_date' === $args['orderby'] ) {
			$args['meta_query'] = array(
				'packing_slip_clause' => array(
					'key'     => '_wcpdf_packing-slip_date',
					'compare' => 'EXISTS',
				),
			);
			$args['orderby']    = array(
				'packing_slip_clause' => 'DESC',
			);
		} elseif ( 'notes' === $args['orderby'] ) {
			$args['meta_query'] = array(
				'notes' => array(
					'key'     => '_wcpdf_invoice_notes',
					'compare' => 'EXISTS',
				),
			);
			$args['orderby']    = 'meta_value';
		} elseif ( 'invoice_number' === $args['orderby'] ) {
			$args['meta_query'] = array(
				'invoice_number' => array(
					'key'     => '_wcpdf_invoice_number',
					'compare' => 'EXISTS',
				),
			);
			$args['orderby']    = 'meta_value';
		} elseif ( 'invoice_date' === $args['orderby'] ) {
			$args['meta_query'] = array(
				'invoice_date' => array(
					'key'     => '_wcpdf_invoice_date',
					'compare' => 'EXISTS',
				),
			);
			$args['orderby']    = 'meta_value_num';
		}
		return $args;
	}

	/**
	 * Configurar columnas por defecto visibles (ambas columnas visibles por defecto).
	 *
	 * @param array  $hidden Array de columnas ocultas por defecto.
	 * @param string $screen Screen ID.
	 * @return array
	 */
	public static function set_default_hidden_columns( $hidden, $screen ) {
		// No ocultar las columnas por defecto, mantenerlas visibles.
		return $hidden;
	}

	/**
	 * Modificar campo de notas de cliente en checkout para hacerlo opcional.
	 *
	 * @param array $fields Array de campos de checkout.
	 * @return array
	 */
	public static function modify_checkout_order_notes_field( $fields ) {
		if ( isset( $fields['order']['order_comments'] ) ) {
			$fields['order']['order_comments']['required']    = false;
			$fields['order']['order_comments']['label']       = __( 'Notas del pedido', 'palafito-wc-extensions' );
			$fields['order']['order_comments']['placeholder'] = __( 'Notas sobre tu pedido, por ejemplo, notas especiales para la entrega.', 'palafito-wc-extensions' );
		}
		return $fields;
	}

	/**
	 * 🔧 ENSURE PDF PLUGIN SETTINGS ARE CONFIGURED CORRECTLY
	 *
	 * This function ensures that the PDF plugin's display settings are configured
	 * to show the invoice date and packing slip elements in templates.
	 * It forces the correct settings for document display.
	 */
	public static function ensure_pdf_display_settings() {
		// Only proceed if the PDF plugin is available.
		if ( ! function_exists( 'wcpdf_get_document' ) ) {
			return;
		}

		// Force invoice settings to show date and number.
		add_filter( 'option_wpo_wcpdf_documents_settings_invoice', array( __CLASS__, 'force_invoice_display_settings' ) );

		// Force packing slip settings to show date.
		add_filter( 'option_wpo_wcpdf_documents_settings_packing-slip', array( __CLASS__, 'force_packing_slip_display_settings' ) );

		// 🎯 TITLES NOW HARDCODED IN TEMPLATES AT CORRECT POSITIONS.
		// Removed hooks as they were causing positioning issues.

		// Log that settings have been enforced.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[PALAFITO] PDF display settings enforced for invoice and packing slip templates.' );
		}
	}

	/**
	 * Force invoice display settings to show date and number.
	 *
	 * @param array $settings Current invoice settings.
	 * @return array Modified settings with display options enabled.
	 */
	public static function force_invoice_display_settings( $settings ) {
		// Ensure settings is an array.
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		// Force display_date to 'document_date' (Invoice Date).
		$settings['display_date'] = 'document_date';

		// Force display_number to 'invoice_number'.
		$settings['display_number'] = 'invoice_number';

		return $settings;
	}

	/**
	 * Force packing slip display settings to show date.
	 *
	 * @param array $settings Current packing slip settings.
	 * @return array Modified settings with display options enabled.
	 */
	public static function force_packing_slip_display_settings( $settings ) {
		// Ensure settings is an array.
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		// Force display_date to be enabled (checkbox style).
		$settings['display_date'] = 1;

		// Force display_number to 'order_number' (as configured in sync function).
		$settings['display_number'] = 'order_number';

		return $settings;
	}

	/**
	 * 🎯 ADD CUSTOM TITLES TO PDF TEMPLATES - DIFFERENT POSITIONS PER DOCUMENT TYPE
	 *
	 * This function adds the custom titles for invoice and packing slip templates
	 * that were missing due to plugin conflicts.
	 *
	 * @param string $document_type The document type (invoice, packing-slip, etc.).
	 * @param object $order The WooCommerce order object.
	 */
	public static function add_custom_order_details_titles( $document_type, $order ) {
		// Add title for invoice documents.
		if ( 'invoice' === $document_type ) {
			echo '<h3>Detalles de factura:</h3>';
		}

		// Add title for packing slip documents.
		if ( 'packing-slip' === $document_type ) {
			echo '<h3>Detalles de albarán:</h3>';
		}

		// Log the title addition for debugging.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "[PALAFITO] Added custom title for {$document_type} document in order {$order->get_id()}" );
		}
	}

	/**
	 * 🎯 ADD CUSTOM TITLES TO PDF TEMPLATES - DIFFERENT POSITIONS PER DOCUMENT TYPE
	 *
	 * This function adds the custom titles for invoice and packing slip templates
	 * that were missing due to plugin conflicts.
	 *
	 * @param string $document_type The document type (invoice, packing-slip, etc.).
	 * @param object $order The WooCommerce order object.
	 */
	public static function add_invoice_title_only( $document_type, $order ) {
		// Add title for invoice documents.
		if ( 'invoice' === $document_type ) {
			echo '<h3>Detalles de factura:</h3>';
		}

		// Log the title addition for debugging.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "[PALAFITO] Added custom title for invoice document in order {$order->get_id()}" );
		}
	}

	/**
	 * 🎯 ADD CUSTOM TITLES TO PDF TEMPLATES - DIFFERENT POSITIONS PER DOCUMENT TYPE
	 *
	 * This function adds the custom titles for invoice and packing slip templates
	 * that were missing due to plugin conflicts.
	 *
	 * @param string $document_type The document type (invoice, packing-slip, etc.).
	 * @param object $order The WooCommerce order object.
	 */
	public static function add_packing_slip_title_only( $document_type, $order ) {
		// Add title for packing slip documents.
		if ( 'packing-slip' === $document_type ) {
			echo '<h3>Detalles de albarán:</h3>';
		}

		// Log the title addition for debugging.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "[PALAFITO] Added custom title for packing slip document in order {$order->get_id()}" );
		}
	}

	/**
	 * 🎯 CRITICAL: Block WooCommerce PDF PRO automatic generation for packing slips.
	 *
	 * This function blocks the WooCommerce PDF PRO plugin from automatically
	 * generating packing slips for orders that are not yet 'entregado'.
	 * It runs with priority 5, BEFORE the PRO plugin (priority 7).
	 *
	 * @param int      $order_id Order ID.
	 * @param string   $old_status Old status.
	 * @param string   $new_status New status.
	 * @param WC_Order $order Order object.
	 */
	public static function block_automatic_packing_slip_generation( $order_id, $old_status, $new_status, $order ) {
		// Define allowed statuses for automatic packing slip generation.
		$allowed_statuses = array( 'entregado', 'facturado', 'completed' );

		// Only prevent packing slip generation for non-allowed statuses.
		if ( ! in_array( $new_status, $allowed_statuses, true ) ) {
			// Block PRO plugin from running by temporarily removing its hook.
			if ( class_exists( 'WPO_WCPDF_Pro_Functions' ) && WPO_WCPDF_Pro()->functions ) {
				remove_action( 'woocommerce_order_status_changed', array( WPO_WCPDF_Pro()->functions, 'generate_documents_on_order_status' ), 7, 4 );

				// Schedule re-adding the hook after our processing is complete.
				add_action(
					'shutdown',
					function () {
						if ( class_exists( 'WPO_WCPDF_Pro_Functions' ) && WPO_WCPDF_Pro()->functions ) {
							add_action( 'woocommerce_order_status_changed', array( WPO_WCPDF_Pro()->functions, 'generate_documents_on_order_status' ), 7, 4 );
						}
					},
					5
				);
			}

			// Log the prevention for debugging.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "[PALAFITO] BLOCKED: Prevented automatic packing slip generation for order {$order_id} status change from '{$old_status}' to '{$new_status}' (not in allowed list)" );
			}
		} elseif ( in_array( $new_status, array( 'facturado', 'completed' ), true ) ) {
			// For 'facturado' and 'completed', check if packing slip date already exists.
			$existing_date = $order->get_meta( '_wcpdf_packing-slip_date' );
			if ( ! empty( $existing_date ) ) {
				// Block generation if date already exists.
				if ( class_exists( 'WPO_WCPDF_Pro_Functions' ) && WPO_WCPDF_Pro()->functions ) {
					remove_action( 'woocommerce_order_status_changed', array( WPO_WCPDF_Pro()->functions, 'generate_documents_on_order_status' ), 7, 4 );

					// Schedule re-adding the hook after our processing is complete.
					add_action(
						'shutdown',
						function () {
							if ( class_exists( 'WPO_WCPDF_Pro_Functions' ) && WPO_WCPDF_Pro()->functions ) {
								add_action( 'woocommerce_order_status_changed', array( WPO_WCPDF_Pro()->functions, 'generate_documents_on_order_status' ), 7, 4 );
							}
						},
						5
					);
				}

				// Log the prevention for debugging.
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( "[PALAFITO] BLOCKED: Prevented automatic packing slip generation for order {$order_id} status change to '{$new_status}' because packing slip date already exists" );
				}
			} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// Allow generation for 'facturado'/'completed' when no date exists.
				error_log( "[PALAFITO] ALLOWED: Automatic packing slip generation for order {$order_id} status change to '{$new_status}' (no existing date)" );
			}
		} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// Allow generation for 'entregado' always.
			error_log( "[PALAFITO] ALLOWED: Automatic packing slip generation for order {$order_id} status change to 'entregado'" );
		}
	}

	/**
	 * 🎯 Filter PRO plugin auto-generation configuration for packing slips.
	 *
	 * This function modifies the PRO plugin's configuration to ensure that
	 * packing slips are NEVER generated automatically for non-allowed statuses.
	 * Only 'entregado' always, and 'facturado'/'completed' conditionally.
	 *
	 * @param array $status Array of statuses that trigger PDF generation.
	 * @param array $documents Array of document objects.
	 * @return array Modified array with filtered statuses.
	 */
	public static function filter_pro_auto_generation_statuses( $status, $documents ) {
		// Define ONLY allowed statuses for automatic packing slip generation.
		$allowed_statuses = array( 'wc-entregado', 'wc-facturado', 'wc-completed' );

		// COMPLETELY remove packing-slip from ALL non-allowed statuses.
		foreach ( $status as $order_status => $document_types ) {
			if ( ! in_array( $order_status, $allowed_statuses, true ) ) {
				// Remove 'packing-slip' from document types for this status.
				$key = array_search( 'packing-slip', $document_types, true );
				if ( false !== $key ) {
					unset( $status[ $order_status ][ $key ] );

					// Re-index the array.
					$status[ $order_status ] = array_values( $status[ $order_status ] );

					// If no documents left for this status, remove the status entirely.
					if ( empty( $status[ $order_status ] ) ) {
						unset( $status[ $order_status ] );
					}
				}
			}
		}

		// Log the filtering for debugging.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[PALAFITO] AGGRESSIVE FILTER: Removed packing-slip auto-generation from non-allowed statuses. Remaining: ' . print_r( $status, true ) );
		}

		return $status;
	}

	/**
	 * Override document settings to block auto-generation for packing slips.
	 *
	 * @param array  $settings Current document store settings.
	 * @param string $document_type The document type (invoice, packing-slip, etc.).
	 * @return array Modified settings.
	 */
	public static function override_packing_slip_auto_generation_settings( $settings, $document_type ) {
		if ( 'packing-slip' === $document_type ) {
			// Force auto-generation to be disabled.
			$settings['auto_generate'] = 0;
		}
		return $settings;
	}

	/**
	 * Clean packing slip auto-generation configuration.
	 *
	 * This function ensures that the 'auto_generate_for_statuses' option for packing slips
	 * is ONLY set for allowed statuses: 'entregado', 'facturado', and 'completed'.
	 * It removes any non-allowed statuses but DOES NOT force-enable allowed ones.
	 *
	 * @param array $options The current options array.
	 * @return array Modified options array.
	 */
	public static function clean_packing_slip_auto_generation_option( $options ) {
		// Ensure 'auto_generate_for_statuses' is an array.
		if ( ! isset( $options['auto_generate_for_statuses'] ) || ! is_array( $options['auto_generate_for_statuses'] ) ) {
			$options['auto_generate_for_statuses'] = array();
		}

		// Define allowed statuses for auto-generation.
		$allowed_statuses = array( 'wc-entregado', 'wc-facturado', 'wc-completed' );

		// Remove any non-allowed statuses (especially wc-processing, wc-on-hold).
		foreach ( $options['auto_generate_for_statuses'] as $status => $enabled ) {
			if ( ! in_array( $status, $allowed_statuses, true ) ) {
				unset( $options['auto_generate_for_statuses'][ $status ] );
			}
		}

		// DO NOT force-enable any status - let admin control this manually.
		// This prevents unwanted automatic generation.

		return $options;
	}

	/**
	 * Block document creation in non-entregado states.
	 *
	 * @param bool   $is_allowed Whether document creation is allowed.
	 * @param object $document The document object.
	 * @return bool Modified allowed status.
	 */
	public static function block_packing_slip_in_non_entregado_states( $is_allowed, $document ) {
		// Only apply to packing slip documents.
		if ( ! $document || 'packing-slip' !== $document->get_type() ) {
			return $is_allowed;
		}

		// Get the order from the document.
		$order = $document->order;
		if ( ! $order ) {
			return $is_allowed;
		}

		$order_status = $order->get_status();

		// Define statuses where automatic generation is allowed.
		$auto_allowed_statuses = array( 'entregado', 'facturado', 'completed' );

		// Check if this is a manual action (admin area with direct user interaction).
		$is_manual_action = is_admin() && ! wp_doing_ajax() && ! defined( 'DOING_CRON' );

		// For manual actions in admin, allow for processing+ statuses.
		if ( $is_manual_action ) {
			$manual_allowed_statuses = array( 'processing', 'on-hold', 'entregado', 'facturado', 'completed' );
			if ( in_array( $order_status, $manual_allowed_statuses, true ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( "[PALAFITO] ALLOWED MANUAL: Manual packing slip creation allowed for order {$order->get_id()} in status '{$order_status}'" );
				}
				return $is_allowed;
			}
		}

		// For automatic actions, only allow in auto_allowed_statuses.
		if ( in_array( $order_status, $auto_allowed_statuses, true ) ) {
			// For 'facturado' and 'completed', check if packing slip date already exists.
			if ( in_array( $order_status, array( 'facturado', 'completed' ), true ) ) {
				$existing_date = $order->get_meta( '_wcpdf_packing-slip_date' );
				if ( ! empty( $existing_date ) ) {
					// Block automatic creation if date already exists.
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( "[PALAFITO] BLOCKED AUTO: Prevented automatic packing slip creation for order {$order->get_id()} in status '{$order_status}' (date already exists)" );
					}
					return false;
				}
			}

			// Allow automatic creation for 'entregado' always, and for 'facturado'/'completed' when no date exists.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "[PALAFITO] ALLOWED AUTO: Automatic packing slip creation allowed for order {$order->get_id()} in status '{$order_status}'" );
			}
			return $is_allowed;
		}

		// Block everything else (automatic actions in non-allowed statuses).
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "[PALAFITO] BLOCKED: Prevented packing slip creation for order {$order->get_id()} in status '{$order_status}' (not in allowed list)" );
		}
		return false;
	}

	/**
	 * Block auto-generation at the source.
	 *
	 * This function blocks the WooCommerce PDF PRO plugin from automatically
	 * generating packing slips for orders in non-allowed statuses.
	 * It runs with priority 5 to intercept early.
	 *
	 * @param bool   $auto_generate Whether auto-generation is enabled.
	 * @param string $document_type The document type (invoice, packing-slip, etc.).
	 * @param object $order The WooCommerce order object.
	 * @return bool Modified auto-generation status.
	 */
	public static function block_auto_generation_aggressively( $auto_generate, $document_type, $order ) {
		// Only apply to packing slip documents.
		if ( 'packing-slip' !== $document_type ) {
			return $auto_generate;
		}

		// Define allowed statuses for automatic packing slip generation.
		$allowed_statuses = array( 'entregado', 'facturado', 'completed' );
		$order_status     = $order->get_status();

		// Block auto-generation for non-allowed statuses.
		if ( ! in_array( $order_status, $allowed_statuses, true ) ) {
			// Log the prevention for debugging.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "[PALAFITO] AGGRESSIVE BLOCK: Prevented automatic packing slip generation for order {$order->get_id()} in status '{$order_status}' (not in allowed list)" );
			}
			return false;
		}

		// For 'facturado' and 'completed', check if packing slip date already exists.
		if ( in_array( $order_status, array( 'facturado', 'completed' ), true ) ) {
			$existing_date = $order->get_meta( '_wcpdf_packing-slip_date' );
			if ( ! empty( $existing_date ) ) {
				// Block generation if date already exists.
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( "[PALAFITO] AGGRESSIVE BLOCK: Prevented automatic packing slip generation for order {$order->get_id()} in status '{$order_status}' (date already exists)" );
				}
				return false;
			}
		}

		// Allow generation for 'entregado' always, and for 'facturado'/'completed' when no date exists.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "[PALAFITO] AGGRESSIVE ALLOW: Automatic packing slip generation for order {$order->get_id()} in status '{$order_status}'" );
		}

		return $auto_generate;
	}

	/**
	 * Personalizar título de email "entregado" con códigos de cliente.
	 *
	 * @param string   $subject Email subject.
	 * @param WC_Order $order Order object.
	 * @return string Modified email subject.
	 */
	public static function customize_entregado_email_subject( $subject, $order ) {
		// Get customer notes from the order.
		$customer_note = $order->get_customer_note();

		if ( empty( $customer_note ) ) {
			return $subject;
		}

		// Extract customer codes (C + exactly 5 numbers) from notes.
		$codes = self::extract_customer_codes_from_notes( $customer_note );

		if ( ! empty( $codes ) ) {
			// Modify subject: "Tu pedido #2514 ha sido entregado" → "Tu pedido #2514 / C00303 ha sido entregado".
			$codes_string = implode( ' ', $codes );
			$subject      = str_replace(
				'ha sido entregado',
				'/ ' . $codes_string . ' ha sido entregado',
				$subject
			);
		}

		return $subject;
	}

	/**
	 * Extract customer codes (CXXXXX format) from order notes.
	 *
	 * Supports patterns:
	 * - "Feria: C00303 - RBF - Benidorm" → C00303
	 * - "Obrador: C02388" → C02388
	 * - "C12345" → C12345
	 *
	 * @param string $notes Order notes text.
	 * @return array Array of found customer codes.
	 */
	public static function extract_customer_codes_from_notes( $notes ) {
		$codes = array();

		// Regex pattern: C followed by exactly 5 digits.
		$pattern = '/C\d{5}/';

		// Find all matches.
		if ( preg_match_all( $pattern, $notes, $matches ) ) {
			$codes = $matches[0]; // Get the full matches (C12345).
		}

		// Remove duplicates and return.
		return array_unique( $codes );
	}

	/**
	 * ULTRA AGGRESSIVE: Completely disable PRO hooks for packing slips in non-allowed statuses.
	 *
	 * This function completely removes the PRO plugin's document generation hook
	 * to prevent ANY automatic packing slip generation in non-allowed statuses.
	 */
	public static function ultra_aggressive_pro_packing_slip_block() {
		// Only proceed if PRO plugin is active.
		if ( ! class_exists( 'WPO_WCPDF_Pro_Functions' ) || ! WPO_WCPDF_Pro()->functions ) {
			return;
		}

		// COMPLETELY remove the PRO plugin's main document generation hook.
		remove_action( 'woocommerce_order_status_changed', array( WPO_WCPDF_Pro()->functions, 'generate_documents_on_order_status' ), 7 );

		// Add our own custom hook that will ONLY generate documents for allowed statuses.
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'custom_pro_document_generation' ), 7, 4 );

		// Log the replacement for debugging.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[PALAFITO] ULTRA AGGRESSIVE: Replaced PRO document generation hook with custom controlled version.' );
		}
	}

	/**
	 * Custom PRO document generation that ONLY allows packing slips for allowed statuses.
	 *
	 * @param int      $order_id Order ID.
	 * @param string   $old_status Old status.
	 * @param string   $new_status New status.
	 * @param WC_Order $order Order object.
	 */
	public static function custom_pro_document_generation( $order_id, $old_status, $new_status, $order ) {
		// Define allowed statuses for automatic packing slip generation.
		$allowed_statuses = array( 'entregado', 'facturado', 'completed' );

		// For packing slips, ONLY allow generation in allowed statuses.
		if ( ! in_array( $new_status, $allowed_statuses, true ) ) {
			// Log the blocking for debugging.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "[PALAFITO] ULTRA BLOCK: Completely blocked PRO document generation for order {$order_id} status change to '{$new_status}'" );
			}
			return; // Exit early, don't generate ANY documents.
		}

		// For facturado/completed, check if packing slip already exists.
		if ( in_array( $new_status, array( 'facturado', 'completed' ), true ) ) {
			$existing_date = $order->get_meta( '_wcpdf_packing-slip_date' );
			if ( ! empty( $existing_date ) ) {
				// Log the blocking for debugging.
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( "[PALAFITO] ULTRA BLOCK: Blocked PRO packing slip generation for order {$order_id} in '{$new_status}' (date already exists)" );
				}
				return; // Exit early, don't generate packing slip.
			}
		}

		// Only if we reach here, call the original PRO function manually.
		if ( method_exists( WPO_WCPDF_Pro()->functions, 'generate_documents_on_order_status' ) ) {
			// Log the allowing for debugging.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "[PALAFITO] ULTRA ALLOW: Allowing PRO document generation for order {$order_id} status change to '{$new_status}'" );
			}
			WPO_WCPDF_Pro()->functions->generate_documents_on_order_status( $order_id, $old_status, $new_status, $order );
		}
	}

	/**
	 * Force disable auto-generation at document settings level.
	 *
	 * @param array  $settings Current document store settings.
	 * @param string $document_type The document type (invoice, packing-slip, etc.).
	 * @return array Modified settings.
	 */
	public static function force_disable_packing_slip_auto_generation( $settings, $document_type ) {
		if ( 'packing-slip' === $document_type ) {
			// Force auto-generation to be disabled.
			$settings['auto_generate'] = 0;
		}
		return $settings;
	}
}
