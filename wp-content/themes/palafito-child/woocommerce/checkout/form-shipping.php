<?php
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
			if ( $key === 'shipping_phone' ) {
				$field['required'] = true;
				$field['label'] = __( 'Teléfono', 'woocommerce' );
			} elseif ( $key === 'shipping_country' ) {
				$field['required'] = true;
				$field['label'] = __( 'País', 'woocommerce' );
			} elseif ( in_array( $key, array( 'shipping_company', 'shipping_last_name' ), true ) ) {
				$field['required'] = false;
			} else {
				$field['required'] = true;
			}
			if (empty($field['class'])) {
				$field['class'] = array('form-row-wide');
			}
			woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
		}
		unset($field);
		?>
	</div>
	<?php do_action( 'woocommerce_after_checkout_shipping_form', $checkout ); ?>
</div> 