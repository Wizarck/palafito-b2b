<?php

namespace Vibe\Merge_Orders;

use Vibe\Merge_Orders\Addons\Subscriptions;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Main plugin class, responsible for initialising the plugin
 *
 * @since 1.0.0
 */
final class Merge_Orders {

	/**
	 * The single instance of the class
	 *
	 * @var Merge_Orders
	 */
	private static $instance;

	/**
	 * Current version of the plugin
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Path to the plugin
	 *
	 * @var string
	 */
	private $path;

	/**
	 * URI of the plugin
	 *
	 * @var string
	 */
	private $uri;

	/**
	 * Array of required core classes
	 *
	 * @var array
	 */
	private $core = array();

	/**
	 * Returns the singleton instance of this class
	 *
	 * @return Merge_Orders The singleton instance
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor to prevent multiple instantiation.
	 */
	private function __construct() {
		$this->version = defined( 'VIBE_MERGE_ORDERS_VERSION' ) ? VIBE_MERGE_ORDERS_VERSION : '';

		$parent = plugin_dir_path( __FILE__ );

		$this->path = plugin_dir_path( $parent );
		$this->uri  = plugin_dir_url( $parent );

		$this->init_core();

		add_action( 'init', array( __CLASS__, 'load_textdomain' ) );

		/**
		 * Plugin loaded
		 */
		do_action( self::hook_prefix( 'loaded' ) );
	}

	/**
	 * Constructs required core classes
	 */
	private function init_core() {
		$this->core[ Admin::class ]    = new Admin();
		$this->core[ AJAX::class ]     = new AJAX();
		$this->core[ Logger::class ]   = new Logger();
		$this->core[ Orders::class ]   = new Orders();
		$this->core[ Settings::class ] = new Settings();

		// Ensure plugin functions are loaded before checking plugin activation
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		if ( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ) {
			$this->core[ Subscriptions::class ] = new Subscriptions();
		}
	}

	/**
	 * Loads the plugin's text domain for translation
	 */
	public static function load_textdomain() {
		load_plugin_textdomain( 'merge-orders', false, self::instance()->path( 'languages' ) );
	}

	/**
	 * Returns the current version of the plugin
	 *
	 * @return string Version number of the plugin
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Returns the path to the plugin with the given path appended
	 *
	 * @param string $append Optional string to append to the path without leading slash
	 *
	 * @return string The path to the plugin with provided path appended
	 */
	public function path( $append = '' ) {
		if ( $append ) {
			return trailingslashit( $this->path ) . $append;
		}

		return $this->path;
	}

	/**
	 * Returns the URI of the plugin with the given path appended
	 *
	 * @param string $append Optional string to append to the URI without leading slash
	 *
	 * @return string The URI of the plugin
	 */
	public function uri( $append = '' ) {
		if ( $append ) {
			return trailingslashit( $this->uri ) . $append;
		}

		return $this->uri;
	}

	/**
	 * Returns the requested core class instances
	 *
	 * @param string $name The name of the core class instance to return
	 *
	 * @return object The requested core class instance or null if it does not exist
	 */
	public function __get( $name ) {
		return isset( $this->core[ $name ] ) ? $this->core[ $name ] : null;
	}

	/**
	 * Appends the plugin's hook prefix to the given hook name
	 *
	 * @param string $hook The hook to prefix
	 *
	 * @return string The prefixed hook
	 */
	public static function hook_prefix( $hook ) {
		return "vibe_merge_orders_{$hook}";
	}

	/**
	 * Returns the plugin's logger
	 *
	 * @return Logger The plugin's logger
	 */
	public static function logger() {
		return self::instance()->core[ Logger::class ];
	}

	/**
	 * Private method to prevent cloning the singleton instance
	 *
	 * @return void
	 */
	private function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Nope', 'merge-orders' ) ), '1.0.0' );
	}

	/**
	 * Triggers an error to prevent unserializing the singleton instance
	 *
	 * @return void
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Nope', 'merge-orders' ) ), '1.0.0' );
	}
}
