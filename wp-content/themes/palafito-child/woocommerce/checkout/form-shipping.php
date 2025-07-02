<?php
/**
 * WooCommerce Checkout: Form Shipping
 *
 * Custom shipping form for Palafito Child theme.
 *
 * @package Palafito_Child
 * @since 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="woocommerce-shipping-fields">
	<?php do_action( 'woocommerce_before_checkout_shipping_form', $checkout ); ?>
	<div class="woocommerce-shipping-fields__field-wrapper">
		<?php
		$fields = $checkout->get_checkout_fields( 'shipping' );
		foreach ( $fields as $key => &$field ) {
			if ( 'shipping_phone' === $key ) {
				$field['required'] = true;
				$field['label']    = __( 'Teléfono', 'woocommerce' );
			} elseif ( 'shipping_country' === $key ) {
				$field['required'] = true;
				$field['label']    = __( 'País', 'woocommerce' );
			} elseif ( in_array( $key, array( 'shipping_first_name', 'shipping_last_name' ), true ) ) {
				$field['type'] = 'hidden';
				$field['required'] = false;
				$field['label'] = '';
			} elseif ( 'shipping_company' === $key ) {
				$field['required'] = true;
			} else {
				$field['required'] = true;
			}
			if ( empty( $field['class'] ) ) {
				$field['class'] = array( 'form-row-wide' );
			}
			woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
		}
		unset( $field );
		?>
	</div>
	<?php do_action( 'woocommerce_after_checkout_shipping_form', $checkout ); ?>
</div> 