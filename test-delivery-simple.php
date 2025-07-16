<?php
/**
 * Test simple de columna fecha de entrega con pedidos específicos
 */

// Cargar WordPress
require_once(__DIR__ . '/wp-load.php');

echo "🧪 TEST SIMPLE - COLUMNA FECHA DE ENTREGA\n";
echo "══════════════════════════════════════════\n\n";

// IDs de pedidos para probar
$test_orders = [2510, 2509, 2508, 2507, 2506, 2500, 2490, 2480];

echo "🔍 PROBANDO PEDIDOS ESPECÍFICOS:\n";
echo "─────────────────────────────────\n";

$found_orders = 0;

foreach ($test_orders as $order_id) {
    $order = wc_get_order($order_id);

    if (!$order) {
        echo "❌ Pedido #$order_id: No encontrado\n";
        continue;
    }

    $found_orders++;
    $order_status = $order->get_status();

    echo "📋 Pedido #$order_id:\n";
    echo "   Estado: $order_status\n";

    // Verificar campos de fecha
    $delivery_date = $order->get_meta('_wcpdf_packing-slip_date');
    $legacy_date = $order->get_meta('_wcpdf_packing_slip_date');

    echo "   Campo estándar: " . ($delivery_date ? date('Y-m-d H:i:s', $delivery_date) : 'VACÍO') . "\n";
    echo "   Campo legacy: " . ($legacy_date ? date('Y-m-d H:i:s', $legacy_date) : 'VACÍO') . "\n";

    // Simular lógica de columna
    $valid_statuses = ['entregado', 'facturado', 'completed'];

    if (in_array($order_status, $valid_statuses, true)) {
        if ($delivery_date) {
            $date = is_numeric($delivery_date) ? $delivery_date : strtotime($delivery_date);
            $formatted_date = date_i18n('d-m-Y', $date);
            echo "   🟢 Columna: $formatted_date\n";
        } else {
            echo "   🔴 Columna: — (sin fecha)\n";
        }
    } else {
        echo "   ⚪ Columna: — (estado no válido)\n";
    }

    echo "\n";
}

if ($found_orders === 0) {
    echo "❌ No se encontraron pedidos válidos para probar\n";
    echo "ℹ️ Esto podría indicar que:\n";
    echo "   - Los pedidos no existen en este entorno\n";
    echo "   - Hay un problema con la conexión a la base de datos\n";
    echo "   - Los post types no están configurados correctamente\n";
} else {
    echo "✅ Se encontraron $found_orders pedidos para probar\n";
}

// Verificar también algunos metadatos del sistema
echo "\n🔧 DIAGNÓSTICO DEL SISTEMA:\n";
echo "────────────────────────────────\n";

// Verificar si WooCommerce está activo
if (class_exists('WooCommerce')) {
    echo "✅ WooCommerce está activo\n";
} else {
    echo "❌ WooCommerce NO está activo\n";
}

// Verificar si nuestro plugin está activo
if (class_exists('Palafito_WC_Extensions')) {
    echo "✅ Plugin Palafito WC Extensions está activo\n";
} else {
    echo "❌ Plugin Palafito WC Extensions NO está activo\n";
}

// Contar pedidos en total
$total_orders = wp_count_posts('shop_order');
if ($total_orders) {
    $published = $total_orders->publish ?? 0;
    $private = $total_orders->private ?? 0;
    $total = $published + $private;
    echo "📊 Total de pedidos en sistema: $total\n";
} else {
    echo "❌ No se pudo contar pedidos\n";
}

echo "\n✅ Test simple completado\n";
?>
