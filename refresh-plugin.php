<?php
/**
 * Script para refrescar el plugin y forzar registro de hooks
 */

// Cargar WordPress
require_once(__DIR__ . '/wp-load.php');

echo "🔄 Refrescando Plugin Palafito WC Extensions...\n\n";

// 1. Obtener información actual
$plugin_file = 'palafito-wc-extensions/palafito-wc-extensions.php';
$is_active = is_plugin_active($plugin_file);

echo "📋 Estado actual:\n";
echo "   Plugin activo: " . ($is_active ? 'SÍ' : 'NO') . "\n";

if (class_exists('Palafito_WC_Extensions')) {
    echo "   Clase cargada: SÍ\n";
} else {
    echo "   Clase cargada: NO\n";
}

// 2. Verificar hooks antes del refresh
global $wp_filter;
$hook_before = isset($wp_filter['woocommerce_order_status_changed']) ?
    count($wp_filter['woocommerce_order_status_changed']->callbacks) : 0;

echo "   Callbacks en hook: $hook_before\n\n";

// 3. Refrescar plugin (desactivar y reactivar)
echo "🔄 Refrescando plugin...\n";

if ($is_active) {
    // Desactivar
    deactivate_plugins($plugin_file, true);
    echo "   ✅ Plugin desactivado\n";

    // Limpiar caché de opciones
    wp_cache_flush();

    // Reactivar
    $result = activate_plugin($plugin_file);

    if (is_wp_error($result)) {
        echo "   ❌ Error al reactivar: " . $result->get_error_message() . "\n";
    } else {
        echo "   ✅ Plugin reactivado exitosamente\n";
    }
} else {
    // Solo activar
    $result = activate_plugin($plugin_file);

    if (is_wp_error($result)) {
        echo "   ❌ Error al activar: " . $result->get_error_message() . "\n";
    } else {
        echo "   ✅ Plugin activado exitosamente\n";
    }
}

// 4. Verificar estado después del refresh
echo "\n📋 Estado después del refresh:\n";

// Recargar la clase si es necesario
if (!class_exists('Palafito_WC_Extensions')) {
    $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
    if (file_exists($plugin_path)) {
        include_once $plugin_path;
    }
}

$is_active_after = is_plugin_active($plugin_file);
echo "   Plugin activo: " . ($is_active_after ? 'SÍ' : 'NO') . "\n";

if (class_exists('Palafito_WC_Extensions')) {
    echo "   Clase cargada: SÍ\n";
} else {
    echo "   Clase cargada: NO\n";
}

// 5. Verificar hooks después del refresh
$hook_after = isset($wp_filter['woocommerce_order_status_changed']) ?
    count($wp_filter['woocommerce_order_status_changed']->callbacks) : 0;

echo "   Callbacks en hook: $hook_after\n";

// 6. Buscar específicamente nuestro callback
$found_callback = false;
if (isset($wp_filter['woocommerce_order_status_changed'])) {
    foreach ($wp_filter['woocommerce_order_status_changed']->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            if (is_array($callback['function']) &&
                isset($callback['function'][0]) &&
                is_object($callback['function'][0]) &&
                get_class($callback['function'][0]) === 'Palafito_WC_Extensions') {
                echo "   ✅ Nuestro callback encontrado en prioridad $priority\n";
                $found_callback = true;
                break 2;
            }
        }
    }
}

if (!$found_callback) {
    echo "   ❌ Nuestro callback NO encontrado\n";

    // Intentar registrar manualmente
    echo "\n🔧 Intentando registro manual del hook...\n";

    if (class_exists('Palafito_WC_Extensions')) {
        add_action('woocommerce_order_status_changed', array('Palafito_WC_Extensions', 'handle_custom_order_status_change'), 20, 4);

        // Verificar si se registró
        $hook_manual = isset($wp_filter['woocommerce_order_status_changed']) ?
            count($wp_filter['woocommerce_order_status_changed']->callbacks) : 0;

        echo "   Callbacks después de registro manual: $hook_manual\n";

        if ($hook_manual > $hook_after) {
            echo "   ✅ Hook registrado manualmente\n";
        } else {
            echo "   ❌ Registro manual falló\n";
        }
    }
}

echo "\n🎯 Resultado Final:\n";
echo "   Plugin activo y hooks registrados: " . ($is_active_after && $found_callback ? 'SÍ' : 'NO') . "\n";

if ($is_active_after && $found_callback) {
    echo "   🚀 Plugin completamente funcional\n";
} else {
    echo "   ⚠️  Plugin requiere atención adicional\n";
}

echo "\n✅ Proceso de refresh completado\n";
?>
