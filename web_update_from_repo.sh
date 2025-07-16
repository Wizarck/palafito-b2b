#!/bin/bash

# Script de deployment para IONOS
# Actualiza el sitio de producción desde el repositorio Git

set -e

echo "🚀 Iniciando deployment de Palafito B2B..."

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log() { echo -e "${GREEN}[$(date +'%H:%M:%S')] $1${NC}"; }
warning() { echo -e "${YELLOW}[WARNING] $1${NC}"; }
error() { echo -e "${RED}[ERROR] $1${NC}"; exit 1; }
info() { echo -e "${BLUE}[INFO] $1${NC}"; }

# Verificar que estamos en el directorio correcto
if [ ! -f "wp-config.php" ]; then
    error "No se encontró wp-config.php. ¿Estás en el directorio correcto?"
fi

log "Directorio de trabajo: $(pwd)"

# Verificar estado actual de Git
info "Verificando estado de Git..."
git status --porcelain > /tmp/git_status.tmp

if [ -s /tmp/git_status.tmp ]; then
    warning "Hay cambios locales pendientes:"
    cat /tmp/git_status.tmp

    # Hacer backup de archivos modificados
    BACKUP_DIR="backup-$(date +%Y%m%d-%H%M%S)"
    mkdir -p "$BACKUP_DIR"

    info "Creando backup en $BACKUP_DIR..."
    while IFS= read -r line; do
        file=$(echo "$line" | awk '{print $2}')
        if [ -f "$file" ]; then
            mkdir -p "$BACKUP_DIR/$(dirname "$file")"
            cp "$file" "$BACKUP_DIR/$file"
            log "Backup: $file"
        fi
    done < /tmp/git_status.tmp
fi

# Configurar Git para deployment
log "Configurando Git..."
git config pull.rebase false 2>/dev/null || true
git config advice.detachedHead false 2>/dev/null || true

# Fetch y pull de cambios
log "Obteniendo cambios del repositorio..."
git fetch origin

CURRENT_BRANCH=$(git branch --show-current)
CURRENT_COMMIT=$(git rev-parse HEAD)

log "Rama actual: $CURRENT_BRANCH"
log "Commit actual: $CURRENT_COMMIT"

# Pull de cambios
log "Aplicando cambios..."
if git pull origin master; then
    NEW_COMMIT=$(git rev-parse HEAD)

    if [ "$CURRENT_COMMIT" != "$NEW_COMMIT" ]; then
        log "✅ Deployment exitoso!"
        log "Commit anterior: $CURRENT_COMMIT"
        log "Commit nuevo: $NEW_COMMIT"

        # Mostrar cambios aplicados
        echo ""
        info "Cambios aplicados:"
        git log --oneline --no-merges $CURRENT_COMMIT..$NEW_COMMIT

        # Verificar archivos críticos
        echo ""
        info "Verificando archivos críticos..."

        # Plugin principal
        if [ -f "wp-content/plugins/palafito-wc-extensions/palafito-wc-extensions.php" ]; then
            log "✓ Plugin principal presente"
        else
            warning "⚠ Plugin principal no encontrado"
        fi

        # Tema hijo
        if [ -f "wp-content/themes/palafito-child/functions.php" ]; then
            log "✓ Tema hijo presente"
        else
            warning "⚠ Tema hijo no encontrado"
        fi

        # Configuración
        if [ -f "wp-config.php" ]; then
            if grep -q "db5016482050.hosting-data.io" wp-config.php; then
                log "✓ Configuración de producción verificada"
            else
                warning "⚠ Verificar configuración de base de datos"
            fi
        fi

    else
        info "No hay cambios nuevos para aplicar"
    fi
else
    error "Error al aplicar cambios desde el repositorio"
fi

# Limpiar archivos temporales
rm -f /tmp/git_status.tmp

# Log final
echo ""
log "🎉 Deployment completado exitosamente!"
log "Sitio actualizado: $(date)"
log "Rama: $CURRENT_BRANCH"
log "Commit: $(git rev-parse HEAD)"

echo ""
info "Para verificar el sitio:"
info "🌐 https://palafitofood.com"
info "🔧 https://palafitofood.com/wp-admin"

exit 0
