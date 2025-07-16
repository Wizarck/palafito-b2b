<?php
/**
 * Test directo del hook de fecha de entrega
 */

// Cargar WordPress
require_once(__DIR__ . '/wp-load.php');

echo "🧪 Test Directo del Hook de Fecha de Entrega\n\n";

// 1. Verificar que WooCommerce está disponible
if (!function_exists('wc_get_orders')) {
    echo "❌ WooCommerce no está disponible\n";
    exit;
}

echo "✅ WooCommerce disponible\n";

// 2. Buscar un pedido para probar
$orders = wc_get_orders([
    'limit' => 1,
    'orderby' => 'date',
    'order' => 'DESC',
    'status' => ['processing', 'pending', 'on-hold']
]);

if (empty($orders)) {
    echo "❌ No hay pedidos disponibles para probar\n";
    exit;
}

$test_order = $orders[0];
$order_id = $test_order->get_id();

echo "✅ Usando pedido #$order_id para prueba\n";
echo "   Estado actual: " . $test_order->get_status() . "\n";

// 3. Verificar fecha actual
$current_date = $test_order->get_meta('_wcpdf_packing-slip_date');
if ($current_date) {
    echo "   Fecha actual: " . date('Y-m-d H:i:s', $current_date) . "\n";
} else {
    echo "   Fecha actual: No establecida\n";
}

// 4. Forzar la carga del plugin si no está cargado
if (!class_exists('Palafito_WC_Extensions')) {
    echo "\n🔄 Cargando clase del plugin...\n";
    require_once(WP_PLUGIN_DIR . '/palafito-wc-extensions/class-palafito-wc-extensions.php');

    if (class_exists('Palafito_WC_Extensions')) {
        echo "✅ Clase cargada manualmente\n";
    } else {
        echo "❌ Error al cargar la clase\n";
        exit;
    }
}

// 5. Verificar que el método existe
if (!method_exists('Palafito_WC_Extensions', 'handle_custom_order_status_change')) {
    echo "❌ Método handle_custom_order_status_change no existe\n";
    exit;
}

echo "✅ Método handle_custom_order_status_change existe\n";

// 6. Ejecutar el método directamente para probar
echo "\n🔬 Probando método directamente...\n";

$old_status = $test_order->get_status();
$new_status = 'entregado';

echo "   Simulando cambio: $old_status → $new_status\n";

// Llamar al método directamente
try {
    Palafito_WC_Extensions::handle_custom_order_status_change($order_id, $old_status, $new_status, $test_order);
    echo "✅ Método ejecutado sin errores\n";
} catch (Exception $e) {
    echo "❌ Error al ejecutar método: " . $e->getMessage() . "\n";
}

// 7. Verificar si la fecha cambió
$test_order = wc_get_order($order_id); // Recargar el pedido
$new_date = $test_order->get_meta('_wcpdf_packing-slip_date');

if ($new_date && $new_date != $current_date) {
    echo "✅ Fecha actualizada exitosamente\n";
    echo "   Nueva fecha: " . date('Y-m-d H:i:s', $new_date) . "\n";
} else {
    echo "❌ Fecha NO se actualizó\n";
    echo "   Fecha sigue siendo: " . ($new_date ? date('Y-m-d H:i:s', $new_date) : 'No establecida') . "\n";
}

// 8. Probar registro manual del hook
echo "\n🎣 Probando registro manual del hook...\n";

// Registrar el hook manualmente
add_action('woocommerce_order_status_changed', array('Palafito_WC_Extensions', 'handle_custom_order_status_change'), 20, 4);

global $wp_filter;
$hook_count = isset($wp_filter['woocommerce_order_status_changed']) ?
    count($wp_filter['woocommerce_order_status_changed']->callbacks) : 0;

echo "   Callbacks registrados: $hook_count\n";

// Buscar nuestro callback específico
$found = false;
if (isset($wp_filter['woocommerce_order_status_changed'])) {
    foreach ($wp_filter['woocommerce_order_status_changed']->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            if (is_array($callback['function']) &&
                isset($callback['function'][0]) &&
                $callback['function'][0] === 'Palafito_WC_Extensions' &&
                $callback['function'][1] === 'handle_custom_order_status_change') {
                echo "   ✅ Nuestro callback encontrado en prioridad $priority\n";
                $found = true;
                break 2;
            }
        }
    }
}

if (!$found) {
    echo "   ❌ Nuestro callback NO encontrado después del registro manual\n";
}

// 9. Probar hook con cambio real de estado
echo "\n🔄 Probando cambio real de estado...\n";

$original_status = $test_order->get_status();
echo "   Estado original: $original_status\n";

// Cambiar a processing primero
$test_order->set_status('processing');
$test_order->save();

echo "   Cambiado a: processing\n";

// Ahora cambiar a entregado (esto debería disparar el hook)
$test_order->set_status('entregado');
$test_order->save();

echo "   Cambiado a: entregado\n";

// Verificar resultado
$test_order = wc_get_order($order_id); // Recargar
$final_date = $test_order->get_meta('_wcpdf_packing-slip_date');

if ($final_date && $final_date != $current_date) {
    echo "✅ Hook funcionó! Fecha actualizada via cambio de estado\n";
    echo "   Fecha final: " . date('Y-m-d H:i:s', $final_date) . "\n";
} else {
    echo "❌ Hook NO funcionó con cambio de estado\n";
}

// Restaurar estado original
$test_order->set_status($original_status);
$test_order->save();

echo "\n✅ Test completado - Estado restaurado a: $original_status\n";
?>
