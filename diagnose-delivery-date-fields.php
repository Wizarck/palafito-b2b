<?php
/**
 * Diagnóstico específico para campos de fecha de entrega
 * Compara todos los posibles campos y métodos
 */

// Configurar order ID para probar
$test_order_id = 2510; // Cambiar por un pedido real

echo "<h2>🔍 Diagnóstico completo de fecha de entrega - Pedido #{$test_order_id}</h2>";

// Cargar WordPress
if (!function_exists('wp_load_config')) {
    define('WP_USE_THEMES', false);
    require_once('./wp-load.php');
}

// Verificar que WooCommerce está cargado
if (!function_exists('wc_get_order')) {
    echo "<p style='color: red;'>ERROR: WooCommerce no está cargado</p>";
    exit;
}

$order = wc_get_order($test_order_id);

if (!$order) {
    echo "<p style='color: red;'>ERROR: Pedido #{$test_order_id} no encontrado</p>";
    exit;
}

echo "<table border='1' cellpadding='10' style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'><th>Origen del dato</th><th>Valor</th><th>Fecha formateada</th><th>Observaciones</th></tr>";

// 1. Campo estándar (_wcpdf_packing-slip_date)
$standard_field = $order->get_meta('_wcpdf_packing-slip_date');
echo "<tr>";
echo "<td><strong>Campo estándar (nuestra columna)</strong><br><code>_wcpdf_packing-slip_date</code></td>";
echo "<td>" . ($standard_field ? $standard_field : '<span style="color: red;">VACÍO</span>') . "</td>";
echo "<td>" . ($standard_field ? date('d-m-Y H:i:s', is_numeric($standard_field) ? $standard_field : strtotime($standard_field)) : 'N/A') . "</td>";
echo "<td>Campo que lee nuestra columna</td>";
echo "</tr>";

// 2. Campo legacy (_wcpdf_packing_slip_date)
$legacy_field = $order->get_meta('_wcpdf_packing_slip_date');
echo "<tr>";
echo "<td><strong>Campo legacy</strong><br><code>_wcpdf_packing_slip_date</code></td>";
echo "<td>" . ($legacy_field ? $legacy_field : '<span style="color: red;">VACÍO</span>') . "</td>";
echo "<td>" . ($legacy_field ? date('d-m-Y H:i:s', is_numeric($legacy_field) ? $legacy_field : strtotime($legacy_field)) : 'N/A') . "</td>";
echo "<td>Campo legacy (sin guión)</td>";
echo "</tr>";

// 3. Documento packing-slip del plugin PDF
$packing_slip = null;
if (function_exists('wcpdf_get_document')) {
    $packing_slip = wcpdf_get_document('packing-slip', $order);

    echo "<tr>";
    echo "<td><strong>Documento packing-slip existe</strong></td>";
    echo "<td>" . ($packing_slip ? 'SÍ' : '<span style="color: red;">NO</span>') . "</td>";
    echo "<td>N/A</td>";
    echo "<td>Documento del plugin PDF</td>";
    echo "</tr>";

    if ($packing_slip) {
        // Fecha del documento
        $doc_date = $packing_slip->get_date();
        echo "<tr>";
        echo "<td><strong>Fecha del documento PDF</strong><br><code>\$packing_slip->get_date()</code></td>";
        echo "<td>" . ($doc_date ? get_class($doc_date) . " object" : '<span style="color: red;">VACÍO</span>') . "</td>";
        echo "<td>" . ($doc_date ? $doc_date->date_i18n('d-m-Y H:i:s') : 'N/A') . "</td>";
        echo "<td>Lo que muestra el metabox</td>";
        echo "</tr>";

        // Método formatted
        if ($doc_date) {
            $formatted_date = $doc_date->date_i18n(wc_date_format().' @ '.wc_time_format());
            echo "<tr>";
            echo "<td><strong>Fecha formateada del documento</strong></td>";
            echo "<td>$formatted_date</td>";
            echo "<td>$formatted_date</td>";
            echo "<td>Formato del metabox</td>";
            echo "</tr>";
        }

        // Verificar si existe
        echo "<tr>";
        echo "<td><strong>Documento existe</strong><br><code>\$packing_slip->exists()</code></td>";
        echo "<td>" . ($packing_slip->exists() ? 'SÍ' : 'NO') . "</td>";
        echo "<td>N/A</td>";
        echo "<td>Estado del documento</td>";
        echo "</tr>";
    }
}

// 4. Obtener todos los metadatos del pedido
echo "<tr style='background: #f9f9f9;'>";
echo "<td colspan='4'><strong>TODOS LOS METADATOS RELACIONADOS CON FECHA</strong></td>";
echo "</tr>";

$all_meta = $order->get_meta_data();
foreach ($all_meta as $meta) {
    $key = $meta->get_data()['key'];
    $value = $meta->get_data()['value'];

    // Solo mostrar campos relacionados con fecha
    if (strpos($key, 'date') !== false || strpos($key, 'packing') !== false || strpos($key, 'entregado') !== false) {
        echo "<tr>";
        echo "<td><code>$key</code></td>";
        echo "<td>$value</td>";
        echo "<td>" . (is_numeric($value) ? date('d-m-Y H:i:s', $value) : $value) . "</td>";
        echo "<td>Metadato del pedido</td>";
        echo "</tr>";
    }
}

// 5. Estado del pedido
echo "<tr style='background: #fff3cd;'>";
echo "<td><strong>Estado del pedido</strong></td>";
echo "<td>" . $order->get_status() . "</td>";
echo "<td>N/A</td>";
echo "<td>Estado actual</td>";
echo "</tr>";

// 6. Test de simulación de columna
echo "<tr style='background: #d1ecf1;'>";
echo "<td><strong>SIMULACIÓN DE COLUMNA</strong></td>";
echo "<td colspan='3'>";

// Simular exactamente la lógica de la columna
$entregado_date = $order->get_meta('_wcpdf_packing-slip_date');

if ($entregado_date) {
    $date = is_numeric($entregado_date) ? $entregado_date : strtotime($entregado_date);
    $column_display = date_i18n('d-m-Y', $date);
    echo "<strong style='color: green;'>$column_display</strong> (esto es lo que debería mostrar la columna)";
} else {
    echo "<strong style='color: red;'>&mdash;</strong> (esto es lo que muestra la columna cuando está vacío)";
}

echo "</td>";
echo "</tr>";

echo "</table>";

// Test de actualización manual
echo "<h3>🔧 Test de actualización manual</h3>";

$current_timestamp = current_time('timestamp');
echo "<p>Timestamp actual: $current_timestamp (" . date('d-m-Y H:i:s', $current_timestamp) . ")</p>";

echo "<p><strong>Actualizando campo estándar manualmente...</strong></p>";

// Actualizar el campo
$order->update_meta_data('_wcpdf_packing-slip_date', $current_timestamp);
$order->save_meta_data();

// Verificar
$verified = $order->get_meta('_wcpdf_packing-slip_date');

if ($verified == $current_timestamp) {
    echo "<p style='color: green;'>✅ Actualización exitosa: $verified (" . date('d-m-Y H:i:s', $verified) . ")</p>";
} else {
    echo "<p style='color: red;'>❌ Actualización falló. Esperado: $current_timestamp, Obtenido: $verified</p>";
}

// Test del documento después de la actualización
if ($packing_slip) {
    echo "<p><strong>Verificando documento después de actualización...</strong></p>";

    // Recargar el documento
    $packing_slip = wcpdf_get_document('packing-slip', $order);
    $doc_date_after = $packing_slip->get_date();

    if ($doc_date_after) {
        echo "<p>Fecha del documento después de actualización: " . $doc_date_after->date_i18n('d-m-Y H:i:s') . "</p>";
    } else {
        echo "<p style='color: red;'>Documento sigue sin fecha después de actualización</p>";
    }
}

?>
