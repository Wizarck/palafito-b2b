<?php

namespace Vibe\Merge_Orders;

use WC_Logger;

/**
 * Logger handles logging debug and other messages for the plugin
 *
 * It sends any messages to the currently configured WC Logger with a source of merge-orders.
 *
 * @since 1.2.0
 */
class Logger {

	const LOG_EMERGENCY = 'emergency';
	const LOG_ALERT = 'alert';
	const LOG_CRITICAL = 'critical';
	const LOG_ERROR = 'error';
	const LOG_WARNING = 'warning';
	const LOG_NOTICE = 'notice';
	const LOG_INFO = 'info';
	const LOG_DEBUG = 'debug';

	/**
	 * Whether logging is enabled or not
	 *
	 * @var bool
	 */
	private $enabled;

	/**
	 * WooCommerce logger for handling log messages
	 *
	 * @var WC_Logger
	 */
	private $logger;

	/**
	 * Creates an instance and sets up the hooks to initialise logging
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Sets up the logger if it's enabled
	 */
	public function init() {
		$this->enabled = apply_filters( Merge_Orders::hook_prefix( 'logging_enabled' ), Settings::enable_logging() );

		if ( $this->enabled ) {
			$this->logger = wc_get_logger();
		}
	}

	/**
	 * Returns whether logging is enabled
	 *
	 * @return bool True if logging is enabled, false otherwise
	 */
	public function is_enabled() {
		return $this->enabled;
	}

	/**
	 * Adds a message log of the given level
	 *
	 * @param string $message The message to log
	 * @param string $level The level of the message, valid options defined by WC_Logger. Defaults to 'debug'.
	 */
	public function log( $message, $level = self::LOG_DEBUG ) {
		if ( ! $this->enabled || ! isset( $this->logger ) ) {
			return;
		}

		$context = array( 'source' => 'merge-orders' );

		$this->logger->log( $level, $message, $context );
	}
}
