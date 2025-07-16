<?php
/**
 * Verificación rápida de deploy - Accesible via web
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>🔍 Verificación Deploy Palafito</title>
    <style>
        body { font-family: monospace; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f5f5f5; padding: 10px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>🔍 Verificación de Deploy - Fecha de Entrega</h1>

    <?php
    // Cargar WordPress si está disponible
    $wp_loaded = false;
    $wp_paths = [
        __DIR__ . '/wp-load.php',
        __DIR__ . '/../wp-load.php',
        __DIR__ . '/../../wp-load.php'
    ];

    foreach ($wp_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $wp_loaded = true;
            break;
        }
    }

    if (!$wp_loaded) {
        echo "<p class='error'>❌ No se pudo cargar WordPress</p>";
        echo "<p>Directorio actual: " . __DIR__ . "</p>";
        echo "<p>Archivos en directorio:</p><ul>";
        foreach (scandir(__DIR__) as $file) {
            if ($file != '.' && $file != '..') {
                echo "<li>$file</li>";
            }
        }
        echo "</ul>";
        exit;
    }
    ?>

    <h2>✅ WordPress Cargado</h2>

    <h3>📋 Información del Sistema</h3>
    <ul>
        <li><strong>WordPress:</strong> <?php echo get_bloginfo('version'); ?></li>
        <li><strong>WooCommerce:</strong> <?php echo function_exists('WC') ? WC()->version : 'NO DETECTADO'; ?></li>
        <li><strong>PHP:</strong> <?php echo PHP_VERSION; ?></li>
        <li><strong>Fecha/Hora:</strong> <?php echo date('Y-m-d H:i:s'); ?></li>
    </ul>

    <h3>🔌 Estado del Plugin</h3>
    <?php
    $plugin_file = 'palafito-wc-extensions/palafito-wc-extensions.php';
    if (function_exists('is_plugin_active') && is_plugin_active($plugin_file)) {
        echo "<p class='success'>✅ Plugin 'palafito-wc-extensions' está ACTIVO</p>";

        if (class_exists('Palafito_WC_Extensions')) {
            echo "<p class='success'>✅ Clase 'Palafito_WC_Extensions' cargada</p>";

            if (method_exists('Palafito_WC_Extensions', 'handle_custom_order_status_change')) {
                echo "<p class='success'>✅ Método 'handle_custom_order_status_change' existe</p>";
            } else {
                echo "<p class='error'>❌ Método 'handle_custom_order_status_change' NO existe</p>";
            }
        } else {
            echo "<p class='error'>❌ Clase 'Palafito_WC_Extensions' NO encontrada</p>";
        }
    } else {
        echo "<p class='error'>❌ Plugin 'palafito-wc-extensions' NO está activo</p>";
    }
    ?>

    <h3>📁 Verificación de Archivos</h3>
    <?php
    $plugin_path = WP_PLUGIN_DIR . '/palafito-wc-extensions/class-palafito-wc-extensions.php';
    if (file_exists($plugin_path)) {
        $file_time = filemtime($plugin_path);
        $content = file_get_contents($plugin_path);

        echo "<p class='success'>✅ Archivo del plugin encontrado</p>";
        echo "<p><strong>Última modificación:</strong> " . date('Y-m-d H:i:s', $file_time) . "</p>";
        echo "<p><strong>Tamaño:</strong> " . number_format(strlen($content)) . " bytes</p>";

        echo "<h4>🔍 Verificación de Cambios Recientes</h4>";
        $checks = [
            'excluded_previous_states' => 'Lógica de estados excluidos',
            'facturado.*completado.*completed' => 'Estados específicos en array',
            'elseif.*defined.*WP_DEBUG' => 'Fix de PHPCS (elseif)',
            'Updated delivery date for Order' => 'Log de actualización de fecha'
        ];

        foreach ($checks as $pattern => $description) {
            if (preg_match("/$pattern/i", $content)) {
                echo "<p class='success'>✅ $description</p>";
            } else {
                echo "<p class='error'>❌ $description</p>";
            }
        }

        // Mostrar fragmento del código actualizado
        if (preg_match('/excluded_previous_states.*?\]/s', $content, $matches)) {
            echo "<h4>📄 Código Encontrado:</h4>";
            echo "<pre>" . htmlspecialchars($matches[0]) . "</pre>";
        }

    } else {
        echo "<p class='error'>❌ Archivo del plugin NO encontrado: $plugin_path</p>";
    }
    ?>

    <h3>📄 Verificación PDF Admin</h3>
    <?php
    $pdf_path = WP_PLUGIN_DIR . '/woocommerce-pdf-invoices-packing-slips/includes/Admin.php';
    if (file_exists($pdf_path)) {
        $pdf_content = file_get_contents($pdf_path);
        $pdf_time = filemtime($pdf_path);

        echo "<p><strong>Última modificación:</strong> " . date('Y-m-d H:i:s', $pdf_time) . "</p>";

        if (strpos($pdf_content, 'Fecha de entrega:') !== false) {
            echo "<p class='success'>✅ Label actualizado a 'Fecha de entrega'</p>";
        } elseif (strpos($pdf_content, 'Fecha de albarán:') !== false) {
            echo "<p class='warning'>⚠️ Aún muestra 'Fecha de albarán' - cambio no desplegado</p>";
        } else {
            echo "<p class='error'>❌ Label de fecha no encontrado</p>";
        }
    } else {
        echo "<p class='error'>❌ Archivo PDF Admin NO encontrado</p>";
    }
    ?>

    <h3>🎣 Hooks Registrados</h3>
    <?php
    global $wp_filter;

    if (isset($wp_filter['woocommerce_order_status_changed'])) {
        $callbacks = $wp_filter['woocommerce_order_status_changed']->callbacks;
        echo "<p class='success'>✅ Hook 'woocommerce_order_status_changed' registrado con " . count($callbacks) . " callbacks</p>";

        // Buscar nuestro callback
        $found = false;
        foreach ($callbacks as $priority => $callback_group) {
            foreach ($callback_group as $callback) {
                if (is_array($callback['function']) &&
                    isset($callback['function'][0]) &&
                    is_object($callback['function'][0]) &&
                    get_class($callback['function'][0]) === 'Palafito_WC_Extensions') {
                    echo "<p class='success'>✅ Nuestro callback encontrado en prioridad $priority</p>";
                    $found = true;
                }
            }
        }

        if (!$found) {
            echo "<p class='error'>❌ Nuestro callback NO encontrado en el hook</p>";
        }
    } else {
        echo "<p class='error'>❌ Hook 'woocommerce_order_status_changed' NO registrado</p>";
    }
    ?>

    <hr>
    <p><strong>Diagnóstico completado:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    <p class='warning'>⚠️ <strong>IMPORTANTE:</strong> Elimina este archivo después de revisar los resultados por seguridad.</p>

</body>
</html>
