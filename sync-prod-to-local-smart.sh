#!/bin/bash
# Script inteligente para sincronizar PROD a Local

echo "🔄 Sincronización Inteligente PROD → Local"

# 1. Verificar si estamos en local
if [ ! -f "wp-config-docker-clean.php" ]; then
    echo "❌ Error: No se encontró wp-config-docker-clean.php"
    echo "Ejecuta este script desde el directorio del proyecto"
    exit 1
fi

# 2. Configurar entorno local
echo "📝 Configurando entorno local..."
cp wp-config-docker-clean.php wp-config.php

# 3. Pull de cambios
echo "📥 Descargando cambios de PROD..."
git pull origin master

# 4. Detectar archivos de sincronización automáticamente
echo "🔍 Detectando archivos de PROD..."

# Crear directorio si no existe
mkdir -p temp-sync-data

# Buscar archivos SQL de backup
SQL_FILES=$(find . -maxdepth 1 -name "*prod*.sql" -o -name "*backup*.sql" -o -name "*sync*.sql" 2>/dev/null)
if [ ! -z "$SQL_FILES" ]; then
    echo "📊 Encontrados archivos SQL:"
    for file in $SQL_FILES; do
        echo "  - $file"
        cp "$file" temp-sync-data/palafito_prod.sql
        break # Usar el primero encontrado
    done
fi

# Buscar archivos de configuración
CONFIG_FILES=$(find . -maxdepth 1 -name "*config*.txt" -o -name "*plugins*.csv" 2>/dev/null)
if [ ! -z "$CONFIG_FILES" ]; then
    echo "⚙️ Encontrados archivos de configuración:"
    for file in $CONFIG_FILES; do
        echo "  - $file"
        cp "$file" temp-sync-data/
    done
fi

# Verificar si tenemos datos para importar
if [ -f "temp-sync-data/palafito_prod.sql" ]; then
    echo "📊 Importando datos de PROD..."
    
    # Levantar Docker
    echo "🐳 Levantando contenedores..."
    docker-compose -f docker-compose.simple.yml up -d
    
    # Esperar a que MySQL esté listo
    echo "⏳ Esperando a que MySQL esté listo..."
    sleep 10
    
    # Verificar que MySQL esté corriendo
    if ! docker exec palafito_mysql_simple mysqladmin ping -u palafito_user -ppalafito_pass --silent; then
        echo "❌ Error: MySQL no está respondiendo"
        exit 1
    fi
    
    # Importar base de datos
    echo "🗃️ Importando base de datos..."
    docker exec -i palafito_mysql_simple mysql -u palafito_user -ppalafito_pass palafito_dev < temp-sync-data/palafito_prod.sql
    
    if [ $? -eq 0 ]; then
        echo "✅ Base de datos importada correctamente"
    else
        echo "❌ Error al importar la base de datos"
        exit 1
    fi
    
    # Ajustar URLs
    echo "🔗 Ajustando URLs para local..."
    docker exec -i palafito_mysql_simple mysql -u palafito_user -ppalafito_pass palafito_dev -e "UPDATE wp_options SET option_value = 'http://localhost:8080' WHERE option_name = 'home';" 2>/dev/null
    docker exec -i palafito_mysql_simple mysql -u palafito_user -ppalafito_pass palafito_dev -e "UPDATE wp_options SET option_value = 'http://localhost:8080' WHERE option_name = 'siteurl';" 2>/dev/null
    
    # Verificar URLs
    echo "🔍 Verificando configuración..."
    URLS=$(docker exec -i palafito_mysql_simple mysql -u palafito_user -ppalafito_pass palafito_dev -e "SELECT option_value FROM wp_options WHERE option_name IN ('home', 'siteurl');" 2>/dev/null | grep localhost)
    
    if [ ! -z "$URLS" ]; then
        echo "✅ URLs configuradas para localhost"
    else
        echo "⚠️ Advertencia: URLs no se configuraron correctamente"
    fi
    
    # Probar conectividad
    echo "🌐 Probando conectividad..."
    if curl -s http://localhost:8080 > /dev/null; then
        echo "✅ Sitio funcionando en: http://localhost:8080"
        echo "🔧 PhpMyAdmin en: http://localhost:8081"
        echo "📧 MailHog en: http://localhost:8025"
    else
        echo "⚠️ El sitio podría estar iniciándose aún. Espera unos momentos."
    fi
    
    echo ""
    echo "🎉 ¡Sincronización completada!"
    echo "📋 Accesos:"
    echo "   • WordPress: http://localhost:8080"
    echo "   • PhpMyAdmin: http://localhost:8081 (user: palafito_user, pass: palafito_pass)"
    echo "   • MailHog: http://localhost:8025"
    
else
    echo "⚠️ No se encontraron archivos SQL de PROD"
    echo "📝 Asegúrate de haber ejecutado el export desde PROD y hacer push"
    echo ""
    echo "🔍 Archivos buscados:"
    echo "   • *prod*.sql, *backup*.sql, *sync*.sql"
    echo "   • En directorio raíz del proyecto"
fi