<?php
/**
 * Plugin Name:          PDF Invoices & Packing Slips for WooCommerce - Professional
 * Description:          Extended functionality for PDF Invoices & Packing Slips for WooCommerce plugin
 * Version:              2.15.11
 * Author:               Palafito
 * WC requires at least: 3.3
 * WC tested up to:      9.4
 */

if ( ! class_exists( 'WooCommerce_PDF_IPS_Pro' ) ) :

class WooCommerce_PDF_IPS_Pro {

	public $version             = '2.15.11';
	public $plugin_basename;
	public $cloud_api           = null;
	public $emails;
	public $settings;
	public $functions;
	public $writepanels;
	public $multilingual_full;
	public $multilingual_html;
	public $bulk_export;
	public $dependencies;
	public $dropbox_api;
	public $ftp_upload;
	public $gdrive_api;
	public $cloud_storage;

	/**
	 * @var WPO\WC\PDF_Invoices_Pro\Rest
	 */
	public $rest;

	protected static $_instance = null;

	/**
	 * Main Plugin Instance
	 *
	 * Ensures only one instance of plugin is loaded or can be loaded.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->plugin_basename = plugin_basename(__FILE__);

		$this->define( 'WPO_WCPDF_PRO_VERSION', $this->version );

		// load the localisation & classes
		add_action( 'init', array( $this, 'translations' ), 8 );
		add_action( 'wpo_wcpdf_reload_attachment_translations', array( $this, 'translations' ) );
		add_action( 'init', array( $this, 'load_classes_early' ), 9 );
		add_action( 'init', array( $this, 'load_classes' ) );
		
		// HPOS compatibility
		add_action( 'before_woocommerce_init', array( $this, 'woocommerce_hpos_compatible' ) );

		// run lifecycle methods
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			add_action( 'wp_loaded', array( $this, 'do_install' ) );
		}

		// Autoloader
		require( plugin_dir_path( __FILE__ ) . 'lib/autoload.php' );
	}

	/**
	 * Define constant if not already set
	 * @param  string $name
	 * @param  string|bool $value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}
	
	/**
	 * Declares WooCommerce HPOS compatibility.
	 *
	 * @return void
	 */
	public function woocommerce_hpos_compatible() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
	
	/**
	 * Load the translation / textdomain files
	 * 
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present
	 */
	public function translations() {
		if ( function_exists( 'determine_locale' ) ) { // WP5.0+
			$locale = determine_locale();
		} else {
			$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		}
		$locale = apply_filters( 'plugin_locale', $locale, 'woocommerce-pdf-invoices-packing-slips' );
		$dir    = trailingslashit( WP_LANG_DIR );

		/**
		 * Frontend/global Locale. Looks in:
		 *
		 * 		- WP_LANG_DIR/woocommerce-pdf-invoices-packing-slips/wpo_wcpdf_pro-LOCALE.mo
		 * 	 	- WP_LANG_DIR/plugins/wpo_wcpdf_pro-LOCALE.mo
		 * 	 	- woocommerce-pdf-invoices-packing-slips/languages/wpo_wcpdf_pro-LOCALE.mo (which if not found falls back to:)
		 * 	 	- WP_LANG_DIR/plugins/wpo_wcpdf_pro-LOCALE.mo
		 *
		 * WP_LANG_DIR defaults to wp-content/languages
		 */
		if ( current_filter() == 'wpo_wcpdf_reload_attachment_translations' ) {
			unload_textdomain( 'wpo_wcpdf_pro' );
			WC()->countries = new \WC_Countries();
		}
		load_textdomain( 'wpo_wcpdf_pro', $dir . 'woocommerce-pdf-ips-pro/wpo_wcpdf_pro-' . $locale . '.mo' );
		load_textdomain( 'wpo_wcpdf_pro', $dir . 'plugins/wpo_wcpdf_pro-' . $locale . '.mo' );
		load_plugin_textdomain( 'wpo_wcpdf_pro', false, dirname( plugin_basename(__FILE__) ) . '/languages' );
	}

	/**
	 * Load the main plugin classes and functions
	 */
	public function includes() {
		// Plugin classes
		$this->settings    = include_once $this->plugin_path() . '/includes/wcpdf-pro-settings.php';
		$this->functions   = include_once $this->plugin_path() . '/includes/wcpdf-pro-functions.php';
		$this->writepanels = include_once $this->plugin_path() . '/includes/wcpdf-pro-writepanels.php';
		
		// Backwards compatibility with self
		include_once( $this->plugin_path().'/includes/legacy/wcpdf-pro-legacy.php' );
		
		// Multilingual
		foreach ( $this->functions->get_active_multilingual_plugins() as $slug => $plugin ) {
			switch ( $plugin['support'] ) {
				case 'full':
					$this->multilingual_full = include_once $this->plugin_path() . '/includes/wcpdf-pro-multilingual-full.php';
					break;
				case 'html':
					$this->multilingual_html = include_once $this->plugin_path() . '/includes/wcpdf-pro-multilingual-html.php';
					break;
			}
		}

		if ( ! $this->multilingual_full && isset( $this->functions->pro_settings['document_language'] ) ) {
			$avoid_options   = array_keys( $this->functions->multilingual_supported_plugins() );
			$avoid_options[] = 'user';

			if ( ! in_array( $this->functions->pro_settings['document_language'], $avoid_options ) ) {
				$this->multilingual_full = include_once $this->plugin_path() . '/includes/wcpdf-pro-multilingual-full.php';
			}
		}

		// Bulk export
		$this->bulk_export = include_once $this->plugin_path() . '/includes/wcpdf-pro-bulk-export.php';

		// Abstract Cloud API class
		$this->cloud_api        = include_once $this->plugin_path() . '/includes/cloud/abstract-wcpdf-cloud-api.php';
		$cloud_services_enabled = include_once $this->plugin_path() . '/includes/cloud/cloud-services-enabled.php';
		foreach ( $cloud_services_enabled::$services_enabled as $service_slug ) {
			switch ( $service_slug ) {
				case 'dropbox': // Dropbox API
					$this->dropbox_api = include_once $this->plugin_path() . '/includes/cloud/dropbox/dropbox-api.php';
					break;
				case 'ftp': // FTP
					$this->ftp_upload = include_once $this->plugin_path() . '/includes/cloud/ftp/ftp.php';
					break;
				case 'gdrive': // Gdrive API
					$this->gdrive_api = include_once $this->plugin_path() . '/includes/cloud/gdrive/gdrive-api.php';
					break;
			}
		}
		// Cloud Storage class
		$this->cloud_storage = include_once $this->plugin_path() . '/includes/wcpdf-pro-cloud-storage.php';

        // REST API class
        $this->rest = include_once $this->plugin_path() . '/includes/wcpdf-pro-rest.php';
	}
	

	/**
	 * Instantiate classes when woocommerce is activated
	 */
	public function load_classes() {
		if ( $this->dependencies->ready() === false ) {
			return;
		}

		// all systems ready - GO!
		$this->includes();
	}

	/**
	 * Instantiate classes when woocommerce is activated
	 */
	public function load_classes_early() {
		$this->dependencies = include_once( 'includes/wcpdf-pro-dependencies.php' );
		if ( $this->dependencies->ready() === false ) {
			return;
		}

		// all systems ready - GO!
		$this->emails = include_once( $this->plugin_path().'/includes/wcpdf-pro-emails.php' );
	}

	/**
	 * Run the installer
	 */
	public function do_install() {
		$installed_version = get_option( 'wpo_wcpdf_pro_version' );

		if ( $installed_version != $this->version ) {
			if ( ! $installed_version ) {
				$this->install();
			} else {
				$this->upgrade( $installed_version );
			}
		}
	}

	/**
	 * Installer
	 */
	protected function install() {
		// Get settings from free plugin
		$wcpdf_settings = get_option( 'wpo_wcpdf_settings', array() );
		
		// Set default settings
		$default_settings = array(
			'packing_slip_enabled'		=> 1,
			'packing_slip_attach_to_email_ids' => array( 'customer_completed_order' => 1 ),
			'packing_slip_number_column' => 1,
			'packing_slip_date_column'	 => 1,
			'packing_slip_my_account_buttons' => 'available',
			'packing_slip_display_number' => 'packing_slip_number',
			'packing_slip_display_date'	 => 'document_date',
			'packing_slip_display_email' => 1,
			'packing_slip_display_phone' => 1,
			'packing_slip_display_customer_notes' => 1,
			'packing_slip_display_billing_address' => 'when_different',
			'packing_slip_reset_number_yearly' => 1,
			'packing_slip_number_format' => array(
				'prefix'	=> '',
				'suffix'	=> '',
				'padding'	=> '',
			),
			'packing_slip_next_number'	 => 1,
			'packing_slip_mark_printed'	 => array( 'email_attachment' => 1 ),
			'packing_slip_unmark_printed' => 1,
			'packing_slip_disable_for_statuses' => array(),
		);

		// Merge with existing settings
		$pro_settings = array_merge( $default_settings, $wcpdf_settings );

		update_option( 'wpo_wcpdf_pro_settings', $pro_settings );
		update_option( 'wpo_wcpdf_pro_version', $this->version );
	}

	/**
	 * Upgrader
	 */
	protected function upgrade( $installed_version ) {
		// Get current settings
		$pro_settings = get_option( 'wpo_wcpdf_pro_settings', array() );
		
		// Version-specific upgrades
		if ( version_compare( $installed_version, '2.0.0', '<' ) ) {
			// Upgrade to 2.0.0
			if ( isset( $pro_settings['packing_slip_enabled'] ) && $pro_settings['packing_slip_enabled'] ) {
				// Enable packing slip in new format
				$packing_slip_settings = get_option( 'wpo_wcpdf_documents_settings_packing-slip', array() );
				$packing_slip_settings['enabled'] = 1;
				update_option( 'wpo_wcpdf_documents_settings_packing-slip', $packing_slip_settings );
			}
		}

		// Update version
		update_option( 'wpo_wcpdf_pro_version', $this->version );
	}

	/**
	 * Get the plugin url
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Get the plugin path
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

}

endif; // class_exists

/**
 * Returns the main instance of WooCommerce_PDF_IPS_Pro to prevent the need to use globals.
 *
 * @return WooCommerce_PDF_IPS_Pro
 */
function WPO_WCPDF_Pro() {
	return WooCommerce_PDF_IPS_Pro::instance();
}

// Global for backwards compatibility.
$GLOBALS['wpo_wcpdf_pro'] = WPO_WCPDF_Pro();

// Remove any license management links that might be added by other plugins or global hooks.
add_filter( 'plugin_action_links_woocommerce-pdf-ips-pro/woocommerce-pdf-ips-pro.php', function( $links ) {
	// Remove any links that contain "license", "licencia", "manage", "gestionar" in their text or URL.
	foreach ( $links as $key => $link ) {
		if ( 
			stripos( $link, 'license' ) !== false || 
			stripos( $link, 'licencia' ) !== false || 
			stripos( $link, 'manage' ) !== false || 
			stripos( $link, 'gestionar' ) !== false ||
			stripos( $link, 'activation' ) !== false ||
			stripos( $link, 'activación' ) !== false
		) {
			unset( $links[$key] );
		}
	}
	return $links;
}, 999 );