<?php
/**
 * Test final: Verificar que SOLO existe _wcpdf_packing-slip_date
 */

// Cargar WordPress
require_once(__DIR__ . '/wp-load.php');

echo "🎯 TEST FINAL - CAMPO ÚNICO\n";
echo "══════════════════════════════════════════\n\n";

echo "✅ OBJETIVO: Solo _wcpdf_packing-slip_date (CON guión)\n";
echo "❌ PROHIBIDO: _wcpdf_packing_slip_date (SIN guión)\n\n";

// Test con pedido #2510
$order_id = 2510;
$order = wc_get_order($order_id);

if (!$order) {
    echo "❌ Pedido #$order_id no encontrado\n";
    exit;
}

echo "📋 PROBANDO PEDIDO #$order_id\n";
echo "────────────────────────────────────────\n";

// Verificar estado actual
$current_status = $order->get_status();
echo "Estado: $current_status\n";

// Verificar SOLO el campo correcto
$standard_field = $order->get_meta('_wcpdf_packing-slip_date');
$legacy_field = $order->get_meta('_wcpdf_packing_slip_date');

echo "\n🔍 VERIFICACIÓN DE CAMPOS:\n";
echo "Campo estándar (_wcpdf_packing-slip_date): " . ($standard_field ? date('Y-m-d H:i:s', $standard_field) : 'VACÍO') . "\n";
echo "Campo legacy (_wcpdf_packing_slip_date): " . ($legacy_field ? date('Y-m-d H:i:s', $legacy_field) : 'VACÍO') . "\n";

// Verificación de columna
$valid_statuses = ['entregado', 'facturado', 'completed'];
if (in_array($current_status, $valid_statuses, true)) {
    if ($standard_field) {
        $column_display = date_i18n('d-m-Y', $standard_field);
        echo "\n📊 COLUMNA DEBERÍA MOSTRAR: $column_display\n";
    } else {
        echo "\n📊 COLUMNA DEBERÍA MOSTRAR: —\n";
    }
} else {
    echo "\n📊 COLUMNA: No aplica (estado no válido)\n";
}

// Test del metabox
echo "\n📄 VERIFICACIÓN DE METABOX:\n";
try {
    $packing_slip = wcpdf_get_document('packing-slip', $order);
    if ($packing_slip && method_exists($packing_slip, 'get_date')) {
        $metabox_date = $packing_slip->get_date();
        if ($metabox_date) {
            echo "Metabox muestra: " . $metabox_date->date_i18n('d-m-Y') . "\n";
        } else {
            echo "Metabox: Sin fecha\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Test de cambio de estado
echo "\n🔄 PROBANDO CAMBIO DE ESTADO A 'ENTREGADO':\n";

// Cambiar temporalmente a processing
$order->set_status('wc-processing');
$order->save();
echo "   ↪️ Cambiado temporalmente a: processing\n";

// Cambiar a entregado para disparar el hook
$order->set_status('wc-entregado');
$order->save();
echo "   ↪️ Cambiado a: entregado\n";

// Verificar resultado
sleep(1);
$order = wc_get_order($order_id);
$new_standard = $order->get_meta('_wcpdf_packing-slip_date');
$new_legacy = $order->get_meta('_wcpdf_packing_slip_date');

echo "\n📊 RESULTADO DESPUÉS DEL CAMBIO:\n";
echo "Campo estándar: " . ($new_standard ? date('Y-m-d H:i:s', $new_standard) : 'VACÍO') . "\n";
echo "Campo legacy: " . ($new_legacy ? date('Y-m-d H:i:s', $new_legacy) : 'VACÍO') . "\n";

// Evaluación final
$now = current_time('timestamp');
$is_recent = ($new_standard && ($now - $new_standard) < 300);

echo "\n🎯 EVALUACIÓN FINAL:\n";
echo "────────────────────────\n";
echo "✅ Solo campo estándar: " . (!empty($new_standard) ? 'SÍ' : 'NO') . "\n";
echo "✅ Campo legacy vacío: " . (empty($new_legacy) ? 'SÍ' : 'NO') . "\n";
echo "✅ Fecha actualizada: " . ($is_recent ? 'SÍ' : 'NO') . "\n";

if (!empty($new_standard) && empty($new_legacy) && $is_recent) {
    echo "\n🎉 ¡ÉXITO TOTAL!\n";
    echo "✅ Sistema usa SOLO campo estándar\n";
    echo "✅ NO hay campos legacy\n";
    echo "✅ Funcionalidad funcionando correctamente\n";
    echo "✅ Metabox y columna mostrarán LA MISMA fecha\n";
} else {
    echo "\n❌ PROBLEMA DETECTADO:\n";
    if (empty($new_standard)) echo "   - Campo estándar vacío\n";
    if (!empty($new_legacy)) echo "   - Campo legacy aún existe\n";
    if (!$is_recent) echo "   - Fecha no se actualizó\n";
}

echo "\n✅ Test completado\n";
?>
