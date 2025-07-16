<?php
/**
 * Test específico del pedido #2510 - Fecha de Entrega
 */

// Cargar WordPress
require_once(__DIR__ . '/wp-load.php');

echo "🧪 Test Específico - Pedido #2510\n";
echo "═══════════════════════════════════\n\n";

$order_id = 2510;

// 1. Verificar que el pedido existe
$order = wc_get_order($order_id);
if (!$order) {
    echo "❌ Pedido #$order_id no encontrado\n";
    exit;
}

echo "✅ Pedido #$order_id encontrado\n";
echo "   Estado actual: " . $order->get_status() . "\n";
echo "   Fecha creación: " . $order->get_date_created()->date('Y-m-d H:i:s') . "\n";

// 2. Verificar fecha de entrega actual
$current_delivery_date = $order->get_meta('_wcpdf_packing-slip_date');
if ($current_delivery_date) {
    echo "   Fecha entrega actual: " . date('Y-m-d H:i:s', $current_delivery_date) . "\n";
} else {
    echo "   Fecha entrega actual: NO ESTABLECIDA\n";
}

// 3. Verificar otros metadatos relacionados
$all_meta = $order->get_meta_data();
echo "\n📋 Todos los metadatos del pedido:\n";
foreach ($all_meta as $meta) {
    $key = $meta->get_data()['key'];
    $value = $meta->get_data()['value'];

    if (strpos($key, '_wcpdf') !== false || strpos($key, 'delivery') !== false || strpos($key, 'entrega') !== false) {
        if (is_numeric($value) && $value > 1000000000) {
            // Es un timestamp
            echo "   $key: " . date('Y-m-d H:i:s', $value) . " (timestamp: $value)\n";
        } else {
            echo "   $key: $value\n";
        }
    }
}

// 4. Probar el método directamente
echo "\n🔬 Probando método directamente con pedido #2510...\n";

if (!class_exists('Palafito_WC_Extensions')) {
    require_once(WP_PLUGIN_DIR . '/palafito-wc-extensions/class-palafito-wc-extensions.php');
}

if (!method_exists('Palafito_WC_Extensions', 'handle_custom_order_status_change')) {
    echo "❌ Método no encontrado\n";
    exit;
}

// Guardar estado actual para restaurar después
$original_status = $order->get_status();
$original_date = $order->get_meta('_wcpdf_packing-slip_date');

echo "   Estado original: $original_status\n";
echo "   Fecha original: " . ($original_date ? date('Y-m-d H:i:s', $original_date) : 'No establecida') . "\n";

// 5. Test 1: Cambio desde processing
echo "\n📝 TEST 1: processing → entregado\n";

// Cambiar temporalmente a processing
$order->set_status('processing');
$order->save();
echo "   ✅ Cambiado temporalmente a processing\n";

// Ejecutar nuestro método manualmente
echo "   🔧 Ejecutando método handle_custom_order_status_change...\n";
Palafito_WC_Extensions::handle_custom_order_status_change($order_id, 'processing', 'entregado', $order);

// Verificar resultado
$order = wc_get_order($order_id); // Recargar
$new_date_1 = $order->get_meta('_wcpdf_packing-slip_date');

if ($new_date_1 && $new_date_1 != $original_date) {
    echo "   ✅ ÉXITO: Fecha actualizada a " . date('Y-m-d H:i:s', $new_date_1) . "\n";
} else {
    echo "   ❌ FALLO: Fecha no se actualizó\n";
    echo "   Fecha actual: " . ($new_date_1 ? date('Y-m-d H:i:s', $new_date_1) : 'No establecida') . "\n";
}

// 6. Test 2: Cambio desde facturado (debería NO actualizar)
echo "\n📝 TEST 2: facturado → entregado (NO debe actualizar)\n";

// Cambiar temporalmente a facturado
$order->set_status('facturado');
$order->save();
echo "   ✅ Cambiado temporalmente a facturado\n";

$before_test2 = $order->get_meta('_wcpdf_packing-slip_date');

// Ejecutar nuestro método
echo "   🔧 Ejecutando método desde estado facturado...\n";
Palafito_WC_Extensions::handle_custom_order_status_change($order_id, 'facturado', 'entregado', $order);

// Verificar que NO cambió
$order = wc_get_order($order_id); // Recargar
$after_test2 = $order->get_meta('_wcpdf_packing-slip_date');

if ($after_test2 == $before_test2) {
    echo "   ✅ CORRECTO: Fecha NO se actualizó (como esperado)\n";
} else {
    echo "   ❌ ERROR: Fecha se actualizó cuando NO debería\n";
    echo "   Antes: " . ($before_test2 ? date('Y-m-d H:i:s', $before_test2) : 'No establecida') . "\n";
    echo "   Después: " . ($after_test2 ? date('Y-m-d H:i:s', $after_test2) : 'No establecida') . "\n";
}

// 7. Test 3: Cambio real usando el hook de WooCommerce
echo "\n📝 TEST 3: Cambio real usando WooCommerce hooks\n";

// Cambiar a processing primero
$order->set_status('processing');
$order->save();
echo "   ✅ Establecido a processing\n";

$before_hook_test = $order->get_meta('_wcpdf_packing-slip_date');
echo "   Fecha antes del cambio: " . ($before_hook_test ? date('Y-m-d H:i:s', $before_hook_test) : 'No establecida') . "\n";

// Ahora cambiar a entregado (esto debería disparar el hook automáticamente)
echo "   🎣 Cambiando a entregado (debería disparar hook automático)...\n";
$order->set_status('entregado');
$order->save();

// Verificar resultado
$order = wc_get_order($order_id); // Recargar
$after_hook_test = $order->get_meta('_wcpdf_packing-slip_date');

if ($after_hook_test && $after_hook_test != $before_hook_test) {
    echo "   ✅ ÉXITO: Hook automático funcionó!\n";
    echo "   Nueva fecha: " . date('Y-m-d H:i:s', $after_hook_test) . "\n";
} else {
    echo "   ❌ FALLO: Hook automático NO funcionó\n";
    echo "   Fecha sigue siendo: " . ($after_hook_test ? date('Y-m-d H:i:s', $after_hook_test) : 'No establecida') . "\n";
}

// 8. Verificar si el hook está realmente registrado en este contexto
echo "\n🎣 Verificando hooks en contexto actual...\n";

global $wp_filter;
$hook_found = false;

if (isset($wp_filter['woocommerce_order_status_changed'])) {
    echo "   ✅ Hook woocommerce_order_status_changed existe\n";
    echo "   Total callbacks: " . count($wp_filter['woocommerce_order_status_changed']->callbacks) . "\n";

    foreach ($wp_filter['woocommerce_order_status_changed']->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            if (is_array($callback['function'])) {
                $class = is_object($callback['function'][0]) ? get_class($callback['function'][0]) : $callback['function'][0];
                $method = $callback['function'][1];
                echo "   - Prioridad $priority: $class::$method\n";

                if ($class === 'Palafito_WC_Extensions' && $method === 'handle_custom_order_status_change') {
                    $hook_found = true;
                    echo "     ✅ ¡Este es nuestro hook!\n";
                }
            }
        }
    }
} else {
    echo "   ❌ Hook woocommerce_order_status_changed NO existe\n";
}

if (!$hook_found) {
    echo "   ❌ Nuestro hook específico NO encontrado\n";

    // Intentar registrarlo manualmente
    echo "   🔧 Registrando hook manualmente...\n";
    add_action('woocommerce_order_status_changed', array('Palafito_WC_Extensions', 'handle_custom_order_status_change'), 20, 4);
    echo "   ✅ Hook registrado manualmente\n";
}

// 9. Restaurar estado original
echo "\n🔄 Restaurando estado original...\n";
$order->set_status($original_status);
if ($original_date) {
    $order->update_meta_data('_wcpdf_packing-slip_date', $original_date);
} else {
    $order->delete_meta_data('_wcpdf_packing-slip_date');
}
$order->save();

echo "   ✅ Estado restaurado a: $original_status\n";
echo "   ✅ Fecha restaurada a: " . ($original_date ? date('Y-m-d H:i:s', $original_date) : 'No establecida') . "\n";

// 10. Resumen final
echo "\n📊 RESUMEN DE PRUEBAS:\n";
echo "═══════════════════════════════════\n";
echo "Método directo (processing→entregado): " . (isset($new_date_1) && $new_date_1 != $original_date ? "✅ FUNCIONA" : "❌ FALLA") . "\n";
echo "Lógica de exclusión (facturado→entregado): " . (isset($after_test2) && $after_test2 == $before_test2 ? "✅ FUNCIONA" : "❌ FALLA") . "\n";
echo "Hook automático: " . (isset($after_hook_test) && $after_hook_test != $before_hook_test ? "✅ FUNCIONA" : "❌ FALLA") . "\n";
echo "Hook registrado: " . ($hook_found ? "✅ SÍ" : "❌ NO") . "\n";

echo "\n✅ Test completado con pedido #2510\n";
?>
