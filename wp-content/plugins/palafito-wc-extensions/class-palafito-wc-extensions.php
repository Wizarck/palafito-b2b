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
		add_filter( 'woocommerce_order_status_changed', array( __CLASS__, 'handle_custom_order_status_change' ), 10, 4 );

		// Disparar emails personalizados cuando cambien los estados.
		add_action( 'woocommerce_order_status_entregado', array( __CLASS__, 'trigger_entregado_email' ), 10, 2 );
		add_action( 'woocommerce_order_status_facturado', array( __CLASS__, 'trigger_facturado_email' ), 10, 2 );

		// Registrar emails personalizados.
		add_filter( 'woocommerce_email_classes', array( __CLASS__, 'add_custom_email_classes' ) );

		// Cargar estilos personalizados para colores de estados.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );

		// Hook para Kadence WooCommerce Email Designer.
		add_action( 'kadence_woomail_designer_email_details', array( $this, 'kadence_email_main_content' ), 10, 4 );
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

		// Guardar fecha de entrega cuando el pedido se marca como "entregado".
		if ( 'entregado' === $new_status ) {
			$order->update_meta_data( '_entregado_date', time() );
			$order->save_meta_data();
		}

		// Los emails se envían automáticamente por los hooks de WooCommerce.
		// No necesitamos disparar manualmente las acciones aquí.
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

		// Obtener el contenido principal del email.
		$main_content = '';

		if ( 'customer_entregado' === $email->id ) {
			$main_content = __( '¡Tu pedido ha sido entregado exitosamente! Nos complace informarte que tu pedido ha sido entregado. A continuación encontrarás un resumen completo de tu compra.', 'palafito-wc-extensions' );
		} elseif ( 'customer_facturado' === $email->id ) {
			$main_content = __( '¡Tu pedido ha sido facturado exitosamente! Nos complace informarte que tu pedido ha sido facturado. A continuación encontrarás un resumen completo de tu compra junto con tu factura adjunta.', 'palafito-wc-extensions' );
		}

		// Mostrar el contenido principal.
		if ( ! empty( $main_content ) ) {
			echo wp_kses_post( wpautop( wptexturize( $main_content ) ) );
		}
	}
}
