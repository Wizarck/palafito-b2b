<?php

namespace Vibe\Merge_Orders;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Sets up Settings page and provides access to setting values
 *
 * @since 1.2.0
 */
class Settings {

	/**
	 * Creates an instance and sets up the hooks to integrate with the admin
	 */
	public function __construct() {
		add_filter( 'woocommerce_get_sections_advanced', array( __CLASS__, 'add_settings_page' ) );
		add_filter( 'woocommerce_get_settings_advanced', array( __CLASS__, 'add_settings' ), 10, 2 );
		add_filter( 'plugin_action_links', array( __CLASS__, 'add_settings_link' ), 10, 2 );
	}

	/**
	 * Adds a section to the advanced tab
	 *
	 * @param array $sections The existing settings sections on the advanced tab
	 *
	 * @return array The sections with the merge-orders settings section added
	 */
	public static function add_settings_page( array $sections ) {
		$sections['merge-orders'] = __( 'Merge orders', 'merge-orders' );

		return $sections;
	}

	/**
	 * Adds setting fields to the merge-orders section of the settings
	 *
	 * @param array  $settings        The current settings
	 * @param string $current_section The name of the current section of settings
	 *
	 * @return array The settings fields including merge orders settings if the current section is 'merge-orders'
	 */
	public static function add_settings( array $settings, $current_section ) {
		if ( 'merge-orders' != $current_section ) {
			return $settings;
		}

		$settings[] = array(
			'name' => __( 'Merge orders', 'merge-orders' ),
			'type' => 'title',
			'desc' => __( 'The following options are used to configure the Merge Orders extension.', 'merge-orders' )
		);

		$settings[] = array(
			'name'     => __( 'Logging', 'merge-orders' ),
			'desc'     => __( 'Enable logging', 'merge-orders' ),
			'desc_tip' => __( 'Enable debug logs to review the progress of merges. Logs can be viewed at WooCommerce > Status > Logs.', 'merge-orders' ),
			'id'       => Merge_Orders::hook_prefix( 'enable_logging' ),
			'type'     => 'checkbox'
		);

		$settings = apply_filters( Merge_Orders::hook_prefix( 'settings' ), $settings );

		$settings[] = array( 'type' => 'sectionend', 'id' => 'merge-orders' );

		return $settings;
	}

	/**
	 * Fetches and returns the logging enabled setting. Defaults to disabled.
	 *
	 * @return bool Whether logging is enabled or not
	 */
	public static function enable_logging() {
		return get_option( Merge_Orders::hook_prefix( 'enable_logging' ), false );
	}

	/**
	 * Adds a Settings link to the plugin list
	 *
	 * @return array The plugin's action links with a link to the plugin settings added
	 */
	public static function add_settings_link( $plugin_actions, $plugin_file ) {
		$new_actions = array();

		if ( 'merge-orders.php' === basename( $plugin_file ) ) {
			/* translators: %s: Settings */
			$new_actions['settings'] = sprintf( __( '<a href="%s">Settings</a>', 'merge-orders' ), esc_url( admin_url( 'admin.php?page=wc-settings&tab=advanced&section=merge-orders' ) ) );
		}

		return array_merge( $new_actions, $plugin_actions );
	}
}
