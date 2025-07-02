# Guía de Desarrollo Local - Palafito B2B

## Configuración Inicial

### 1. Setup del Entorno Local
```bash
# Configurar entorno completo
./setup-local.sh

# Iniciar servidor de desarrollo
wp server --host=localhost --port=8080

# Acceso al sitio
# URL: http://localhost:8080
# Admin: admin / admin123
```

### 2. Base de Datos Local
```bash
# Crear base de datos
mysql -u root -p -e "CREATE DATABASE palafito_local"

# Importar datos de producción (opcional)
wp db import production-backup.sql

# Reset completo
wp reset-db
```

## Comandos WP-CLI Esenciales

### Desarrollo General
```bash
# Verificar instalación
wp core is-installed

# Información del sitio
wp core version --extra

# Verificar salud
wp doctor check --all

# Generar claves de seguridad
wp config shuffle-salts
```

### Plugin Palafito
```bash
# Activar/desactivar plugin
wp plugin activate palafito-wc-extensions
wp plugin deactivate palafito-wc-extensions

# Ver hooks activos de Palafito
wp palafito:debug:hooks

# Ver logs en tiempo real
wp palafito:debug:logs

# Estados de pedidos personalizados
wp palafito:orders:status
```

### Debugging y Logs
```bash
# Activar debugging
wp config set WP_DEBUG true --type=constant
wp config set WP_DEBUG_LOG true --type=constant

# Ver logs en tiempo real
tail -f wp-content/debug.log

# Filtrar logs de Palafito
tail -f wp-content/debug.log | grep -i palafito

# Limpiar logs
wp clean
```

### WooCommerce Testing
```bash
# Crear productos de prueba
wp wc product create --name="Test Product" --type=simple --regular_price=29.99

# Crear pedidos de prueba
wp wc order create --status=processing

# Cambiar estado de pedido
wp wc order update [ORDER_ID] --status=entregado

# Listar pedidos
wp wc order list --status=processing

# Ver metadatos de pedido
wp wc order get [ORDER_ID] --format=json
```

### Base de Datos
```bash
# Backup
wp db export wp-content/backups/backup-$(date +%Y%m%d).sql

# Restore
wp db import wp-content/backups/backup-YYYYMMDD.sql

# Search & Replace (cambiar URLs)
wp search-replace 'old-url.com' 'localhost:8080'

# Optimizar
wp db optimize

# Verificar integridad
wp db check
```

## Scripts de Testing

### Setup de Datos de Prueba
```bash
# Crear datos completos de prueba
./testing-tools.sh setup-test-data

# Probar funcionalidad de pedidos
./testing-tools.sh test-orders

# Probar sincronización de albarán
./testing-tools.sh test-packing-slip

# Probar emails personalizados
./testing-tools.sh test-emails
```

### Herramientas de QA
```bash
# Verificar salud del sistema
./testing-tools.sh health-check

# Backup rápido
./testing-tools.sh backup-db

# Limpiar logs y cache
./testing-tools.sh clean-logs
```

## Workflows de Desarrollo

### 1. Testing de Nueva Funcionalidad
```bash
# 1. Backup antes de cambios
wp backup

# 2. Activar debugging
wp debug-on

# 3. Implementar cambios
# ... desarrollo ...

# 4. Probar funcionalidad
./testing-tools.sh test-orders

# 5. Verificar logs
wp palafito:debug:logs

# 6. Commit si todo OK
git add . && git commit -m "feature: nueva funcionalidad"
```

### 2. Debugging de Issues
```bash
# 1. Reproducir el problema
./testing-tools.sh test-packing-slip

# 2. Revisar logs específicos
tail -f wp-content/debug.log | grep -i "packing\|slip\|entrega"

# 3. Verificar hooks activos
wp palafito:debug:hooks

# 4. Verificar estado de BD
wp eval 'var_dump(wc_get_order(123)->get_meta("_wcpdf_packing-slip_date"));'
```

### 3. Performance Testing
```bash
# Instalar Query Monitor
wp plugin install query-monitor --activate

# Verificar queries lentas
wp db query "SHOW PROCESSLIST"

# Profile de rendimiento
wp profile stage --all
```

## Configuración de Herramientas

### MailHog (Testing de Emails)
```bash
# Instalar MailHog
brew install mailhog

# Ejecutar
mailhog

# Web UI: http://localhost:8025
```

### Xdebug (PHP Debugging)
```bash
# Verificar Xdebug
php -m | grep -i xdebug

# Configuración recomendada en php.ini:
# xdebug.mode=debug
# xdebug.start_with_request=yes
# xdebug.client_host=localhost
# xdebug.client_port=9003
```

### Node.js Tools (para assets)
```bash
# Instalar dependencias
npm install

# Watch mode para CSS/JS
npm run watch

# Build para producción
npm run build
```

## Troubleshooting Común

### WordPress no se conecta a BD
```bash
# Verificar configuración
wp config get DB_NAME
wp config get DB_HOST

# Test de conexión
wp db check
```

### Plugin no se activa
```bash
# Verificar errores PHP
tail wp-content/debug.log

# Verificar permisos
ls -la wp-content/plugins/palafito-wc-extensions/
```

### WP-CLI no funciona
```bash
# Verificar instalación
wp --info

# Verificar configuración
cat wp-cli.yml

# Regenerar configuración
wp cli update
```

### Logs no aparecen
```bash
# Verificar permisos
ls -la wp-content/debug.log

# Forzar creación
touch wp-content/debug.log
chmod 666 wp-content/debug.log

# Verificar configuración
wp config get WP_DEBUG
wp config get WP_DEBUG_LOG
```

## URLs Útiles para Desarrollo

- **Sitio Local**: http://localhost:8080
- **Admin**: http://localhost:8080/wp-admin
- **WooCommerce**: http://localhost:8080/wp-admin/admin.php?page=wc-admin
- **Pedidos**: http://localhost:8080/wp-admin/edit.php?post_type=shop_order
- **MailHog**: http://localhost:8025
- **Query Monitor**: http://localhost:8080/?qm=1

## Mejores Prácticas

1. **Siempre hacer backup antes de cambios importantes**
2. **Usar debugging en desarrollo, desactivar en producción**
3. **Probar en datos limpios y con datos reales**
4. **Verificar logs regularmente**
5. **Usar testing automatizado cuando sea posible**
6. **Documentar cambios en CLAUDE.md**