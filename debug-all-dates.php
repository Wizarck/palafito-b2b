<?php
/**
 * Debug completo de TODOS los campos de fecha del pedido #2510
 */

// Cargar WordPress
require_once(__DIR__ . '/wp-load.php');

echo "🔍 DEBUG COMPLETO DE FECHAS - PEDIDO #2510\n";
echo "══════════════════════════════════════════\n\n";

$order_id = 2510;
$order = wc_get_order($order_id);

if (!$order) {
    echo "❌ Pedido #$order_id no encontrado\n";
    exit;
}

echo "📋 INFORMACIÓN BÁSICA:\n";
echo "────────────────────────\n";
echo "ID: " . $order->get_id() . "\n";
echo "Estado: " . $order->get_status() . "\n";
echo "Fecha creación: " . $order->get_date_created()->date('Y-m-d H:i:s') . "\n\n";

echo "🗂️ TODOS LOS META FIELDS DE FECHA:\n";
echo "──────────────────────────────────────\n";

// Lista de todos los campos posibles relacionados con fechas
$date_fields = [
    '_wcpdf_packing-slip_date',
    '_wcpdf_packing_slip_date',
    '_entregado_date',
    '_wcpdf_invoice_date',
    '_date_completed',
    '_completed_date',
    '_date_paid',
    '_paid_date',
    '_wcpdf_invoice_display_date',
    '_wcpdf_packing-slip_display_date',
    '_wcpdf_packing_slip_display_date'
];

foreach ($date_fields as $field) {
    $value = $order->get_meta($field);
    if ($value) {
        $formatted = is_numeric($value) ? date('Y-m-d H:i:s', $value) : $value;
        echo "✅ $field: $formatted\n";
    } else {
        echo "⚪ $field: VACÍO\n";
    }
}

echo "\n🔧 META FIELDS DIRECTOS DE BD:\n";
echo "────────────────────────────────\n";

// Verificar directamente en la base de datos
global $wpdb;
$meta_results = $wpdb->get_results($wpdb->prepare("
    SELECT meta_key, meta_value
    FROM {$wpdb->postmeta}
    WHERE post_id = %d
    AND meta_key LIKE '%date%'
    ORDER BY meta_key
", $order_id));

foreach ($meta_results as $meta) {
    $formatted = is_numeric($meta->meta_value) ? date('Y-m-d H:i:s', $meta->meta_value) : $meta->meta_value;
    echo "🗄️ {$meta->meta_key}: $formatted\n";
}

echo "\n📊 ANÁLISIS ESPECÍFICO:\n";
echo "─────────────────────────\n";

// Analizar específicamente los campos principales
$standard_field = $order->get_meta('_wcpdf_packing-slip_date');
$legacy_field = $order->get_meta('_wcpdf_packing_slip_date');

echo "Campo ESTÁNDAR (_wcpdf_packing-slip_date):\n";
if ($standard_field) {
    echo "   🟢 Valor: " . date('Y-m-d H:i:s', $standard_field) . "\n";
    echo "   🟢 Formato columna: " . date_i18n('d-m-Y', $standard_field) . "\n";
} else {
    echo "   ❌ VACÍO\n";
}

echo "\nCampo LEGACY (_wcpdf_packing_slip_date):\n";
if ($legacy_field) {
    echo "   🟡 Valor: " . date('Y-m-d H:i:s', $legacy_field) . "\n";
    echo "   🟡 Formato columna: " . date_i18n('d-m-Y', $legacy_field) . "\n";
} else {
    echo "   ✅ VACÍO (correcto)\n";
}

echo "\n🎯 SIMULACIÓN DE LÓGICA:\n";
echo "─────────────────────────\n";

// Simular exactamente lo que hace la columna
$order_status = $order->get_status();
$valid_statuses = ['entregado', 'facturado', 'completed'];

echo "Estado del pedido: $order_status\n";
echo "Estados válidos: " . implode(', ', $valid_statuses) . "\n";

if (in_array($order_status, $valid_statuses, true)) {
    echo "✅ Estado es válido para mostrar fecha\n";

    // Esta es la lógica EXACTA de la columna
    $entregado_date = $order->get_meta('_wcpdf_packing-slip_date');

    if ($entregado_date) {
        $date = is_numeric($entregado_date) ? $entregado_date : strtotime($entregado_date);
        $column_display = date_i18n('d-m-Y', $date);
        echo "🎯 COLUMNA DEBERÍA MOSTRAR: $column_display\n";
    } else {
        echo "🎯 COLUMNA DEBERÍA MOSTRAR: —\n";
    }
} else {
    echo "❌ Estado no válido\n";
}

echo "\n📄 VERIFICACIÓN DE METABOX:\n";
echo "───────────────────────────\n";

// Intentar determinar qué lee el metabox
// El metabox del plugin PDF lee usando wcpdf_get_document
try {
    $packing_slip = wcpdf_get_document('packing-slip', $order);
    if ($packing_slip && method_exists($packing_slip, 'get_date')) {
        $metabox_date = $packing_slip->get_date();
        if ($metabox_date) {
            echo "📝 Fecha del metabox (documento): " . $metabox_date->date_i18n('d-m-Y') . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error obteniendo documento: " . $e->getMessage() . "\n";
}

echo "\n🚨 DISCREPANCIA IDENTIFICADA:\n";
echo "────────────────────────────────\n";
echo "Si metabox muestra: 11-07-2025\n";
echo "Y columna muestra: 16-07-2025\n";
echo "Entonces están leyendo de campos diferentes.\n";

echo "\n✅ Debug completo terminado\n";
?>
