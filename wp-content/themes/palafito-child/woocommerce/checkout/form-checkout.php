<?php
/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 8.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

wc_print_notices();

do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}
?>
<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">
	<div style="display: none;">
		<?php do_action( 'woocommerce_checkout_billing' ); ?>
	</div>
	<div class="palafito-checkout-grid" style="display: flex; gap: 2rem; align-items: flex-start; flex-wrap: wrap;">
		<div class="palafito-checkout-left" style="flex: 1 1 350px; min-width: 320px;">
			<h3><?php esc_html_e( 'Dirección de envío', 'woocommerce' ); ?></h3>
			<?php do_action( 'woocommerce_checkout_shipping' ); ?>
			
			<!-- Sección de notas de cliente -->
			<div class="woocommerce-additional-fields">
				<?php do_action( 'woocommerce_before_order_notes', $checkout ); ?>

				<?php if ( apply_filters( 'woocommerce_enable_order_notes_field', 'yes' === get_option( 'woocommerce_enable_order_comments', 'yes' ) ) ) : ?>

					<h3><?php esc_html_e( 'Información adicional', 'woocommerce' ); ?></h3>

					<div class="woocommerce-additional-fields__field-wrapper">
						<?php foreach ( $checkout->get_checkout_fields( 'order' ) as $key => $field ) : ?>
							<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
						<?php endforeach; ?>
					</div>

				<?php endif; ?>

				<?php do_action( 'woocommerce_after_order_notes', $checkout ); ?>
			</div>
		</div>
		<div class="palafito-checkout-right" style="flex: 1 1 350px; min-width: 320px; max-width: 500px;">
			<h3><?php esc_html_e( 'Pedido', 'woocommerce' ); ?></h3>
			<?php do_action( 'woocommerce_checkout_order_review' ); ?>
		</div>
	</div>
</form>
<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>