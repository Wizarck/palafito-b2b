#!/bin/bash

# Script para subir diagnóstico a producción
echo "🚀 Subiendo script de diagnóstico avanzado a producción..."

# Verificar que el archivo existe
if [ ! -f "prod-diagnostic-v2.php" ]; then
    echo "❌ Error: prod-diagnostic-v2.php no encontrado"
    exit 1
fi

echo "📁 Archivo encontrado, subiendo..."

# Subir a producción
scp prod-diagnostic-v2.php a1559522@access-5016482035.webspace-host.com:~/clickandbuilds/Palafito/

if [ $? -eq 0 ]; then
    echo "✅ Archivo subido exitosamente"
    echo "🌐 Ahora puedes visitar: https://palafito.com/prod-diagnostic-v2.php"
    echo "⚠️  RECUERDA: Eliminar el archivo después de usarlo por seguridad"
else
    echo "❌ Error al subir el archivo"
    exit 1
fi