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
 * Disable Kadence dynamic CSS generation to avoid CSP issues
 * This prevents inline styles that are blocked by Content Security Policy
 */
function palafito_disable_kadence_dynamic_css($css) {
    // Debug: log that this filter is being called
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Palafito: kadence_dynamic_css filter called - returning empty string');
    }
    return '';
}

// Add filter with high priority to ensure it runs
add_filter('kadence_dynamic_css', 'palafito_disable_kadence_dynamic_css', 999);

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
        
        // Soporte para características adicionales
        add_action('after_setup_theme', [$this, 'theme_support']);
    }

    /**
     * Enqueue de estilos
     */
    public function enqueue_styles() {
        // Let Kadence handle its own styles automatically
        // We only need to enqueue our child theme styles
        
        // Enqueue child theme style with dependency on Kadence
        wp_enqueue_style( 
            'palafito-child-style', 
            get_stylesheet_directory_uri() . '/style.css',
            array(), // Let WordPress handle dependencies automatically
            wp_get_theme( get_stylesheet() )->get( 'Version' )
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
            'style',
            'script',
        ]);

        // Soporte para título dinámico
        add_theme_support('title-tag');

        // Soporte para imágenes destacadas
        add_theme_support('post-thumbnails');

        // Soporte para logo personalizado
        add_theme_support('custom-logo', [
            'height' => 100,
            'width' => 400,
            'flex-height' => true,
            'flex-width' => true,
        ]);

        // Soporte para menús
        register_nav_menus([
            'primary' => __('Menú Principal', 'palafito-child'),
            'footer' => __('Menú Footer', 'palafito-child'),
        ]);

        // Soporte para widgets
        add_theme_support('customize-selective-refresh-widgets');
    }
}

// Inicializar el tema
new Palafito_Child_Theme();

/**
 * Obtener URL del logo
 *
 * @return string
 */
function palafito_get_logo_url() {
    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        return wp_get_attachment_image_url($custom_logo_id, 'full');
    }
    
    return get_stylesheet_directory_uri() . '/images/logo.png';
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
 * @param int|null $user_id ID del usuario
 * @return array
 */
function palafito_get_b2b_user_info($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return [];
    }
    
    $user = get_userdata($user_id);
    if (!$user) {
        return [];
    }
    
    return [
        'id' => $user_id,
        'name' => $user->display_name,
        'email' => $user->user_email,
        'role' => $user->roles[0] ?? '',
        'company' => get_user_meta($user_id, 'billing_company', true),
        'rfc' => get_user_meta($user_id, 'billing_rfc', true),
    ];
}

/**
 * Formatear precio B2B
 *
 * @param float $price Precio
 * @param string $currency Moneda
 * @return string
 */
function palafito_format_b2b_price($price, $currency = 'MXN') {
    if (!class_exists('WooCommerce')) {
        return number_format($price, 2) . ' ' . $currency;
    }
    
    return wc_price($price, ['currency' => $currency]);
}

/**
 * Add custom functionality for B2B features
 */
function palafito_child_b2b_features() {
    // B2B specific customizations can be added here
}
add_action( 'init', 'palafito_child_b2b_features' );