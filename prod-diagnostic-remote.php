<?php
/**
 * DIAGNÓSTICO REMOTO PARA PRODUCCIÓN
 * 
 * INSTRUCCIONES:
 * 1. Sube este archivo a la raíz de tu WordPress en PROD
 * 2. Visita: https://tu-dominio.com/prod-diagnostic-remote.php
 * 3. ELIMINA el archivo después de usarlo por seguridad
 */

// Seguridad básica - solo funciona si estás logueado como admin
if (!defined('ABSPATH')) {
    require_once('./wp-config.php');
    require_once('./wp-load.php');
}

if (!current_user_can('administrator')) {
    die('Acceso denegado. Debes estar logueado como administrador.');
}

echo "<h1>🔍 Diagnóstico Palafito PRODUCCIÓN</h1>";
echo "<pre>";

// 1. Información del entorno
echo "=== INFORMACIÓN DEL ENTORNO ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "WordPress Version: " . get_bloginfo('version') . "\n";
echo "Site URL: " . site_url() . "\n";
echo "Home URL: " . home_url() . "\n";
echo "WP_DEBUG: " . (defined('WP_DEBUG') && WP_DEBUG ? 'YES' : 'NO') . "\n";
echo "Current time: " . current_time('Y-m-d H:i:s') . "\n";

// 2. Estado de plugins críticos
echo "\n=== PLUGINS CRÍTICOS ===\n";
$critical_plugins = [
    'palafito-wc-extensions/palafito-wc-extensions.php',
    'woocommerce/woocommerce.php',
    'woocommerce-pdf-invoices-packing-slips/woocommerce-pdf-invoices-packing-slips.php',
    'woocommerce-pdf-ips-pro/woocommerce-pdf-ips-pro.php'
];

foreach ($critical_plugins as $plugin) {
    $is_active = is_plugin_active($plugin);
    echo ($is_active ? "✅" : "❌") . " {$plugin}: " . ($is_active ? "ACTIVO" : "INACTIVO") . "\n";
}

// 3. Verificar clase principal
echo "\n=== CLASES Y FUNCIONES ===\n";
echo "Palafito_WC_Extensions class: " . (class_exists('Palafito_WC_Extensions') ? "✅ EXISTS" : "❌ NOT FOUND") . "\n";
echo "wc_get_orders function: " . (function_exists('wc_get_orders') ? "✅ EXISTS" : "❌ NOT FOUND") . "\n";
echo "wcpdf_get_document function: " . (function_exists('wcpdf_get_document') ? "✅ EXISTS" : "❌ NOT FOUND") . "\n";

// 4. Verificar hooks registrados
echo "\n=== HOOKS REGISTRADOS ===\n";
$hook_callbacks = $GLOBALS['wp_filter']['woocommerce_order_status_changed'] ?? null;
if ($hook_callbacks) {
    echo "Hook 'woocommerce_order_status_changed' registrado: ✅ YES\n";
    foreach ($hook_callbacks->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            if (is_array($callback['function'])) {
                $class = is_object($callback['function'][0]) ? get_class($callback['function'][0]) : $callback['function'][0];
                $method = $callback['function'][1];
                echo "  Priority {$priority}: {$class}::{$method}\n";
            }
        }
    }
} else {
    echo "❌ Hook 'woocommerce_order_status_changed' NO registrado\n";
}

// 5. Probar con pedido real
echo "\n=== PRUEBA CON PEDIDO REAL ===\n";
$orders = wc_get_orders(array(
    'limit' => 1,
    'status' => array('processing', 'pending', 'entregado'),
    'orderby' => 'date',
    'order' => 'DESC'
));

if (!empty($orders)) {
    $order = $orders[0];
    $order_id = $order->get_id();
    $status = $order->get_status();
    $delivery_date = $order->get_meta('_wcpdf_packing-slip_date');
    
    echo "Pedido de prueba: {$order_id}\n";
    echo "Estado actual: {$status}\n";
    echo "Fecha de entrega: " . ($delivery_date ? date('d-m-Y H:i:s', is_numeric($delivery_date) ? $delivery_date : strtotime($delivery_date)) : 'VACÍA') . "\n";
    
    // Probar función de columna
    ob_start();
    if (method_exists('Palafito_WC_Extensions', 'custom_order_columns_data')) {
        Palafito_WC_Extensions::custom_order_columns_data('entregado_date', $order_id);
    }
    $column_output = ob_get_clean();
    echo "Salida de columna: '{$column_output}'\n";
    
} else {
    echo "❌ No se encontraron pedidos para probar\n";
}

// 6. Configuración PDF plugin
echo "\n=== CONFIGURACIÓN PDF PLUGIN ===\n";
$packing_slip_settings = get_option('wpo_wcpdf_documents_settings_packing-slip', array());
if (isset($packing_slip_settings['auto_generate_for_statuses'])) {
    echo "Auto-generate statuses: " . implode(', ', $packing_slip_settings['auto_generate_for_statuses']) . "\n";
} else {
    echo "❌ No auto-generate settings found\n";
}

// 7. Archivos críticos
echo "\n=== ARCHIVOS CRÍTICOS ===\n";
$critical_files = [
    'wp-content/plugins/palafito-wc-extensions/class-palafito-wc-extensions.php',
    'wp-content/themes/kadence/woocommerce/pdf/mio/template-functions.php'
];

foreach ($critical_files as $file) {
    if (file_exists($file)) {
        $mtime = filemtime($file);
        echo "✅ {$file}: " . date('Y-m-d H:i:s', $mtime) . " (" . filesize($file) . " bytes)\n";
    } else {
        echo "❌ {$file}: NO ENCONTRADO\n";
    }
}

echo "\n=== DIAGNÓSTICO COMPLETADO ===\n";
echo "Fecha del diagnóstico: " . date('Y-m-d H:i:s') . "\n";
echo "\n⚠️  IMPORTANTE: ELIMINA ESTE ARCHIVO después de revisar los resultados\n";
echo "</pre>";
?>