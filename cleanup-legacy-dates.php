<?php
/**
 * Script de limpieza masiva: ELIMINAR todos los campos legacy
 * Usar SOLO _wcpdf_packing-slip_date (CON guión)
 */

// Cargar WordPress
require_once(__DIR__ . '/wp-load.php');

echo "🧹 LIMPIEZA MASIVA DE CAMPOS LEGACY\n";
echo "══════════════════════════════════════════\n\n";

echo "🎯 OBJETIVO: Solo usar _wcpdf_packing-slip_date (CON guión)\n";
echo "🗑️ ELIMINAR: Todos los _wcpdf_packing_slip_date (SIN guión)\n\n";

// Buscar TODOS los pedidos con campos legacy
global $wpdb;

echo "🔍 Buscando campos legacy en base de datos...\n";

$legacy_fields = $wpdb->get_results($wpdb->prepare("
    SELECT post_id, meta_value
    FROM {$wpdb->postmeta}
    WHERE meta_key = %s
    AND post_id IN (
        SELECT ID FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
    )
", '_wcpdf_packing_slip_date'));

echo "📊 Encontrados " . count($legacy_fields) . " campos legacy\n\n";

if (empty($legacy_fields)) {
    echo "✅ No hay campos legacy que limpiar\n";
    exit;
}

$migrated = 0;
$errors = 0;

foreach ($legacy_fields as $field) {
    $order_id = $field->post_id;
    $legacy_value = $field->meta_value;

    // Verificar si ya existe el campo estándar
    $standard_value = get_post_meta($order_id, '_wcpdf_packing-slip_date', true);

    echo "📋 Pedido #$order_id:\n";
    echo "   Legacy: " . ($legacy_value ? date('Y-m-d H:i:s', $legacy_value) : 'vacío') . "\n";
    echo "   Estándar: " . ($standard_value ? date('Y-m-d H:i:s', $standard_value) : 'vacío') . "\n";

    // Si no existe campo estándar, migrar el valor legacy
    if (!$standard_value && $legacy_value) {
        update_post_meta($order_id, '_wcpdf_packing-slip_date', $legacy_value);
        echo "   ✅ Migrado a campo estándar\n";
        $migrated++;
    } elseif ($standard_value) {
        echo "   ✅ Campo estándar ya existe, manteniendo\n";
    }

    // ELIMINAR campo legacy EN TODOS LOS CASOS
    $deleted = delete_post_meta($order_id, '_wcpdf_packing_slip_date');
    if ($deleted) {
        echo "   🗑️ Campo legacy eliminado\n";
    } else {
        echo "   ❌ Error eliminando campo legacy\n";
        $errors++;
    }

    echo "\n";
}

echo "📊 RESUMEN DE LIMPIEZA:\n";
echo "────────────────────────\n";
echo "Total procesados: " . count($legacy_fields) . "\n";
echo "Migrados: $migrated\n";
echo "Errores: $errors\n\n";

// Verificación final
echo "🔍 Verificación final...\n";

$remaining_legacy = $wpdb->get_var($wpdb->prepare("
    SELECT COUNT(*)
    FROM {$wpdb->postmeta}
    WHERE meta_key = %s
    AND post_id IN (
        SELECT ID FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
    )
", '_wcpdf_packing_slip_date'));

$total_standard = $wpdb->get_var($wpdb->prepare("
    SELECT COUNT(*)
    FROM {$wpdb->postmeta}
    WHERE meta_key = %s
    AND post_id IN (
        SELECT ID FROM {$wpdb->posts}
        WHERE post_type = 'shop_order'
    )
", '_wcpdf_packing-slip_date'));

echo "Campos legacy restantes: $remaining_legacy\n";
echo "Campos estándar totales: $total_standard\n";

if ($remaining_legacy == 0) {
    echo "\n🎉 ¡LIMPIEZA EXITOSA!\n";
    echo "✅ No quedan campos legacy\n";
    echo "✅ Solo se usa _wcpdf_packing-slip_date (CON guión)\n";
    echo "✅ Sistema completamente estandarizado\n";
} else {
    echo "\n⚠️ ATENCIÓN: Aún quedan $remaining_legacy campos legacy\n";
}

echo "\n✅ Limpieza completada\n";
?>
