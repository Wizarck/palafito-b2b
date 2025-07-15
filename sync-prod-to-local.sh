#!/bin/bash
# Script para sincronizar PROD a Local vía Git

echo "🔄 Sincronizando PROD → Local"

# 1. Verificar si estamos en local
if [ ! -f "wp-config-docker-clean.php" ]; then
    echo "❌ Error: No se encontró wp-config-docker-clean.php"
    echo "Ejecuta este script desde el directorio del proyecto"
    exit 1
fi

# 2. Cambiar a configuración local
echo "📝 Configurando entorno local..."
cp wp-config-docker-clean.php wp-config.php

# 3. Pull de cambios
echo "📥 Descargando cambios de PROD..."
git pull origin master

# 4. Verificar si hay datos de sincronización
if [ -d "temp-sync-data" ]; then
    echo "📊 Importando datos de PROD..."
    
    # Levantar Docker
    docker-compose -f docker-compose.simple.yml up -d
    
    # Esperar a que MySQL esté listo
    echo "⏳ Esperando a que MySQL esté listo..."
    sleep 10
    
    # Importar base de datos
    if [ -f "temp-sync-data/palafito_prod.sql" ]; then
        echo "🗃️ Importando base de datos..."
        docker exec -i palafito_db mysql -u palafito_user -ppalafito_pass palafito_dev < temp-sync-data/palafito_prod.sql
    fi
    
    # Extraer uploads
    if [ -f "temp-sync-data/uploads_products.tar.gz" ]; then
        echo "📁 Extrayendo archivos..."
        tar -xzf temp-sync-data/uploads_products.tar.gz
    fi
    
    # Ajustar URLs
    echo "🔗 Ajustando URLs para local..."
    docker exec -i palafito_mysql_simple mysql -u palafito_user -ppalafito_pass palafito_dev -e "UPDATE wp_options SET option_value = 'http://localhost:8080' WHERE option_name = 'home';"
    docker exec -i palafito_mysql_simple mysql -u palafito_user -ppalafito_pass palafito_dev -e "UPDATE wp_options SET option_value = 'http://localhost:8080' WHERE option_name = 'siteurl';"
    
    echo "✅ Sincronización completada!"
    echo "🌐 Accede a: http://localhost:8080"
else
    echo "⚠️  No se encontraron datos de sincronización"
    echo "Ejecuta el script desde PROD primero"
fi