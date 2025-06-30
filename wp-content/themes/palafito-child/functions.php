<?php
/**
 * Funciones del tema hijo Palafito
 *
 * @package Palafito_Child
 * @version 1.0.2
 */

// Seguridad: evita acceso directo
defined('ABSPATH') || exit;

/**
 * Filter Kadence dynamic CSS to remove only inline styles that cause CSP issues
 * Keep the dynamic CSS generation but remove problematic inline styles
 */
function palafito_filter_kadence_dynamic_css($css) {
    // Debug: log that this filter is being called
    error_log('Palafito: kadence_dynamic_css filter called with length: ' . strlen($css));
    error_log('Palafito: CSS content preview: ' . substr($css, 0, 200));
    
    // If CSS is empty, don't filter it
    if (empty($css)) {
        error_log('Palafito: CSS is empty, returning as is');
        return $css;
    }
    
    // Remove any style tags or inline styles that might cause CSP issues
    $css = preg_replace('/<style[^>]*>.*?<\/style>/s', '', $css);
    
    // Keep the CSS but ensure it's clean
    return $css;
}

// Use a lower priority to allow Kadence to generate CSS first, then filter
add_filter('kadence_dynamic_css', 'palafito_filter_kadence_dynamic_css', 20);

/**
 * Debug: Catch CSS output to see what's happening
 */
function palafito_debug_css_output() {
    error_log('Palafito: Debug CSS output function called');
}
add_action('wp_head', 'palafito_debug_css_output', 1);

/**
 * Remove inline styles from wp_head that cause CSP issues
 */
function palafito_remove_inline_styles() {
    // Remove emoji styles that can cause CSP issues
    remove_action('wp_head', 'print_emoji_styles');
    remove_action('wp_print_styles', 'print_emoji_styles');
    
    // Remove admin bar for non-admin users to avoid inline styles
    if (!current_user_can('administrator')) {
        add_filter('show_admin_bar', '__return_false');
    }
}
add_action('init', 'palafito_remove_inline_styles');

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
        // Enqueue child theme style con dependencia explícita de Kadence
        wp_enqueue_style( 
            'palafito-child-style', 
            get_stylesheet_directory_uri() . '/style.css',
            array('kadence-style'), // Asegura que Kadence se cargue antes
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

/**
 * Comprehensive fix for Mixed Content warnings
 * Runs on init to ensure all URLs are properly converted
 */
function palafito_comprehensive_https_fix() {
    // Only run on frontend
    if (is_admin()) {
        return;
    }
    
    // Fix various URL scenarios
    add_filter('wp_get_attachment_url', function($url) {
        return str_replace('http://palafitofood.com/', 'https://palafitofood.com/', $url);
    });
    
    add_filter('wp_get_attachment_image_src', function($image) {
        if (is_array($image) && isset($image[0])) {
            $image[0] = str_replace('http://palafitofood.com/', 'https://palafitofood.com/', $image[0]);
        }
        return $image;
    });
    
    add_filter('wp_get_attachment_image_attributes', function($attr) {
        if (isset($attr['src'])) {
            $attr['src'] = str_replace('http://palafitofood.com/', 'https://palafitofood.com/', $attr['src']);
        }
        if (isset($attr['srcset'])) {
            $attr['srcset'] = str_replace('http://palafitofood.com/', 'https://palafitofood.com/', $attr['srcset']);
        }
        return $attr;
    });
    
    // Fix content URLs
    add_filter('the_content', function($content) {
        return str_replace('http://palafitofood.com/', 'https://palafitofood.com/', $content);
    });
    
    // Fix widget content
    add_filter('widget_text', function($text) {
        return str_replace('http://palafitofood.com/', 'https://palafitofood.com/', $text);
    });
    
    // Fix shortcode content
    add_filter('do_shortcode_tag', function($output, $tag, $attr, $m) {
        return str_replace('http://palafitofood.com/', 'https://palafitofood.com/', $output);
    }, 10, 4);
}

add_action('init', 'palafito_comprehensive_https_fix');

// Enqueue parent and child theme styles
function palafito_child_enqueue_styles() {
    wp_enqueue_style( 'kadence-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'palafito-child-style', get_stylesheet_directory_uri() . '/style.css', array( 'kadence-style' ), wp_get_theme()->get('Version') );
}
add_action( 'wp_enqueue_scripts', 'palafito_child_enqueue_styles' );

// Modify formatted addresses to show postcode + city - country format
function palafito_modify_formatted_address( $formatted_address, $args ) {
    // Only modify Spanish addresses
    if ( isset( $args['country'] ) && $args['country'] === 'ES' ) {
        // Split the address into lines
        $lines = explode( "\n", $formatted_address );
        
        // Find the line with postcode and city (usually the 5th line for Spanish addresses)
        foreach ( $lines as $index => $line ) {
            if ( preg_match( '/^\d{5}\s+\w+/', trim( $line ) ) ) {
                // This is the postcode + city line, add country to it
                $lines[$index] = trim( $line ) . ' - España';
                break;
            }
        }
        
        $formatted_address = implode( "\n", $lines );
    }
    
    return $formatted_address;
}
add_filter( 'woocommerce_formatted_address', 'palafito_modify_formatted_address', 10, 2 );

// Add email to shop address in PDF templates
function palafito_add_email_to_shop_address( $address, $document ) {
    // Only apply to our custom templates
    if ( ! in_array( $document->get_type(), array( 'invoice', 'packing-slip' ) ) ) {
        return $address;
    }

    // Añadir '- España' solo si el país es España y no está ya
    $country = $document->order->get_billing_country();
    if ( $country === 'ES' && strpos($address, 'España') === false ) {
        // Añadir al final de la última línea de dirección
        $lines = explode("\n", $address);
        // Evitar duplicados de ciudad
        $lines = array_unique($lines);
        // Añadir '- España' a la última línea de dirección (antes del email si existe)
        $last = array_pop($lines);
        if (filter_var($last, FILTER_VALIDATE_EMAIL)) {
            $email = $last;
            $last = array_pop($lines);
            $last .= ' - España';
            $lines[] = $last;
            $lines[] = $email;
        } else {
            $last .= ' - España';
            $lines[] = $last;
        }
        $address = implode("\n", $lines);
    }

    // Add email on a new line at the end of shop address (si no está ya)
    $email = 'hola@palafitofood.com';
    if (strpos($address, $email) === false) {
        $address .= "\n" . $email;
    }

    return $address;
}
add_filter( 'wpo_wcpdf_shop_address', 'palafito_add_email_to_shop_address', 20, 2 );

// Convert HTTP URLs to HTTPS
function palafito_convert_http_to_https( $content ) {
    if ( is_ssl() ) {
        $content = str_replace( 'http://', 'https://', $content );
    }
    return $content;
}
add_filter( 'the_content', 'palafito_convert_http_to_https' );
add_filter( 'widget_text', 'palafito_convert_http_to_https' );

// Convert attachment URLs to HTTPS
function palafito_convert_attachment_urls_to_https( $url ) {
    if ( is_ssl() && strpos( $url, 'http://' ) === 0 ) {
        $url = str_replace( 'http://', 'https://', $url );
    }
    return $url;
}
add_filter( 'wp_get_attachment_url', 'palafito_convert_attachment_urls_to_https' );
add_filter( 'wp_get_attachment_image_src', function( $image ) {
    if ( is_array( $image ) && isset( $image[0] ) ) {
        $image[0] = palafito_convert_attachment_urls_to_https( $image[0] );
    }
    return $image;
} );

// Disable Kadence dynamic CSS generation to avoid CSP issues
function palafito_disable_kadence_dynamic_css( $css ) {
    // Log that the filter is being called
    error_log( 'Palafito: Kadence dynamic CSS filter called - CSS length: ' . strlen( $css ) );
    
    // Return empty CSS to disable dynamic generation
    return '';
}
add_filter( 'kadence_dynamic_css', 'palafito_disable_kadence_dynamic_css', 999 );
add_filter( 'kadence_blocks_dynamic_css', 'palafito_disable_kadence_dynamic_css', 999 );
add_filter( 'kadence_theme_dynamic_css', 'palafito_disable_kadence_dynamic_css', 999 );