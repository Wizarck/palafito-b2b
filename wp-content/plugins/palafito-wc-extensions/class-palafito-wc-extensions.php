<?php
/**
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
		// Log initialization for debugging.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Palafito WC Extensions: Plugin initialized' );
		}

		// Registrar nuevos estados personalizados de pedido.
		add_filter( 'woocommerce_register_shop_order_post_statuses', array( $this, 'register_custom_order_statuses' ) );
		add_filter( 'wc_order_statuses', array( $this, 'add_custom_order_statuses_to_list' ) );
		add_filter( 'bulk_actions-edit-shop_order', array( $this, 'add_custom_order_statuses_to_bulk_actions' ) );
		// Registrar post status personalizados en el hook init.
		add_action( 'init', array( $this, 'register_custom_post_statuses' ) );
	}

	/**
	 * Registrar los post status personalizados de pedido en WordPress.
	 */
	public function register_custom_post_statuses() {
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
	public function register_custom_order_statuses( $order_statuses ) {
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
	 * Añadir los nuevos estados personalizados a la lista de estados de WooCommerce.
	 *
	 * @param array $order_statuses Array de estados de pedido.
	 * @return array
	 */
	public function add_custom_order_statuses_to_list( $order_statuses ) {
		// Insertar 'entregado' después de 'processing'.
		$new_order_statuses = array();
		foreach ( $order_statuses as $key => $label ) {
			$new_order_statuses[ $key ] = $label;
			if ( 'wc-processing' === $key ) {
				$new_order_statuses['wc-entregado'] = _x( 'Entregado', 'Order status', 'palafito-wc-extensions' );
			}
			if ( 'wc-entregado' === $key ) {
				$new_order_statuses['wc-facturado'] = _x( 'Facturado', 'Order status', 'palafito-wc-extensions' );
			}
		}
		return $new_order_statuses;
	}

	/**
	 * Añadir los nuevos estados personalizados a las acciones masivas del admin.
	 *
	 * @param array $bulk_actions Acciones masivas disponibles.
	 * @return array
	 */
	public function add_custom_order_statuses_to_bulk_actions( $bulk_actions ) {
		$bulk_actions['mark_entregado'] = __( 'Cambiar a Entregado', 'palafito-wc-extensions' );
		$bulk_actions['mark_facturado'] = __( 'Cambiar a Facturado', 'palafito-wc-extensions' );
		return $bulk_actions;
	}

	/**
	 * Load plugin classes.
	 */
	private function load_classes() {
		// Load plugin classes.
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-palafito-checkout-customizations.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/plugin-hooks.php';
	}

	/**
	 * Initialize plugin components.
	 */
	private function init_components() {
		// Initialize checkout customizations.
		new Palafito_Checkout_Customizations();
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
}
