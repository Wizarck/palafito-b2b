<?php
// Test directo de la función de sincronización
require_once('/var/www/html/wp-load.php');

// Test manual de la función
$order_id = 8;
$meta_id = 999;
$meta_key = '_wcpdf_packing-slip_date';
$meta_value = '2025-01-25';

echo "Testing function palafito_sync_packing_slip_to_entregado\n";
echo "Order ID: $order_id\n";
echo "Meta key: $meta_key\n";
echo "Meta value: $meta_value\n";

// Verificar estado inicial
$before_entregado = get_post_meta($order_id, '_entregado_date', true);
$before_packing = get_post_meta($order_id, '_wcpdf_packing-slip_date', true);
echo "Before - _entregado_date: $before_entregado\n";
echo "Before - _wcpdf_packing-slip_date: $before_packing\n";

// Verificar si la función existe
if (function_exists('palafito_sync_packing_slip_to_entregado')) {
    echo "Function exists, calling directly...\n";
    palafito_sync_packing_slip_to_entregado($meta_id, $order_id, $meta_key, $meta_value);
} else {
    echo "Function does not exist\n";
}

// Verificar estado después
$after_entregado = get_post_meta($order_id, '_entregado_date', true);
$after_packing = get_post_meta($order_id, '_wcpdf_packing-slip_date', true);
echo "After - _entregado_date: $after_entregado\n";
echo "After - _wcpdf_packing-slip_date: $after_packing\n";

if ($after_entregado === $meta_value) {
    echo "✅ MANUAL TEST SUCCESS!\n";
} else {
    echo "❌ MANUAL TEST FAILED\n";
}
?>