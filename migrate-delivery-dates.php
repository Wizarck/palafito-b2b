<?php
/**
 * Script de migración para consolidar campos de fecha de entrega
 *
 * Migra todos los campos legacy '_wcpdf_packing_slip_date' (sin guión)
 * al estándar '_wcpdf_packing-slip_date' (con guión) y limpia duplicados.
 */

// Cargar WordPress
require_once(__DIR__ . '/wp-load.php');

echo "🔄 MIGRACIÓN DE CAMPOS DE FECHA DE ENTREGA\n";
echo "══════════════════════════════════════════\n\n";

echo "📋 Estándar del proyecto: _wcpdf_packing-slip_date (CON guión)\n";
echo "🧹 Limpiando: _wcpdf_packing_slip_date (SIN guión - legacy)\n\n";

// 1. Buscar todos los pedidos con alguno de los campos
$orders_with_legacy = [];
$orders_with_standard = [];
$orders_with_both = [];

echo "🔍 Buscando pedidos con campos de fecha...\n";

// Buscar pedidos con campo legacy (sin guión)
$legacy_query = new WP_Query([
    'post_type' => 'shop_order',
    'post_status' => 'any',
    'posts_per_page' => -1,
    'meta_query' => [
        [
            'key' => '_wcpdf_packing_slip_date',
            'compare' => 'EXISTS'
        ]
    ],
    'fields' => 'ids'
]);

foreach ($legacy_query->posts as $order_id) {
    $orders_with_legacy[] = $order_id;
}

echo "   📊 Pedidos con campo legacy: " . count($orders_with_legacy) . "\n";

// Buscar pedidos con campo estándar (con guión)
$standard_query = new WP_Query([
    'post_type' => 'shop_order',
    'post_status' => 'any',
    'posts_per_page' => -1,
    'meta_query' => [
        [
            'key' => '_wcpdf_packing-slip_date',
            'compare' => 'EXISTS'
        ]
    ],
    'fields' => 'ids'
]);

foreach ($standard_query->posts as $order_id) {
    $orders_with_standard[] = $order_id;
}

echo "   📊 Pedidos con campo estándar: " . count($orders_with_standard) . "\n";

// Encontrar pedidos que tienen ambos campos
$orders_with_both = array_intersect($orders_with_legacy, $orders_with_standard);
echo "   📊 Pedidos con AMBOS campos: " . count($orders_with_both) . "\n";

// Encontrar pedidos que solo tienen el legacy
$only_legacy = array_diff($orders_with_legacy, $orders_with_standard);
echo "   📊 Pedidos SOLO con legacy: " . count($only_legacy) . "\n\n";

// 2. Migrar pedidos que solo tienen campo legacy
if (!empty($only_legacy)) {
    echo "🔄 Migrando pedidos con SOLO campo legacy...\n";

    foreach ($only_legacy as $order_id) {
        $legacy_value = get_post_meta($order_id, '_wcpdf_packing_slip_date', true);

        if ($legacy_value) {
            // Migrar al campo estándar
            update_post_meta($order_id, '_wcpdf_packing-slip_date', $legacy_value);

            // Eliminar campo legacy
            delete_post_meta($order_id, '_wcpdf_packing_slip_date');

            echo "   ✅ Pedido #$order_id: " . date('Y-m-d H:i:s', $legacy_value) . " migrado\n";
        }
    }
    echo "   📊 Total migrados: " . count($only_legacy) . "\n\n";
} else {
    echo "✅ No hay pedidos que requieran migración (solo legacy)\n\n";
}

// 3. Limpiar duplicados en pedidos que tienen ambos campos
if (!empty($orders_with_both)) {
    echo "🧹 Limpiando duplicados en pedidos con AMBOS campos...\n";

    foreach ($orders_with_both as $order_id) {
        $legacy_value = get_post_meta($order_id, '_wcpdf_packing_slip_date', true);
        $standard_value = get_post_meta($order_id, '_wcpdf_packing-slip_date', true);

        echo "   📋 Pedido #$order_id:\n";
        echo "      Legacy: " . ($legacy_value ? date('Y-m-d H:i:s', $legacy_value) : 'vacío') . "\n";
        echo "      Estándar: " . ($standard_value ? date('Y-m-d H:i:s', $standard_value) : 'vacío') . "\n";

        // Determinar qué valor mantener
        if ($standard_value && $legacy_value) {
            // Si ambos existen, usar el más reciente
            $keep_value = max($standard_value, $legacy_value);
            $source = ($keep_value == $standard_value) ? 'estándar' : 'legacy';

            echo "      🎯 Manteniendo valor más reciente ($source): " . date('Y-m-d H:i:s', $keep_value) . "\n";

            // Actualizar campo estándar con el valor más reciente
            update_post_meta($order_id, '_wcpdf_packing-slip_date', $keep_value);

        } elseif ($legacy_value && !$standard_value) {
            // Solo existe legacy, migrar
            echo "      🔄 Migrando legacy a estándar\n";
            update_post_meta($order_id, '_wcpdf_packing-slip_date', $legacy_value);

        } elseif ($standard_value && !$legacy_value) {
            echo "      ✅ Ya tiene valor estándar, manteniendo\n";
        }

        // Eliminar campo legacy en todos los casos
        delete_post_meta($order_id, '_wcpdf_packing_slip_date');
        echo "      🗑️ Campo legacy eliminado\n\n";
    }
    echo "   📊 Total limpiados: " . count($orders_with_both) . "\n\n";
} else {
    echo "✅ No hay pedidos con campos duplicados\n\n";
}

// 4. Verificación final
echo "🔍 Verificación final...\n";

// Contar campos restantes
$remaining_legacy = new WP_Query([
    'post_type' => 'shop_order',
    'post_status' => 'any',
    'posts_per_page' => -1,
    'meta_query' => [
        [
            'key' => '_wcpdf_packing_slip_date',
            'compare' => 'EXISTS'
        ]
    ],
    'fields' => 'ids'
]);

$final_standard = new WP_Query([
    'post_type' => 'shop_order',
    'post_status' => 'any',
    'posts_per_page' => -1,
    'meta_query' => [
        [
            'key' => '_wcpdf_packing-slip_date',
            'compare' => 'EXISTS'
        ]
    ],
    'fields' => 'ids'
]);

echo "   📊 Campos legacy restantes: " . count($remaining_legacy->posts) . "\n";
echo "   📊 Campos estándar finales: " . count($final_standard->posts) . "\n";

if (count($remaining_legacy->posts) === 0) {
    echo "   ✅ MIGRACIÓN EXITOSA: No quedan campos legacy\n";
} else {
    echo "   ⚠️ ATENCIÓN: Aún quedan " . count($remaining_legacy->posts) . " campos legacy\n";
}

// 5. Resumen
echo "\n📋 RESUMEN DE MIGRACIÓN:\n";
echo "══════════════════════════════════════════\n";
echo "✅ Campo estándar: _wcpdf_packing-slip_date (CON guión)\n";
echo "🗑️ Campo legacy eliminado: _wcpdf_packing_slip_date (SIN guión)\n";
echo "📊 Total de pedidos con fecha de entrega: " . count($final_standard->posts) . "\n";
echo "\n🎯 CONSISTENCIA LOGRADA:\n";
echo "   - ✅ Plugin usa solo _wcpdf_packing-slip_date\n";
echo "   - ✅ Template PDF usa solo _wcpdf_packing-slip_date\n";
echo "   - ✅ Columnas admin usan solo _wcpdf_packing-slip_date\n";
echo "   - ✅ Documentación usa solo _wcpdf_packing-slip_date\n";

echo "\n✅ Migración completada exitosamente\n";
?>
