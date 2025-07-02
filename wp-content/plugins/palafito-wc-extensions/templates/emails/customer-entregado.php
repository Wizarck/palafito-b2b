<?php
/**
 * Email template: Customer Entregado
 *
 * HTML email sent to customer when order is marked as Entregado.
 *
 * @package Palafito_WC_Extensions
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php esc_html_e( '¡Tu pedido ha sido entregado exitosamente!', 'palafito-wc-extensions' ); ?></p>

<p><?php esc_html_e( 'Nos complace informarte que tu pedido ha sido entregado. A continuación encontrarás un resumen completo de tu compra.', 'palafito-wc-extensions' ); ?></p>

<?php
/**
 * Show order details, meta, and customer details in Entregado email.
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );
?>

<p><?php esc_html_e( '¡Gracias por confiar en nosotros!', 'palafito-wc-extensions' ); ?></p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>