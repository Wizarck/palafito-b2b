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
**Sesión**: Corrección de emails duplicados, eliminación de triggers manuales, emails nativos funcionando correctamente

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
│       └── palafito-wc-extensions/  # Plugin custom
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
  - Meta keys estándar para exportaciones/importaciones
  - Código más limpio y mantenible

### 10. Emails Duplicados en Estado "Facturado"
- **Problema**: Email de factura llegaba 3 veces al cliente
- **Síntomas**: 
  - Múltiples emails idénticos con el mismo adjunto
  - Formato nativo de WooCommerce en cada email
- **Causa**: Múltiples triggers manuales disparando la misma acción `woocommerce_order_status_facturado`
- **Ubicaciones del problema**:
  - `plugin-hooks.php` línea 87: Trigger manual
  - `class-palafito-wc-extensions.php` línea 242: Otro trigger manual
  - `class-wc-email-customer-facturado.php` línea 35: Hook automático del email
- **Solución**: Eliminación de triggers manuales duplicados
- **Cambios Realizados**:
  - ✅ Eliminado trigger manual en `plugin-hooks.php`
  - ✅ Eliminado trigger manual en `class-palafito-wc-extensions.php`
  - ✅ Agregado hook automático en `plugin-hooks.php` para disparar acciones de estado personalizado
  - ✅ Mantenido solo el hook automático del email nativo de WooCommerce
- **Resultado**: Email se envía una sola vez por cambio de estado
- **Estado**: ✅ Resuelto
- **Estado**: ✅ Resuelto

---

## 🔧 Configuraciones Importantes

### Plugin `palafito-wc-extensions`
- **Ubicación**: `wp-content/plugins/palafito-wc-extensions/`
- **Inicialización**: Hook `init` (evita problemas de carga temprana)
- **Dependencias**: Requiere WooCommerce activo
- **Funcionalidades**: Customizaciones de checkout, estados personalizados, PDF PRO
- **Estructura**: Clase principal + clases específicas
- **Clases Principales**:
  - `Palafito_WC_Extensions` - Clase principal
  - `Palafito_Checkout_Customizations` - Personalizaciones de checkout
  - `Palafito_Email_Attachments` - Adjuntos automáticos de PDF
  - `Palafito_Packing_Slip_Settings` - Configuración de packing slip
  - `Palafito_Admin_PDF_Actions` - Botones de descarga en admin
  - `Palafito_Packing_Slip_Meta_Box` - Meta box PRO para albarán

### Child Theme `palafito-child`
- **Tema Padre**: Kadence
- **Funcionalidades**: Estilos personalizados, scripts, hooks WooCommerce
- **Dependencias**: Removidas dependencias del plugin custom
- **CSS**: Carga correctamente desde Kadence via `@import`
- **Sistema de carga**: WordPress nativo para child themes
- **CSP Fix**: Deshabilitado CSS dinámico de Kadence para evitar bloqueos
- **HTTPS Fix**: Función `palafito_comprehensive_https_fix()` implementada

### Plugin `wholesalex`
- **Propósito**: Gestión de precios B2B
- **Integración**: Funciona independientemente del plugin custom
- **Configuración**: Requiere configuración manual en admin
- **Estado**: YA FUNCIONANDO - NO TOCAR

### Plugin PDF Invoices & Packing Slips (Mejorado)
- **Propósito**: Generación de facturas y albaranes con funcionalidades Pro
- **Funcionalidades Pro Replicadas**:
  - ✅ **Adjuntos a Emails**: Configuración dinámica para todos los emails de WooCommerce
  - ✅ **Numeración de Packing Slip**: Sistema completo con prefix, suffix, padding
  - ✅ **Meta Box PRO**: Albarán editable con misma estructura que factura
  - ✅ **Meta Keys Nativos**: `_wcpdf_packing-slip_number`, `_wcpdf_packing-slip_date`, etc.
  - ✅ **Generación Automática**: Número al pasar a "processing", fecha al pasar a "entregado"
  - ✅ **Columna Ordenable**: Fecha de entrega en lista de pedidos
  - ✅ **Template Integration**: Campos de albarán en PDF usando métodos PRO
  - ✅ **Reset Yearly**: Reinicio anual de numeración
  - ✅ **Display Date**: Mostrar fecha del packing slip
  - ✅ **Disable for Statuses**: Deshabilitar en estados específicos
- **Funcionalidades Automáticas**:
  - ✅ **Albarán Automático**: Se adjunta cuando pedido cambia a "Entregado"
  - ✅ **Factura Automática**: Se adjunta cuando pedido cambia a "Facturado"
  - ✅ **Emails Automáticos**: Envío automático con PDFs adjuntos
  - ✅ **Botones de Descarga**: En lista de pedidos según estado
- **Sin Restricciones**: Eliminados todos los avisos de upgrade a Pro
- **Estado**: FUNCIONANDO - Todas las funcionalidades Pro disponibles

---

## 🚀 Deployment y CI/CD

### GitHub Actions Workflow
- **Rama**: `master`
- **Triggers**: Push, Pull Request
- **Jobs**: PHP linting, coding standards
- **Exclusiones**: Plugins de terceros, archivos de vendor

### Proceso de Deployment
1. Push a rama `master`
2. GitHub Actions ejecuta tests
3. Si pasa, cambios se reflejan en producción
4. Monitoreo de logs en `wp-content/debug.log`

---

## 📊 Estado Actual de Producción

### Servidor
- **Hosting**: 1&1 IONOS
- **PHP**: 4.4.9 (⚠️ Versión antigua pero FUNCIONA)
- **WordPress**: 6.4+
- **WooCommerce**: 8.0+

### Monitoreo
- **Logs**: `wp-content/debug.log`
- **Errores**: Fatal errors resueltos
- **Performance**: CSS loading optimizado
- **CSP**: CSS dinámico deshabilitado para evitar bloqueos
- **HTTPS**: URLs convertidas correctamente

### Problemas Actuales
- **Diseño**: Fuentes y botones no coinciden con Kadence
- **CSS**: Posible interferencia entre child theme y Kadence
- **Diagnóstico**: TODO-DESIGN-DIAGNOSIS.md creado con 10 puntos de verificación

---

## 🎨 Personalizaciones de UI/UX

### Tema Kadence
- **Base**: Tema Kadence estándar
- **Customizaciones**: Via child theme
- **Responsive**: Mobile-first design
- **CSS Dinámico**: Deshabilitado para evitar problemas CSP

### WooCommerce
- **Checkout**: Campos personalizados (Last Name opcional)
- **Precios**: Sistema B2B via wholesalex
- **Emails**: Templates personalizados (pendiente)

---

## 📅 Historial de Sesiones

### Última Sesión: [FECHA ACTUAL]
- ✅ Resuelto: Mixed Content warnings con script HTTPS
- ✅ Creado: TODO-DESIGN-DIAGNOSIS.md con diagnóstico completo
- ✅ Implementado: Función `palafito_comprehensive_https_fix()`
- 🔄 Pendiente: Diagnóstico de problemas de diseño (fuentes, botones)
- 📋 Próximo: Seguir TODO-DESIGN-DIAGNOSIS.md punto por punto

---

## 🛠️ Comandos Útiles

### Desarrollo Local
```bash
# Verificar estado del repositorio
git status

# Ver logs de producción
tail -f wp-content/debug.log

# Ejecutar PHPCS localmente
./vendor/bin/phpcs wp-content/plugins/palafito-wc-extensions/

# Hacer commit y push
git add .
git commit -m "Descripción del cambio"
git push origin master
```

### Troubleshooting
```bash
# Verificar versión de PHP
php -v

# Verificar plugins activos
wp plugin list --status=active

# Verificar tema activo
wp theme list --status=active
```

---

## 📞 Contacto y Recursos

### Equipo
- **Desarrollador**: Arturo Ramirez
- **Cliente**: Palafito B2B
- **Hosting**: 1&1 IONOS

### Documentación
- **WordPress**: https://developer.wordpress.org/
- **WooCommerce**: https://docs.woocommerce.com/
- **Kadence**: https://www.kadencewp.com/docs/
- **WholesaleX**: https://docs.wpxpo.com/wholesalex/

---

## 🔄 Última Actualización

**Fecha**: 30 de Junio, 2025
**Versión**: 1.1.0
**Estado**: Estable (problemas de CSP resueltos)

**ÚLTIMA SESIÓN**: Resolvimos problemas de Content Security Policy bloqueando CSS dinámico de Kadence. Implementamos solución deshabilitando CSS dinámico via filter en el child theme.

---

## 📝 NOTAS IMPORTANTES PARA FUTURAS SESIONES

1. **wholesalex YA funciona** - NO tocar ese plugin
2. **El tema es Kadence** - NO confundir con otros temas
3. **Rama principal es `master`** - NO `main`
4. **PHP 4.4.9 es antigua pero funciona** - NO es prioridad actualizar
5. **El usuario quiere funcionalidades B2B escalables** - Enfocarse en eso
6. **El TO-DO list está en archivo separado** - NO en este archivo de contexto
7. **CSP está configurado a nivel de hosting** - NO intentar modificar desde WordPress

---

## 🚨 INFORMACIÓN CRÍTICA DE TROUBLESHOOTING

### Content Security Policy (CSP) Issues
- **Problema**: Console errors sobre CSP bloqueando inline styles
- **Mensaje típico**: `Refused to apply inline style because it violates the following Content Security Policy directive`
- **Causa**: Hosting 1&1 IONOS tiene CSP estricto configurado a nivel servidor
- **Impacto**: CSS dinámico de temas modernos (como Kadence) se bloquea
- **Diagnóstico**:
  - Verificar console del navegador para errores CSP
  - Confirmar que no hay plugins de seguridad configurando CSP
  - Verificar que no hay configuraciones en `.htaccess` o `wp-config.php`
- **Soluciones intentadas**:
  - Agregar headers CSP en `.htaccess` → Error 500 (hosting lo bloquea)
  - Contactar hosting → No es opción inmediata
- **Solución aplicada**: Deshabilitar CSS dinámico de Kadence
  ```php
  // En functions.php del child theme
  add_filter( 'kadence_dynamic_css', '__return_false' );
  ```
- **Verificación**: Revisar console del navegador para confirmar que no hay errores CSP
- **Patrón**: Cuando CSP bloquea CSS dinámico, deshabilitar la generación dinámica es menos invasivo que modificar CSP

### Archivos CSS No Accesibles
- **Problema**: Hosting 1&1 IONOS bloquea acceso directo a archivos CSS
- **Archivos afectados**: 
  - `wp-content/themes/kadence/style.css` → Error 405
  - `wp-content/themes/kadence/assets/css/all.min.css` → Error 405
- **Solución aplicada**: Usar sistema nativo de WordPress child themes con `@import`
- **Comando de verificación**: `curl -I https://palafito.com/wp-content/themes/kadence/style.css`

### Plugin Inicialización
- **Problema**: Plugin se inicializa múltiples veces por carga de página
- **Logs típicos**: `Palafito WC Extensions: Plugin initialized` (múltiples veces)
- **Solución**: Hook `init` con verificación de WooCommerce
- **Verificación**: Revisar `wp-content/debug.log`

### Traducciones Tempranas
- **Problema**: `woocommerce-payments` carga traducciones muy temprano
- **Logs típicos**: `Function _load_textdomain_just_in_time was called incorrectly`
- **Impacto**: Solo warnings, no crítico
- **Solución**: Plugin se inicializa en hook `init`

### Estructura de Archivos Kadence
- **CSS principal**: `wp-content/themes/kadence/style.css` (no accesible públicamente)
- **CSS compilado**: `wp-content/themes/kadence/assets/css/all.min.css` (no accesible públicamente)
- **Solución**: Usar `@import` en child theme + sistema nativo WordPress

### Sistema Automático de Kadence
- **Problema**: Interferencia con sistema automático de carga de estilos de Kadence
- **Arquitectura Kadence**: 
  - Componente `Styles\Component()` maneja todo automáticamente
  - Carga: `global.min.css`, fuentes Google, CSS dinámico, etc.
  - Ubicación: `wp-content/themes/kadence/inc/components/styles/component.php`
- **Solución**: NO cargar estilos manualmente, dejar que Kadence maneje todo
- **Patrón**: Temas modernos tienen sistemas complejos que deben respetarse
- **Verificación**: Revisar `get_css_files()` en el componente de estilos

### Child Themes en Temas Modernos
- **Regla general**: Child themes deben respetar el sistema del tema padre
- **NO hacer**: Cargar estilos del tema padre manualmente
- **SÍ hacer**: Solo cargar estilos específicos del child theme
- **Patrón**: Tema padre maneja su sistema, child theme solo personalizaciones
- **Ejemplo**: Kadence carga automáticamente fuentes, CSS dinámico, estilos base

### CSS Dinámico y CSP
- **Problema**: Temas modernos generan CSS dinámico que puede ser bloqueado por CSP
- **Causa**: CSP bloquea `style` attributes en HTML
- **Impacto**: Diseño roto, estilos no aplicados
- **Solución**: Deshabilitar CSS dinámico cuando CSP lo bloquea
- **Patrón**: `add_filter( 'theme_dynamic_css', '__return_false' )` o similar
- **Verificación**: Console del navegador para errores CSP

---

## 🧠 CÓMO IDENTIFICAR APRENDIZAJES PARA EL CONTEXTO

### Criterios para agregar información al CONTEXT.md:

1. **Problemas que requirieron investigación profunda**
   - Investigación de arquitectura interna de temas/plugins
   - Descubrimiento de sistemas complejos
   - Causas raíz no obvias

2. **Soluciones que van contra la intuición inicial**
   - Cuando la solución real es opuesta a lo que se pensaba
   - Patrones que contradicen las mejores prácticas generales
   - Comportamientos específicos del entorno/hosting

3. **Información específica del hosting/entorno**
   - Limitaciones del hosting (como error 405 en 1&1 IONOS)
   - Configuraciones específicas del servidor
   - Comportamientos únicos del entorno

4. **Patrones que se pueden reutilizar**
   - Sistemas de temas modernos (como Kadence)
   - Arquitecturas de plugins complejos
   - Patrones de troubleshooting específicos

5. **Comandos y rutas específicas**
   - Ubicaciones de archivos importantes
   - Comandos de verificación específicos
   - Logs típicos para identificar problemas

6. **Problemas de seguridad y compliance**
   - Content Security Policy (CSP) issues
   - Configuraciones de hosting que afectan funcionalidad
   - Soluciones que respetan restricciones de seguridad

### Ejemplo de aprendizaje agregado:
- **Problema**: CSS roto en child theme
- **Investigación**: Arquitectura interna de Kadence
- **Descubrimiento**: Sistema automático de componentes
- **Solución**: NO interferir con sistema automático
- **Patrón**: Respetar arquitectura del tema padre

### Nuevo aprendizaje sobre CSP:
- **Problema**: Console errors sobre CSP bloqueando inline styles
- **Investigación**: Configuraciones de hosting y seguridad
- **Descubrimiento**: CSP configurado a nivel servidor, no WordPress
- **Solución**: Deshabilitar CSS dinámico en lugar de modificar CSP
- **Patrón**: Cuando CSP bloquea funcionalidad, deshabilitar la fuente es menos invasivo

---

## [2024-xx-xx] Lessons learned: Child theme y personalizador
- Si el child theme está limpio y bien configurado, debe comportarse igual que el parent.
- Las diferencias visuales suelen deberse a que el personalizador de WordPress guarda los settings por theme activo.
- Para que el child herede el diseño del parent, es necesario exportar las personalizaciones desde el parent e importarlas en el child (Apariencia > Personalizar > Import/Export).
- No es necesario ningún CSS, JS ni plantilla personalizada para que el child herede el diseño base de Kadence.

---

## [2024-xx-xx] display_name en direcciones y nuevo orden en albarán
- Ahora se usa el campo 'Mostrar este nombre públicamente' (display_name) como primera línea en las direcciones de cliente en factura y albarán.
- En el albarán, el bloque de datos de pedido (derecha) muestra los campos en este orden:
  1. Número de albarán
  2. Fecha de entrega
  3. Método de envío
  4. Número de pedido
  5. Fecha de pedido

## [2024-xx-xx] Nombre de PDF personalizado para albarán y factura
- El nombre del PDF generado para el albarán es: [A-numero de pedido] - [display_name].pdf
- El nombre del PDF generado para la factura es: [numero de factura] - [display_name].pdf

---

## [2024-xx-xx] Nuevos estados personalizados de pedido en WooCommerce
- Se han añadido los estados personalizados 'Entregado' y 'Facturado' a WooCommerce mediante el plugin palafito-wc-extensions.
- Flujos de pedido:
  - **B2B:** Pendiente de pago → Procesando → Entregado → Facturado → Completado
  - **B2C:** Pendiente de pago → Procesando → Entregado → Completado
- Finalidad de los nuevos estados:
  - **Entregado:** El pedido ha sido entregado físicamente, pero aún no facturado o cobrado (típico en B2B).
  - **Facturado:** El pedido ha sido incluido en una factura consolidada del mes, y está pendiente de pago.
- Los estados se muestran en el admin, en los filtros y en las acciones masivas, y se comportan como los nativos.

---

## Lessons learned y normas de workflow

- Todos los comentarios inline deben terminar en punto, exclamación o interrogación para pasar phpcs.
- Los comentarios 'translators:' deben estar presentes antes de cada llamada a _n_noop con placeholders y también terminar en punto.
- En Mac, siempre priorizar bash sobre PowerShell para evitar errores de entorno.
- Antes de cualquier push, **siempre** correr `composer install` y los comandos de linting y autofix definidos en `composer.json` (por ejemplo, `phpcs` y `phpcbf`).
- Nunca hacer push sin validar el código con composer y los tests automáticos definidos en el proyecto.
- Esto es obligatorio para evitar errores en el pipeline de GitHub Actions y asegurar la calidad del código.

*Este archivo es MI MEMORIA EXTERNA. Debo actualizarlo al final de cada sesión cuando el usuario diga "buenas noches".*

- Para la compatibilidad con HPOS de WooCommerce, si el warning persiste, usar plugin_basename(__FILE__) en la declaración de FeaturesUtil::declare_compatibility en vez de __FILE__, ya que algunas instalaciones lo requieren para detectar correctamente el archivo principal del plugin. 

- Nunca debe haber más de un archivo con cabecera de plugin (Plugin Name, etc.) en la misma carpeta de plugin. Si hay dos, WordPress mostrará el plugin duplicado en el admin. La estructura profesional de clases se mantiene, pero solo el archivo principal debe tener la cabecera de plugin. 

- No usar tildes, eñes ni símbolos especiales en los mensajes de commit. Usar solo caracteres ASCII para asegurar compatibilidad en terminal, git y GitHub Actions. Ejemplo: 'anadir', 'funcion', 'correccion'. 

## Estándares PHPCS obligatorios para cambios PHP

Siempre que se realicen cambios en archivos PHP del proyecto, es obligatorio cumplir los estándares de PHPCS (WordPress/WooCommerce):

- Los comentarios inline deben terminar en punto, exclamación o interrogación.
- Usar Yoda conditions en comparaciones.
- Todas las funciones públicas deben tener comentarios de parámetros y retorno.
- Usar elseif en vez de else con un solo if dentro.

Esto es imprescindible para evitar errores en los checks automáticos y mantener la calidad y coherencia del código.