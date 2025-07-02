<?php
/**
 * Plain email template: Customer Entregado
 *
 * Plain text email sent to customer when order is marked as Entregado.
 *
 * @package Palafito_WC_Extensions
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php echo esc_html( $email_heading ) . "\n\n"; ?>
<?php esc_html_e( 'Nos complace informarte que tu pedido ha sido entregado. A continuación encontrarás un resumen completo de tu compra junto con tu albarán adjunta.', 'palafito-wc-extensions' ); ?>

<?php
/**
 * Show order details, meta, and customer details in Entregado email (plain).
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );
?>

<?php
esc_html_e( '¡Gracias por confiar en nosotros!', 'palafito-wc-extensions' );
