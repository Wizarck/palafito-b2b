<?php
/**
 * Template file for the order merging modal controls
 *
 * @var WC_Order $order
 * @var string $message
 */

defined( 'ABSPATH' ) || exit;
?>

<div id="merge-orders-popup" data-order-id="<?php echo esc_attr( $order->get_id() ); ?>">

	<?php if ( $message ) : ?>
		<div class="notice notice-warning"><?php echo esc_html( $message ); ?></div>
	<?php endif; ?>

	<table class="widefat">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Order', 'merge-orders' ); ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>
					<select id="merge_order_select" name="merge_order_select" class="merge-order-select" title="Order Select">
						<option value=""><?php esc_html_e( 'Please select', 'merge-orders' ); ?></option>
					</select>
				</td>
				<td>
					<button type="button" class="button add-order-to-merge">
						<?php esc_html_e( 'Add to merge', 'merge-orders' ); ?>
					</button>
				</td>
			</tr>
		</tbody>
	</table>

	<input id="merge_order_orders" type="hidden" />

</div>
