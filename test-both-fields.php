<?php
/**
 * Test para verificar actualización de AMBOS campos de fecha
 */

// Cargar WordPress
require_once(__DIR__ . '/wp-load.php');

echo "🔄 Test Actualización de AMBOS Campos de Fecha\n";
echo "═══════════════════════════════════════════════\n\n";

$order_id = 2510;

// 1. Verificar pedido
$order = wc_get_order($order_id);
if (!$order) {
    echo "❌ Pedido #$order_id no encontrado\n";
    exit;
}

echo "✅ Usando pedido #$order_id\n";

// 2. Mostrar estado actual de AMBOS campos
echo "\n📋 Estado actual de los campos:\n";

$field_with_dash = $order->get_meta('_wcpdf_packing-slip_date');
$field_without_dash = $order->get_meta('_wcpdf_packing_slip_date');

echo "   _wcpdf_packing-slip_date (con guión): " . ($field_with_dash ? date('Y-m-d H:i:s', $field_with_dash) : 'No establecido') . "\n";
echo "   _wcpdf_packing_slip_date (sin guión): " . ($field_without_dash ? date('Y-m-d H:i:s', $field_without_dash) : 'No establecido') . "\n";

// 3. Cargar plugin si es necesario
if (!class_exists('Palafito_WC_Extensions')) {
    require_once(WP_PLUGIN_DIR . '/palafito-wc-extensions/class-palafito-wc-extensions.php');
}

// 4. Guardar valores originales
$original_status = $order->get_status();
$original_with_dash = $field_with_dash;
$original_without_dash = $field_without_dash;

echo "\n🧪 Ejecutando test de actualización...\n";
echo "   Estado original: $original_status\n";

// 5. Cambiar a processing primero
$order->set_status('processing');
$order->save();
echo "   ✅ Cambiado temporalmente a processing\n";

// 6. Ejecutar nuestro método de actualización
echo "   🔧 Ejecutando handle_custom_order_status_change...\n";
Palafito_WC_Extensions::handle_custom_order_status_change($order_id, 'processing', 'entregado', $order);

// 7. Verificar ambos campos después de la actualización
$order = wc_get_order($order_id); // Recargar
$new_with_dash = $order->get_meta('_wcpdf_packing-slip_date');
$new_without_dash = $order->get_meta('_wcpdf_packing_slip_date');

echo "\n📊 Resultados después de la actualización:\n";
echo "   _wcpdf_packing-slip_date (con guión): " . ($new_with_dash ? date('Y-m-d H:i:s', $new_with_dash) : 'No establecido') . "\n";
echo "   _wcpdf_packing_slip_date (sin guión): " . ($new_without_dash ? date('Y-m-d H:i:s', $new_without_dash) : 'No establecido') . "\n";

// 8. Verificar que ambos campos se actualizaron
$dash_updated = ($new_with_dash && $new_with_dash != $original_with_dash);
$no_dash_updated = ($new_without_dash && $new_without_dash != $original_without_dash);
$both_same = ($new_with_dash == $new_without_dash);

echo "\n✅ Verificación de resultados:\n";
echo "   Campo CON guión actualizado: " . ($dash_updated ? "✅ SÍ" : "❌ NO") . "\n";
echo "   Campo SIN guión actualizado: " . ($no_dash_updated ? "✅ SÍ" : "❌ NO") . "\n";
echo "   Ambos campos tienen el mismo valor: " . ($both_same ? "✅ SÍ" : "❌ NO") . "\n";

if ($dash_updated && $no_dash_updated && $both_same) {
    echo "\n🎉 ¡ÉXITO TOTAL! Ambos campos actualizados correctamente\n";
    echo "   Esto significa que el metabox ahora mostrará la fecha actual\n";
} else {
    echo "\n⚠️  Problema detectado:\n";
    if (!$dash_updated) echo "   - Campo con guión NO se actualizó\n";
    if (!$no_dash_updated) echo "   - Campo sin guión NO se actualizó\n";
    if (!$both_same) echo "   - Los campos tienen valores diferentes\n";
}

// 9. Mostrar timestamps para debugging
if ($new_with_dash && $new_without_dash) {
    echo "\n🔍 Timestamps para debugging:\n";
    echo "   Con guión: $new_with_dash\n";
    echo "   Sin guión: $new_without_dash\n";
    echo "   Diferencia: " . abs($new_with_dash - $new_without_dash) . " segundos\n";
}

// 10. Restaurar estado original
echo "\n🔄 Restaurando estado original...\n";
$order->set_status($original_status);

// Restaurar fechas originales solo si existían
if ($original_with_dash) {
    $order->update_meta_data('_wcpdf_packing-slip_date', $original_with_dash);
}
if ($original_without_dash) {
    $order->update_meta_data('_wcpdf_packing_slip_date', $original_without_dash);
}

$order->save();

echo "   ✅ Estado y fechas restaurados\n";

echo "\n📝 RESUMEN:\n";
echo "═══════════════════════════════════════════════\n";
echo "El problema era que existían DOS campos diferentes:\n";
echo "1. _wcpdf_packing-slip_date (con guión) - que actualizábamos antes\n";
echo "2. _wcpdf_packing_slip_date (sin guión) - que muestra el metabox\n";
echo "\nAhora actualizamos AMBOS campos simultáneamente.\n";
echo "Esto garantiza que el metabox muestre la fecha correcta.\n";

echo "\n✅ Test completado\n";
?>
