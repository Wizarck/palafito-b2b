#!/bin/bash

PROJECT_DIR="$HOME/clickandbuilds/Palafito"
LOG_FILE="$PROJECT_DIR/deploy.log"

log() {
  echo "$(date +'%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

log "🚀 Iniciando proceso de deploy..."

if [ -d "$PROJECT_DIR" ]; then
  cd "$PROJECT_DIR" || {
    log "❌ Error: no se pudo acceder al directorio del proyecto"
    exit 1
  }
else
  log "❌ Error: El directorio $PROJECT_DIR no existe."
  exit 1
fi

log "🔁 Intentando hacer git pull (máx 5 intentos)..."

for i in {1..5}; do
  git pull origin master && break
  log "❗ Pull fallido. Reintentando en 10 segundos... ($i/5)"
  sleep 10
done

if [ "$i" -eq 5 ]; then
  log "❌ Git pull falló después de 5 intentos"
  exit 1
fi

log "✅ Pull completado correctamente. Deploy finalizado."