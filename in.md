## Problema: Gestión de fecha de entrega en albaranes - Palafito B2B

### Contexto del proyecto
- WordPress/WooCommerce B2B con plugin custom `palafito-wc-extensions`
- Plugin PDF PRO `woocommerce-pdf-ips-pro` (white label)
- Meta field `_wcpdf_packing-slip_date` como fuente única de verdad
- Estado personalizado "entregado" implementado

### Funcionalidad actual (funcionando)
✅ Al editar fecha en metabox → albarán muestra fecha correcta
✅ Al cambiar a "entregado" con campo vacío → se rellena automáticamente

### Problemas identificados
❌ **Problema 1**: Al cambiar estado a "entregado" por segunda vez, NO sobreescribe la fecha existente
❌ **Problema 2**: La columna "Fecha de entrega" en listado de pedidos NO se actualiza nunca

### Análisis requerido
1. **Revisar lógica de cambio de estado** en `handle_custom_order_status_change()`
2. **Verificar hooks de actualización** de columnas personalizadas
3. **Comprobar sincronización** entre metabox y listado
4. **Identificar conflictos** entre plugin custom y plugin PDF PRO

### Solución esperada
- **Sobreescritura forzada**: Cada vez que pase a "entregado" debe actualizar `_wcpdf_packing-slip_date`
- **Actualización de columna**: La columna debe reflejar cambios inmediatamente
- **Formato consistente**: d-m-Y en todos los puntos (metabox, columna, PDF)

### Tareas específicas
1. **Debuggear** la función `handle_custom_order_status_change()` 
2. **Verificar** hooks de `custom_order_columns_data()` para columna "entregado_date"
3. **Implementar** actualización forzada de meta field
4. **Probar** cambios en entorno local
5. **Validar** con PHPCS antes de push
6. **Documentar** cambios en CONTEXT.md

### Criterios de éxito
- ✅ Fecha se sobreescribe cada vez que pasa a "entregado"
- ✅ Columna se actualiza inmediatamente tras cambios
- ✅ Formato d-m-Y consistente en todas las vistas
- ✅ Código cumple PHPCS
- ✅ Funcionalidad existente no se rompe

### Entregables
- Código corregido y probado
- Documentación actualizada
- Push a producción con configuración PROD verificada
