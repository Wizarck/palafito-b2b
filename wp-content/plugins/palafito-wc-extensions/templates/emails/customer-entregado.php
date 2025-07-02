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

/**
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<?php
/**
 * Hook for Kadence WooCommerce Email Designer main content area
 */
do_action( 'kadence_woomail_designer_email_details', $order, $sent_to_admin, $plain_text, $email );
?>

<p><?php esc_html_e( '¡Tu pedido ha sido entregado exitosamente!', 'palafito-wc-extensions' ); ?></p>

<p><?php esc_html_e( 'Nos complace informarte que tu pedido ha sido entregado. A continuación encontrarás un resumen completo de tu compra.', 'palafito-wc-extensions' ); ?></p>

<?php
/**
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );
?>

<p><?php esc_html_e( '¡Gracias por confiar en nosotros!', 'palafito-wc-extensions' ); ?></p>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
$additional_enable = function_exists( 'Kadence_Woomail_Customizer::opt' ) ? Kadence_Woomail_Customizer::opt( 'additional_content_enable' ) : false;
if ( isset( $additional_content ) && ! empty( $additional_content ) && apply_filters( 'kadence_email_customizer_additional_enable', $additional_enable, $email ) ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

/**
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );