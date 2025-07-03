<?php
// Test script para probar sincronización de fechas
// Se ejecuta en el contexto de WordPress

// Cargar WordPress
define('WP_USE_THEMES', false);
require_once('/var/www/html/wp-load.php');

// Obtener pedido de prueba
$order_id = 8;
$order = wc_get_order($order_id);

if (!$order) {
    die("Order not found\n");
}

echo "=== TESTING PALAFITO SYNC FUNCTIONALITY ===\n";
echo "Order ID: $order_id\n";

// Mostrar estado inicial
$current_entregado = $order->get_meta('_entregado_date');
$current_packing = $order->get_meta('_wcpdf_packing-slip_date');

echo "Initial _entregado_date: $current_entregado\n";
echo "Initial _wcpdf_packing-slip_date: $current_packing\n";

// Test 1: Actualizar _wcpdf_packing-slip_date usando update_meta_data
echo "\n--- Test 1: Updating _wcpdf_packing-slip_date ---\n";
$new_date = '2025-01-15';
$order->update_meta_data('_wcpdf_packing-slip_date', $new_date);
$order->save_meta_data();

// Verificar resultado
$order = wc_get_order($order_id); // Recargar orden
$updated_entregado = $order->get_meta('_entregado_date');
$updated_packing = $order->get_meta('_wcpdf_packing-slip_date');

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
$order->update_meta_data('_entregado_date', $new_date2);
$order->save_meta_data();

// Verificar resultado
$order = wc_get_order($order_id); // Recargar orden
$final_entregado = $order->get_meta('_entregado_date');
$final_packing = $order->get_meta('_wcpdf_packing-slip_date');

echo "After update _entregado_date: $final_entregado\n";
echo "After update _wcpdf_packing-slip_date: $final_packing\n";

if ($final_packing === $new_date2) {
    echo "✅ REVERSE SYNC SUCCESS: _wcpdf_packing-slip_date was synchronized!\n";
} else {
    echo "❌ REVERSE SYNC FAILED: _wcpdf_packing-slip_date was NOT synchronized\n";
}

echo "\n=== TEST COMPLETED ===\n";
?>