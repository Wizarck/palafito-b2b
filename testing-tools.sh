#!/bin/bash

# Herramientas de Testing y QA para Palafito B2B
# Autor: Palafito Development Team

set -e

# Colores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

log() { echo -e "${GREEN}[$(date +'%H:%M:%S')] $1${NC}"; }
warning() { echo -e "${YELLOW}[WARNING] $1${NC}"; }
error() { echo -e "${RED}[ERROR] $1${NC}"; }
info() { echo -e "${BLUE}[INFO] $1${NC}"; }

# Función para mostrar ayuda
show_help() {
    echo "Herramientas de Testing y QA para Palafito B2B"
    echo ""
    echo "Uso: ./testing-tools.sh [COMANDO]"
    echo ""
    echo "Comandos disponibles:"
    echo "  setup-test-data     Crear datos de prueba para testing"
    echo "  test-orders         Probar funcionalidad de pedidos"
    echo "  test-packing-slip   Probar sincronización de fecha de albarán"
    echo "  test-emails         Probar emails personalizados"
    echo "  test-statuses       Probar estados de pedidos personalizados"
    echo "  performance-test    Ejecutar tests de rendimiento"
    echo "  security-scan       Ejecutar escaneo de seguridad"
    echo "  debug-mode          Activar/desactivar modo debug"
    echo "  clean-logs          Limpiar logs de debug"
    echo "  health-check        Verificar salud del sistema"
    echo "  backup-db           Crear backup de base de datos"
    echo "  restore-db          Restaurar backup de base de datos"
    echo ""
}

# Función para crear datos de prueba
setup_test_data() {
    log "Creando datos de prueba..."
    
    # Crear productos de prueba
    log "Creando productos..."
    for i in {1..10}; do
        wp wc product create \
            --name="Producto de Prueba $i" \
            --type=simple \
            --regular_price=$(( $RANDOM % 100 + 10 )) \
            --description="Descripción del producto $i para testing" \
            --short_description="Producto $i" \
            --sku="TEST-PROD-$i" \
            --manage_stock=true \
            --stock_quantity=$(( $RANDOM % 50 + 10 )) \
            --status=publish
    done
    
    # Crear usuarios de prueba
    log "Creando usuarios de prueba..."
    for i in {1..5}; do
        wp user create "cliente$i" "cliente$i@palafito.local" \
            --role=customer \
            --first_name="Cliente" \
            --last_name="$i" \
            --send-email=false 2>/dev/null || true
    done
    
    # Crear pedidos de prueba
    log "Creando pedidos de prueba..."
    for i in {1..5}; do
        ORDER_ID=$(wp wc order create \
            --customer_id=$(wp user list --role=customer --format=ids | head -n1) \
            --status=processing \
            --porcelain)
        
        # Agregar productos al pedido
        PRODUCT_ID=$(wp wc product list --format=ids | head -n1)
        wp wc order_item create $ORDER_ID \
            --type=line_item \
            --product_id=$PRODUCT_ID \
            --quantity=1
        
        log "Pedido #$ORDER_ID creado"
    done
    
    log "✅ Datos de prueba creados correctamente"
}

# Función para probar pedidos
test_orders() {
    log "Probando funcionalidad de pedidos..."
    
    # Verificar estados personalizados
    info "Estados de pedidos disponibles:"
    wp eval '
    foreach(wc_get_order_statuses() as $key => $label) {
        echo "- $key: $label\n";
    }'
    
    # Crear pedido de prueba y cambiar estados
    CUSTOMER_ID=$(wp user list --role=customer --format=ids | head -n1)
    if [ -n "$CUSTOMER_ID" ]; then
        ORDER_ID=$(wp wc order create \
            --customer_id=$CUSTOMER_ID \
            --status=processing \
            --porcelain)
        
        log "Pedido #$ORDER_ID creado. Probando cambios de estado..."
        
        # Cambiar a entregado
        wp wc order update $ORDER_ID --status=entregado
        log "Estado cambiado a: entregado"
        
        # Cambiar a facturado
        wp wc order update $ORDER_ID --status=facturado
        log "Estado cambiado a: facturado"
        
        # Verificar metadatos
        wp eval "
        \$order = wc_get_order($ORDER_ID);
        \$entrega_date = \$order->get_meta('_wcpdf_packing-slip_date');
        echo 'Fecha de entrega: ' . (\$entrega_date ? date('d/m/Y H:i', \$entrega_date) : 'No establecida') . \"\n\";
        "
    fi
    
    log "✅ Test de pedidos completado"
}

# Función para probar sincronización de fecha de albarán
test_packing_slip() {
    log "Probando sincronización de fecha de albarán..."
    
    # Activar logging temporal
    wp config set WP_DEBUG true --type=constant
    wp config set WP_DEBUG_LOG true --type=constant
    
    # Crear pedido y simular guardado con fecha
    CUSTOMER_ID=$(wp user list --role=customer --format=ids | head -n1)
    ORDER_ID=$(wp wc order create \
        --customer_id=$CUSTOMER_ID \
        --status=processing \
        --porcelain)
    
    log "Pedido #$ORDER_ID creado para test de albarán"
    
    # Simular el guardado con fecha de albarán
    wp eval "
    \$order = wc_get_order($ORDER_ID);
    \$timestamp = time();
    \$order->update_meta_data('_wcpdf_packing-slip_date', \$timestamp);
    \$order->save_meta_data();
    echo 'Fecha de albarán simulada: ' . date('d/m/Y H:i', \$timestamp) . \"\n\";
    
    // Verificar en columna de tabla
    \$saved_date = \$order->get_meta('_wcpdf_packing-slip_date');
    echo 'Fecha guardada: ' . (\$saved_date ? date('d/m/Y H:i', \$saved_date) : 'ERROR: No guardada') . \"\n\";
    "
    
    # Mostrar logs recientes
    if [ -f "wp-content/debug.log" ]; then
        info "Últimas entradas del log:"
        tail -10 wp-content/debug.log | grep -i palafito || info "No hay logs de Palafito recientes"
    fi
    
    log "✅ Test de albarán completado - Revisar orden #$ORDER_ID en admin"
}

# Función para probar emails
test_emails() {
    log "Probando emails personalizados..."
    
    # Verificar configuración de emails
    wp eval '
    $emails = WC()->mailer()->get_emails();
    foreach($emails as $email) {
        if(strpos($email->id, "entregado") !== false || strpos($email->id, "facturado") !== false) {
            echo "Email encontrado: " . $email->id . " - " . $email->title . "\n";
        }
    }
    '
    
    log "✅ Test de emails completado"
}

# Función para backup
backup_db() {
    log "Creando backup de base de datos..."
    
    mkdir -p wp-content/backups
    BACKUP_FILE="wp-content/backups/backup-$(date +%Y%m%d-%H%M%S).sql"
    
    wp db export $BACKUP_FILE
    log "✅ Backup creado: $BACKUP_FILE"
}

# Función para verificar salud
health_check() {
    log "Verificando salud del sistema..."
    
    # Verificar WordPress
    wp core verify-checksums || warning "Checksums de WordPress fallidos"
    
    # Verificar plugins
    wp plugin verify-checksums --all || warning "Checksums de plugins fallidos"
    
    # Verificar base de datos
    wp db check || warning "Verificación de BD fallida"
    
    # Verificar permisos
    info "Verificando permisos de archivos..."
    find wp-content -type f -not -perm 644 -ls | head -5
    
    # Verificar logs de errores
    if [ -f "wp-content/debug.log" ]; then
        ERROR_COUNT=$(grep -c "ERROR\|FATAL" wp-content/debug.log 2>/dev/null || echo "0")
        if [ "$ERROR_COUNT" -gt 0 ]; then
            warning "Se encontraron $ERROR_COUNT errores en debug.log"
        fi
    fi
    
    log "✅ Verificación de salud completada"
}

# Función para limpiar logs
clean_logs() {
    log "Limpiando logs..."
    
    # Limpiar debug.log
    > wp-content/debug.log
    
    # Limpiar cache
    wp cache flush
    
    log "✅ Logs limpiados"
}

# Main script
case "${1:-help}" in
    "setup-test-data")
        setup_test_data
        ;;
    "test-orders")
        test_orders
        ;;
    "test-packing-slip")
        test_packing_slip
        ;;
    "test-emails")
        test_emails
        ;;
    "backup-db")
        backup_db
        ;;
    "health-check")
        health_check
        ;;
    "clean-logs")
        clean_logs
        ;;
    "help"|*)
        show_help
        ;;
esac