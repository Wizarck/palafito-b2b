#!/bin/bash

# Script de configuración para tests de Palafito B2B

set -e

echo "🧪 Configurando entorno de testing..."

# Configurar WordPress para testing
wp core download --allow-root --force
wp config create \
    --dbname="${WP_TESTS_DB_NAME:-palafito_test}" \
    --dbuser="${WP_TESTS_DB_USER:-palafito_user}" \
    --dbpass="${WP_TESTS_DB_PASSWORD:-palafito_pass}" \
    --dbhost="${WP_TESTS_DB_HOST:-mysql}" \
    --allow-root \
    --extra-php <<PHP
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
define('WP_ENVIRONMENT_TYPE', 'testing');
PHP

# Instalar WordPress
wp core install \
    --url="${WP_TESTS_DOMAIN:-localhost}" \
    --title="${WP_TESTS_TITLE:-Palafito B2B Tests}" \
    --admin_user="admin" \
    --admin_password="admin123" \
    --admin_email="${WP_TESTS_EMAIL:-admin@test.com}" \
    --skip-email \
    --allow-root

# Activar plugins necesarios
wp plugin activate woocommerce --allow-root
wp plugin activate palafito-wc-extensions --allow-root

echo "✅ Entorno de testing configurado"