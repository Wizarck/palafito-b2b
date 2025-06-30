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
    <div class="palafito-checkout-grid" style="display: flex; gap: 2rem; align-items: flex-start; flex-wrap: wrap;">
        <div class="palafito-checkout-left" style="flex: 1 1 350px; min-width: 320px;">
            <h3><?php esc_html_e( 'Dirección de envío', 'woocommerce' ); ?></h3>
            <?php
            // Solo mostramos los campos de envío, sin checkbox ni facturación.
            do_action( 'woocommerce_checkout_shipping' );
            ?>
        </div>
        <div class="palafito-checkout-right" style="flex: 1 1 350px; min-width: 320px; max-width: 500px;">
            <h3 id="order_review_heading"><?php esc_html_e( 'Tu pedido', 'woocommerce' ); ?></h3>
            <div id="order_review" class="woocommerce-checkout-review-order">
                <?php do_action( 'woocommerce_checkout_order_review' ); ?>
            </div>
        </div>
    </div>
</form>
<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?> 