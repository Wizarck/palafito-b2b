<?php
/**
 * DIAGNÓSTICO AVANZADO - VERSION 2
 * Análisis más profundo del estado de plugins PDF
 */

if (!defined('ABSPATH')) {
    require_once('./wp-config.php');
    require_once('./wp-load.php');
}

if (!current_user_can('administrator')) {
    die('Acceso denegado.');
}

echo "<h1>🔍 Diagnóstico Avanzado PDF Plugins</h1>";
echo "<pre>";

// 1. Verificar estado detallado de plugins PDF
echo "=== ANÁLISIS DETALLADO DE PLUGINS PDF ===\n";

$pdf_plugins = [
    'woocommerce-pdf-invoices-packing-slips/woocommerce-pdf-invoices-packing-slips.php' => 'PDF Base',
    'woocommerce-pdf-ips-pro/woocommerce-pdf-ips-pro.php' => 'PDF Pro'
];

foreach ($pdf_plugins as $plugin_path => $name) {
    echo "\n--- {$name} ---\n";
    
    // Estado de activación
    $is_active = is_plugin_active($plugin_path);
    echo "Estado WordPress: " . ($is_active ? "✅ ACTIVO" : "❌ INACTIVO") . "\n";
    
    // Verificar archivos
    $plugin_file = WP_PLUGIN_DIR . '/' . $plugin_path;
    if (file_exists($plugin_file)) {
        echo "Archivo principal: ✅ EXISTE\n";
        echo "Tamaño: " . filesize($plugin_file) . " bytes\n";
        echo "Modificado: " . date('Y-m-d H:i:s', filemtime($plugin_file)) . "\n";
        
        // Verificar si es legible
        if (is_readable($plugin_file)) {
            echo "Permisos: ✅ LEGIBLE\n";
        } else {
            echo "Permisos: ❌ NO LEGIBLE\n";
        }
    } else {
        echo "Archivo principal: ❌ NO EXISTE\n";
    }
    
    // Verificar directorio del plugin
    $plugin_dir = dirname($plugin_file);
    if (is_dir($plugin_dir)) {
        $files_count = count(glob($plugin_dir . '/*'));
        echo "Directorio: ✅ EXISTE ({$files_count} archivos)\n";
    } else {
        echo "Directorio: ❌ NO EXISTE\n";
    }
}

// 2. Verificar funciones específicas del plugin PDF
echo "\n=== FUNCIONES PDF DISPONIBLES ===\n";
$pdf_functions = [
    'wcpdf_get_document' => 'Función principal PDF',
    'wpo_wcpdf_get_document' => 'Función alternativa PDF',
    'wcpdf_get_invoice' => 'Función factura',
    'wcpdf_get_packing_slip' => 'Función albarán'
];

foreach ($pdf_functions as $function => $description) {
    $exists = function_exists($function);
    echo "{$description}: " . ($exists ? "✅ DISPONIBLE" : "❌ NO DISPONIBLE") . "\n";
}

// 3. Verificar clases del plugin
echo "\n=== CLASES PDF DISPONIBLES ===\n";
$pdf_classes = [
    'WPO\\WC\\PDF_Invoices\\Main' => 'Clase principal PDF',
    'WPO\\WC\\PDF_Invoices_Pro\\Main' => 'Clase principal PDF Pro',
    'WCPDF_Document' => 'Clase documento',
    'WC_PDF_Document' => 'Clase documento alternativa'
];

foreach ($pdf_classes as $class => $description) {
    $exists = class_exists($class);
    echo "{$description}: " . ($exists ? "✅ DISPONIBLE" : "❌ NO DISPONIBLE") . "\n";
}

// 4. Verificar hooks PDF
echo "\n=== HOOKS PDF REGISTRADOS ===\n";
$pdf_hooks = [
    'wpo_wcpdf_save_document',
    'wpo_wcpdf_before_document',
    'wpo_wcpdf_after_document',
    'woocommerce_admin_order_actions_end'
];

foreach ($pdf_hooks as $hook) {
    $has_callbacks = isset($GLOBALS['wp_filter'][$hook]) && !empty($GLOBALS['wp_filter'][$hook]->callbacks);
    echo "Hook '{$hook}': " . ($has_callbacks ? "✅ REGISTRADO" : "❌ NO REGISTRADO") . "\n";
}

// 5. Probar creación de documento PDF
echo "\n=== PRUEBA DE CREACIÓN DE DOCUMENTO ===\n";
$orders = wc_get_orders(array('limit' => 1, 'orderby' => 'date', 'order' => 'DESC'));

if (!empty($orders)) {
    $order = $orders[0];
    $order_id = $order->get_id();
    
    echo "Probando con pedido: {$order_id}\n";
    
    // Intentar crear documento
    try {
        if (function_exists('wcpdf_get_document')) {
            $packing_slip = wcpdf_get_document('packing-slip', $order);
            if ($packing_slip) {
                echo "Creación de albarán: ✅ EXITOSA\n";
                echo "Tipo de documento: " . $packing_slip->get_type() . "\n";
                
                // Verificar fecha
                $date = $packing_slip->get_date();
                if ($date) {
                    echo "Fecha del documento: " . $date->date('Y-m-d H:i:s') . "\n";
                } else {
                    echo "Fecha del documento: ❌ NO ESTABLECIDA\n";
                }
            } else {
                echo "Creación de albarán: ❌ FALLÓ (documento nulo)\n";
            }
        } else {
            echo "Creación de albarán: ❌ FUNCIÓN NO DISPONIBLE\n";
        }
    } catch (Exception $e) {
        echo "Creación de albarán: ❌ ERROR - " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ No hay pedidos para probar\n";
}

// 6. Verificar errores recientes
echo "\n=== ERRORES RECIENTES ===\n";
if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
    $log_file = WP_CONTENT_DIR . '/debug.log';
    if (file_exists($log_file)) {
        $log_content = file_get_contents($log_file);
        $recent_lines = array_slice(explode("\n", $log_content), -20);
        
        echo "Últimas 20 líneas del debug.log:\n";
        foreach ($recent_lines as $line) {
            if (!empty(trim($line))) {
                echo $line . "\n";
            }
        }
    } else {
        echo "Archivo debug.log no encontrado\n";
    }
} else {
    echo "Debug logging no está habilitado\n";
}

echo "\n=== DIAGNÓSTICO V2 COMPLETADO ===\n";
echo "⚠️  ELIMINA ESTE ARCHIVO después de revisar\n";
echo "</pre>";
?>