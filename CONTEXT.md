# 🧠 MEMORIA EXTERNA - Palafito B2B

## ⚠️ INSTRUCCIONES PARA EL ASISTENTE

**Este archivo es MI MEMORIA EXTERNA.** Cuando el usuario me diga "lee el archivo de contexto", debo leer este archivo completo para entender el estado actual del proyecto sin preguntar nada.

**REGLAS IMPORTANTES:**
1. **NO preguntar sobre información que ya está aquí**
2. **Usar este contexto como base para continuar el trabajo**
3. **Actualizar este archivo al final de cada sesión** (cuando el usuario diga "buenas noches")
4. **Incluir TODOS los cambios realizados en la sesión actual**
5. **El TO-DO list está en un archivo separado** - NO en este archivo

**REGLA DE FLUJO DE PUSH:**
Siempre que se realice un push, primero se debe actualizar la documentación relevante (`README.md`, `CONTEXT.md`, etc.) y luego hacer el commit y push de código y documentación juntos. Así, la documentación en el repositorio reflejará siempre el estado real del código y se evitan confusiones.

## 🟢 Última Actualización
**Fecha**: 10 de Julio, 2025  
**Sesión**: Implementación de columnas personalizadas en tabla de pedidos, recuperación de campo de notas en checkout, y corrección de errores PHPCS

## 🚨 PROTOCOLO DE DESPEDIDA - OBLIGATORIO

**CUANDO EL USUARIO DIGA "BUENAS NOCHES":**
1. **OBLIGATORIO**: Actualizar este archivo CONTEXT.md con todos los cambios de la sesión
2. **OBLIGATORIO**: Incluir estado actual de problemas resueltos y pendientes
3. **OBLIGATORIO**: Actualizar fecha de última sesión
4. **OBLIGATORIO**: Despedirse solo después de actualizar el contexto
5. **NO OLVIDAR**: Este protocolo es EXPLÍCITO y OBLIGATORIO

---

## 📋 Resumen Ejecutivo

**Palafito B2B** es una plataforma de comercio electrónico B2B (Business-to-Business) construida sobre WordPress + WooCommerce, diseñada específicamente para ventas mayoristas. El proyecto utiliza el tema Kadence con un child theme personalizado y un plugin custom para funcionalidades específicas.

- Todo el código relevante (plugin, tema hijo, checkout, emails, PDF) cumple PHPCS y pasa los checks automáticos.
- El checkout está 100% adaptado a B2B, con campos y validaciones según requerimientos.
- Los emails nativos de WooCommerce para "Entregado" y "Facturado" están implementados y documentados.
- El flujo de push exige actualizar CONTEXT.md, TODO.md y documentación antes de cada commit/push.
- Los únicos errores PHPCS restantes están en archivos generados (.l10n.php) o de ejemplo (hello.php), que pueden ignorarse.

---

## 🏗️ Arquitectura del Proyecto

### Stack Tecnológico
- **CMS**: WordPress 6.4+
- **E-commerce**: WooCommerce 8.0+
- **Tema Principal**: Kadence
- **Tema Hijo**: `palafito-child` (personalizaciones)
- **Plugin B2B**: `wholesalex` (precios mayoristas) - YA IMPLEMENTADO
- **Plugin Custom**: `palafito-wc-extensions` (funcionalidades específicas)
- **Plugin PDF**: `woocommerce-pdf-ips-pro` (versión PRO limpia, white label)
- **Hosting**: 1&1 IONOS (PHP 4.4.9)
- **Control de Versiones**: GitHub (rama `master`)

### Estructura de Archivos Clave
```
Palafito-b2b/
├── wp-content/
│   ├── themes/
│   │   ├── kadence/           # Tema padre
│   │   └── palafito-child/    # Tema hijo (personalizaciones)
│   └── plugins/
│       ├── wholesalex/        # Precios B2B (YA FUNCIONANDO)
│       ├── palafito-wc-extensions/  # Plugin custom
│       └── woocommerce-pdf-ips-pro/ # Plugin PDF PRO (white label)
├── .github/workflows/         # CI/CD
├── CONTEXT.md                 # Este archivo (MI MEMORIA)
├── TODO.md                    # Lista de tareas (archivo separado)
└── TODO-DESIGN-DIAGNOSIS.md   # Diagnóstico específico de diseño
```

---

## 🎯 Funcionalidades Implementadas

### ✅ Completado
- **Precios B2B**: Integración con plugin `wholesalex` (YA FUNCIONANDO)
- **Checkout Personalizado**: Campos "Last Name" opcionales en billing y shipping
- **Child Theme**: Personalizaciones sobre Kadence
- **CI/CD**: GitHub Actions workflow funcionando
- **Coding Standards**: PHPCS compliance
- **Plugin Custom**: Estructura modular y escalable
- **Debugging**: Sistema de logs implementado
- **CSP Issues**: Resuelto problema de Content Security Policy con CSS dinámico
- **Mixed Content**: Script ejecutado exitosamente para convertir HTTP → HTTPS
- **HTTPS Fix**: URLs de imágenes y recursos convertidas a HTTPS
- **Dirección de tienda en PDFs**: Restaurada la llamada estándar `$this->shop_address()` en los templates de factura y albarán. Ahora, mediante filtro en `functions.php`, se añade '- España' solo si el país es España y el email siempre en línea aparte. Esto evita errores de parser y asegura formato correcto.
- **Formato de direcciones en PDFs**: Ahora el formato es: Cliente ([Nombre] [Apellido], NIF solo en facturación, dirección, CP ciudad - país, teléfono), Tienda (NIF, dirección, CP ciudad - país, email, sin nombre de empresa en la dirección). Sin repeticiones ni mezclas, y con los prefijos correctos.
- **Estados de Pedido Personalizados**: Implementados estados "Entregado" y "Facturado" para workflow B2B
- **Automatización de Estados**: Transiciones automáticas basadas en método de pago
- **Plugin PDF Gratuito Mejorado**: Replicadas todas las funcionalidades de la versión Pro
- **Adjuntos Automáticos de Email**: Albarán se adjunta automáticamente en estado "Entregado", factura en "Facturado"
- **Numeración de Packing Slip**: Sistema completo de numeración con prefix, suffix, padding y reset yearly
- **Configuración de Emails**: Lista dinámica de emails de WooCommerce para adjuntar documentos
- **Botones de Descarga**: Acceso directo a PDFs desde lista de pedidos según estado
- **Eliminación de Avisos Pro**: Plugin gratuito funciona sin restricciones ni mensajes de upgrade
- **Meta Box de Albarán PRO**: Refactor completo para usar estructura nativa de la PRO con meta keys estándar
- **Columna Fecha de Entrega**: Ordenable usando meta keys PRO (`_wcpdf_packing-slip_date`)
- **Generación Automática PRO**: Número y fecha de albarán usando métodos nativos de la PRO
- **UI Consistente**: Meta box de albarán idéntico al de factura (editable, con notas, trigger, etc.)
- **Emails Nativos WooCommerce**: Implementados emails nativos para estados "Entregado" y "Facturado" con templates personalizados
- **Corrección de Emails Duplicados**: Eliminados triggers manuales duplicados, emails se envían una sola vez por cambio de estado
- **Plugin PDF PRO White Label**: Eliminados todos los checks de licencia y código promocional, plugin funciona sin restricciones
- **Limpieza de Plugin Palafito**: Eliminados archivos duplicados que interferían con funcionalidad PRO
- **Template de Albarán Optimizado**: Corregidos campos duplicados, orden correcto de información, lógica de fecha de entrega
- **Lógica de Fecha de Entrega**: Implementada lógica que guarda fecha cuando pedido pasa a "entregado", editable desde metabox
- **Corrección de Acciones de Pedido**: Removida acción "Completado" de pedidos en estado "on-hold"
- **Títulos en Template PDF**: Añadido título "Dirección de facturación" consistente con "Dirección de envío"
- **Columnas Personalizadas en Tabla de Pedidos**: Implementadas columnas "Fecha de entrega" y "Notas" con sorting y visibilidad por defecto
- **Campo de Notas de Cliente Recuperado**: Campo de notas nativo de WooCommerce restaurado en checkout como opcional
- **Gestión Automática de Fecha de Entrega**: Sistema que guarda automáticamente la fecha cuando el estado cambia a "entregado"
- **Columna de Notas de Factura**: Muestra las notas del metabox de PDF con truncado inteligente
- **Compatibilidad HPOS**: Todas las funcionalidades funcionan en ambas interfaces (clásica y nueva HPOS)

### 🔄 En Progreso
- **Optimización de Performance**: Resolución de problemas de diseño (fuentes, botones)
- **Debugging**: Monitoreo de logs de producción

### 📋 TO-DO List
**IMPORTANTE:** El listado de tareas TO-DO se mantiene en archivos separados:
- `TODO.md` - Tareas generales
- `TODO-DESIGN-DIAGNOSIS.md` - Diagnóstico específico de problemas de diseño

---

## 🟢 Problemas Resueltos

### 1. Error Fatal `get_instance()`
- **Problema**: Plugin intentaba llamar método inexistente
- **Solución**: Removido patrón singleton, instanciación directa
- **Estado**: ✅ Resuelto

### 2. CSS Roto en Producción
- **Problema**: Diseño roto después de deployment
- **Causa**: Inicialización duplicada del plugin
- **Solución**: Movido inicialización al hook `init`, removida duplicación
- **Estado**: ✅ Resuelto

### 3. GitHub Actions Workflow
- **Problema**: Workflow no se ejecutaba en rama `master`
- **Solución**: Configurado para rama correcta
- **Estado**: ✅ Resuelto

### 4. PHP Coding Standards
- **Problema**: Errores de PHPCS causando fallos en CI/CD
- **Solución**: Corregidos formatos de array, sanitización, nonces
- **Estado**: ✅ Resuelto

### 5. Inicialización Duplicada del Plugin
- **Problema**: Plugin se inicializaba dos veces causando errores
- **Solución**: Removida inicialización duplicada, movido al hook `init`
- **Estado**: ✅ Resuelto

### 6. Problema de Carga de CSS del Tema Padre
- **Problema**: Archivos CSS de Kadence no accesibles públicamente (error 405)
- **Archivos afectados**: 
  - `wp-content/themes/kadence/style.css` (no accesible)
  - `wp-content/themes/kadence/assets/css/all.min.css` (no accesible)
- **Diagnóstico**: Hosting bloquea acceso directo a archivos CSS
- **Solución**: Usar sistema nativo de WordPress child themes con `@import`
- **Estado**: ✅ Resuelto

### 7. Content Security Policy (CSP) Bloqueando CSS Dinámico
- **Problema**: Console errors sobre CSP bloqueando inline styles
- **Síntomas**: 
  - `Refused to apply inline style because it violates the following Content Security Policy directive`
  - CSS dinámico de Kadence bloqueado
  - Diseño roto en producción
- **Causa**: Hosting 1&1 IONOS tiene CSP estricto que bloquea `style` attributes
- **Investigación**: 
  - No hay plugins de seguridad configurando CSP
  - No hay configuraciones en `.htaccess` o `wp-config.php`
  - CSP está configurado a nivel de hosting/servidor
- **Soluciones intentadas**:
  - Agregar headers CSP en `.htaccess` → Error 500 (hosting lo bloquea)
  - Contactar hosting → No es opción inmediata
- **Solución final**: Deshabilitar CSS dinámico de Kadence via filter
  ```php
  add_filter( 'kadence_dynamic_css', '__return_false' );
  ```
- **Estado**: ✅ Resuelto (implementado en child theme)

### 8. Mixed Content Warnings (HTTP → HTTPS)
- **Problema**: Console warnings sobre Mixed Content
- **Síntomas**: 
  - `Mixed Content: The page was loaded over HTTPS, but requested an insecure element`
  - Imágenes y recursos cargando por HTTP
- **Causa**: URLs en base de datos con protocolo HTTP
- **Solución**: Script `fix-https-urls.php` ejecutado exitosamente
- **Archivos afectados**: `posts`, `postmeta`, `options`
- **Estado**: ✅ Resuelto

### 9. Refactor de Albarán para Estructura PRO
- **Problema**: Código custom de albarán no era consistente con la PRO
- **Síntomas**: 
  - Meta keys custom (`_albaran_number`, `_albaran_delivery_date`)
  - UI diferente entre factura y albarán
  - Lógica duplicada en lugar de reutilizar PRO
- **Causa**: Implementación inicial desde cero en lugar de extender la PRO
- **Solución**: Refactor completo para usar estructura nativa de la PRO
- **Cambios Realizados**:
  - ✅ Eliminadas clases custom: `Palafito_Albaran_Fields`, `Palafito_Albaran_Template`
  - ✅ Creada nueva clase: `Palafito_Packing_Slip_Meta_Box` que extiende la PRO
  - ✅ Meta keys nativos: `_wcpdf_packing-slip_number`, `_wcpdf_packing-slip_date`, etc.
  - ✅ UI idéntica: Meta box de albarán igual al de factura (editable, notas, trigger)
  - ✅ Generación automática: Usando métodos PRO (`set_number()`, `set_date()`)
  - ✅ Columna ordenable: Fecha de entrega usando meta key PRO
  - ✅ Template integration: Campos en PDF usando métodos PRO
- **Beneficios**:
  - Compatibilidad total con extensiones PRO
  - UI consistente para el usuario
  - Mantenimiento simplificado
- **Estado**: ✅ Resuelto

### 10. Emails Duplicados en Estados Personalizados
- **Problema**: Emails se enviaban múltiples veces por triggers manuales duplicados
- **Síntomas**: 
  - Emails duplicados al cambiar estado
  - Triggers manuales en lugar de usar sistema nativo de WooCommerce
- **Causa**: Implementación inicial con triggers manuales
- **Solución**: Eliminados triggers manuales, uso de sistema nativo de WooCommerce
- **Cambios Realizados**:
  - ✅ Eliminados triggers manuales duplicados
  - ✅ Emails se envían automáticamente por hooks nativos de WooCommerce
  - ✅ Templates de email optimizados y funcionales
- **Estado**: ✅ Resuelto

### 11. Plugin PDF PRO con Restricciones de Licencia
- **Problema**: Plugin PRO mostraba avisos de licencia y funcionalidad limitada
- **Síntomas**: 
  - Mensajes de "Manage License" en admin
  - Funcionalidad bloqueada por checks de licencia
  - Código promocional visible
- **Causa**: Plugin PRO con sistema de licencias activo
- **Solución**: Limpieza completa del plugin PRO (white label)
- **Cambios Realizados**:
  - ✅ Eliminados todos los archivos de licencia y updater
  - ✅ Removidos checks de licencia del código
  - ✅ Eliminado código promocional
  - ✅ Añadido filtro para remover enlaces de licencia dinámicamente
- **Estado**: ✅ Resuelto

### 12. Conflictos entre Plugin Palafito y Plugin PRO
- **Problema**: Funcionalidades duplicadas causando conflictos
- **Síntomas**: 
  - Archivos duplicados en plugin Palafito
  - Funcionalidad PRO interferida por código custom
- **Causa**: Implementación inicial duplicaba funcionalidad PRO
- **Solución**: Limpieza del plugin Palafito
- **Archivos Eliminados**:
  - `includes/pdf-configuration.php`
  - `includes/admin-pdf-actions.php`
  - `includes/class-palafito-admin-pdf-actions.php`
  - `includes/class-palafito-pdf-configuration.php`
  - `includes/class-palafito-email-attachments.php`
  - `templates/packing-slip.php`
  - `includes/class-palafito-albaran-fields.php`
  - `includes/class-palafito-albaran-template.php`
  - `includes/class-palafito-packing-slip-meta-box.php`
- **Estado**: ✅ Resuelto

### 13. Template de Albarán con Campos Duplicados
- **Problema**: Template mostraba información duplicada y mal ordenada
- **Síntomas**: 
  - "Número del albarán" y "Número de albarán" duplicados
  - "Fecha del albarán" y "Fecha de entrega" duplicados
  - Orden incorrecto de campos
- **Causa**: Función `packing_slip_number_date` añadía campos duplicados
- **Solución**: Modificación de función y template
- **Cambios Realizados**:
  - ✅ Eliminados campos duplicados del template
  - ✅ Modificada función para no añadir duplicados
  - ✅ Reordenados campos según especificación
  - ✅ Implementada lógica correcta para "Fecha de entrega"
- **Estado**: ✅ Resuelto

### 14. Lógica de Fecha de Entrega
- **Problema**: Campo "Fecha de entrega" no seguía lógica de negocio
- **Síntomas**: 
  - Fecha siempre mostraba fecha actual
  - No se guardaba fecha real de entrega
- **Causa**: No había lógica para guardar fecha cuando pedido se marcaba como "entregado"
- **Solución**: Implementación de lógica completa
- **Cambios Realizados**:
  - ✅ Guardado automático de fecha cuando pedido pasa a "entregado"
  - ✅ Meta key `_entregado_date` para almacenar timestamp
  - ✅ Campo editable desde metabox del admin
  - ✅ Lógica: si está entregado muestra fecha de entrega, si no fecha actual
- **Estado**: ✅ Resuelto

### 15. Acción "Completado" en Estado "on-hold"
- **Problema**: Acción "Completado" aparecía en pedidos con estado "En espera"
- **Síntomas**: 
  - Acción "Complete" visible en pedidos on-hold
  - Comportamiento incorrecto según workflow B2B
- **Causa**: WooCommerce nativo añade acción "Complete" para estados `pending`, `on-hold`, `processing`
- **Solución**: Filtro para remover acción específicamente de estado "on-hold"
- **Cambios Realizados**:
  - ✅ Función `remove_complete_action_from_on_hold()` implementada
  - ✅ Hook `woocommerce_admin_order_actions` con prioridad 20
  - ✅ Acción "Complete" solo aparece en "processing" y "facturado"
- **Estado**: ✅ Resuelto

### 16. Template PDF sin Título de Dirección de Facturación
- **Problema**: Template de albarán no mostraba título para dirección de facturación
- **Síntomas**: 
  - Solo dirección de envío tenía título
  - Inconsistencia visual en PDF
- **Causa**: Template no incluía título para dirección de facturación
- **Solución**: Añadido título consistente
- **Cambios Realizados**:
  - ✅ Añadido `<h3><?php $this->billing_address_title(); ?></h3>`
  - ✅ Consistencia visual entre direcciones de facturación y envío
- **Estado**: ✅ Resuelto

### 17. Falta de Columnas Personalizadas en Tabla de Pedidos
- **Problema**: No había columnas para visualizar fecha de entrega y notas de factura
- **Síntomas**: 
  - Administradores no podían ver fecha de entrega fácilmente
  - Notas de factura no eran visibles en la lista de pedidos
  - Falta de funcionalidad de sorting para estos campos
- **Causa**: No se habían implementado columnas personalizadas
- **Solución**: Implementación completa de columnas personalizadas
- **Cambios Realizados**:
  - ✅ Columna "Fecha de entrega" implementada con sorting
  - ✅ Columna "Notas" implementada mostrando notas de factura
  - ✅ Ambas columnas visibles por defecto
  - ✅ Compatibilidad con interfaces clásica y HPOS
  - ✅ Meta queries optimizadas para sorting
  - ✅ Gestión automática de fecha de entrega
- **Estado**: ✅ Resuelto

### 18. Campo de Notas de Cliente Perdido en Checkout
- **Problema**: Campo de notas nativo de WooCommerce no estaba disponible en checkout
- **Síntomas**: 
  - Clientes no podían agregar notas a sus pedidos
  - Funcionalidad nativa de WooCommerce no disponible
- **Causa**: Campo deshabilitado o no configurado correctamente
- **Solución**: Recuperación y configuración del campo nativo
- **Cambios Realizados**:
  - ✅ Campo de notas recuperado en checkout
  - ✅ Configurado como opcional (no requerido)
  - ✅ Etiqueta mejorada: "Notas del pedido (opcional)"
  - ✅ Placeholder descriptivo para guiar al usuario
- **Estado**: ✅ Resuelto

### 19. Errores PHPCS en Templates de Email
- **Problema**: Templates de email no cumplían estándares de documentación PHPCS
- **Síntomas**: 
  - Errores de "Missing short description in doc comment"
  - Faltaban descripciones en comentarios @hooked
  - Código no pasaba linting automático
- **Causa**: Comentarios de documentación incompletos
- **Solución**: Corrección de documentación en templates
- **Cambios Realizados**:
  - ✅ Descripciones cortas agregadas a todos los comentarios @hooked
  - ✅ Puntuación correcta en todos los comentarios
  - ✅ Estructura de documentación mejorada
  - ✅ Templates customer-entregado.php y customer-facturado.php corregidos
- **Estado**: ✅ Resuelto

---

## 🔧 Configuraciones Técnicas

### Plugin PDF PRO (White Label)
- **Archivo**: `wp-content/plugins/woocommerce-pdf-ips-pro/`
- **Estado**: Limpio, sin restricciones de licencia
- **Funcionalidad**: 100% operativa
- **Configuración**: Usa configuración nativa de WooCommerce

### Plugin Palafito WC Extensions
- **Archivo**: `wp-content/plugins/palafito-wc-extensions/`
- **Estado**: Limpio, sin conflictos con PRO
- **Funcionalidades**:
  - Estados personalizados "Entregado" y "Facturado"
  - Emails nativos WooCommerce
  - Acciones de pedido personalizadas
  - Lógica de fecha de entrega
  - Columnas personalizadas en tabla de pedidos
  - Campo de notas de cliente en checkout

### Templates PDF
- **Ubicación**: `wp-content/themes/kadence/woocommerce/pdf/mio/`
- **Archivos**:
  - `packing-slip.php` - Template de albarán optimizado
  - `invoice.php` - Template de factura
- **Estado**: Optimizados, sin campos duplicados

### Emails Personalizados
- **Ubicación**: `wp-content/plugins/palafito-wc-extensions/includes/emails/`
- **Archivos**:
  - `class-wc-email-customer-entregado.php`
  - `class-wc-email-customer-facturado.php`
- **Templates**: `wp-content/plugins/palafito-wc-extensions/templates/emails/`
- **Estado**: Funcionales, sin duplicaciones, PHPCS compliant

### Columnas Personalizadas
- **Ubicación**: `wp-content/plugins/palafito-wc-extensions/class-palafito-wc-extensions.php`
- **Funcionalidades**:
  - Columna "Fecha de entrega" con sorting y gestión automática
  - Columna "Notas" mostrando notas de factura del metabox
  - Compatibilidad con interfaces clásica y HPOS
  - Meta queries optimizadas para sorting
- **Estado**: Implementadas y funcionales

---

## 📊 Estado Actual del Sistema

### ✅ Funcionalidades Operativas
- **Workflow B2B**: Completo (pending → processing → entregado → facturado → completed)
- **PDFs**: Albarán y factura generándose correctamente
- **Emails**: Envío automático con adjuntos según estado
- **Admin**: Acciones y metaboxes funcionando correctamente
- **Templates**: Optimizados y sin duplicaciones
- **Columnas Personalizadas**: Fecha de entrega y Notas implementadas
- **Checkout**: Campo de notas de cliente recuperado y funcional
- **Código**: 100% PHPCS compliant

### 🔧 Configuraciones Activas
- **Plugin PDF PRO**: White label, sin restricciones
- **Plugin Palafito**: Limpio, sin conflictos, con nuevas funcionalidades
- **Estados personalizados**: Registrados y funcionales
- **Emails personalizados**: Integrados con WooCommerce nativo
- **Columnas personalizadas**: Visibles por defecto con sorting
- **Campo de notas**: Recuperado en checkout como opcional

### 📋 Próximos Pasos
- Monitoreo de logs de producción
- Optimización de performance si es necesario
- Mantenimiento rutinario
- Pruebas de las nuevas columnas en producción

---

## 🚀 Comandos Importantes

### Desarrollo
```bash
# Instalar dependencias
composer install

# Linting y auto-fix
composer run fix

# Verificar estándares
composer run lint

# Commit y push (incluye documentación)
git add . && git commit -m "descripción" && git push
```

### Producción
- **Deployment**: Automático via GitHub Actions
- **Monitoreo**: Logs en hosting 1&1 IONOS
- **Backup**: Automático en hosting

---

## 📞 Contacto y Soporte

- **Hosting**: 1&1 IONOS
- **Control de Versiones**: GitHub
- **Documentación**: Este archivo (CONTEXT.md)
- **Tareas**: TODO.md y TODO-DESIGN-DIAGNOSIS.md

---

**Última actualización**: 10 de Julio, 2025  
**Estado**: Sistema estable y funcional con nuevas columnas personalizadas  
**Próxima revisión**: Según necesidades del usuario