<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="woocommerce-shipping-fields" style="display: none;">
	<?php do_action( 'woocommerce_before_checkout_shipping_form', $checkout ); ?>
	<div class="woocommerce-shipping-fields__field-wrapper">
		<?php
		$fields = $checkout->get_checkout_fields( 'shipping' );
		foreach ( $fields as $key => &$field ) {
			$field['required'] = false;
			if ( $key === 'shipping_phone' ) {
				$field['label'] = __( 'Teléfono', 'woocommerce' );
			} elseif ( $key === 'shipping_country' ) {
				$field['label'] = __( 'País', 'woocommerce' );
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