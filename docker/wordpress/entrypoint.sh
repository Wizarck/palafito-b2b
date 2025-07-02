#!/bin/bash
set -e

# Palafito B2B WordPress Container Entrypoint

echo "🚀 Iniciando contenedor WordPress para Palafito B2B..."

# Esperar a que MySQL esté disponible
echo "⏳ Esperando MySQL..."
while ! mysqladmin ping -h mysql --silent; do
    sleep 1
done
echo "✅ MySQL está disponible"

# Crear directorios necesarios
mkdir -p /var/www/html/wp-content/{uploads,cache,backups,logs}
mkdir -p /var/www/html/wp-content/xdebug

# Configurar permisos
chown -R www-data:www-data /var/www/html/wp-content
chmod -R 755 /var/www/html/wp-content

# Si WordPress no está instalado, instalarlo
if [ ! -f /var/www/html/wp-config.php ]; then
    echo "📥 Instalando WordPress..."
    
    # Descargar WordPress
    wp core download --allow-root --force
    
    # Crear wp-config.php
    wp config create \
        --dbname="${WORDPRESS_DB_NAME}" \
        --dbuser="${WORDPRESS_DB_USER}" \
        --dbpass="${WORDPRESS_DB_PASSWORD}" \
        --dbhost="${WORDPRESS_DB_HOST}" \
        --allow-root \
        --extra-php <<PHP
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
define('WP_ENVIRONMENT_TYPE', 'local');
define('WP_CACHE', false);
define('AUTOMATIC_UPDATER_DISABLED', true);
define('DISALLOW_FILE_EDIT', false);

// Redis Object Cache
define('WP_REDIS_HOST', 'redis');
define('WP_REDIS_PORT', 6379);
define('WP_REDIS_DATABASE', 0);

// MailHog SMTP
define('SMTP_HOST', 'mailhog');
define('SMTP_PORT', 1025);

// Debugging
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/html/wp-content/debug.log');
PHP
    
    # Instalar WordPress si la BD está vacía
    if ! wp core is-installed --allow-root 2>/dev/null; then
        echo "🔧 Configurando WordPress..."
        wp core install \
            --url="http://localhost:8080" \
            --title="Palafito B2B Development" \
            --admin_user="admin" \
            --admin_password="admin123" \
            --admin_email="admin@palafito.local" \
            --skip-email \
            --allow-root
        
        echo "✅ WordPress instalado correctamente"
        echo "   Usuario: admin"
        echo "   Contraseña: admin123"
    fi
fi

# Activar plugins si existen
if [ -d "/var/www/html/wp-content/plugins/palafito-wc-extensions" ]; then
    echo "🔌 Activando plugin Palafito..."
    wp plugin activate palafito-wc-extensions --allow-root || true
fi

if [ -d "/var/www/html/wp-content/plugins/woocommerce" ]; then
    echo "🛒 Activando WooCommerce..."
    wp plugin activate woocommerce --allow-root || true
fi

# Configurar permalinks
wp rewrite structure '/%postname%/' --hard --allow-root || true

# Limpiar cache
wp cache flush --allow-root || true

echo "🎉 WordPress listo para desarrollo!"
echo "🌐 Accede a: http://localhost:8080"
echo "⚙️  Admin: http://localhost:8080/wp-admin"
echo "📧 MailHog: http://localhost:8025"
echo "🗄️  phpMyAdmin: http://localhost:8081"

# Ejecutar comando original
exec "$@"