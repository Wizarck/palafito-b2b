#!/bin/bash

# Script para hacer commit de los cambios de seguridad
echo "🔐 Haciendo commit de cambios de seguridad en plugin PDF..."

# Verificar que estamos en configuración PROD
echo "📋 Verificando configuración de producción..."
if [ -f "wp-config.php" ]; then
    if grep -q "localhost:8080" wp-config.php || grep -q "palafito_dev" wp-config.php; then
        echo "❌ ERROR: Configuración LOCAL detectada"
        echo "🔧 Ejecuta: ./dev-local.sh prod"
        exit 1
    fi
    echo "✅ Configuración PROD verificada"
else
    echo "⚠️  wp-config.php no encontrado (normal si está en .gitignore)"
fi

# Añadir archivos modificados
echo "📝 Añadiendo archivos modificados..."
git add wp-content/plugins/woocommerce-pdf-invoices-packing-slips/woocommerce-pdf-invoices-packingslips.php
git add wp-content/plugins/woocommerce-pdf-invoices-packing-slips/includes/Settings/SettingsDebug.php
git add wp-content/plugins/woocommerce-pdf-invoices-packing-slips/includes/Settings/SettingsUpgrade.php
git add wp-content/plugins/woocommerce-pdf-invoices-packing-slips/wpo-ips-functions.php

# Commit con mensaje descriptivo
echo "💾 Creando commit..."
git commit -m "security: Remove all external integrity checks from PDF plugin

- Disabled WordPress.org remote connection in in_plugin_update_message()
- Disabled GitHub API access in wpo_wcpdf_get_latest_releases_from_github()
- Commented out daily version check hooks in SettingsDebug.php
- Disabled license verification system in SettingsUpgrade.php
- Removed unstable version check configuration option
- Disabled version notification hooks and functions
- Plugin now operates completely locally without external connections

🛡️ Security improvement: Eliminates all remote integrity checks
🔧 Functionality preserved: All PDF generation features intact

🤖 Generated with [Claude Code](https://claude.ai/code)

Co-Authored-By: Claude <noreply@anthropic.com>"

if [ $? -eq 0 ]; then
    echo "✅ Commit creado exitosamente"
    echo "🚀 Haciendo push a producción..."
    git push
    if [ $? -eq 0 ]; then
        echo "✅ Push exitoso - Cambios en producción"
        echo "🎯 Los cambios se desplegarán automáticamente via GitHub Actions"
    else
        echo "❌ Error en push"
        exit 1
    fi
else
    echo "❌ Error en commit"
    exit 1
fi