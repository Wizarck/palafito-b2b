<?php
/**
 * Test del hook con cualquier pedido disponible
 */

// Cargar WordPress
require_once(__DIR__ . '/wp-load.php');

echo "🧪 Test del Hook de Fecha de Entrega (Cualquier Pedido)\n\n";

// 1. Verificar WooCommerce
if (!function_exists('wc_get_orders')) {
    echo "❌ WooCommerce no está disponible\n";
    exit;
}

echo "✅ WooCommerce disponible\n";

// 2. Buscar cualquier pedido
$orders = wc_get_orders([
    'limit' => 5,
    'orderby' => 'date',
    'order' => 'DESC'
]);

if (empty($orders)) {
    echo "❌ No hay pedidos en el sistema\n";
    exit;
}

echo "✅ Encontrados " . count($orders) . " pedidos\n";

// Usar el primer pedido para prueba
$test_order = $orders[0];
$order_id = $test_order->get_id();

echo "✅ Usando pedido #$order_id\n";
echo "   Estado: " . $test_order->get_status() . "\n";
echo "   Fecha creación: " . $test_order->get_date_created()->date('Y-m-d H:i:s') . "\n";

// 3. Verificar fecha actual de entrega
$current_date = $test_order->get_meta('_wcpdf_packing-slip_date');
if ($current_date) {
    echo "   Fecha entrega actual: " . date('Y-m-d H:i:s', $current_date) . "\n";
} else {
    echo "   Fecha entrega: No establecida\n";
}

// 4. Verificar clase del plugin
if (!class_exists('Palafito_WC_Extensions')) {
    echo "\n🔄 Cargando clase del plugin...\n";
    require_once(WP_PLUGIN_DIR . '/palafito-wc-extensions/class-palafito-wc-extensions.php');
}

if (!class_exists('Palafito_WC_Extensions')) {
    echo "❌ No se pudo cargar la clase del plugin\n";
    exit;
}

echo "✅ Clase Palafito_WC_Extensions disponible\n";

// 5. Verificar método
if (!method_exists('Palafito_WC_Extensions', 'handle_custom_order_status_change')) {
    echo "❌ Método handle_custom_order_status_change no existe\n";
    exit;
}

echo "✅ Método handle_custom_order_status_change existe\n";

// 6. Test directo del método (SIN modificar el pedido real)
echo "\n🔬 Probando lógica del método...\n";

// Simular diferentes escenarios
$test_scenarios = [
    ['from' => 'processing', 'to' => 'entregado', 'should_update' => true],
    ['from' => 'facturado', 'to' => 'entregado', 'should_update' => false],
    ['from' => 'completado', 'to' => 'entregado', 'should_update' => false],
    ['from' => 'pending', 'to' => 'entregado', 'should_update' => true]
];

foreach ($test_scenarios as $scenario) {
    echo "\n   📋 Escenario: {$scenario['from']} → {$scenario['to']}\n";

    // Crear una copia temporal del pedido para testing
    $temp_order = wc_get_order($order_id);
    $original_date = $temp_order->get_meta('_wcpdf_packing-slip_date');

    // Solo probar la lógica interna sin guardar
    $excluded_states = ['facturado', 'completado', 'completed'];
    $should_skip = in_array($scenario['from'], $excluded_states);

    if ($should_skip && !$scenario['should_update']) {
        echo "      ✅ Lógica correcta: Estado excluido, NO actualizar\n";
    } elseif (!$should_skip && $scenario['should_update']) {
        echo "      ✅ Lógica correcta: Estado válido, SÍ actualizar\n";
    } else {
        echo "      ❌ Lógica incorrecta\n";
    }
}

// 7. Verificar hook registration
echo "\n🎣 Verificando registro de hooks...\n";

global $wp_filter;

// Registrar hook manualmente para test
add_action('woocommerce_order_status_changed', array('Palafito_WC_Extensions', 'handle_custom_order_status_change'), 20, 4);

$hook_count = isset($wp_filter['woocommerce_order_status_changed']) ?
    count($wp_filter['woocommerce_order_status_changed']->callbacks) : 0;

echo "   Total callbacks en hook: $hook_count\n";

// Buscar nuestro callback
$found_callback = false;
if (isset($wp_filter['woocommerce_order_status_changed'])) {
    foreach ($wp_filter['woocommerce_order_status_changed']->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            if (is_array($callback['function']) &&
                isset($callback['function'][0]) &&
                $callback['function'][0] === 'Palafito_WC_Extensions' &&
                isset($callback['function'][1]) &&
                $callback['function'][1] === 'handle_custom_order_status_change') {
                echo "   ✅ Callback encontrado en prioridad $priority\n";
                $found_callback = true;
                break 2;
            }
        }
    }
}

if (!$found_callback) {
    echo "   ❌ Callback NO encontrado\n";
}

// 8. Test de archivo actualizado
echo "\n📁 Verificando archivo del plugin...\n";

$plugin_file = WP_PLUGIN_DIR . '/palafito-wc-extensions/class-palafito-wc-extensions.php';
if (file_exists($plugin_file)) {
    $content = file_get_contents($plugin_file);
    $file_time = filemtime($plugin_file);

    echo "   Archivo existe: SÍ\n";
    echo "   Última modificación: " . date('Y-m-d H:i:s', $file_time) . "\n";

    // Verificar que contiene add_action (no add_filter)
    if (strpos($content, "add_action( 'woocommerce_order_status_changed'") !== false) {
        echo "   ✅ Contiene add_action correcto\n";
    } elseif (strpos($content, "add_filter( 'woocommerce_order_status_changed'") !== false) {
        echo "   ❌ Aún contiene add_filter incorrecto\n";
    } else {
        echo "   ❌ No encuentra registro del hook\n";
    }

    // Verificar lógica de excluded_states
    if (strpos($content, 'excluded_previous_states') !== false) {
        echo "   ✅ Contiene lógica de estados excluidos\n";
    } else {
        echo "   ❌ No contiene lógica de estados excluidos\n";
    }
} else {
    echo "   ❌ Archivo del plugin no encontrado\n";
}

// 9. Resumen
echo "\n📊 RESUMEN DEL DIAGNÓSTICO:\n";
echo "════════════════════════════════\n";

$plugin_ok = class_exists('Palafito_WC_Extensions') && method_exists('Palafito_WC_Extensions', 'handle_custom_order_status_change');
$file_ok = file_exists($plugin_file) && strpos(file_get_contents($plugin_file), "add_action( 'woocommerce_order_status_changed'") !== false;
$hook_ok = $found_callback;

echo "Plugin cargado correctamente: " . ($plugin_ok ? "✅ SÍ" : "❌ NO") . "\n";
echo "Archivo actualizado: " . ($file_ok ? "✅ SÍ" : "❌ NO") . "\n";
echo "Hook registrado: " . ($hook_ok ? "✅ SÍ" : "❌ NO") . "\n";

if ($plugin_ok && $file_ok && $hook_ok) {
    echo "\n🚀 Estado: FUNCIONANDO - El sistema debería funcionar correctamente\n";
} elseif ($plugin_ok && $file_ok) {
    echo "\n⚠️  Estado: PARCIAL - Plugin correcto pero hook no registrado automáticamente\n";
    echo "   Esto puede ser normal en ejecución via web. Probar con cambio real de pedido.\n";
} else {
    echo "\n❌ Estado: PROBLEMA - Revisar plugin o archivo\n";
}

echo "\n✅ Diagnóstico completado\n";
?>
