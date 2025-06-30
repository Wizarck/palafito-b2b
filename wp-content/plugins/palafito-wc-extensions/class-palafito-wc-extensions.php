<?php
/**
 * Plugin Name: Palafito WC Extensions
 * Plugin URI: https://github.com/wizarck/palafito-b2b
 * Description: Personalizaciones funcionales de WooCommerce para el proyecto Palafito B2B.
 * Version: 1.0.0
 * Author: Arturo Ramirez
 * Author URI: https://github.com/wizarck
 * Text Domain: palafito-wc-extensions
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Palafito_WC_Extensions
 * @version 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase principal del plugin.
 *
 * Maneja la inicialización y gestión del plugin Palafito WC Extensions.
 */
final class Palafito_WC_Extensions {

	/**
	 * Instancia única del plugin.
	 *
	 * @var Palafito_WC_Extensions
	 */
	private static $instance = null;

	/**
	 * Obtener instancia única del plugin.
	 *
	 * @return Palafito_WC_Extensions
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor privado.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize plugin hooks.
	 */
	private function init_hooks() {
		// Log initialization for debugging.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Palafito WC Extensions: Plugin initialized' );
		}
	}

	/**
	 * Inicializar el plugin.
	 */
	public function init() {
		// Verificar que WooCommerce esté activo.
		if ( ! $this->is_woocommerce_active() ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		// Cargar funcionalidades.
		$this->load_features();

		// Log de carga exitosa solo en desarrollo.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[Palafito WC Extensions] Plugin cargado correctamente v' . PALAFITO_WC_EXTENSIONS_VERSION );
		}
	}

	/**
	 * Verificar si WooCommerce está activo.
	 *
	 * @return bool
	 */
	private function is_woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Aviso si WooCommerce no está activo.
	 */
	public function woocommerce_missing_notice() {
		echo '<div class="notice notice-error"><p>';
		echo '<strong>Palafito WC Extensions</strong> requiere que WooCommerce esté instalado y activado.';
		echo '</p></div>';
	}

	/**
	 * Cargar funcionalidades del plugin.
	 */
	private function load_features() {
		// Cargar archivos de funcionalidades.
		$this->load_includes();

		// Inicializar clases principales.
		$this->init_classes();

		// Inicializar hooks de WooCommerce.
		$this->init_woocommerce_hooks();
	}

	/**
	 * Cargar archivos incluidos.
	 */
	private function load_includes() {
		// Cargar clases y funciones.
		$includes_dir = PALAFITO_WC_EXTENSIONS_PLUGIN_DIR . 'includes/';

		if ( is_dir( $includes_dir ) ) {
			$files = array(
				$includes_dir . 'class-checkout-customizations.php',
				$includes_dir . 'class-activation.php',
				$includes_dir . 'class-deactivation.php',
			);
			foreach ( $files as $file ) {
				if ( file_exists( $file ) ) {
					require_once $file;
				}
			}
		}

		// Load plugin classes.
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-palafito-checkout-customizations.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-palafito-b2b-pricing.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/plugin-hooks.php';
	}

	/**
	 * Inicializar clases principales.
	 */
	private function init_classes() {
		// Inicializar personalizaciones de checkout.
		new Palafito_Checkout_Customizations();
	}

	/**
	 * Inicializar hooks de WooCommerce.
	 */
	private function init_woocommerce_hooks() {
		// Hooks de checkout.
		add_action( 'woocommerce_before_checkout_form', array( $this, 'custom_checkout_notice' ) );

		// Hooks de pedidos.
		add_action( 'woocommerce_order_status_changed', array( $this, 'handle_order_status_change' ), 10, 4 );
	}

	/**
	 * Cargar archivos de traducción.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'palafito-wc-extensions',
			false,
			dirname( plugin_basename( PALAFITO_WC_EXTENSIONS_PLUGIN_FILE ) ) . '/languages'
		);
	}

	/**
	 * Aviso personalizado en checkout.
	 */
	public function custom_checkout_notice() {
		if ( is_checkout() ) {
			echo '<div class="woocommerce-info palafito-checkout-notice">';
			echo '<p><strong>' . esc_html__( '¡Bienvenido a Palafito B2B!', 'palafito-wc-extensions' ) . '</strong> ';
			echo esc_html__( 'Disfruta de nuestros precios especiales para mayoristas.', 'palafito-wc-extensions' ) . '</p>';
			echo '</div>';
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
}
