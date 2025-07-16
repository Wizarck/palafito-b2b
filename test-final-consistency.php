<?php
/**
 * Test final de consistencia: Verificar que el sistema usa solo _wcpdf_packing-slip_date
 */

// Cargar WordPress
require_once(__DIR__ . '/wp-load.php');

echo "🧪 TEST FINAL DE CONSISTENCIA\n";
echo "══════════════════════════════════════════\n\n";

// Test con pedido #2510 específico
$order_id = 2510;
$order = wc_get_order($order_id);

if (!$order) {
    echo "❌ ERROR: Pedido #$order_id no encontrado\n";
    exit;
}

echo "📋 PROBANDO PEDIDO #$order_id\n";
echo "────────────────────────────────────────\n";

// 1. Estado actual
$current_status = $order->get_status();
echo "Estado actual: $current_status\n";

// 2. Verificar campos actuales
$standard_field = $order->get_meta('_wcpdf_packing-slip_date');
$legacy_field = $order->get_meta('_wcpdf_packing_slip_date');

echo "Campo estándar (_wcpdf_packing-slip_date): " . ($standard_field ? date('Y-m-d H:i:s', $standard_field) : 'VACÍO') . "\n";
echo "Campo legacy (_wcpdf_packing_slip_date): " . ($legacy_field ? date('Y-m-d H:i:s', $legacy_field) : 'VACÍO') . "\n\n";

// 3. Simular cambio a entregado para probar lógica
echo "🔄 SIMULANDO CAMBIO A 'ENTREGADO'...\n";

// Cambiar temporalmente a un estado diferente
$order->set_status('wc-processing');
$order->save();

echo "   ↪️ Estado cambiado temporalmente a: processing\n";

// Ahora cambiar a entregado para disparar el hook
$order->set_status('wc-entregado');
$order->save();

echo "   ↪️ Estado cambiado a: entregado\n";

// 4. Verificar resultado después del cambio
sleep(1); // Dar tiempo para que se ejecuten los hooks

$order = wc_get_order($order_id); // Recargar orden
$new_standard_field = $order->get_meta('_wcpdf_packing-slip_date');
$new_legacy_field = $order->get_meta('_wcpdf_packing_slip_date');

echo "\n📊 RESULTADO DESPUÉS DEL CAMBIO:\n";
echo "Campo estándar (_wcpdf_packing-slip_date): " . ($new_standard_field ? date('Y-m-d H:i:s', $new_standard_field) : 'VACÍO') . "\n";
echo "Campo legacy (_wcpdf_packing_slip_date): " . ($new_legacy_field ? date('Y-m-d H:i:s', $new_legacy_field) : 'VACÍO') . "\n";

// 5. Verificar que la fecha es reciente (últimos 5 minutos)
$now = current_time('timestamp');
$is_recent = ($new_standard_field && ($now - $new_standard_field) < 300); // 5 minutos

echo "\n✅ VERIFICACIONES:\n";
echo "   🕒 Fecha es reciente (últimos 5 min): " . ($is_recent ? 'SÍ' : 'NO') . "\n";
echo "   🗑️ Campo legacy está vacío: " . (empty($new_legacy_field) ? 'SÍ' : 'NO') . "\n";
echo "   📝 Campo estándar tiene valor: " . (!empty($new_standard_field) ? 'SÍ' : 'NO') . "\n";

// 6. Resultado final
if ($is_recent && empty($new_legacy_field) && !empty($new_standard_field)) {
    echo "\n🎉 ¡ÉXITO TOTAL! Sistema completamente consistente\n";
    echo "   ✅ Solo usa campo estándar: _wcpdf_packing-slip_date\n";
    echo "   ✅ Actualiza fecha correctamente al cambiar a entregado\n";
    echo "   ✅ No hay duplicados con campo legacy\n";
} else {
    echo "\n❌ ERROR: Sistema aún no está completamente consistente\n";
    if (!$is_recent) echo "   - Fecha no se actualizó correctamente\n";
    if (!empty($new_legacy_field)) echo "   - Campo legacy aún tiene valor\n";
    if (empty($new_standard_field)) echo "   - Campo estándar no tiene valor\n";
}

echo "\n📋 DETALLES TÉCNICOS:\n";
echo "   🕒 Timestamp actual: $now (" . date('Y-m-d H:i:s', $now) . ")\n";
echo "   🕒 Timestamp guardado: $new_standard_field (" . ($new_standard_field ? date('Y-m-d H:i:s', $new_standard_field) : 'N/A') . ")\n";
echo "   ⏰ Diferencia: " . ($new_standard_field ? ($now - $new_standard_field) . " segundos" : 'N/A') . "\n";

echo "\n✅ Test de consistencia completado\n";
?>
