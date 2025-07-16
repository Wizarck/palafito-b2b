<?php
/**
 * Test de la columna de fecha de entrega en listado de pedidos
 */

// Cargar WordPress
require_once(__DIR__ . '/wp-load.php');

echo "🧪 TEST DE COLUMNA FECHA DE ENTREGA\n";
echo "══════════════════════════════════════════\n\n";

// Obtener algunos pedidos para probar
$orders_query = new WP_Query([
    'post_type' => 'shop_order',
    'post_status' => 'any',
    'posts_per_page' => 10,
    'orderby' => 'date',
    'order' => 'DESC'
]);

if (empty($orders_query->posts)) {
    echo "❌ No se encontraron pedidos para probar\n";
    exit;
}

echo "📋 PROBANDO COLUMNA CON PEDIDOS RECIENTES:\n";
echo "────────────────────────────────────────────\n";

foreach ($orders_query->posts as $post) {
    $order = wc_get_order($post->ID);
    if (!$order) continue;

    $order_id = $order->get_id();
    $order_status = $order->get_status();

    echo "🔍 Pedido #$order_id (Estado: $order_status)\n";

    // Verificar si tiene el campo de fecha de entrega
    $delivery_date = $order->get_meta('_wcpdf_packing-slip_date');
    $legacy_date = $order->get_meta('_wcpdf_packing_slip_date');

    echo "   📅 Campo estándar (_wcpdf_packing-slip_date): " . ($delivery_date ? date('Y-m-d H:i:s', $delivery_date) : 'VACÍO') . "\n";
    echo "   📅 Campo legacy (_wcpdf_packing_slip_date): " . ($legacy_date ? date('Y-m-d H:i:s', $legacy_date) : 'VACÍO') . "\n";

    // Simular la lógica de la columna
    $valid_statuses = array('entregado', 'facturado', 'completed');

    if (in_array($order_status, $valid_statuses, true)) {
        if ($delivery_date) {
            $date = is_numeric($delivery_date) ? $delivery_date : strtotime($delivery_date);
            $formatted_date = date_i18n('d-m-Y', $date);
            echo "   ✅ Columna mostraría: $formatted_date\n";
        } else {
            echo "   ❌ Columna mostraría: —\n";
        }
    } else {
        echo "   ℹ️ Columna no aplica (estado no válido)\n";
    }

    echo "\n";
}

// Probar específicamente con pedido #2510 si existe
echo "🎯 PRUEBA ESPECÍFICA CON PEDIDO #2510:\n";
echo "────────────────────────────────────────────\n";

$test_order = wc_get_order(2510);
if ($test_order) {
    $order_status = $test_order->get_status();
    $delivery_date = $test_order->get_meta('_wcpdf_packing-slip_date');
    $legacy_date = $test_order->get_meta('_wcpdf_packing_slip_date');

    echo "📋 Estado actual: $order_status\n";
    echo "📅 Campo estándar: " . ($delivery_date ? date('Y-m-d H:i:s', $delivery_date) : 'VACÍO') . "\n";
    echo "📅 Campo legacy: " . ($legacy_date ? date('Y-m-d H:i:s', $legacy_date) : 'VACÍO') . "\n";

    // Simular exactamente lo que hace la columna
    $valid_statuses = array('entregado', 'facturado', 'completed');

    if (in_array($order_status, $valid_statuses, true)) {
        if ($delivery_date) {
            $date = is_numeric($delivery_date) ? $delivery_date : strtotime($delivery_date);
            $formatted_date = date_i18n('d-m-Y', $date);
            echo "✅ La columna debería mostrar: $formatted_date\n";
        } else {
            echo "❌ La columna mostraría: — (campo vacío)\n";
        }
    } else {
        echo "ℹ️ Estado '$order_status' no es válido para mostrar fecha\n";
    }
} else {
    echo "❌ Pedido #2510 no encontrado\n";
}

echo "\n📊 DIAGNÓSTICO DE FUNCIONALIDAD:\n";
echo "────────────────────────────────────────────\n";

// Verificar que las funciones de columna estén registradas
$hook_functions = [];
if (has_filter('manage_edit-shop_order_columns')) {
    echo "✅ Hook de columnas (clásico) registrado\n";
}

if (has_filter('manage_woocommerce_page_wc-orders_columns')) {
    echo "✅ Hook de columnas (HPOS) registrado\n";
}

if (has_action('manage_shop_order_posts_custom_column')) {
    echo "✅ Hook de datos de columna (clásico) registrado\n";
}

if (has_action('manage_woocommerce_page_wc-orders_custom_column')) {
    echo "✅ Hook de datos de columna (HPOS) registrado\n";
}

echo "\n🎯 CONCLUSIÓN:\n";
echo "Si la columna no muestra la fecha correcta, el problema puede ser:\n";
echo "1. Cache del navegador o WordPress\n";
echo "2. Otro plugin interfiriendo\n";
echo "3. El pedido no tiene el campo '_wcpdf_packing-slip_date' actualizado\n";
echo "4. El estado del pedido no está en la lista válida\n";

echo "\n✅ Test de columna completado\n";
?>
