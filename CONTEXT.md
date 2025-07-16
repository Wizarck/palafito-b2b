# CONTEXT.md - Palafito B2B

**Última actualización: 16 Julio 2025**

## 🎯 ESTADO ACTUAL DEL PROYECTO

### ✅ SISTEMA COMPLETAMENTE FUNCIONAL Y ESTABLE

El proyecto Palafito B2B está en **ESTADO PRODUCTION-READY** con todas las funcionalidades críticas implementadas y operativas:

- ✅ **Sistema de fechas de entrega**: 100% resuelto y sincronizado
- ✅ **Sistema de fecha de factura**: Implementado con auto-generación automática
- ✅ **Estados de pedido custom**: Entregado y facturado operativos
- ✅ **Templates PDF**: Completamente optimizados y unificados
- ✅ **Plugin palafito-wc-extensions**: Robusto y estable
- ✅ **GitHub Actions**: Pipeline de deploy automático funcional
- ✅ **PHPCS compliance**: Código cumple estándares WordPress/WooCommerce
- ✅ **Servidor IONOS**: Deploy automático operativo

---

## 🗂️ ARQUITECTURA DEL SISTEMA

### Plugin Principal: `palafito-wc-extensions`

**Ubicación**: `wp-content/plugins/palafito-wc-extensions/`

**Componentes principales**:
- `class-palafito-wc-extensions.php` - Plugin principal
- `class-palafito-order-admin.php` - Gestión admin de pedidos
- `class-palafito-delivery-date.php` - Sistema fechas entrega
- `class-palafito-invoice-date.php` - Sistema fechas factura
- `class-palafito-order-status.php` - Estados de pedido custom

### Templates PDF Personalizados

**Ubicación**: `wp-content/themes/kadence/woocommerce/pdf/mio/`

**Archivos**:
- `invoice.php` - Template factura optimizado
- `packing-slip.php` - Template albarán optimizado
- `template-functions.php` - Funciones personalizadas
- `style.css` - Estilos PDF
- `html-document-wrapper.php` - Wrapper HTML

---

## 📅 SISTEMA DE FECHAS

### Sistema de Fecha de Entrega

**Estado**: ✅ **COMPLETAMENTE RESUELTO**

**Implementación Triple**:
1. **WooCommerce Meta**: `_delivery_date`
2. **Direct Database**: Operaciones directas para consistencia
3. **PDF Document Sync**: Sincronización con documentos PDF

**Características**:
- Auto-generación en cambio a estado "entregado"
- Persistencia garantizada en múltiples ubicaciones
- Formato d-m-Y consistente
- Debugging logs completos

### Sistema de Fecha de Factura

**Estado**: ✅ **IMPLEMENTADO Y OPERATIVO**

**Implementación Triple** (igual que entrega):
1. **WooCommerce Meta**: `_wcpdf_invoice_date`
2. **Direct Database**: Consistencia garantizada
3. **PDF Document Sync**: Sincronización completa

**Características**:
- Auto-generación en cambio a "facturado" o "completed"
- Enhanced Logic en columna admin con PDF document priority
- Funciona con sistema invoice date existente del plugin PDF
- Formato timestamp coherente con plugin original

---

## 📄 TEMPLATES PDF OPTIMIZADOS

### Estado Actual: ✅ **COMPLETAMENTE MEJORADOS**

**Templates personalizados ubicados en**: `wp-content/themes/kadence/woocommerce/pdf/mio/`

### Mejoras Implementadas

#### 📄 FACTURA (`invoice.php`)
- **✅ Estructura billing unificada**: Título "Dirección de facturación"
- **✅ Sin dirección de envío**: Sección shipping address eliminada
- **✅ Título sección productos**: "Detalles de factura:"
- **✅ Order data simplificado**: Solo número factura, fecha factura, método pago
- **✅ Eliminado due date**: Campo no usado removido
- **✅ Eliminado order date**: Campo redundante removido

#### 📋 ALBARÁN (`packing-slip.php`)
- **✅ Estructura billing**: Título "Dirección de facturación"
- **✅ Título sección productos**: "Detalles de albarán:"
- **✅ Mantiene shipping address**: Conserva dirección de envío

### Estructura Billing Unificada
1. **📍 Título**: "Dirección de facturación" / "Dirección de envío"
2. **👤 Nombre**: Display name del usuario
3. **📄 NIF**: Campo personalizado `_billing_rfc`
4. **🏠 Dirección**: Completa con CP y ciudad
5. **📞 Teléfono**: Si disponible
6. **📧 Email**: Si habilitado en configuración

---

## 🚀 GITHUB ACTIONS & DEPLOY

### Pipeline Automático

**Estado**: ✅ **COMPLETAMENTE OPERATIVO**

**Flujo**:
1. Push a master → Trigger GitHub Actions
2. Validaciones automáticas (PHPCS, tests)
3. Deploy a servidor IONOS via SSH
4. Ejecución de `web_update_from_repo.sh`
5. Verificaciones post-deploy

**Script deploy**: `web_update_from_repo.sh` **FUNCIONAL** en IONOS

### Comandos Pre-Push OBLIGATORIOS

```bash
composer install
composer run fix    # Auto-fix PHPCS
composer run lint   # Verificar estándares
git add .
git commit -m "mensaje"
git push  # Activa pipeline automático
```

---

## 🎛️ ESTADOS DE PEDIDO CUSTOM

### Estados Implementados

1. **wc-entregado** - "Entregado"
   - Color: Verde (#2ea44f)
   - Auto-genera fecha de entrega
   - Visible en admin y frontend

2. **wc-facturado** - "Facturado"
   - Color: Azul (#0969da)
   - Auto-genera fecha de factura
   - Integrado con sistema PDF

### Gestión Automática

- **Hook principal**: `woocommerce_order_status_changed`
- **Función**: `handle_custom_order_status_change()`
- **Logging**: Debug completo de cambios de estado

---

## 🏛️ COLUMNAS ADMIN PERSONALIZADAS

### Columnas Implementadas

1. **delivery_date** - "Fecha Entrega"
   - Enhanced Logic con múltiples fallbacks
   - Formato d-m-Y user-friendly
   - Prioridad: DB directo → WC meta → Post meta

2. **invoice_date** - "Fecha Factura"
   - Enhanced Logic con PDF document priority
   - Formato d-m-Y consistente
   - Prioridad: PDF document → WC meta → Fallbacks

### Características
- Sortable y ordenables
- Performance optimizada
- Fallbacks robustos
- Debugging integrado

---

## 🔧 CONFIGURACIÓN CRÍTICA

### Variables Entorno
- **PROD=true**: Configuración de producción activa
- **PHPCS Standards**: WordPress/WooCommerce compliance
- **Timezone**: Europe/Madrid

### Campos Meta Críticos
- `_delivery_date`: Fecha de entrega (timestamp)
- `_wcpdf_invoice_date`: Fecha de factura (timestamp)
- `_billing_rfc`: NIF del cliente
- `_order_status_history`: Historial de estados

---

## 📊 MÉTRICAS DE CALIDAD

### Code Standards
- **PHPCS**: ✅ 100% WordPress/WooCommerce compliant
- **Funciones documentadas**: ✅ PHPDoc completo
- **Error handling**: ✅ Robusto
- **Logging**: ✅ Debug comprehensivo

### Testing
- **Funcionalidad**: ✅ Probado en producción
- **Performance**: ✅ Optimizado
- **Compatibilidad**: ✅ WooCommerce + WordPress actual

---

## 🚨 NORMAS CRÍTICAS DE DESARROLLO

### ⚠️ NUNCA HACER:
- **NUNCA** subir archivos directos con SCP a producción
- **NUNCA** hacer push sin linting previo (`composer run fix`)
- **NUNCA** modificar directamente en servidor
- **NUNCA** usar PowerShell en Mac (usar bash)

### ✅ SIEMPRE HACER:
- **SIEMPRE** usar flujo GitHub Actions
- **SIEMPRE** ejecutar `composer run fix` antes de commit
- **SIEMPRE** probar cambios localmente
- **SIEMPRE** usar bash para comandos terminal

---

## 📋 TAREAS DE MANTENIMIENTO

### Regulares (Mensual)
- Verificar logs de errores
- Actualizar dependencias seguras
- Backup de configuraciones
- Review de performance

### Críticas (Inmediatas)
- Monitoring de GitHub Actions
- Verificación de estados de pedido
- Consistencia de fechas
- Funcionalidad PDF templates

---

## 🔍 DEBUGGING & LOGS

### Ubicaciones de Logs
- **WordPress**: `wp-content/debug.log`
- **Plugin**: Integrado en WordPress debug
- **GitHub Actions**: Logs automáticos en repositorio

### Debug Functions
- `palafito_log()`: Logging personalizado del plugin
- `error_log()`: Logs de PHP estándar
- Debug flags en funciones críticas

---

## 📞 SOPORTE & CONTACTO

### Información del Sistema
- **Servidor**: IONOS
- **Dominio**: [Palafito B2B]
- **Repository**: GitHub privado
- **WordPress**: Última versión estable
- **WooCommerce**: Última versión compatible

### Documentación Relacionada
- `CLAUDE.md`: Información técnica detallada
- `README.md`: Arquitectura y setup
- `composer.json`: Dependencias y scripts

---

**🎯 ESTADO FINAL: PROYECTO PRODUCTION-READY Y COMPLETAMENTE FUNCIONAL**

*Última verificación: 16 Julio 2025 - Todos los sistemas operativos*
