<?php
/**
 * My Orders
 *
 * Shows recent orders on the account page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/orders.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you (the theme developer).
 * will need to copy the new files to your theme to maintain compatibility. We try to do this
 * as little as possible, but it does happen. When this occurs the version of the template file will
 * be bumped and the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$my_orders_columns = apply_filters( 'woocommerce_my_account_my_orders_columns', array(
	'order-number'  => __( 'Pedido', 'woocommerce' ),
	'order-date'    => __( 'Fecha', 'woocommerce' ),
	'order-status'  => __( 'Estado', 'woocommerce' ),
	'order-total'   => __( 'Total', 'woocommerce' ),
	'order-actions' => '&nbsp;',
) );

// Insertar columna de nota de cliente después de 'order-number'.
$my_orders_columns = array_merge(
	array_slice( $my_orders_columns, 0, 1, true ),
	array( 'order-customer-note' => __( 'Nota de cliente', 'woocommerce' ) ),
	array_slice( $my_orders_columns, 1, null, true )
);

// Obtener los pedidos del usuario actual (máximo 10, paginados)
$customer_orders = wc_get_orders( apply_filters( 'woocommerce_my_account_my_orders_query', array(
	'customer' => get_current_user_id(),
	'limit'    => 10,
	'orderby'  => 'date',
	'order'    => 'DESC',
) ) );

$has_orders = ! empty( $customer_orders );
?>

<section class="woocommerce-orders">
	<h2 class="woocommerce-orders__title"><?php esc_html_e( 'Pedidos recientes', 'woocommerce' ); ?></h2>

	<?php if ( $has_orders ) : ?>
		<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
			<thead>
				<tr>
					<?php foreach ( $my_orders_columns as $column_id => $column_name ) : ?>
						<th class="woocommerce-orders-table__header woocommerce-orders-table__header-<?php echo esc_attr( $column_id ); ?>"><span class="nobr"><?php echo esc_html( $column_name ); ?></span></th>
					<?php endforeach; ?>
				</tr>
			</thead>

			<tbody>
				<?php foreach ( $customer_orders as $order ) {
					$order_id   = $order ? $order->get_id() : 0;
					?>
					<tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-<?php echo esc_attr( $order ? $order->get_status() : '' ); ?> order">
						<?php foreach ( $my_orders_columns as $column_id => $column_name ) {
							?>
							<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-<?php echo esc_attr( $column_id ); ?>" data-title="<?php echo esc_attr( $column_name ); ?>">
								<?php
								if ( 'order-customer-note' === $column_id ) {
									$note = $order ? $order->get_customer_note() : '';
									if ( $note ) {
										$truncated = mb_strlen( $note ) > 25 ? mb_substr( $note, 0, 25 ) . '…' : $note;
										// Mostrar en una sola línea, sin saltos.
										$truncated = str_replace(["\r", "\n"], ' ', $truncated);
										?>
										<span title="<?php echo esc_attr( $note ); ?>" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display:inline-block; max-width:150px; vertical-align:middle;">
											<?php echo esc_html( $truncated ); ?>
										</span>
										<?php
									} // Si no hay nota, celda vacía
								} else {
									// Renderizar el resto de columnas como hace WooCommerce normalmente
									if ( has_action( 'woocommerce_my_account_my_orders_column_' . $column_id ) ) {
										do_action( 'woocommerce_my_account_my_orders_column_' . $column_id, $order );
									} elseif ( isset( $order ) ) {
										switch ( $column_id ) {
											case 'order-number':
												?>
												<a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
													<?php echo esc_html( $order->get_order_number() ); ?>
												</a>
												<?php
												break;
											case 'order-date':
												?>
												<time datetime="<?php echo esc_attr( $order->get_date_created() ? $order->get_date_created()->date( 'c' ) : '' ); ?>">
													<?php echo esc_html( $order->get_date_created() ? wc_format_datetime( $order->get_date_created() ) : '' ); ?>
												</time>
												<?php
												break;
											case 'order-status':
												echo esc_html( wc_get_order_status_name( $order->get_status() ) );
												break;
											case 'order-total':
												/* translators: %s: order total */
												printf( _x( '%s for %s item', 'Order total on My Account page', 'woocommerce' ), $order->get_formatted_order_total(), $order->get_item_count() );
												break;
											case 'order-actions':
												$actions = wc_get_account_orders_actions( $order );
												unset($actions['view']); // Eliminar el botón 'Ver'
												foreach ( $actions as $key => $action ) {
													?>
													<a href="<?php echo esc_url( $action['url'] ); ?>" class="woocommerce-button button <?php echo esc_attr( $key ); ?>"><?php echo esc_html( $action['name'] ); ?></a>
													<?php
												}
												break;
										}
									}
								}
								?>
							</td>
						<?php } // end foreach columnas ?>
					</tr>
				<?php } // end foreach pedidos ?>
			</tbody>
		</table>

		<?php do_action( 'woocommerce_after_account_orders', $has_orders ); ?>

	<?php else : ?>
		<div class="woocommerce-message woocommerce-message--info woocommerce-Message woocommerce-Message--info woocommerce-info">
			<?php esc_html_e( 'No hay pedidos todavía.', 'woocommerce' ); ?>
		</div>
	<?php endif; ?>
</section> 