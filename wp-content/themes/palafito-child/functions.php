<?php
/**
 * Funciones del tema hijo Palafito
 *
 * @package Palafito_Child
 * @version 1.0.0
 */

// Seguridad: evita acceso directo
defined('ABSPATH') || exit;

/**
 * Clase principal del tema hijo
 */
class Palafito_Child_Theme {

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // Hooks básicos del tema
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // Personalizaciones de WooCommerce
        add_action('after_setup_theme', [$this, 'woocommerce_setup']);
        add_filter('woocommerce_enqueue_styles', [$this, 'dequeue_woocommerce_styles']);
        
        // Personalizaciones del header
        add_action('wp_head', [$this, 'add_custom_meta_tags']);
        add_filter('wp_title', [$this, 'custom_wp_title'], 10, 2);
        
        // Personalizaciones del footer
        add_action('wp_footer', [$this, 'add_custom_footer_content']);
        
        // Personalizaciones de productos
        add_action('woocommerce_before_shop_loop_item', [$this, 'add_product_badge']);
        add_action('woocommerce_after_shop_loop_item_title', [$this, 'add_product_meta']);
        
        // Personalizaciones de carrito
        add_filter('woocommerce_add_to_cart_fragments', [$this, 'cart_fragments']);
        add_action('woocommerce_before_cart', [$this, 'add_cart_notice']);
        
        // Personalizaciones de checkout
        add_action('woocommerce_before_checkout_form', [$this, 'add_checkout_header']);
        
        // Personalizaciones de cuenta
        add_action('woocommerce_account_navigation', [$this, 'customize_account_navigation']);
        
        // Personalizaciones de emails
        add_filter('woocommerce_email_styles', [$this, 'customize_email_styles']);
        
        // Soporte para características adicionales
        add_action('after_setup_theme', [$this, 'theme_support']);
        
        // Personalizaciones de admin
        if (is_admin()) {
            add_action('admin_enqueue_scripts', [$this, 'admin_styles']);
        }
    }

    /**
     * Enqueue de estilos
     */
    public function enqueue_styles() {
        // Estilo principal del tema hijo
        wp_enqueue_style(
            'palafito-child-style',
            get_stylesheet_uri(),
            ['kadence-style'],
            filemtime(get_stylesheet_directory() . '/style.css')
        );

        // Estilos personalizados para WooCommerce
        if (class_exists('WooCommerce')) {
            wp_enqueue_style(
                'palafito-woocommerce-style',
                get_stylesheet_directory_uri() . '/woocommerce.css',
                ['palafito-child-style'],
                filemtime(get_stylesheet_directory() . '/woocommerce.css')
            );
        }

        // Estilos del plugin personalizado
        if (is_plugin_active('palafito-wc-extensions/palafito-wc-extensions.php')) {
            wp_enqueue_style(
                'palafito-wc-extensions-style',
                plugins_url('assets/css/palafito-wc-extensions.css', 'palafito-wc-extensions/palafito-wc-extensions.php'),
                ['palafito-child-style'],
                PALAFITO_WC_EXTENSIONS_VERSION
            );
        }

        // Google Fonts
        wp_enqueue_style(
            'palafito-google-fonts',
            'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
            [],
            null
        );
    }

    /**
     * Enqueue de scripts
     */
    public function enqueue_scripts() {
        // Script principal del tema hijo
        wp_enqueue_script(
            'palafito-child-script',
            get_stylesheet_directory_uri() . '/js/palafito-child.js',
            ['jquery'],
            filemtime(get_stylesheet_directory() . '/js/palafito-child.js'),
            true
        );

        // Localizar script para AJAX
        wp_localize_script('palafito-child-script', 'palafito_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('palafito_nonce'),
        ]);

        // Scripts específicos de WooCommerce
        if (class_exists('WooCommerce') && is_woocommerce()) {
            wp_enqueue_script(
                'palafito-woocommerce-script',
                get_stylesheet_directory_uri() . '/js/woocommerce.js',
                ['jquery', 'wc-add-to-cart'],
                filemtime(get_stylesheet_directory() . '/js/woocommerce.js'),
                true
            );
        }
    }

    /**
     * Configuración de WooCommerce
     */
    public function woocommerce_setup() {
        // Soporte para WooCommerce
        add_theme_support('woocommerce');
        add_theme_support('wc-product-gallery-zoom');
        add_theme_support('wc-product-gallery-lightbox');
        add_theme_support('wc-product-gallery-slider');

        // Personalizar número de productos por página
        add_filter('loop_shop_per_page', function() {
            return 12;
        }, 20);

        // Personalizar columnas en grid
        add_filter('loop_shop_columns', function() {
            return 3;
        }, 20);
    }

    /**
     * Dequeue estilos de WooCommerce no deseados
     *
     * @param array $enqueue_styles Estilos de WooCommerce
     * @return array
     */
    public function dequeue_woocommerce_styles($enqueue_styles) {
        // Remover estilos específicos si es necesario
        // unset($enqueue_styles['woocommerce-general']);
        
        return $enqueue_styles;
    }

    /**
     * Agregar meta tags personalizados
     */
    public function add_custom_meta_tags() {
        echo '<meta name="theme-color" content="#667eea">';
        echo '<meta name="msapplication-TileColor" content="#667eea">';
        echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">';
    }

    /**
     * Personalizar título de página
     *
     * @param string $title Título actual
     * @param string $sep Separador
     * @return string
     */
    public function custom_wp_title($title, $sep) {
        if (is_woocommerce()) {
            $title = 'Palafito B2B - ' . $title;
        }
        
        return $title;
    }

    /**
     * Agregar contenido personalizado al footer
     */
    public function add_custom_footer_content() {
        if (is_woocommerce()) {
            echo '<div class="palafito-footer-notice">';
            echo '<p>Palafito B2B - Tu proveedor de confianza para mayoristas</p>';
            echo '</div>';
        }
    }

    /**
     * Agregar badge a productos
     */
    public function add_product_badge() {
        global $product;
        
        if ($product && $product->is_on_sale()) {
            echo '<div class="product-badge sale-badge">Oferta</div>';
        }
        
        // Badge para productos B2B
        if ($product && get_post_meta($product->get_id(), '_b2b_price', true)) {
            echo '<div class="product-badge b2b-badge">B2B</div>';
        }
    }

    /**
     * Agregar meta información del producto
     */
    public function add_product_meta() {
        global $product;
        
        if ($product) {
            $sku = $product->get_sku();
            if ($sku) {
                echo '<div class="product-sku">SKU: ' . esc_html($sku) . '</div>';
            }
        }
    }

    /**
     * Fragmentos del carrito para AJAX
     *
     * @param array $fragments Fragmentos del carrito
     * @return array
     */
    public function cart_fragments($fragments) {
        // Agregar fragmentos personalizados si es necesario
        $fragments['.cart-count'] = '<span class="cart-count">' . WC()->cart->get_cart_contents_count() . '</span>';
        
        return $fragments;
    }

    /**
     * Agregar aviso al carrito
     */
    public function add_cart_notice() {
        if (WC()->cart->is_empty()) {
            echo '<div class="woocommerce-info empty-cart-notice">';
            echo '<p>Tu carrito está vacío. ¡Explora nuestros productos B2B!</p>';
            echo '</div>';
        }
    }

    /**
     * Agregar header personalizado al checkout
     */
    public function add_checkout_header() {
        echo '<div class="checkout-header">';
        echo '<h2>Finalizar Compra - Palafito B2B</h2>';
        echo '<p>Completa tu pedido mayorista de forma segura</p>';
        echo '</div>';
    }

    /**
     * Personalizar navegación de cuenta
     */
    public function customize_account_navigation() {
        // Agregar enlaces personalizados si es necesario
        add_filter('woocommerce_account_menu_items', function($items) {
            $items['b2b-info'] = __('Información B2B', 'palafito-child');
            return $items;
        });
    }

    /**
     * Personalizar estilos de emails
     *
     * @param string $css Estilos CSS
     * @return string
     */
    public function customize_email_styles($css) {
        $css .= '
        .palafito-email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .palafito-email-footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            margin-top: 30px;
        }
        ';
        
        return $css;
    }

    /**
     * Soporte para características del tema
     */
    public function theme_support() {
        // Soporte para HTML5
        add_theme_support('html5', [
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
        ]);

        // Soporte para título dinámico
        add_theme_support('title-tag');

        // Soporte para logo personalizado
        add_theme_support('custom-logo', [
            'height' => 100,
            'width' => 400,
            'flex-height' => true,
            'flex-width' => true,
        ]);

        // Soporte para miniaturas destacadas
        add_theme_support('post-thumbnails');

        // Soporte para widgets
        add_theme_support('customize-selective-refresh-widgets');
    }

    /**
     * Estilos de admin
     */
    public function admin_styles() {
        wp_enqueue_style(
            'palafito-admin-style',
            get_stylesheet_directory_uri() . '/admin.css',
            [],
            filemtime(get_stylesheet_directory() . '/admin.css')
        );
    }
}

// Inicializar el tema
new Palafito_Child_Theme();

/**
 * Funciones auxiliares
 */

/**
 * Obtener URL del logo personalizado
 *
 * @return string
 */
function palafito_get_logo_url() {
    $custom_logo_id = get_theme_mod('custom_logo');
    $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
    
    return $logo ? $logo[0] : get_stylesheet_directory_uri() . '/images/logo.png';
}

/**
 * Verificar si es página de WooCommerce
 *
 * @return bool
 */
function palafito_is_woocommerce_page() {
    return class_exists('WooCommerce') && (is_woocommerce() || is_cart() || is_checkout() || is_account_page());
}

/**
 * Obtener información del usuario B2B
 *
 * @param int $user_id ID del usuario
 * @return array
 */
function palafito_get_b2b_user_info($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    return [
        'is_b2b' => get_user_meta($user_id, '_is_b2b_customer', true),
        'company_name' => get_user_meta($user_id, 'billing_company', true),
        'rfc' => get_user_meta($user_id, 'billing_rfc', true),
        'payment_terms' => get_user_meta($user_id, 'billing_payment_terms', true),
    ];
}

/**
 * Formatear precio para B2B
 *
 * @param float $price Precio
 * @param string $currency Moneda
 * @return string
 */
function palafito_format_b2b_price($price, $currency = 'MXN') {
    return sprintf(
        '<span class="b2b-price-formatted">%s %s</span>',
        $currency,
        number_format($price, 2, '.', ',')
    );
}

/**
 * Hook para limpiar caché al actualizar productos
 */
add_action('woocommerce_update_product', function($product_id) {
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
});

/**
 * Personalizar mensajes de WooCommerce
 */
add_filter('woocommerce_add_to_cart_message', function($message, $product_id) {
    $product = wc_get_product($product_id);
    if ($product) {
        $message = sprintf(
            __('¡%s agregado al carrito! <a href="%s">Ver carrito</a>', 'palafito-child'),
            $product->get_name(),
            wc_get_cart_url()
        );
    }
    return $message;
}, 10, 2);