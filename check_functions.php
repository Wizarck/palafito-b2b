<?php
require_once('/var/www/html/wp-load.php');

echo "=== CHECKING PLUGIN FUNCTIONS ===\n";

// Lista de funciones del plugin Palafito
$functions_to_check = [
    'palafito_sync_packing_slip_to_entregado',
    'palafito_sync_entregado_to_packing_slip',
    'palafito_save_packing_slip_data_on_order_save',
    'palafito_wc_extensions_init'
];

foreach ($functions_to_check as $func) {
    $exists = function_exists($func);
    echo "Function $func: " . ($exists ? "✅ EXISTS" : "❌ NOT FOUND") . "\n";
}

// Verificar si la clase principal existe
$class_exists = class_exists('Palafito_WC_Extensions');
echo "Class Palafito_WC_Extensions: " . ($class_exists ? "✅ EXISTS" : "❌ NOT FOUND") . "\n";

// Verificar si WooCommerce existe
$wc_exists = class_exists('WooCommerce');
echo "WooCommerce: " . ($wc_exists ? "✅ EXISTS" : "❌ NOT FOUND") . "\n";

// Verificar plugins activos
$active_plugins = get_option('active_plugins', []);
echo "\nActive plugins:\n";
foreach ($active_plugins as $plugin) {
    echo "- $plugin\n";
}

// Verificar si el archivo del plugin existe
$plugin_file = '/var/www/html/wp-content/plugins/palafito-wc-extensions/palafito-wc-extensions.php';
$file_exists = file_exists($plugin_file);
echo "\nPlugin file exists: " . ($file_exists ? "✅ YES" : "❌ NO") . "\n";

if ($file_exists) {
    $file_size = filesize($plugin_file);
    echo "Plugin file size: $file_size bytes\n";
}
?>