# TO-DO List - Palafito B2B

**Última actualización:** 17 Julio 2025
**Versión:** v2.2.0 - ROADMAP EXTENDIDO

## 🎯 ESTADO ACTUAL DEL SISTEMA
✅ **PRODUCCIÓN ESTABLE**: Todos los sistemas core están 100% operativos
- PDF Generation (4 triggers automáticos)
- Triple-Sync Date Management
- Custom Order States (entregado/facturado)
- Email Personalization con códigos cliente
- Ultra Aggressive Control System
- GitHub Actions CI/CD Pipeline

---

## 🚀 ROADMAP DE NUEVAS FUNCIONALIDADES

### 📋 Phase 1: Sistema de Notas de Pedido (2-3 semanas)
**Objetivo:** PDFs de confirmación para nuevos pedidos (sin fecha de entrega)

- [ ] **1.1. Research PDF Plugin Extensibility**
  - [ ] Investigar cómo extender WooCommerce PDF Invoices & Packing Slips
  - [ ] Analizar estructura de clases existentes (WPO_WCPDF_Document)
  - [ ] Documentar hooks y filtros disponibles

- [ ] **1.2. Create Order Notes Document Class**
  - [ ] Crear `class WPO_WCPDF_Order_Note extends WPO_WCPDF_Document`
  - [ ] Implementar métodos requeridos: `get_type()`, `get_title()`, etc.
  - [ ] Registrar nuevo tipo de documento via `wpo_wcpdf_document_classes`

- [ ] **1.3. Implement Template System**
  - [ ] Crear template `order-notes.php` en `/wp-content/themes/kadence/woocommerce/pdf/mio/`
  - [ ] Estructura similar a packing slip pero SIN fecha de entrega
  - [ ] Incluir: número pedido, fecha, método pago, productos, contacto
  - [ ] Validar positioning perfecto con estructura existente

- [ ] **1.4. Auto-generation Integration**
  - [ ] Hook `woocommerce_order_status_processing` para auto-generación
  - [ ] Hook `woocommerce_order_status_changed` para control inteligente
  - [ ] Función `generate_order_note_pdf($order)` siguiendo patrón existente
  - [ ] Logging con prefijo `[PALAFITO]` para trazabilidad

- [ ] **1.5. Email Integration**
  - [ ] Filtro `woocommerce_email_attachments` para adjuntar nota automáticamente
  - [ ] Integrar con emails: `new_order`, `customer_processing_order`
  - [ ] Validar que funciona con personalización de códigos cliente existente

- [ ] **1.6. Auto-print Functionality**
  - [ ] Investigar opciones de impresión automática (browser API, servidor)
  - [ ] Implementar configuración opcional en admin
  - [ ] Hook `wpo_wcpdf_after_pdf_generation` para trigger impresión
  - [ ] Testing en entorno local antes de producción

- [ ] **1.7. Testing & Validation**
  - [ ] Crear tests unitarios para nueva funcionalidad
  - [ ] Validar integración con sistema ultra aggressive control
  - [ ] Testing completo: processing → nota PDF → email → (optional print)
  - [ ] Documentar en CLAUDE.md y CONTEXT.md

### 🧪 Phase 2: E2E Testing Framework (3-4 semanas)
**Objetivo:** Testing automatizado end-to-end con Playwright

- [ ] **2.1. Setup Playwright Framework**
  - [ ] Instalar Playwright: `npm install @playwright/test`
  - [ ] Configurar `playwright.config.js` con proyectos Chrome/Firefox
  - [ ] Setup test environment con base URL local/staging
  - [ ] Configurar screenshots y videos en fallos

- [ ] **2.2. Core Test Scenarios**
  - [ ] **Test: Complete Order Lifecycle**
    - [ ] Crear pedido via frontend → Verificar en admin
    - [ ] Cambio a processing → Verificar nota pedido PDF
    - [ ] Cambio a entregado → Verificar packing slip + fecha
    - [ ] Cambio a facturado → Verificar factura + email
    - [ ] Validar códigos cliente en emails

  - [ ] **Test: PDF Generation Control**
    - [ ] Verificar bloqueo auto-generación en processing
    - [ ] Validar generación manual desde admin
    - [ ] Test sistema ultra aggressive blocking
    - [ ] Verificar 4 triggers automáticos existentes

  - [ ] **Test: Email Personalization**
    - [ ] Crear pedido con nota cliente conteniendo códigos CXXXXX
    - [ ] Cambiar a entregado y verificar email personalizado
    - [ ] Test múltiples formatos: "Feria: C00303", "Obrador: C02388"

- [ ] **2.3. Integration with CI/CD**
  - [ ] Actualizar `.github/workflows/deploy.yml` con step E2E
  - [ ] Configurar Node.js 18+ en GitHub Actions
  - [ ] E2E tests como prerequisito para deploy a producción
  - [ ] Artifact collection: screenshots, videos, reports

- [ ] **2.4. Visual Regression Testing**
  - [ ] Screenshots de páginas clave: checkout, admin orders, emails
  - [ ] Comparación automática con baseline images
  - [ ] Alertas en caso de cambios visuales no esperados

- [ ] **2.5. Automated Reporting**
  - [ ] HTML reports con resultados detallados
  - [ ] Integration con GitHub para comentarios automáticos en PRs
  - [ ] Slack/email notifications en fallos críticos

### 🤖 Phase 3: WhatsApp Integration (4-6 semanas)
**Objetivo:** Sistema completo de pedidos vía WhatsApp

- [ ] **3.1. WhatsApp Business API Research**
  - [ ] Investigar WhatsApp Business API requirements
  - [ ] Configurar webhook endpoint en WordPress
  - [ ] Implementar verificación de webhook con VERIFY_TOKEN
  - [ ] Testing inicial con Meta Developer Console

- [ ] **3.2. Message Parser Implementation**
  - [ ] Crear `class Palafito_WhatsApp_Parser`
  - [ ] Regex patterns para extraer productos y cantidades
  - [ ] Validación contra catálogo WooCommerce
  - [ ] Support múltiples formatos: "2x Producto A, 1x Producto B"

- [ ] **3.3. Order Creation System**
  - [ ] Crear `class Palafito_WhatsApp_Order_Creator`
  - [ ] Customer lookup/creation basado en número de teléfono
  - [ ] Aplicar pricing B2B y términos específicos
  - [ ] Generar draft order para confirmación

- [ ] **3.4. Bidirectional Communication**
  - [ ] Crear `class Palafito_WhatsApp_API`
  - [ ] Métodos: `send_message()`, `receive_webhook()`, `verify_webhook()`
  - [ ] Template messages para confirmaciones y errores
  - [ ] Queue system para reliable message delivery

- [ ] **3.5. Integration with Order Notes**
  - [ ] WhatsApp order confirmation → auto-generate order note PDF
  - [ ] Send order note back to customer via WhatsApp
  - [ ] Link con sistema de estados existente

- [ ] **3.6. Comprehensive Testing**
  - [ ] Unit tests para parser y order creation
  - [ ] E2E tests para workflow completo WhatsApp
  - [ ] Mock WhatsApp API para testing sin costos
  - [ ] Load testing para múltiples pedidos simultáneos

### 📊 Phase 4: Monitoring Dashboard (2-3 semanas)
**Objetivo:** Dashboard de métricas y monitoreo en tiempo real

- [ ] **4.1. Dashboard Interface**
  - [ ] Crear página admin: `WooCommerce > Palafito Dashboard`
  - [ ] Widgets: orders processed, PDFs generated, WhatsApp activity
  - [ ] Charts con Chart.js o similar librería
  - [ ] Responsive design para móvil/tablet

- [ ] **4.2. Metrics Collection**
  - [ ] Tracking de PDFs generados por tipo y trigger
  - [ ] Métricas de emails enviados y códigos cliente extraídos
  - [ ] WhatsApp message volume y success rate
  - [ ] Order conversion rates por canal (web vs WhatsApp)

- [ ] **4.3. Real-time Monitoring**
  - [ ] WebSocket o polling para updates en tiempo real
  - [ ] Health checks automáticos de todos los componentes
  - [ ] Status indicators: sistema PDF, email, WhatsApp API
  - [ ] Performance metrics: response times, error rates

- [ ] **4.4. Alert System**
  - [ ] Email/Slack alerts para errores críticos
  - [ ] Thresholds configurables para métricas clave
  - [ ] Dashboard de incidents y resolution tracking
  - [ ] Integration con logging existente [PALAFITO]

- [ ] **4.5. Reporting Features**
  - [ ] Export reports: CSV, PDF, Excel
  - [ ] Scheduled reports (diario, semanal, mensual)
  - [ ] Custom date ranges y filtering options
  - [ ] Revenue analytics por canal y customer segment

---

## 🎨 MEJORAS DE EXPERIENCIA DE USUARIO (UX/UI)

### Checkout & Formularios B2B
- [ ] **Campo Compañía Obligatorio en Checkout**
  - [ ] Hacer que el campo "Compañía" sea realmente mandatory (no solo visual con asterisco)
  - [ ] Validar en frontend: bloquear submit si está vacío
  - [ ] Validar en backend: error message específico si falta
  - [ ] Testing: intentar completar checkout sin compañía y verificar bloqueo
  - [ ] Mantener coherencia visual con otros campos obligatorios

### Experiencia de Compra en Tienda
- [ ] **Selector de Cantidad en Hover de Productos**
  - [ ] Implementar cantidad selector (+ / - / número) en hover de productos de tienda
  - [ ] Diseño armonioso: selector encima del botón "Añadir al carrito"
  - [ ] Ambos elementos (selector + botón) más pequeños para mejor integración visual
  - [ ] Funcionalidad similar a página individual de producto (ej: /product/pp-carne/)
  - [ ] Estados hover/focus/active para mejor UX
  - [ ] Responsive design: adaptar para móvil/tablet
  - [ ] Testing cross-browser: Chrome, Firefox, Safari
  - [ ] Validación: no permitir cantidades <= 0 o no numéricas
  - [ ] Animaciones sutiles para transiciones suaves
  - [ ] Integration con JavaScript existente de WooCommerce

### Optimizaciones Visuales
- [ ] **Consistency Check**
  - [ ] Revisar que todos los elementos hover mantengan coherencia visual
  - [ ] Validar que colores/fonts sigan design system existente
  - [ ] Mobile-first approach para todos los cambios
  - [ ] Accessibility compliance (WCAG guidelines)

---

## 🔧 TAREAS DE MANTENIMIENTO Y MEJORA

### Seguridad y Performance
- [ ] **Security Hardening**
  - [ ] Implementar rate limiting para WhatsApp webhook
  - [ ] Encrypt sensitive data en database
  - [ ] Regular security audit de código custom
  - [ ] Update dependencies y plugins regularmente

- [ ] **Performance Optimization**
  - [ ] Database query optimization para dashboard
  - [ ] Caching layer para WhatsApp responses
  - [ ] Image optimization para PDFs
  - [ ] CDN setup para assets estáticos

### Documentation & Training
- [ ] **API Documentation**
  - [ ] Swagger/OpenAPI docs para WhatsApp endpoints
  - [ ] Developer guide para extending functionality
  - [ ] Troubleshooting guide para common issues

- [ ] **User Training**
  - [ ] Video tutorials para nuevas funcionalidades
  - [ ] Admin manual para WhatsApp management
  - [ ] Customer guide para WhatsApp ordering

---

## ✅ COMPLETADO - SISTEMA CORE

### Sistema PDF Avanzado ✅
- [x] 4 triggers automáticos: metabox, botón, entregado, facturado
- [x] Templates optimizados con positioning perfecto
- [x] Triple-sync date management completamente resuelto
- [x] Ultra aggressive control system implementado

### Email Personalization ✅
- [x] Extracción automática códigos cliente (CXXXXX)
- [x] Personalización títulos email entregado
- [x] Support múltiples formatos: Feria, Obrador, directo

### Custom Order States ✅
- [x] Estados "entregado" y "facturado" implementados
- [x] Transiciones automáticas basadas en método pago
- [x] Bulk actions para cambios masivos de estado
- [x] Emails automáticos por cambio de estado

### CI/CD Pipeline ✅
- [x] GitHub Actions completamente automatizado
- [x] PHPCS validation automática
- [x] Deploy seguro con backup automático
- [x] Rollback en caso de error

### Admin Interface ✅
- [x] Columnas personalizadas con enhanced logic
- [x] Sorting por fechas de entrega y factura
- [x] Colores de estado personalizados
- [x] Meta boxes integrados con PDF plugin Pro

---

## 📈 MÉTRICAS DE PROGRESO

### Current Sprint (Phase 1 - Order Notes)
**Timeline:** 2-3 semanas
**Progress:** 0% - Research phase

### Overall Roadmap
- **Phase 1**: Order Notes System (2-3 weeks)
- **Phase 2**: E2E Testing Framework (3-4 weeks)
- **Phase 3**: WhatsApp Integration (4-6 weeks)
- **Phase 4**: Monitoring Dashboard (2-3 weeks)

**Total Timeline:** ~4-6 meses para roadmap completo
**Current System Status:** ✅ 100% operational, ready for expansion

---

## 🎯 PRÓXIMOS PASOS INMEDIATOS

### Roadmap Principal
1. **Esta semana**: Research Order Notes system y PDF plugin extensibility
2. **Próxima semana**: Implementar document class y template básico
3. **Siguientes 2 semanas**: Completar integración con emails y testing

### Mejoras UX/UI (Paralelo)
1. **Campo Compañía Obligatorio**: Implementación rápida (1-2 días)
2. **Selector Cantidad Hover**: Diseño + implementación (3-5 días)

**Ready para comenzar Phase 1 - Order Notes System + UX Improvements**

---

*Este TODO.md se actualiza semanalmente para reflejar el progreso real del roadmap extendido.*
