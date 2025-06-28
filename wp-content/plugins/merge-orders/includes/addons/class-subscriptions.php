<?php

namespace Vibe\Merge_Orders\Addons;

use Vibe\Merge_Orders\Merge_Orders;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Provides support for Subscriptions plugin
 *
 * @since 1.2
 */
class Subscriptions {

	/**
	 * Creates an instance and sets up hooks to integrate with the rest of the extension, only if Subscriptions is
	 * installed
	 */
	public function __construct() {
		add_action( Merge_Orders::hook_prefix( 'can_merge' ), array( __CLASS__, 'can_merge' ), 10, 2 );
	}

	/**
	 * Filters the orders that can be merged to remove from subscription orders
	 *
	 * Subscription parent orders can potentially be merged as they may contain non-subscription products.
	 *
	 * @param bool      $can_merge If the current screen is one to support a merging action or not
	 * @param \WC_Order $order     The order to potentially merge
	 *
	 * @return bool False if the order is for a subscription renewal, resubscribe or switch, otherwise the core logic is
	 *              used to determine if the screen is suitable for merging.
	 */
	public static function can_merge( $can_merge, $order ) {
		if ( $can_merge && function_exists( 'wcs_order_contains_subscription' ) ) {
			if ( $order->get_type() == 'shop_subscription' || wcs_order_contains_subscription( $order, array( 'renewal', 'switch', 'resubscribe' ) ) ) {
				$can_merge = false;
			}
		}

		return $can_merge;
	}
}
