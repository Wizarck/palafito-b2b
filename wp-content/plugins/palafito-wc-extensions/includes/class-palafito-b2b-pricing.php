<?php
/**
 * Clase para manejo de precios B2B y descuentos por cantidad.
 *
 * @package Palafito_WC_Extensions
 * @since 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase Palafito_B2B_Pricing.
 *
 * Maneja la lógica de precios B2B, descuentos por cantidad y precios especiales.
 */
class Palafito_B2B_Pricing {

	/**
	 * Constructor de la clase.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Inicializar hooks de WordPress.
	 */
	private function init_hooks() {
		// Hooks de precios.
		add_filter( 'woocommerce_product_get_price', array( $this, 'apply_b2b_pricing' ), 10, 2 );
		add_filter( 'woocommerce_product_get_regular_price', array( $this, 'apply_b2b_pricing' ), 10, 2 );
		add_filter( 'woocommerce_product_get_sale_price', array( $this, 'apply_b2b_pricing' ), 10, 2 );

		// Hooks de carrito.
		add_filter( 'woocommerce_cart_item_price', array( $this, 'display_cart_item_price' ), 10, 3 );
		add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'display_cart_item_subtotal' ), 10, 3 );

		// Hooks de admin.
		add_action( 'add_meta_boxes', array( $this, 'add_b2b_pricing_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_b2b_pricing_data' ) );

		// Hooks de productos variables.
		add_filter( 'woocommerce_variation_prices', array( $this, 'apply_b2b_pricing_to_variations' ), 10, 3 );
	}

	/**
	 * Aplicar precios B2B a productos.
	 *
	 * @param float      $price Precio del producto.
	 * @param WC_Product $product Producto.
	 * @return float
	 */
	public function apply_b2b_pricing( $price, $product ) {
		if ( ! $product || ! is_user_logged_in() ) {
			return $price;
		}

		// Verificar si el usuario tiene rol B2B.
		if ( ! $this->is_b2b_user() ) {
			return $price;
		}

		// Obtener precio B2B del producto.
		$b2b_price = $this->get_b2b_price( $product->get_id() );
		if ( $b2b_price > 0 ) {
			return $b2b_price;
		}

		// Aplicar descuento por defecto si no hay precio específico.
		$b2b_discount = $this->get_b2b_discount_percentage();
		if ( $b2b_discount > 0 ) {
			$price = $price * ( 1 - ( $b2b_discount / 100 ) );
		}

		return $price;
	}

	/**
	 * Verificar si el usuario actual es B2B.
	 *
	 * @return bool
	 */
	private function is_b2b_user() {
		$user = wp_get_current_user();
		if ( ! $user ) {
			return false;
		}

		// Verificar roles B2B.
		$b2b_roles  = array( 'b2b_customer', 'wholesale_customer', 'administrator' );
		$user_roles = $user->roles;

		foreach ( $user_roles as $role ) {
			if ( in_array( $role, $b2b_roles, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Obtener precio B2B de un producto.
	 *
	 * @param int $product_id ID del producto.
	 * @return float
	 */
	private function get_b2b_price( $product_id ) {
		$b2b_price = get_post_meta( $product_id, '_b2b_price', true );
		return $b2b_price ? (float) $b2b_price : 0;
	}

	/**
	 * Obtener porcentaje de descuento B2B por defecto.
	 *
	 * @return float
	 */
	private function get_b2b_discount_percentage() {
		$discount = get_option( 'palafito_b2b_default_discount', 10 );
		return (float) $discount;
	}

	/**
	 * Display cart item price with B2B pricing.
	 *
	 * @param string $price_html   Price HTML.
	 * @param array  $cart_item    Cart item data.
	 * @param string $_cart_item_key Cart item key (unused).
	 * @return string
	 */
	public function display_cart_item_price( $price_html, $cart_item, $_cart_item_key ) {
		if ( ! $this->is_b2b_user() ) {
			return $price_html;
		}

		$product_id = $cart_item['product_id'];
		$b2b_price  = $this->get_b2b_price( $product_id );

		if ( $b2b_price > 0 ) {
			$price_html = wc_price( $b2b_price );
		}

		return $price_html;
	}

	/**
	 * Display cart item subtotal with B2B pricing.
	 *
	 * @param string $subtotal_html   Subtotal HTML.
	 * @param array  $cart_item       Cart item data.
	 * @param string $_cart_item_key  Cart item key (unused).
	 * @return string
	 */
	public function display_cart_item_subtotal( $subtotal_html, $cart_item, $_cart_item_key ) {
		if ( ! $this->is_b2b_user() ) {
			return $subtotal_html;
		}

		$product_id = $cart_item['product_id'];
		$quantity   = $cart_item['quantity'];
		$b2b_price  = $this->get_b2b_price( $product_id );

		if ( $b2b_price > 0 ) {
			$subtotal = $b2b_price * $quantity;
			$subtotal_html = wc_price( $subtotal );
		}

		return $subtotal_html;
	}

	/**
	 * Agregar meta box para precios B2B.
	 */
	public function add_b2b_pricing_meta_box() {
		add_meta_box(
			'palafito-b2b-pricing',
			esc_html__( 'Precios B2B', 'palafito-wc-extensions' ),
			array( $this, 'render_b2b_pricing_meta_box' ),
			'product',
			'side',
			'default'
		);
	}

	/**
	 * Renderizar meta box de precios B2B.
	 *
	 * @param WP_Post $post Objeto del post.
	 */
	public function render_b2b_pricing_meta_box( $post ) {
		wp_nonce_field( 'palafito_b2b_pricing_nonce', 'palafito_b2b_pricing_nonce' );

		$b2b_price          = get_post_meta( $post->ID, '_b2b_price', true );
		$quantity_discounts = get_post_meta( $post->ID, '_quantity_discounts', true );

		if ( ! is_array( $quantity_discounts ) ) {
			$quantity_discounts = array();
		}

		echo '<table class="form-table">';
		echo '<tr>';
		echo '<th scope="row">' . esc_html__( 'Precio B2B', 'palafito-wc-extensions' ) . '</th>';
		echo '<td>';
		echo '<input type="number" step="0.01" min="0" name="b2b_price" value="' . esc_attr( $b2b_price ) . '" class="regular-text" />';
		echo '<p class="description">' . esc_html__( 'Precio especial para clientes B2B. Dejar vacío para usar descuento por defecto.', 'palafito-wc-extensions' ) . '</p>';
		echo '</td>';
		echo '</tr>';
		echo '</table>';

		echo '<h4>' . esc_html__( 'Descuentos por Cantidad', 'palafito-wc-extensions' ) . '</h4>';
		echo '<div id="quantity-discounts">';

		foreach ( $quantity_discounts as $index => $discount ) {
			echo '<div class="discount-row">';
			echo '<label>' . esc_html__( 'Cantidad mínima:', 'palafito-wc-extensions' ) . '</label>';
			echo '<input type="number" min="1" name="quantity_discounts[' . esc_attr( $index ) . '][min_quantity]" value="' . esc_attr( $discount['min_quantity'] ) . '" />';
			echo '<label>' . esc_html__( 'Descuento (%):', 'palafito-wc-extensions' ) . '</label>';
			echo '<input type="number" step="0.01" min="0" max="100" name="quantity_discounts[' . esc_attr( $index ) . '][percentage]" value="' . esc_attr( $discount['percentage'] ) . '" />';
			echo '<button type="button" class="button remove-discount">' . esc_html__( 'Eliminar', 'palafito-wc-extensions' ) . '</button>';
			echo '</div>';
		}

		echo '</div>';
		echo '<button type="button" class="button" id="add-discount">' . esc_html__( 'Agregar Descuento', 'palafito-wc-extensions' ) . '</button>';
	}

	/**
	 * Save B2B pricing data.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_b2b_pricing_data( $post_id ) {
		// Verify nonce.
		if ( ! isset( $_POST['palafito_b2b_pricing_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['palafito_b2b_pricing_nonce'] ) ), 'palafito_b2b_pricing_nonce' ) ) {
			return;
		}

		// Verify permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save B2B price.
		if ( isset( $_POST['b2b_price'] ) ) {
			$b2b_price = sanitize_text_field( wp_unslash( $_POST['b2b_price'] ) );
			$b2b_price = $b2b_price ? (float) $b2b_price : '';
			update_post_meta( $post_id, '_b2b_price', $b2b_price );
		}

		// Save quantity discounts.
		if ( isset( $_POST['quantity_discounts'] ) && is_array( $_POST['quantity_discounts'] ) ) {
			$quantity_discounts = array();
			$discounts          = wp_unslash( $_POST['quantity_discounts'] );

			foreach ( $discounts as $discount ) {
				if ( ! empty( $discount['min_quantity'] ) && ! empty( $discount['percentage'] ) ) {
					$quantity_discounts[] = array(
						'min_quantity' => (int) sanitize_text_field( $discount['min_quantity'] ),
						'percentage'   => (float) sanitize_text_field( $discount['percentage'] ),
					);
				}
			}

			update_post_meta( $post_id, '_quantity_discounts', $quantity_discounts );
		}
	}

	/**
	 * Apply B2B pricing to product variations.
	 *
	 * @param array      $prices Variation prices.
	 * @param WC_Product $product Product object.
	 * @param bool       $for_display Whether for display.
	 * @return array
	 */
	public function apply_b2b_pricing_to_variations( $prices, $product, $for_display ) {
		if ( ! $this->is_b2b_user() ) {
			return $prices;
		}

		$variation_ids = array_keys( $prices['price'] );
		foreach ( $variation_ids as $variation_id ) {
			$b2b_price = $this->get_b2b_price( $variation_id );
			if ( $b2b_price > 0 ) {
				$prices['price'][ $variation_id ]         = $b2b_price;
				$prices['regular_price'][ $variation_id ] = $b2b_price;
				$prices['sale_price'][ $variation_id ]    = $b2b_price;
			}
		}

		return $prices;
	}

	/**
	 * Get quantity discount for a product.
	 *
	 * @param int $product_id Product ID.
	 * @param int $quantity   Quantity.
	 * @return float
	 */
	public function get_quantity_discount( $product_id, $quantity ) {
		$quantity_discounts = get_post_meta( $product_id, '_quantity_discounts', true );
		if ( ! is_array( $quantity_discounts ) ) {
			return 0;
		}

		$max_discount = 0;
		foreach ( $quantity_discounts as $discount ) {
			if ( $quantity >= $discount['min_quantity'] && $discount['percentage'] > $max_discount ) {
				$max_discount = $discount['percentage'];
			}
		}

		return $max_discount;
	}

	/**
	 * Get quantity discount rules for a product.
	 *
	 * @param int $product_id Product ID.
	 * @return array Discount rules.
	 */
	public function get_quantity_discounts( $product_id ) {
		return get_post_meta( $product_id, '_quantity_discounts', true );
	}
}
