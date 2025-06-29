<?php
/**
 * Plugin Name: Palafito WC Extensions
 * Description: Personalizaciones funcionales de WooCommerce para el proyecto Palafito.
 * Author: Arturo Ramirez
 * Version: 1.0.0
 */

// Seguridad: evita acceso directo
defined('ABSPATH') || exit;

// Aquí van tus funciones personalizadas
add_action('init', function () {
    // Puedes dejar este mensaje para saber que el plugin carga bien
    error_log('[Palafito WC Extensions] Plugin cargado correctamente');
});

/**
 * Añadir un aviso personalizado en el checkout de WooCommerce.
 */
add_action('woocommerce_before_checkout_form', 'palafito_custom_checkout_notice');