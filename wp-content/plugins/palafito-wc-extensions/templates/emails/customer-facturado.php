<?php
/**
 * Email template: Customer Facturado
 *
 * HTML email sent to customer when order is marked as Facturado.
 *
 * @package Palafito_WC_Extensions
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php esc_html_e( '¡Tu pedido ha sido facturado! A continuación encontrarás un resumen de tu compra y tu factura adjunta.', 'palafito-wc-extensions' ); ?></p>

<?php
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );
?>

<?php do_action( 'woocommerce_email_footer', $email ); ?>