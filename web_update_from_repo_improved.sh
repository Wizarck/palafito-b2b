#!/bin/bash

# ========================================
# Script de Deploy Mejorado - Palafito B2B
# Elimina advertencias y mejora la robustez
# ========================================

set -e  # Salir en caso de error

# Configuración
LOG_PREFIX="$(date '+%Y-%m-%d %H:%M:%S')"
MAX_RETRIES=5
RETRY_DELAY=10
PROJECT_DIR="$HOME/clickandbuilds/Palafito"

# Función de logging
log() {
    echo "$LOG_PREFIX - $1"
}

log_error() {
    echo "$LOG_PREFIX - ❌ ERROR: $1" >&2
}

# Verificar que estamos en el directorio correcto
if [ ! -d "$PROJECT_DIR" ]; then
    log_error "Directorio del proyecto no encontrado: $PROJECT_DIR"
    exit 1
fi

cd "$PROJECT_DIR" || {
    log_error "No se pudo acceder al directorio: $PROJECT_DIR"
    exit 1
}

log "🚀 Iniciando proceso de deploy mejorado..."
log "📂 Directorio de trabajo: $(pwd)"

# Configurar Git para evitar advertencias
log "⚙️ Configurando Git para deploy limpio..."
git config pull.rebase false 2>/dev/null || true
git config advice.detachedHead false 2>/dev/null || true

# Verificar estado del repositorio
log "🔍 Verificando estado del repositorio..."
if ! git status >/dev/null 2>&1; then
    log_error "No es un repositorio Git válido"
    exit 1
fi

# Obtener información antes del pull
CURRENT_COMMIT=$(git rev-parse HEAD 2>/dev/null || echo "unknown")
CURRENT_BRANCH=$(git branch --show-current 2>/dev/null || echo "detached")

log "📊 Estado actual: rama '$CURRENT_BRANCH', commit ${CURRENT_COMMIT:0:8}"

# Intentar pull con reintentos
log "🔁 Intentando git pull (máx $MAX_RETRIES intentos)..."

for attempt in $(seq 1 $MAX_RETRIES); do
    log "🔄 Intento $attempt de $MAX_RETRIES..."

    if git pull --ff-only origin master 2>/dev/null; then
        NEW_COMMIT=$(git rev-parse HEAD 2>/dev/null || echo "unknown")

        if [ "$CURRENT_COMMIT" != "$NEW_COMMIT" ]; then
            log "✅ Pull completado: ${CURRENT_COMMIT:0:8} → ${NEW_COMMIT:0:8}"
        else
            log "✅ Repositorio ya actualizado (sin cambios)"
        fi

        log "🎉 Deploy finalizado exitosamente"
        exit 0
    else
        log "❗ Pull fallido (intento $attempt/$MAX_RETRIES)"

        if [ $attempt -lt $MAX_RETRIES ]; then
            log "⏳ Reintentando en $RETRY_DELAY segundos..."
            sleep $RETRY_DELAY
        fi
    fi
done

# Si llegamos aquí, todos los intentos fallaron
log_error "Git pull falló después de $MAX_RETRIES intentos"

# Información de diagnóstico
log "🔍 Información de diagnóstico:"
log "   - Directorio: $(pwd)"
log "   - Usuario Git: $(git config user.name 2>/dev/null || echo 'no configurado')"
log "   - Repositorio remoto: $(git remote get-url origin 2>/dev/null || echo 'no configurado')"
log "   - Estado working tree: $(git status --porcelain 2>/dev/null | wc -l | tr -d ' ') archivos modificados"

exit 1
