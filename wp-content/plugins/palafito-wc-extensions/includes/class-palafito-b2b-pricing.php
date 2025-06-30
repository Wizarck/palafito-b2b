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
	 * Mostrar precio B2B en el carrito.
	 *
	 * @param string $price_html HTML del precio.
	 * @param array  $cart_item Item del carrito.
	 * @param string $cart_item_key Clave del item del carrito.
	 * @return string
	 */
	public function display_cart_item_price( $price_html, $cart_item, $cart_item_key ) {
		if ( ! $this->is_b2b_user() ) {
			return $price_html;
		}

		$product   = $cart_item['data'];
		$b2b_price = $this->get_b2b_price( $product->get_id() );

		if ( $b2b_price > 0 ) {
			$price_html = '<span class="b2b-price">';
			/* translators: %s: B2B price */
			$price_html .= sprintf( esc_html__( 'Precio B2B: %s', 'palafito-wc-extensions' ), wc_price( $b2b_price ) );
			$price_html .= '</span>';
		}

		return $price_html;
	}

	/**
	 * Mostrar subtotal B2B en el carrito.
	 *
	 * @param string $subtotal_html HTML del subtotal.
	 * @param array  $cart_item Item del carrito.
	 * @param string $cart_item_key Clave del item del carrito.
	 * @return string
	 */
	public function display_cart_item_subtotal( $subtotal_html, $cart_item, $cart_item_key ) {
		if ( ! $this->is_b2b_user() ) {
			return $subtotal_html;
		}

		$product   = $cart_item['data'];
		$b2b_price = $this->get_b2b_price( $product->get_id() );

		if ( $b2b_price > 0 ) {
			$b2b_subtotal  = $b2b_price * $cart_item['quantity'];
			$subtotal_html = '<span class="b2b-subtotal">';
			/* translators: %s: B2B subtotal */
			$subtotal_html .= sprintf( esc_html__( 'Subtotal B2B: %s', 'palafito-wc-extensions' ), wc_price( $b2b_subtotal ) );
			$subtotal_html .= '</span>';
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
	 * Guardar datos de precios B2B.
	 *
	 * @param int $post_id ID del post.
	 */
	public function save_b2b_pricing_data( $post_id ) {
		// Verificar nonce.
		if ( ! isset( $_POST['palafito_b2b_pricing_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['palafito_b2b_pricing_nonce'] ) ), 'palafito_b2b_pricing_nonce' ) ) {
			return;
		}

		// Verificar permisos.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Guardar precio B2B.
		if ( isset( $_POST['b2b_price'] ) ) {
			$b2b_price = sanitize_text_field( wp_unslash( $_POST['b2b_price'] ) );
			$b2b_price = $b2b_price ? (float) $b2b_price : '';
			update_post_meta( $post_id, '_b2b_price', $b2b_price );
		}

		// Guardar descuentos por cantidad.
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
	 * Aplicar precios B2B a variaciones de productos.
	 *
	 * @param array      $prices Precios de variaciones.
	 * @param WC_Product $product Producto.
	 * @param bool       $for_display Si es para mostrar.
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
	 * Obtener descuento por cantidad para un producto.
	 *
	 * @param int $product_id ID del producto.
	 * @param int $quantity Cantidad.
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
}
