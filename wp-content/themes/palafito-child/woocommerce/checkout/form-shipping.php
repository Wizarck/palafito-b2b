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
		if ( isset( $fields['shipping_phone'] ) ) {
			$fields['shipping_phone']['required'] = true;
			$fields['shipping_phone']['label'] = __( 'Teléfono de envío', 'woocommerce' ) . ' <span class="required">*</span>';
			$fields['shipping_phone']['custom_attributes']['required'] = 'required';
		}
		foreach ( $fields as $key => $field ) {
			// Aseguramos que las clases estándar estén presentes
			if (empty($field['class'])) {
				$field['class'] = array('form-row-wide');
			}
			woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
		}
		?>
	</div>
	<?php do_action( 'woocommerce_after_checkout_shipping_form', $checkout ); ?>
</div> 