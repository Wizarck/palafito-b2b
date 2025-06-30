<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div style="color:red;">TEST OVERRIDE SHIPPING</div>
<div class="woocommerce-shipping-fields">
	<?php do_action( 'woocommerce_before_checkout_shipping_form', $checkout ); ?>
	<div class="woocommerce-shipping-fields__field-wrapper">
		<?php
		$fields = $checkout->get_checkout_fields( 'shipping' );
		// Hacer todos los campos de envío opcionales y ajustar el label de teléfono.
		foreach ( $fields as $key => &$field ) {
			$field['required'] = false;
			if ( $key === 'shipping_phone' ) {
				$field['label'] = __( 'Teléfono', 'woocommerce' );
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