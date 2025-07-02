#!/bin/bash

# Script de gestión Docker para Palafito B2B
# Uso: ./docker-dev.sh [comando]

set -e

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

# Verificar Docker
check_docker() {
    if ! command -v docker &> /dev/null; then
        error "Docker no está instalado"
    fi
    
    if ! docker info &> /dev/null; then
        error "Docker no está ejecutándose"
    fi
}

# Mostrar ayuda
show_help() {
    echo "🐳 Gestión Docker para Palafito B2B"
    echo ""
    echo "Uso: ./docker-dev.sh [COMANDO]"
    echo ""
    echo "Comandos disponibles:"
    echo "  up              Iniciar todos los servicios"
    echo "  up simple       Iniciar servicios (versión simplificada)"
    echo "  down            Detener todos los servicios"
    echo "  restart         Reiniciar servicios"
    echo "  build           Construir/reconstruir imágenes"
    echo "  rebuild         Reconstruir desde cero (sin cache)"
    echo "  logs            Ver logs de todos los servicios"
    echo "  logs [service]  Ver logs de un servicio específico"
    echo "  shell           Acceder al shell de WordPress"
    echo "  wp              Ejecutar comandos WP-CLI"
    echo "  mysql           Acceder a MySQL"
    echo "  redis           Acceder a Redis CLI"
    echo "  test            Ejecutar tests"
    echo "  debug           Herramientas de debugging"
    echo "  clean           Limpiar contenedores y volúmenes"
    echo "  status          Ver estado de servicios"
    echo "  backup          Crear backup de base de datos"
    echo "  restore         Restaurar backup"
    echo ""
    echo "URLs de desarrollo:"
    echo "  🌐 WordPress:   http://localhost:8080"
    echo "  ⚙️  Admin:       http://localhost:8080/wp-admin"
    echo "  📧 MailHog:     http://localhost:8025"
    echo "  🗄️  phpMyAdmin:  http://localhost:8081"
    echo ""
}

# Verificar si el proyecto está configurado
check_setup() {
    if [ ! -f "docker-compose.yml" ]; then
        error "docker-compose.yml no encontrado"
    fi
}

# Iniciar servicios
docker_up() {
    log "🚀 Iniciando servicios de Palafito B2B..."
    
    # Usar versión simple si hay problemas de build
    if [ "$1" = "simple" ]; then
        log "📋 Usando configuración simplificada..."
        docker-compose -f docker-compose.simple.yml up -d
    else
        docker-compose up -d --build
    fi
    
    log "⏳ Esperando que los servicios estén listos..."
    sleep 10
    
    # Verificar servicios
    if docker-compose ps | grep -q "Up"; then
        log "✅ Servicios iniciados correctamente"
        info "🌐 WordPress: http://localhost:8080"
        info "⚙️  Admin: http://localhost:8080/wp-admin (admin/admin123)"
        info "📧 MailHog: http://localhost:8025"
        info "🗄️  phpMyAdmin: http://localhost:8081"
    else
        error "❌ Error al iniciar servicios"
    fi
}

# Detener servicios
docker_down() {
    log "🛑 Deteniendo servicios..."
    docker-compose down
    log "✅ Servicios detenidos"
}

# Reiniciar servicios
docker_restart() {
    log "🔄 Reiniciando servicios..."
    docker-compose restart
    log "✅ Servicios reiniciados"
}

# Construir imágenes
docker_build() {
    log "🔨 Construyendo imágenes..."
    docker-compose build
    log "✅ Imágenes construidas"
}

# Reconstruir desde cero
docker_rebuild() {
    log "🔨 Reconstruyendo desde cero..."
    docker-compose build --no-cache
    log "✅ Imágenes reconstruidas"
}

# Ver logs
docker_logs() {
    if [ -n "$1" ]; then
        log "📋 Logs de $1:"
        docker-compose logs -f "$1"
    else
        log "📋 Logs de todos los servicios:"
        docker-compose logs -f
    fi
}

# Acceso a shell
docker_shell() {
    log "🐚 Accediendo al shell de WordPress..."
    docker-compose exec wordpress bash
}

# Ejecutar WP-CLI
docker_wp() {
    if [ $# -eq 0 ]; then
        log "🔧 Accediendo a WP-CLI..."
        docker-compose exec wp-cli bash
    else
        log "🔧 Ejecutando: wp $*"
        docker-compose exec wp-cli wp "$@"
    fi
}

# Acceso a MySQL
docker_mysql() {
    log "🗄️  Accediendo a MySQL..."
    docker-compose exec mysql mysql -u palafito_user -ppalafito_pass palafito_dev
}

# Acceso a Redis
docker_redis() {
    log "📦 Accediendo a Redis CLI..."
    docker-compose exec redis redis-cli
}

# Ejecutar tests
docker_test() {
    log "🧪 Ejecutando tests..."
    docker-compose exec testing run-tests
}

# Herramientas de debugging
docker_debug() {
    log "🔍 Herramientas de debugging disponibles:"
    echo ""
    echo "1. Ver logs de Palafito:"
    echo "   ./docker-dev.sh wp exec wp-cli palafito-debug logs"
    echo ""
    echo "2. Verificar hooks activos:"
    echo "   ./docker-dev.sh wp exec wp-cli palafito-debug hooks"
    echo ""
    echo "3. Analizar pedidos:"
    echo "   ./docker-dev.sh wp exec wp-cli palafito-debug orders"
    echo ""
    echo "4. Test de sincronización:"
    echo "   ./docker-dev.sh wp exec wp-cli palafito-debug test-sync"
    echo ""
    echo "5. Estado del sistema:"
    echo "   ./docker-dev.sh wp exec wp-cli palafito-debug status"
    echo ""
    
    # Ejecutar debug interactivo
    docker-compose exec wp-cli palafito-debug
}

# Limpiar contenedores
docker_clean() {
    warning "⚠️  Esto eliminará todos los contenedores y volúmenes"
    read -p "¿Continuar? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        log "🧹 Limpiando contenedores y volúmenes..."
        docker-compose down -v
        docker system prune -f
        log "✅ Limpieza completada"
    else
        log "❌ Limpieza cancelada"
    fi
}

# Ver estado
docker_status() {
    log "📊 Estado de servicios:"
    docker-compose ps
    echo ""
    
    log "📈 Uso de recursos:"
    docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.NetIO}}\t{{.BlockIO}}"
}

# Backup de base de datos
docker_backup() {
    log "💾 Creando backup de base de datos..."
    
    mkdir -p backups
    BACKUP_FILE="backups/palafito-backup-$(date +%Y%m%d-%H%M%S).sql"
    
    docker-compose exec mysql mysqldump -u palafito_user -ppalafito_pass palafito_dev > "$BACKUP_FILE"
    
    if [ -f "$BACKUP_FILE" ]; then
        log "✅ Backup creado: $BACKUP_FILE"
    else
        error "❌ Error al crear backup"
    fi
}

# Restaurar backup
docker_restore() {
    if [ -z "$1" ]; then
        error "Uso: ./docker-dev.sh restore [archivo_backup.sql]"
    fi
    
    if [ ! -f "$1" ]; then
        error "Archivo de backup no encontrado: $1"
    fi
    
    warning "⚠️  Esto sobrescribirá la base de datos actual"
    read -p "¿Continuar? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        log "📥 Restaurando backup: $1"
        docker-compose exec -T mysql mysql -u palafito_user -ppalafito_pass palafito_dev < "$1"
        log "✅ Backup restaurado"
    else
        log "❌ Restauración cancelada"
    fi
}

# Main script
check_docker
check_setup

case "${1:-help}" in
    "up")
        docker_up "$2"
        ;;
    "down")
        docker_down
        ;;
    "restart")
        docker_restart
        ;;
    "build")
        docker_build
        ;;
    "rebuild")
        docker_rebuild
        ;;
    "logs")
        docker_logs "$2"
        ;;
    "shell")
        docker_shell
        ;;
    "wp")
        shift
        docker_wp "$@"
        ;;
    "mysql")
        docker_mysql
        ;;
    "redis")
        docker_redis
        ;;
    "test")
        docker_test
        ;;
    "debug")
        docker_debug
        ;;
    "clean")
        docker_clean
        ;;
    "status")
        docker_status
        ;;
    "backup")
        docker_backup
        ;;
    "restore")
        docker_restore "$2"
        ;;
    "help"|*)
        show_help
        ;;
esac