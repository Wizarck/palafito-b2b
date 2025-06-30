<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="woocommerce-billing-fields">
	<?php if ( wc_ship_to_billing_address_only() && WC()->cart->needs_shipping() ) : ?>
		<h3><?php esc_html_e( 'Dirección de facturación y envío', 'woocommerce' ); ?></h3>
	<?php else : ?>
		<h3><?php esc_html_e( 'Dirección de facturación', 'woocommerce' ); ?></h3>
	<?php endif; ?>
	<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>
	<div class="woocommerce-billing-fields__field-wrapper">
		<?php
		$fields = $checkout->get_checkout_fields( 'billing' );
		// Hacer todos los campos de facturación opcionales y ajustar el label de teléfono.
		foreach ( $fields as $key => &$field ) {
			$field['required'] = false;
			if ( $key === 'billing_phone' ) {
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
	<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>
</div> 