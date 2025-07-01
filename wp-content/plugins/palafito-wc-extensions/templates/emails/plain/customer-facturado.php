<?php
/**
 * Plain email template: Customer Facturado
 *
 * Plain text email sent to customer when order is marked as Facturado.
 *
 * @package Palafito_WC_Extensions
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php echo esc_html( $email_heading ) . "\n\n"; ?>
<?php esc_html_e( '¡Tu pedido ha sido facturado! A continuación encontrarás un resumen de tu compra y tu factura adjunta si está configurado.', 'palafito-wc-extensions' ); ?>

<?php
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );
