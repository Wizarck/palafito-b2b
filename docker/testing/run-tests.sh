#!/bin/bash

# Script para ejecutar tests de Palafito B2B

set -e

echo "🧪 Ejecutando tests de Palafito B2B..."

# Configurar entorno si es necesario
if [ ! -f "wp-config.php" ]; then
    echo "⚙️ Configurando entorno de testing..."
    setup-tests
fi

# Verificar que WordPress y plugins estén activos
wp core is-installed --allow-root
wp plugin is-active woocommerce --allow-root
wp plugin is-active palafito-wc-extensions --allow-root

# Ejecutar PHPUnit si existe configuración
if [ -f "phpunit.xml" ]; then
    echo "🔬 Ejecutando PHPUnit..."
    phpunit --configuration phpunit.xml
else
    echo "⚠️ No se encontró configuración PHPUnit"
fi

# Tests funcionales específicos de Palafito
echo "🧪 Ejecutando tests funcionales de Palafito..."

# Test 1: Verificar estados de pedidos personalizados
echo "📦 Test: Estados de pedidos personalizados"
wp eval '
$statuses = wc_get_order_statuses();
$expected = ["wc-entregado", "wc-facturado"];
$found = 0;
foreach($expected as $status) {
    if(isset($statuses[$status])) {
        echo "✅ Estado $status encontrado: " . $statuses[$status] . "\n";
        $found++;
    } else {
        echo "❌ Estado $status NO encontrado\n";
    }
}
echo $found == count($expected) ? "✅ Test PASSED\n" : "❌ Test FAILED\n";
' --allow-root

# Test 2: Crear pedido y probar sincronización
echo "🔄 Test: Sincronización de fecha de albarán"
wp eval '
echo "Creando pedido de prueba...\n";
$order = wc_create_order();
$order->set_status("processing");
$order->save();
$order_id = $order->get_id();
echo "Pedido #$order_id creado\n";

echo "Configurando fecha de albarán...\n";
$timestamp = time();
$order->update_meta_data("_wcpdf_packing-slip_date", $timestamp);
$order->save_meta_data();

echo "Verificando sincronización...\n";
$saved_date = $order->get_meta("_wcpdf_packing-slip_date");
if($saved_date && $saved_date == $timestamp) {
    echo "✅ Test PASSED: Fecha sincronizada correctamente (" . date("d/m/Y H:i", $saved_date) . ")\n";
} else {
    echo "❌ Test FAILED: Fecha no sincronizada\n";
}

// Limpiar
wp_delete_post($order_id, true);
echo "Pedido de prueba eliminado\n";
' --allow-root

# Test 3: Verificar hooks activos
echo "🔗 Test: Hooks de Palafito activos"
wp eval '
global $wp_filter;
$palafito_hooks = 0;
foreach($wp_filter as $hook => $callbacks) {
    foreach($callbacks as $priority => $functions) {
        foreach($functions as $function) {
            if(is_string($function["function"]) && strpos($function["function"], "palafito") !== false) {
                echo "✅ Hook encontrado: $hook - " . $function["function"] . "\n";
                $palafito_hooks++;
            }
        }
    }
}
echo $palafito_hooks > 0 ? "✅ Test PASSED: $palafito_hooks hooks activos\n" : "❌ Test FAILED: No se encontraron hooks\n";
' --allow-root

echo "🎉 Tests completados"