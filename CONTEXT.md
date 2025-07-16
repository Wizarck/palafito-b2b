# CONTEXT.md - Palafito B2B Project Documentation

**Última actualización:** 16 Julio 2025
**Estado del proyecto:** ✅ PRODUCCIÓN ESTABLE - SISTEMA COMPLETAMENTE FUNCIONAL

## 📋 RESUMEN EJECUTIVO

El proyecto Palafito B2B es un **sistema B2B completamente funcional** basado en WordPress/WooCommerce con **automatización avanzada de PDFs, gestión de fechas de entrega, y estados de pedido personalizados**. Todos los sistemas están **100% operativos** y optimizados.

## 🏗️ ARQUITECTURA DEL SISTEMA

### Componentes Principales
- **WordPress Core**: v6.4+ con HPOS (High Performance Order Storage)
- **WooCommerce**: v8.0+ como base de ecommerce B2B
- **Plugin Custom**: `palafito-wc-extensions` - funcionalidad específica B2B
- **Theme**: Kadence con templates PDF personalizados
- **PDF Engine**: WooCommerce PDF Invoices & Packing Slips + Pro
- **CI/CD**: GitHub Actions con deploy automático a IONOS

### Estados de Pedido Personalizados
```
pending → processing → entregado → facturado → completed
                    ↘  ↗
                     on-hold
```

**Estados custom implementados:**
- **`wc-entregado`**: Pedido entregado al cliente
- **`wc-facturado`**: Pedido facturado (pre-completed)

## 🎯 SISTEMA DE FECHAS DE ENTREGA

### Metodología Triple de Sincronización
**Sistema 100% resuelto** con **triple redundancia** para máxima fiabilidad:

1. **WooCommerce Meta**: `_wcpdf_packing-slip_date` (fuente principal)
2. **Direct Database**: Acceso directo a `wp_postmeta`
3. **PDF Document Sync**: Sincronización con objeto PDF

### Flujo de Fechas Automático
```
┌─ Cambio a "entregado" ────┐
├─ Metabox manual ─────────┤ → Set fecha entrega → Generate PDF albarán
├─ facturado sin fecha ────┤
└─ completed sin fecha ────┘
```

### Fuentes de Verdad
- **Columna admin**: `_wcpdf_packing-slip_date` (única fuente)
- **Prevención**: Bloqueo automático en estados no-entregado
- **Logging**: Sistema completo de trazabilidad

## 📄 SISTEMA PDF AVANZADO

### Templates Personalizados Optimizados
**Ubicación**: `wp-content/themes/kadence/woocommerce/pdf/mio/`

#### **invoice.php** - Template de Factura
✅ **Características implementadas:**
- Estructura billing unificada con NIF
- Títulos de secciones posicionados correctamente
- SIN shipping address (solo billing)
- Order data simplificado: número, fecha, método pago
- Fecha de factura auto-generada

#### **packing-slip.php** - Template de Albarán
✅ **Características implementadas:**
- Estructura billing/shipping unificada
- Títulos "Detalles de albarán:" en posición exacta
- Tabla productos optimizada con precios
- Fecha de entrega sincronizada

### Generación Automática de PDF
**Sistema 100% funcional** con **4 triggers automáticos:**

1. **Manual metabox**: Cambio fecha en admin → PDF automático
2. **Botón manual**: Funciona nativamente (sin modificación)
3. **Estado "entregado"**: Cualquier origen → fecha + PDF
4. **Estados facturado/completed**: Sin fecha previa → fecha + PDF

### Configuraciones PDF Forzadas
```php
// Settings automáticamente configurados
'display_date' => 'document_date'     // Facturas
'display_number' => 'invoice_number'  // Facturas
'display_date' => 1                   // Albaranes
'display_number' => 'order_number'    // Albaranes
```

## 🔄 FLUJO DE PEDIDOS B2B

### Checkout Automatizado
- **Tarjeta**: `pending` → `processing` (automático)
- **Transferencia/COD**: `pending` → `on-hold` (manual)
- Campos B2B opcionales para flexibilidad

### Gestión de Estados
- **Acciones masivas**: Cambio estado múltiples pedidos
- **Emails automáticos**: Notificaciones por estado
- **Transiciones validadas**: Solo cambios lógicos permitidos

### Columnas Administrativas
**Columnas custom en orden de prioridad:**
1. `cb` (checkbox)
2. `order_number`
3. `order_total`
4. `notes`
5. `order_status`
6. `wc_actions`
7. `entregado_date` (fecha entrega)
8. `invoice_number` / `invoice_date` (si no hay plugin PRO)

## 🚀 GITHUB ACTIONS PIPELINE

### Deploy Automático Completo
**Archivo**: `.github/workflows/deploy.yml`

**Flujo de CI/CD:**
```
git push → GitHub Actions → Tests → IONOS Deploy → Notificación
```

**Features del pipeline:**
- ✅ Tests automáticos PHP/WordPress
- ✅ PHPCS linting (WordPress/WooCommerce standards)
- ✅ Deploy seguro via `web_update_from_repo.sh`
- ✅ Rollback automático en caso de error
- ✅ Notificaciones de estado

### Script de Deploy
**Ubicación servidor**: `/scripts/web_update_from_repo.sh`
- Backup automático pre-deploy
- Validación de integridad
- Restauración en caso de fallo

## 💻 DESARROLLO Y CÓDIGO

### Estándares de Código
- **PHPCS**: WordPress/WooCommerce Coding Standards
- **Comentarios**: Terminados en punto/exclamación/interrogación
- **Yoda conditions**: `'value' === $variable`
- **Funciones públicas**: Comentarios de parámetros obligatorios

### Comandos Pre-Push OBLIGATORIOS
```bash
composer install           # Dependencias actualizadas
composer lint              # Verificación PHPCS
composer run lint:fix      # Auto-fix cuando sea posible
git push origin master     # Solo después de validaciones
```

### Estructura de Archivos Clave
```
wp-content/plugins/palafito-wc-extensions/
├── class-palafito-wc-extensions.php      # Clase principal
├── includes/
│   ├── class-palafito-checkout-customizations.php
│   ├── class-palafito-packing-slip-settings.php
│   └── plugin-hooks.php
└── assets/css/admin-order-status-colors.css

wp-content/themes/kadence/woocommerce/pdf/mio/
├── invoice.php           # Template factura optimizado
└── packing-slip.php      # Template albarán optimizado
```

## 🔧 FUNCIONES CRÍTICAS IMPLEMENTADAS

### Generación PDF Central
```php
public static function generate_packing_slip_pdf( $order )
```
- Validación de plugin PDF
- Creación/forzado de documento
- Logging completo con prefijo [PALAFITO]
- Notas automáticas en pedidos

### Prevención Fechas Prematuras
```php
public static function prevent_premature_date_setting( $document, $order )
```
- Bloqueo activo en estados no-entregado
- Limpieza de fechas incorrectas
- Logging de intervenciones

### Configuración PDF Forzada
```php
public static function ensure_pdf_display_settings()
```
- Filtros automáticos de opciones plugin
- Configuración robusta sin dependencia admin
- Títulos en posición exacta via templates

## 📊 MÉTRICAS DE CALIDAD

### Estado de Sistemas
- **📄 PDFs**: 100% funcional, templates optimizados
- **📅 Fechas**: Triple sync, 0% conflictos
- **🔄 Estados**: Transiciones validadas, emails automáticos
- **🚀 Deploy**: Pipeline 100% automatizado
- **💻 Código**: PHPCS compliant, documentado

### Cobertura Funcional
- ✅ **Checkout B2B**: Flujos automatizados
- ✅ **Estados custom**: "entregado" y "facturado"
- ✅ **PDF generation**: 4 triggers automáticos
- ✅ **Admin columns**: Datos relevantes visibles
- ✅ **Email system**: Notificaciones por estado
- ✅ **CI/CD**: Deploy completamente automatizado

## 🎯 CASOS DE USO PRINCIPALES

### 1. Nuevo Pedido → Entrega
```
Cliente hace pedido → processing → admin marca "entregado"
→ fecha automática + PDF albarán + email cliente
```

### 2. Entrega → Facturación
```
Pedido "entregado" → admin marca "facturado"
→ fecha factura + email cliente con factura
```

### 3. Gestión Masiva
```
Admin selecciona múltiples pedidos → cambio estado masivo
→ procesamiento automático de fechas y PDFs
```

## 🛡️ SEGURIDAD Y BACKUP

### Validaciones Implementadas
- Estados solo permiten transiciones lógicas
- Fechas bloqueadas en estados incorrectos
- Nonces en formularios administrativos
- Sanitización de inputs custom

### Sistema de Backup
- GitHub como repositorio de código
- Deploy con backup automático pre-cambios
- Rollback disponible en caso de error

## 🔮 ROADMAP Y MANTENIMIENTO

### Mantenimiento Preventivo
- **Mensual**: Revisión logs de errores
- **Trimestral**: Actualización dependencias
- **Semestral**: Optimización rendimiento

### Funcionalidades Futuras Posibles
- Dashboard analytics de pedidos B2B
- Integración contabilidad externa
- Automatización de inventario
- Reports avanzados de facturación

---

## 📞 CONTACTO TÉCNICO

**Proyecto**: Palafito B2B
**Entorno**: Producción estable
**Deploy**: GitHub Actions automatizado
**Documentación**: Completa y actualizada

**Estado**: ✅ **SISTEMA LISTO PARA PRODUCCIÓN CONTINUA**
