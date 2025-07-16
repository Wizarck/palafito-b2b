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

		// Permitir transiciones de estado personalizadas.
		// Priority 20 to ensure it runs AFTER other plugins and forces the update.
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'handle_custom_order_status_change' ), 20, 4 );

		// Active prevention: Block any attempts to set delivery date in non-entregado states.
		add_action( 'wpo_wcpdf_save_document', array( __CLASS__, 'prevent_premature_date_setting' ), 5, 2 );

		// Disparar emails personalizados cuando cambien los estados.
		add_action( 'woocommerce_order_status_entregado', array( __CLASS__, 'trigger_entregado_email' ), 10, 2 );
		add_action( 'woocommerce_order_status_facturado', array( __CLASS__, 'trigger_facturado_email' ), 10, 2 );

		// Registrar emails personalizados.
		add_filter( 'woocommerce_email_classes', array( __CLASS__, 'add_custom_email_classes' ) );

		// Cargar estilos personalizados para colores de estados.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );

		// Hook para Kadence WooCommerce Email Designer.
		add_action( 'kadence_woomail_designer_email_details', array( $this, 'kadence_email_main_content' ), 10, 4 );

		// Columnas personalizadas para la tabla de pedidos.
		add_filter( 'manage_edit-shop_order_columns', array( __CLASS__, 'add_custom_order_columns' ), 20 );
		add_action( 'manage_shop_order_posts_custom_column', array( __CLASS__, 'custom_order_columns_data' ), 10, 2 );
		add_filter( 'manage_edit-shop_order_sortable_columns', array( __CLASS__, 'add_custom_order_sortable_columns' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'sort_orders_by_custom_columns' ) );

		// Columnas para nueva interfaz HPOS.
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( __CLASS__, 'add_custom_order_columns' ), 20 );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( __CLASS__, 'custom_order_columns_data' ), 10, 2 );
		add_filter( 'manage_woocommerce_page_wc-orders_sortable_columns', array( __CLASS__, 'add_custom_order_sortable_columns' ) );
		add_filter( 'woocommerce_shop_order_list_table_sortable_columns', array( __CLASS__, 'add_custom_order_sortable_columns' ) );
		add_filter( 'woocommerce_order_list_table_prepare_items_query_args', array( __CLASS__, 'adjust_order_list_query_args' ) );

		// Configurar columnas por defecto visibles.
		add_filter( 'default_hidden_columns', array( __CLASS__, 'set_default_hidden_columns' ), 10, 2 );

		// Recuperar campo de notas de cliente en checkout.
		add_filter( 'woocommerce_enable_order_notes_field', '__return_true' );
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

		// Update delivery date when order status changes to "entregado".
		// Only update if NOT coming from "facturado" or "completado" states.
		if ( 'entregado' === $new_status ) {
			// Define states that should NOT trigger date update.
			$excluded_previous_states = array( 'facturado', 'completado', 'completed' );

			// Check if the previous state should be excluded.
			if ( ! in_array( $old_status, $excluded_previous_states, true ) ) {
				// Get current value from the STANDARD field (with dash).
				$previous_value = $order->get_meta( '_wcpdf_packing-slip_date' );

				// Force update with current timestamp to ensure COMPLETE overwrite.
				$current_timestamp = current_time( 'timestamp' );

				// Update ONLY the standard field: _wcpdf_packing-slip_date (with dash).
				// This is our single source of truth as documented in CLAUDE.md.
				$order->delete_meta_data( '_wcpdf_packing-slip_date' );
				$order->update_meta_data( '_wcpdf_packing-slip_date', $current_timestamp );

				// Clean up any legacy field without dash to avoid confusion.
				$order->delete_meta_data( '_wcpdf_packing_slip_date' );

				// Save all meta changes.
				$order->save_meta_data();

				// Direct database update as fallback for the standard field.
				update_post_meta( $order_id, '_wcpdf_packing-slip_date', $current_timestamp );

				// Also clean up legacy field in database.
				delete_post_meta( $order_id, '_wcpdf_packing_slip_date' );

				// Verify the update was successful.
				$verified_value = $order->get_meta( '_wcpdf_packing-slip_date' );

				// Enhanced logging for debugging.
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '=== PALAFITO DELIVERY DATE UPDATE ===' );
					error_log( "Order {$order_id}: {$old_status} → {$new_status}" );
					error_log( 'Previous value: ' . ( $previous_value ? gmdate( 'Y-m-d H:i:s', $previous_value ) : 'EMPTY' ) );
					error_log( "New timestamp: {$current_timestamp} (" . gmdate( 'Y-m-d H:i:s', $current_timestamp ) . ')' );
					error_log( 'Verified value: ' . ( $verified_value ? gmdate( 'Y-m-d H:i:s', $verified_value ) : 'FAILED' ) );
					error_log( 'Update successful: ' . ( $verified_value == $current_timestamp ? 'YES' : 'NO' ) );
					error_log( 'Standard field: _wcpdf_packing-slip_date (single source of truth)' );
					error_log( '=========================================' );
				}
			} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// Log when update is skipped due to excluded previous state.
				error_log( "Palafito WC Extensions: Skipped date update for Order {$order_id} - previous state '{$old_status}' is excluded" );
			}
		}
	}

	/**
	 * Prevent any attempts to set delivery date in non-entregado states.
	 *
	 * This function actively blocks the PDF plugin from setting packing slip dates
	 * when orders are in states other than 'entregado'.
	 *
	 * @param object $document The PDF document object.
	 * @param object $order The WooCommerce order object.
	 */
	public static function prevent_premature_date_setting( $document, $order ) {
		// Only act on packing slip documents.
		if ( $document && $document->get_type() === 'packing-slip' ) {
			$order_status = $order->get_status();

			// If order is NOT in 'entregado' state, prevent date setting.
			if ( 'entregado' !== $order_status ) {
				// Clear any date that might have been set.
				if ( method_exists( $document, 'set_date' ) ) {
					$document->set_date( null );
				}

				// Also ensure meta field stays empty (standard field only).
				$order->delete_meta_data( '_wcpdf_packing-slip_date' );
				$order->save_meta_data();
				delete_post_meta( $order->get_id(), '_wcpdf_packing-slip_date' );

				// Clean up legacy field as well.
				delete_post_meta( $order->get_id(), '_wcpdf_packing_slip_date' );

				// Log the prevention for debugging.
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( "[PALAFITO] BLOCKED: Prevented date setting for order {$order->get_id()} in status '{$order_status}' (only 'entregado' allowed)" );
				}
			}
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
				// Only show delivery date if order is in 'entregado' status or later.
				$order_status = $order->get_status();

				// Define valid statuses that should show delivery date.
				$valid_statuses = array( 'entregado', 'facturado', 'completed' );

				if ( in_array( $order_status, $valid_statuses, true ) ) {
					// Use only _wcpdf_packing-slip_date as single source of truth.
					$entregado_date = $order->get_meta( '_wcpdf_packing-slip_date' );

					if ( $entregado_date ) {
						$date = is_numeric( $entregado_date ) ? $entregado_date : strtotime( $entregado_date );
						// Format as d-m-Y as specified in requirements.
						echo esc_html( date_i18n( 'd-m-Y', $date ) );
					} else {
						echo '&mdash;';
					}
				} else {
					// Order is not in valid status, don't show any date.
					echo '&mdash;';
				}
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
				// Mostrar fecha de factura del plugin PDF (solo si no es columna PRO).
				$invoice_date = $order->get_meta( '_wcpdf_invoice_date' );
				if ( $invoice_date ) {
					$date = is_numeric( $invoice_date ) ? $invoice_date : strtotime( $invoice_date );
					echo esc_html( date_i18n( 'd/m/Y', $date ) );
				} else {
					echo '&mdash;';
				}
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
	public static function add_custom_order_sortable_columns( $columns ) {
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
	public static function adjust_order_list_query_args( $args ) {
		if ( 'entregado_date' === $args['orderby'] ) {
			$args['meta_query'] = array(
				'relation'            => 'OR',
				'entregado_clause'    => array(
					'key'     => '_entregado_date',
					'compare' => 'EXISTS',
				),
				'packing_slip_clause' => array(
					'key'     => '_wcpdf_packing-slip_date',
					'compare' => 'EXISTS',
				),
			);
			$args['orderby']    = array(
				'entregado_clause'    => 'DESC',
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
}
