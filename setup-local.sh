#!/bin/bash

# Script de configuración para entorno local de desarrollo Palafito B2B
# Autor: Palafito Development Team
# Versión: 1.0

set -e

echo "🚀 Configurando entorno local de desarrollo Palafito B2B..."

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para logging
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

warning() {
    echo -e "${YELLOW}[WARNING] $1${NC}"
}

error() {
    echo -e "${RED}[ERROR] $1${NC}"
    exit 1
}

# 1. Verificar dependencias
log "Verificando dependencias..."
command -v wp >/dev/null 2>&1 || error "WP-CLI no está instalado"
command -v mysql >/dev/null 2>&1 || error "MySQL no está instalado"
command -v php >/dev/null 2>&1 || error "PHP no está instalado"

# 2. Configurar base de datos local
log "Configurando base de datos local..."
DB_NAME="palafito_local"
DB_USER="root"
DB_PASS=""

# Crear base de datos si no existe
mysql -u$DB_USER -p$DB_PASS -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || {
    warning "No se pudo crear la base de datos automáticamente"
    echo "Por favor, crea manualmente la base de datos '$DB_NAME'"
    read -p "Presiona Enter cuando hayas creado la base de datos..."
}

# 3. Configurar wp-config.php local
if [ ! -f "wp-config-local.php" ]; then
    error "wp-config-local.php no encontrado. Ejecuta primero la configuración con Claude."
fi

log "Configurando wp-config.php para desarrollo local..."
cp wp-config.php wp-config-production.php.bak
cp wp-config-local.php wp-config.php

# 4. Generar claves de seguridad
log "Generando claves de seguridad..."
wp config shuffle-salts

# 5. Instalar WordPress si no está instalado
if ! wp core is-installed 2>/dev/null; then
    log "Instalando WordPress..."
    wp core install \
        --url=http://localhost:8080 \
        --title="Palafito B2B Local" \
        --admin_user=admin \
        --admin_password=admin123 \
        --admin_email=admin@palafito.local \
        --skip-email
    
    log "✅ WordPress instalado correctamente"
    echo "  Usuario: admin"
    echo "  Contraseña: admin123"
    echo "  Email: admin@palafito.local"
else
    log "WordPress ya está instalado"
fi

# 6. Activar plugins necesarios
log "Activando plugins..."
wp plugin activate palafito-wc-extensions
wp plugin activate woocommerce

# 7. Configurar WooCommerce básico
if wp plugin is-active woocommerce; then
    log "Configurando WooCommerce..."
    wp option update woocommerce_store_address "Calle Ejemplo 123"
    wp option update woocommerce_store_city "Madrid"
    wp option update woocommerce_default_country "ES"
    wp option update woocommerce_store_postcode "28001"
    wp option update woocommerce_currency "EUR"
    wp option update woocommerce_price_decimal_sep ","
    wp option update woocommerce_price_thousand_sep "."
fi

# 8. Instalar plugins de desarrollo recomendados
log "Instalando plugins de desarrollo..."
DEVELOPMENT_PLUGINS=(
    "query-monitor"
    "debug-bar"
    "debug-bar-console"
    "log-deprecated-notices"
    "developer"
)

for plugin in "${DEVELOPMENT_PLUGINS[@]}"; do
    if ! wp plugin is-installed $plugin; then
        wp plugin install $plugin --activate || warning "No se pudo instalar $plugin"
    else
        wp plugin activate $plugin || warning "No se pudo activar $plugin"
    fi
done

# 9. Configurar permalinks
log "Configurando permalinks..."
wp rewrite structure '/%postname%/' --hard

# 10. Crear usuario de prueba B2B
log "Creando usuarios de prueba..."
wp user create cliente cliente@palafito.local \
    --role=customer \
    --first_name="Cliente" \
    --last_name="Prueba" \
    --send-email=false || warning "Usuario cliente ya existe"

# 11. Configurar debugging
log "Configurando debugging..."
wp config set WP_DEBUG true --type=constant
wp config set WP_DEBUG_LOG true --type=constant
wp config set WP_DEBUG_DISPLAY false --type=constant
wp config set SCRIPT_DEBUG true --type=constant

# 12. Limpiar cache
log "Limpiando cache..."
wp cache flush

# 13. Verificar instalación
log "Verificando instalación..."
wp doctor check --all || warning "Algunas verificaciones fallaron"

# 14. Mostrar información final
log "🎉 ¡Configuración completada!"
echo ""
echo -e "${BLUE}Información del entorno local:${NC}"
echo "  URL: http://localhost:8080"
echo "  Admin: admin / admin123"
echo "  Base de datos: $DB_NAME"
echo ""
echo -e "${BLUE}Comandos útiles:${NC}"
echo "  wp server                    # Iniciar servidor de desarrollo"
echo "  wp palafito:debug:logs      # Ver logs de Palafito en tiempo real"
echo "  wp palafito:orders:status   # Ver estados de pedidos personalizados"
echo "  wp health-check             # Verificar salud del sitio"
echo "  wp clean                    # Limpiar cache y logs"
echo ""
echo -e "${GREEN}¡Listo para desarrollar! 🚀${NC}"