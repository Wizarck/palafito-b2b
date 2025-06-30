# 🧠 MEMORIA EXTERNA - Palafito B2B

## ⚠️ INSTRUCCIONES PARA EL ASISTENTE

**Este archivo es MI MEMORIA EXTERNA.** Cuando el usuario me diga "lee el archivo de contexto", debo leer este archivo completo para entender el estado actual del proyecto sin preguntar nada.

**REGLAS IMPORTANTES:**
1. **NO preguntar sobre información que ya está aquí**
2. **Usar este contexto como base para continuar el trabajo**
3. **Actualizar este archivo al final de cada sesión** (cuando el usuario diga "buenas noches")
4. **Incluir TODOS los cambios realizados en la sesión actual**
5. **El TO-DO list está en un archivo separado** - NO en este archivo

---

## 📋 Resumen Ejecutivo

**Palafito B2B** es una plataforma de comercio electrónico B2B (Business-to-Business) construida sobre WordPress + WooCommerce, diseñada específicamente para ventas mayoristas. El proyecto utiliza el tema Kadence con un child theme personalizado y un plugin custom para funcionalidades específicas.

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
└── TODO.md                    # Lista de tareas (archivo separado)
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

### 🔄 En Progreso
- **Optimización de Performance**: Resolución de problemas de CSS
- **Debugging**: Monitoreo de logs de producción

### 📋 TO-DO List
**IMPORTANTE:** El listado de tareas TO-DO se mantiene en un archivo separado (`TODO.md`). Este archivo de contexto es solo para entender el proyecto, no para el status de tareas.

---

## 🐛 Problemas Resueltos

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

---

## 🔧 Configuraciones Importantes

### Plugin `palafito-wc-extensions`
- **Ubicación**: `wp-content/plugins/palafito-wc-extensions/`
- **Inicialización**: Hook `init` (evita problemas de carga temprana)
- **Dependencias**: Requiere WooCommerce activo
- **Funcionalidades**: Customizaciones de checkout
- **Estructura**: Clase principal + clases específicas

### Child Theme `palafito-child`
- **Tema Padre**: Kadence
- **Funcionalidades**: Estilos personalizados, scripts, hooks WooCommerce
- **Dependencias**: Removidas dependencias del plugin custom
- **CSS**: Carga correctamente desde Kadence via `@import`
- **Sistema de carga**: WordPress nativo para child themes

### Plugin `wholesalex`
- **Propósito**: Gestión de precios B2B
- **Integración**: Funciona independientemente del plugin custom
- **Configuración**: Requiere configuración manual en admin
- **Estado**: YA FUNCIONANDO - NO TOCAR

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

---

## 🎨 Personalizaciones de UI/UX

### Tema Kadence
- **Base**: Tema Kadence estándar
- **Customizaciones**: Via child theme
- **Responsive**: Mobile-first design

### WooCommerce
- **Checkout**: Campos personalizados (Last Name opcional)
- **Precios**: Sistema B2B via wholesalex
- **Emails**: Templates personalizados (pendiente)

---

## 🔐 Seguridad y Compliance

### WordPress
- **Updates**: Automáticos habilitados
- **Backups**: Configurados en hosting
- **Security**: Nonces, sanitización implementados

### WooCommerce
- **Payments**: WooCommerce Payments
- **SSL**: Certificado activo
- **GDPR**: Compliance básico

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
**Versión**: 1.0.0
**Estado**: Estable (problemas de CSS resueltos)

**ÚLTIMA SESIÓN**: Resolvimos problemas de inicialización duplicada del plugin y CSS roto. El diseño debería estar funcionando correctamente ahora.

---

## 📝 NOTAS IMPORTANTES PARA FUTURAS SESIONES

1. **wholesalex YA funciona** - NO tocar ese plugin
2. **El tema es Kadence** - NO confundir con otros temas
3. **Rama principal es `master`** - NO `main`
4. **PHP 4.4.9 es antigua pero funciona** - NO es prioridad actualizar
5. **El usuario quiere funcionalidades B2B escalables** - Enfocarse en eso
6. **El TO-DO list está en archivo separado** - NO en este archivo de contexto

---

## 🚨 INFORMACIÓN CRÍTICA DE TROUBLESHOOTING

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

---

*Este archivo es MI MEMORIA EXTERNA. Debo actualizarlo al final de cada sesión cuando el usuario diga "buenas noches".* 