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
		add_filter( 'bulk_actions-edit-shop_order', array( __CLASS__, 'add_custom_order_statuses_to_bulk_actions' ) );
		// Manejar acciones masivas de estados personalizados.
		add_filter( 'handle_bulk_actions-edit-shop_order', array( __CLASS__, 'handle_bulk_order_status_actions' ), 10, 3 );
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

		// Cargar estilos personalizados para colores de estados.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
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

		// Trigger custom actions for our custom statuses.
		if ( 'entregado' === $new_status ) {
			do_action( 'woocommerce_order_status_entregado', $order_id, $old_status, $new_status, $order );
		} elseif ( 'facturado' === $new_status ) {
			do_action( 'woocommerce_order_status_facturado', $order_id, $old_status, $new_status, $order );
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
			require_once plugin_dir_path( __FILE__ ) . 'includes/class-palafito-admin-pdf-actions.php';
			require_once plugin_dir_path( __FILE__ ) . 'includes/class-palafito-email-attachments.php';
			require_once plugin_dir_path( __FILE__ ) . 'includes/class-palafito-packing-slip-settings.php';
		}
	}

	/**
	 * Initialize plugin components.
	 */
	private function init_components() {
		// Initialize checkout customizations.
		new Palafito_Checkout_Customizations();

		// Initialize email attachments if PDF plugin is available.
		if ( class_exists( 'WPO_WCPDF' ) ) {
			new Palafito_Email_Attachments();
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
}
