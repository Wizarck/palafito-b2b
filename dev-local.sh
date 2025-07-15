#!/bin/bash
# Script para desarrollo local - NUNCA se sube a PROD

echo "🔧 Configurando entorno de desarrollo local..."

# Verificar que estamos en local
if [ ! -f "wp-config.php.backup" ]; then
    echo "❌ Error: No se encontró wp-config.php.backup"
    echo "Este script solo debe ejecutarse en local"
    exit 1
fi

# Función para ir a configuración local
function to_local() {
    echo "📝 Cambiando a configuración LOCAL..."
    cp wp-config-docker-clean.php wp-config.php
    echo "✅ Configuración local activa"
    echo "🌐 WordPress: http://localhost:8080"
    echo "🗃️ PhpMyAdmin: http://localhost:8081" 
    echo "📧 MailHog: http://localhost:8025"
}

# Función para volver a configuración de PROD
function to_prod() {
    echo "🚀 Restaurando configuración de PROD..."
    cp wp-config.php.backup wp-config.php
    echo "✅ Configuración de PROD restaurada"
    echo "⚠️  Ahora puedes hacer push sin romper PROD"
}

# Función para verificar configuración actual
function check_config() {
    if grep -q "localhost:8080" wp-config.php 2>/dev/null; then
        echo "📍 Estado: CONFIGURACIÓN LOCAL"
        echo "⚠️  NO hagas push con esta configuración"
    elif grep -q "db5016482050.hosting-data.io" wp-config.php 2>/dev/null; then
        echo "📍 Estado: CONFIGURACIÓN DE PROD"
        echo "✅ Seguro para push"
    else
        echo "📍 Estado: CONFIGURACIÓN DESCONOCIDA"
        echo "⚠️  Revisa wp-config.php manualmente"
    fi
}

# Ejecutar función según parámetro
case "$1" in
    "local")
        to_local
        ;;
    "prod")
        to_prod
        ;;
    "check")
        check_config
        ;;
    *)
        echo "💡 Uso:"
        echo "  ./dev-local.sh local   # Cambiar a configuración local"
        echo "  ./dev-local.sh prod    # Restaurar configuración PROD"
        echo "  ./dev-local.sh check   # Verificar configuración actual"
        echo ""
        check_config
        ;;
esac