#!/bin/bash

# ========================================
# Configuración de Git para Deploy Limpio
# Elimina advertencias permanentemente
# ========================================

PROJECT_DIR="$HOME/clickandbuilds/Palafito"

echo "🔧 CONFIGURANDO GIT PARA DEPLOYS LIMPIOS"
echo "========================================"

# Verificar directorio
if [ ! -d "$PROJECT_DIR" ]; then
    echo "❌ ERROR: Directorio no encontrado: $PROJECT_DIR"
    exit 1
fi

cd "$PROJECT_DIR" || {
    echo "❌ ERROR: No se pudo acceder al directorio"
    exit 1
}

echo "📂 Configurando en: $(pwd)"

# Configuraciones para eliminar advertencias
echo "⚙️ Aplicando configuraciones de Git..."

# 1. Estrategia de pull (elimina hint de pull strategy)
git config pull.rebase false
echo "   ✅ pull.rebase = false (merge strategy)"

# 2. Desactivar advertencias de detached HEAD
git config advice.detachedHead false
echo "   ✅ advice.detachedHead = false"

# 3. Configuración de push default (buena práctica)
git config push.default simple
echo "   ✅ push.default = simple"

# 4. Configuración de merge (para evitar advertencias en merges)
git config merge.ours.driver true
echo "   ✅ merge.ours.driver = true"

# 5. Configuración de core (mejoras generales)
git config core.autocrlf false
echo "   ✅ core.autocrlf = false (Unix line endings)"

git config core.filemode false
echo "   ✅ core.filemode = false (ignore file permissions)"

# Verificar configuraciones aplicadas
echo ""
echo "📋 CONFIGURACIONES APLICADAS:"
echo "----------------------------------------"
git config --list --local | grep -E "(pull|advice|push|merge|core)" | sort

echo ""
echo "✅ Configuración completada exitosamente!"
echo "🎯 Las advertencias de Git deberían eliminarse en futuros deploys."

# Test rápido
echo ""
echo "🧪 Test rápido de configuración..."
if git status >/dev/null 2>&1; then
    echo "   ✅ Git status OK"
else
    echo "   ❌ Problema con Git status"
fi

if git remote get-url origin >/dev/null 2>&1; then
    echo "   ✅ Repositorio remoto OK"
else
    echo "   ❌ Problema con repositorio remoto"
fi

echo ""
echo "🎉 ¡Configuración lista para deploys limpios!"
