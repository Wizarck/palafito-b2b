<?php
// Test script para probar sincronización de fechas
// Se ejecuta en el contexto de WordPress

// Cargar WordPress
define('WP_USE_THEMES', false);
require_once('/var/www/html/wp-load.php');

// Verificar que WooCommerce está disponible
if (!function_exists('wc_get_order')) {
    die("WooCommerce is not loaded\n");
}

// Obtener pedido de prueba
$order_id = 8;

echo "=== TESTING PALAFITO SYNC WITH update_post_meta ===\n";
echo "Order ID: $order_id\n";

// Mostrar estado inicial
$current_entregado = get_post_meta($order_id, '_entregado_date', true);
$current_packing = get_post_meta($order_id, '_wcpdf_packing-slip_date', true);

echo "Initial _entregado_date: $current_entregado\n";
echo "Initial _wcpdf_packing-slip_date: $current_packing\n";

// Test 1: Actualizar _wcpdf_packing-slip_date usando update_post_meta para disparar hooks
echo "\n--- Test 1: Updating _wcpdf_packing-slip_date ---\n";
$new_date = '2025-01-15';
$result = update_post_meta($order_id, '_wcpdf_packing-slip_date', $new_date);
echo "update_post_meta result: " . ($result ? 'true' : 'false') . "\n";

// Verificar resultado inmediatamente
$updated_entregado = get_post_meta($order_id, '_entregado_date', true);
$updated_packing = get_post_meta($order_id, '_wcpdf_packing-slip_date', true);

echo "After update _entregado_date: $updated_entregado\n";
echo "After update _wcpdf_packing-slip_date: $updated_packing\n";

if ($updated_entregado === $new_date) {
    echo "✅ SYNC SUCCESS: _entregado_date was synchronized!\n";
} else {
    echo "❌ SYNC FAILED: _entregado_date was NOT synchronized\n";
}

// Test 2: Actualizar _entregado_date
echo "\n--- Test 2: Updating _entregado_date ---\n";
$new_date2 = '2025-01-20';
$result2 = update_post_meta($order_id, '_entregado_date', $new_date2);
echo "update_post_meta result: " . ($result2 ? 'true' : 'false') . "\n";

// Verificar resultado
$final_entregado = get_post_meta($order_id, '_entregado_date', true);
$final_packing = get_post_meta($order_id, '_wcpdf_packing-slip_date', true);

echo "After update _entregado_date: $final_entregado\n";
echo "After update _wcpdf_packing-slip_date: $final_packing\n";

if ($final_packing === $new_date2) {
    echo "✅ REVERSE SYNC SUCCESS: _wcpdf_packing-slip_date was synchronized!\n";
} else {
    echo "❌ REVERSE SYNC FAILED: _wcpdf_packing-slip_date was NOT synchronized\n";
}

echo "\n=== TEST COMPLETED ===\n";
?>