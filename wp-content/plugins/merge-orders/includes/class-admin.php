<?php

namespace Vibe\Merge_Orders;

use WC_Order;
use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Sets up Admin modifications
 *
 * @since 1.0.0
 */
class Admin {

	/**
	 * Creates an instance and sets up the hooks to integrate with the admin
	 */
	public function __construct() {
		add_action( 'woocommerce_order_item_add_action_buttons', array( __CLASS__, 'output_merge_button' ) );
		add_action( 'in_admin_footer', array( __CLASS__, 'output_modal' ) );

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
	}

	/**
	 * Outputs an action button to merge an order if the given order can be merged
	 *
	 * @param WC_Order $order The order that would be merge
	 */
	public static function output_merge_button( WC_Order $order ) {
		if ( Orders::can_merge( $order->get_id() ) ) {
			printf( '<button type="button" class="button merge-order" data-id="%d">%s</button>', esc_attr__( $order->get_id(), 'merge-orders' ), esc_html__( 'Merge order', 'merge-orders' ) );
		}
	}

	/**
	 * Outputs the HTML for a modal to be used for merging an order
	 */
	public static function output_modal() {
		if ( static::is_mergeable_screen() ) {
			?>
			<script type="text/template" id="tmpl-wc-modal-merge-orders">
				<div class="wc-backbone-modal">
					<div class="wc-backbone-modal-content">
						<section class="wc-backbone-modal-main" role="main">
							<header class="wc-backbone-modal-header">
								<h1><?php esc_html_e( 'Merge order', 'merge-orders' ); ?></h1>
								<button class="modal-close modal-close-link dashicons dashicons-no-alt">
									<span class="screen-reader-text">Close modal panel</span>
								</button>
							</header>
							<article id="modal-merge-orders-orders">
								<?php // Will be populated by AJAX when opened ?>
							</article>
							<footer>
								<div class="inner">
									<button id="btn-ok" class="button button-primary button-large">
										<?php esc_html_e( 'Complete Merge', 'merge-orders' ); ?>
									</button>
								</div>
							</footer>
						</section>
					</div>
				</div>
				<div class="wc-backbone-modal-backdrop modal-close"></div>
			</script>
			<?php
		}
	}

	/**
	 * Returns the HTML to populate the order merge modal, with line items from the given order
	 *
	 * @param int $order_id The ID of the order to generate the output for
	 *
	 * @return string An HTML string with controls for merging the given order
	 */
	public static function get_merging_popup( $order_id ) {
		$order   = wc_get_order( $order_id );
		$message = '';

		if ( self::use_optimised_search() ) {
			$message = __( 'This site has a lot of orders. To keep performance usable, searching has been switched to use order numbers only.', 'merge-orders' );
		}

		ob_start();

		include vibe_merge_orders()->path( 'includes/partials/popup.php' );

		return ob_get_clean();
	}

	/**
	 * Enqueues scripts and styles on the order admin pages
	 */
	public static function enqueue_scripts() {
		if ( ! static::is_mergeable_screen() ) {
			return;
		}

		$handle = Merge_Orders::hook_prefix( 'js' );

		wp_register_script(
			$handle,
			vibe_merge_orders()->uri( 'assets/js/vibe-merge-orders.min.js' ),
			array( 'jquery' ),
			vibe_merge_orders()->get_version(),
			true
		);
		wp_localize_script( $handle, 'vibe_merge_orders_data', static::script_data() );

		wp_enqueue_script( $handle );

		$handle = Merge_Orders::hook_prefix( 'css' );
		wp_enqueue_style(
			$handle,
			vibe_merge_orders()->uri( 'assets/css/vibe-merge-orders.min.css' ),
			array(),
			vibe_merge_orders()->get_version(),
			'all'
		);
	}

	/**
	 * Sets up data to be passed to front end via script localisation
	 *
	 * @return array An array of data items
	 */
	public static function script_data() {
		$script_data['ajaxurl']          = admin_url( 'admin-ajax.php' );
		$script_data['popup_nonce']      = wp_create_nonce( Merge_Orders::hook_prefix( 'popup-nonce' ) );
		$script_data['get_orders_nonce'] = wp_create_nonce( Merge_Orders::hook_prefix( 'get-orders-nonce' ) );
		$script_data['merging_nonce']    = wp_create_nonce( Merge_Orders::hook_prefix( 'merging-nonce' ) );
		$script_data['refund_notice']    = esc_html__(
			'This order has been merged with others, it may not be possible to refund more than the original order total',
			'merge-orders'
		);

		if ( isset( $_GET['post'] ) ) {
			$script_data['has_been_merged_into'] = Orders::has_been_merged_into( absint( $_GET['post'] ) ) ? 1 : 0;
		} else {
			$script_data['has_been_merged_into'] = 0;
		}

		/**
		 * Allow data to be passed to the front end to be modified
		 *
		 * @param array $script_data The data to be passed to the front end
		 *
		 * @since 1.0.0
		 */
		return apply_filters( Merge_Orders::hook_prefix( 'script_data' ), $script_data );
	}

	public static function use_optimised_search() {
		$total_count = get_transient( 'vibe_merge_orders_order_count' );

		if ( ! $total_count ) {
			$order_counts = wp_count_posts( 'shop_order' );
			$total_count = 0;

			foreach ( $order_counts as $status => $count ) {
				$total_count += $count;
			}

			set_transient( 'vibe_merge_orders_order_count', $total_count, HOUR_IN_SECONDS );
		}

		/**
		 * Allow use of optimised search to be filtered
		 *
		 * @param bool $var Whether to use optimised search or not
		 *
		 * @since 1.1.0
		 */
		return apply_filters( Merge_Orders::hook_prefix( 'optimised_search' ), $total_count > 10000 );
	}

	/**
	 * Checks whether the current screen is one to display merge functionality on
	 *
	 * By default splittable screens would be any for the shop_order post type, except adding a new post
	 *
	 * @return bool True if the current screen is one that should include a split button, false otherwise
	 */
	public static function is_mergeable_screen() {
		$screen        = get_current_screen();
		$screen_base   = isset( $screen->base ) ? $screen->base : '';
		$screen_id     = isset( $screen->id ) ? $screen->id : '';
		$screen_action = isset( $screen->action ) ? $screen->action : '';

		// Check we're on the order screen, which could be the old order post type
		$order_screen      = function_exists( 'wc_get_page_screen_id' ) && wc_get_page_screen_id( 'shop-order' ) == $screen_base;
		$post_order_screen = ( 'post' === $screen_base && 'shop_order' === $screen_id && 'add' !== $screen_action );

		$is_mergeable = $order_screen || $post_order_screen;

		return apply_filters( Merge_Orders::hook_prefix( 'is_mergeable_screen' ), $is_mergeable, $screen );
	}

	public static function is_using_hpos() {
		if ( ! did_action( 'plugins_loaded' ) && ! doing_action( 'plugins_loaded' ) ) {
			_doing_it_wrong( __FUNCTION__, esc_html__( 'is_hpos_enabled should not be called before the init action.', 'assign-orders' ), '1.3.11' );
		}

		return class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && OrderUtil::custom_orders_table_usage_is_enabled();
	}
}
