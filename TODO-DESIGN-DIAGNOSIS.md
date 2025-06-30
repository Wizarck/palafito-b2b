# TODO: Diagnóstico de Problemas de Diseño - WordPress + Kadence + Child Theme

## Problema Actual
- Fuentes no coinciden con Kadence
- Botones con comportamiento extraño en hover
- Diseño general no se ve como debería

## ✅ Problemas Resueltos
- [x] Mixed Content warnings (HTTP → HTTPS) - Script ejecutado exitosamente
- [x] CSS loading sin errores de CSP
- [x] Plugin initialization working

## 🔍 Diagnóstico Pendiente

### 1. Verificar CSS del Tema Hijo
- [ ] Revisar `wp-content/themes/palafito-child/style.css`
- [ ] Confirmar que solo contiene:
  ```css
  /*
   Theme Name:   Palafito Child
   Template:     kadence
   Description:  Tema hijo para personalizaciones de Palafito
   Author:       Arturo Ramirez
   Version:      1.0
  */
  @import url('../kadence/style.css');
  
  /* Solo estilos B2B puntuales aquí */
  .b2b-pricing { ... }
  ```
- [ ] Eliminar cualquier regla para: `body`, `h1`, `a`, `.button`, etc.
- [ ] No debe haber definiciones de CSS variables que interfieran con Kadence

### 2. Verificar Carga de CSS en Frontend
- [ ] Abrir herramientas de desarrollador (F12)
- [ ] Ir a pestaña "Network" → "CSS"
- [ ] Verificar que se carga `/wp-content/themes/kadence/style.css`
- [ ] Verificar que se carga `/wp-content/themes/palafito-child/style.css`
- [ ] Confirmar que no hay archivos CSS con error 404/405
- [ ] Verificar orden de carga (Kadence primero, luego child)

### 3. Verificar Consola del Navegador
- [ ] Revisar errores de CSP
- [ ] Verificar errores 404/405 en CSS
- [ ] Revisar warnings de fuentes bloqueadas
- [ ] Confirmar que no hay errores de JavaScript que afecten CSS

### 4. Verificar HTML Generado
- [ ] Revisar `<head>` en el código fuente
- [ ] Confirmar presencia de `<link rel="stylesheet" ...>` de Kadence
- [ ] Verificar `<link rel="stylesheet" ...>` del child theme
- [ ] Buscar `<style>` con variables CSS de Kadence (`:root { --global-palette1: ... }`)

### 5. Desactivar Plugins de Optimización/Caché
- [ ] Identificar plugins de caché activos (Autoptimize, WP Rocket, LiteSpeed, etc.)
- [ ] Desactivar temporalmente plugins de optimización
- [ ] Desactivar plugins de caché
- [ ] Verificar si el diseño mejora
- [ ] Si mejora, reactivar uno por uno para identificar el culpable

### 6. Revisar Configuración de Kadence
- [ ] Ir a **Apariencia > Personalizar > Tipografía**
- [ ] Verificar configuración de fuentes
- [ ] Hacer cambio menor y guardar para forzar regeneración de CSS dinámico
- [ ] Verificar **Apariencia > Personalizar > Colores**
- [ ] Confirmar que las variables CSS están bien definidas

### 7. Probar con Tema Padre
- [ ] Activar temporalmente tema Kadence (no el hijo)
- [ ] Verificar si el diseño es correcto con tema padre
- [ ] Si se ve bien con padre: problema está en child theme
- [ ] Si se ve mal con padre: problema es de configuración/caché/archivos

### 8. Revisar Orden de Estilos
- [ ] Verificar `functions.php` del child theme
- [ ] Confirmar que no hay `wp_enqueue_style` que sobreescriba orden natural
- [ ] Verificar que child theme carga después del padre
- [ ] Revisar prioridades de enqueue

### 9. Forzar Recarga de CSS
- [ ] Recarga forzada en navegador:
  - Windows: `Ctrl + F5`
  - Mac: `Cmd + Shift + R`
- [ ] Limpiar caché del navegador
- [ ] Probar en modo incógnito

### 10. Reinstalar Kadence (Último Recurso)
- [ ] Hacer backup completo
- [ ] Desactivar child theme
- [ ] Reinstalar tema Kadence
- [ ] Verificar que archivos no están corruptos
- [ ] Reactivar child theme

## 📋 Información Necesaria para Diagnóstico
- [ ] Captura de pestaña Network (CSS)
- [ ] Captura del `<head>` en código fuente
- [ ] Configuración actual de Apariencia > Personalizar > Tipografía
- [ ] Lista de plugins activos
- [ ] Captura de errores en consola

## 🎯 Prioridades
1. **Alta**: Verificar CSS del tema hijo (punto 1)
2. **Alta**: Verificar carga de CSS (punto 2)
3. **Media**: Revisar plugins de caché (punto 5)
4. **Media**: Probar con tema padre (punto 7)
5. **Baja**: Reinstalar Kadence (punto 10)

## 📝 Notas
- El script de fix HTTPS funcionó correctamente
- Los logs muestran que Kadence está generando CSS dinámico correctamente
- El problema parece ser de interferencia entre child theme y Kadence
- Enfoque: minimizar CSS del child theme y dejar que Kadence maneje todo

---
**Creado:** $(date)
**Estado:** Pendiente de ejecución
**Responsable:** Arturo Ramirez 