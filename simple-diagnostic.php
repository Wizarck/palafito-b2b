<?php
/**
 * Diagnóstico Simple de Producción - Fecha de Entrega
 * Ejecutar desde el directorio raíz de WordPress
 */

// Cargar WordPress
require_once(__DIR__ . '/wp-load.php');

echo "=== DIAGNÓSTICO PRODUCCIÓN - FECHA DE ENTREGA ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Verificar versiones
echo "1. VERSIONES:\n";
echo "   WordPress: " . get_bloginfo('version') . "\n";
echo "   WooCommerce: " . (function_exists('WC') ? WC()->version : 'NO DETECTADO') . "\n";
echo "   PHP: " . PHP_VERSION . "\n\n";

// 2. Verificar plugin activo
echo "2. PLUGIN PALAFITO:\n";
$plugin_file = 'palafito-wc-extensions/palafito-wc-extensions.php';
if (is_plugin_active($plugin_file)) {
    echo "   ✅ Plugin ACTIVO\n";
} else {
    echo "   ❌ Plugin NO ACTIVO\n";
}

// 3. Verificar clase
if (class_exists('Palafito_WC_Extensions')) {
    echo "   ✅ Clase cargada\n";

    if (method_exists('Palafito_WC_Extensions', 'handle_custom_order_status_change')) {
        echo "   ✅ Método handle_custom_order_status_change existe\n";
    } else {
        echo "   ❌ Método NO existe\n";
    }
} else {
    echo "   ❌ Clase NO encontrada\n";
}

// 4. Verificar archivo del plugin
echo "\n3. ARCHIVO DEL PLUGIN:\n";
$plugin_path = WP_PLUGIN_DIR . '/palafito-wc-extensions/class-palafito-wc-extensions.php';
if (file_exists($plugin_path)) {
    $file_time = filemtime($plugin_path);
    echo "   ✅ Archivo existe\n";
    echo "   Modificado: " . date('Y-m-d H:i:s', $file_time) . "\n";

    // Verificar contenido
    $content = file_get_contents($plugin_path);

    $checks = [
        'excluded_previous_states' => 'Estados excluidos',
        'facturado.*completado.*completed' => 'Array de estados',
        'elseif.*WP_DEBUG' => 'Fix PHPCS',
        'Updated delivery date' => 'Log actualización'
    ];

    echo "   Verificando cambios:\n";
    foreach ($checks as $pattern => $desc) {
        if (preg_match("/$pattern/i", $content)) {
            echo "   ✅ $desc\n";
        } else {
            echo "   ❌ $desc\n";
        }
    }
} else {
    echo "   ❌ Archivo NO encontrado\n";
}

// 5. Verificar hooks
echo "\n4. HOOKS REGISTRADOS:\n";
global $wp_filter;

if (isset($wp_filter['woocommerce_order_status_changed'])) {
    $count = count($wp_filter['woocommerce_order_status_changed']->callbacks);
    echo "   ✅ woocommerce_order_status_changed: $count callbacks\n";

    // Buscar nuestro callback
    $found = false;
    foreach ($wp_filter['woocommerce_order_status_changed']->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            if (is_array($callback['function']) &&
                isset($callback['function'][0]) &&
                is_object($callback['function'][0]) &&
                get_class($callback['function'][0]) === 'Palafito_WC_Extensions') {
                echo "   ✅ Nuestro callback encontrado (prioridad: $priority)\n";
                $found = true;
            }
        }
    }
    if (!$found) {
        echo "   ❌ Nuestro callback NO encontrado\n";
    }
} else {
    echo "   ❌ Hook woocommerce_order_status_changed NO registrado\n";
}

// 6. Verificar cambio en PDF Admin
echo "\n5. PDF ADMIN:\n";
$pdf_path = WP_PLUGIN_DIR . '/woocommerce-pdf-invoices-packing-slips/includes/Admin.php';
if (file_exists($pdf_path)) {
    $pdf_content = file_get_contents($pdf_path);
    if (strpos($pdf_content, 'Fecha de entrega:') !== false) {
        echo "   ✅ Label actualizado a 'Fecha de entrega'\n";
    } elseif (strpos($pdf_content, 'Fecha de albarán:') !== false) {
        echo "   ❌ Aún muestra 'Fecha de albarán'\n";
    } else {
        echo "   ❌ Label no encontrado\n";
    }
} else {
    echo "   ❌ Archivo PDF Admin no encontrado\n";
}

// 7. Probar con pedido real
echo "\n6. PRUEBA CON PEDIDO:\n";
$orders = wc_get_orders(['limit' => 1, 'orderby' => 'date', 'order' => 'DESC']);

if (!empty($orders)) {
    $order = $orders[0];
    $order_id = $order->get_id();
    echo "   Pedido de prueba: #$order_id\n";
    echo "   Estado actual: " . $order->get_status() . "\n";

    $current_date = $order->get_meta('_wcpdf_packing-slip_date');
    if ($current_date) {
        echo "   Fecha actual: " . date('Y-m-d H:i:s', $current_date) . "\n";
    } else {
        echo "   Fecha: No establecida\n";
    }

    // Verificar lógica
    $excluded = ['facturado', 'completado', 'completed'];
    if (in_array($order->get_status(), $excluded)) {
        echo "   Estado excluido: NO se actualizaría\n";
    } else {
        echo "   Estado válido: SÍ se actualizaría\n";
    }
} else {
    echo "   ❌ No hay pedidos para probar\n";
}

echo "\n=== FIN DIAGNÓSTICO ===\n";
?>
