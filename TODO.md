# TO-DO List - Palafito B2B

## 🚀 Próximas Implementaciones

### 1. Hardening / Seguridad básica
- [ ] Restringir edición de archivos vía wp-admin (`DISALLOW_FILE_EDIT`)
- [ ] Desactivar XML-RPC si no se necesita
- [ ] Asegurar claves y secrets (mover a `.env` o variables de entorno)
- [ ] Revisar permisos de archivos y carpetas sensibles

### 2. 🧪 Testing y control de calidad
- [x] Configurar PHPUnit (tests unitarios para tu plugin o funciones)
- [x] Automatizar tests con GitHub Actions
- [x] Validar calidad de código continuo (PHPCS, PHPStan, etc.)
- [ ] Cobertura de tests (coverage report)
- [ ] Tests de integración/end-to-end (opcional)
- [ ] **Investigar y documentar la posibilidad de que varios AI (agentes) se comuniquen entre sí para tareas colaborativas.**
- [ ] **Crear scripts automáticos (pre-push hook o npm/composer script) que ejecuten todos los checks y fixes de linting antes de cada push.**
- [ ] **Implementar pre-push hook para Composer:**
    - Usar el script `prepush` de composer para ejecutar automáticamente `composer fix:all && composer lint:all` antes de cada push.
    - Ejemplo de hook:
      ```bash
      # .git/hooks/pre-push
      #!/bin/bash
      composer prepush
      RESULT=$?
      if [ $RESULT -ne 0 ]; then
        echo "Pre-push hook: Lint or fix failed. Push aborted."
        exit 1
      fi
      ```
    - Dar permisos: `chmod +x .git/hooks/pre-push`
    - Así ningún push pasará si hay errores de linting.

- [ ] **AI colaborativo:**
    - Investigar frameworks como AutoGen, CrewAI, LangChain Agents, etc. para orquestar varios agentes AI colaborando en tareas de desarrollo, QA o documentación.
    - Probar flujos de comunicación entre agentes usando archivos de coordinación, mensajes en GitHub, o APIs.
    - Documentar casos de uso y posibles integraciones para el equipo.

### 3. 🔁 Flujo completo de desarrollo
- [ ] Definir branch strategy (main, develop, feature/*, release/*, hotfix/*)
- [ ] Configurar reglas de PR y revisión obligatoria
- [ ] Documentar el flujo de push y CI/CD

### 4. 🤖 Mejorar el deploy
- [ ] Añadir logs detallados a los scripts de deploy
- [ ] Notificar vía email o Slack en cada deploy (éxito/fallo)
- [ ] Hacer rollback automático en caso de error en el deploy
- [ ] Deploy automatizado a staging y producción (con aprobación manual)

### 5. 🚦 Flujo de estados y pagos en pedidos WooCommerce
- [x] **5.1. Crear/ajustar estados personalizados necesarios**
  - [x] Registrar los estados "Entregado" y "Facturado" solo si son imprescindibles.
  - [x] Asegurar el orden correcto de los estados en el admin.
- [x] **5.2. Lógica de transición automática tras checkout**
  - [x] Todos los pedidos nuevos se crean en "Pendiente de pago" (`pending`).
  - [x] Si el cliente elige "Pago por tarjeta" y el cobro es exitoso, pasar a "Procesando" (`processing`).
  - [x] Si el cliente elige "Pago mensual", pasar a "En espera" (`on-hold`).
  - [x] Si el pago por tarjeta falla, pasar a "Fallido" (`failed`).
- [x] **5.3. Flujo manual del administrador**
  - [x] Permitir al admin pasar manualmente de "En espera" a "Procesando" tras validar la orden y el stock.
- [x] **5.4. Albarán**
  - [x] Permitir descarga del albarán solo en "Procesando" (solo admin).
  - [x] Al pasar a "Entregado", enviar el albarán al cliente por email y permitir su descarga en el portal.
  - [x] Guardar la fecha de entrega del albarán al cambiar a "Entregado".
  - [x] **CENTRALIZACIÓN DE FECHAS DE ENTREGA**: Lógica centralizada en plugins PDF usando `_wcpdf_packing-slip_date` como única fuente de verdad, formato d-m-Y estandarizado, eliminadas duplicaciones.
- [x] **5.5. Facturación**
  - [x] Al pasar a "Facturado", generar la factura.
  - [x] Permitir descarga de la factura tanto al admin como al cliente.
- [x] **5.6. Estado final**
  - [x] El pedido pasa a "Completado" (`completed`) como estado final.
- [x] **5.7. Documentación**
  - [x] Documentar el flujo y las transiciones en el README/CONTEXT.md.

### 6. Checkout y experiencia de usuario
- [x] Checkout minimalista B2B (solo dirección de envío y métodos de pago en dos columnas)
- [x] Restaurar dinámica de métodos de pago (Stripe, Apple Pay, Google Pay, etc.)
- [x] Teléfono de envío obligatorio
- [x] Unificar bloque de pedido y métodos de pago
- [x] Automatización de transición de estado tras checkout:
    - Si el método de pago es "Pago mensual" (`cod`), el pedido pasa automáticamente a "on-hold".
    - Si es cualquier otro método de pago, el pedido pasa automáticamente a "processing".
- [x] Implementar transiciones manuales desde el admin:
    - [x] De "procesando" a "entregado" (con registro de fecha y envío de albarán).
    - [x] De "entregado" a "facturado" (con generación de factura).
    - [x] De "facturado" a "completado".
- [x] Quitar método de pago Trustly
- [x] Quitar "& Free Shipping" y "Añadir a la lista de deseos" en producto
- [x] Quitar PayPal como método de pago
- [ ] Forzar que el icono del carrito lleve siempre a /carrito/
- [ ] Personalizar color del hero/banner en Tienda, Mi cuenta, Carrito y Checkout, incluyendo el fondo, el título y el breadcrumb para mantener coherencia visual

## ✅ Completado
- [x] Estructura base del plugin
- [x] Checkout customizations básicas y cumplimiento PHPCS
- [x] Tests unitarios con PHPUnit
- [x] Limpieza de funcionalidades innecesarias (RFC, B2B pricing)
- [x] Modificar campos de apellidos en checkout (no mandatory)
- [x] Checkout visual B2B minimalista (solo dirección de envío y métodos de pago en dos columnas)
- [x] Restauración de la dinámica de métodos de pago (Stripe, Apple Pay, Google Pay, Pago mensual, etc.)
- [x] Uso de mensajes de commit solo en ASCII para evitar problemas de codificación
- [x] Este archivo TODO.md ahora está en la raíz del proyecto
- [x] **Estados de Pedido Personalizados**: Implementados "Entregado" y "Facturado"
- [x] **Automatización de Estados**: Transiciones automáticas basadas en método de pago
- [x] **Plugin PDF Mejorado**: Replicadas todas las funcionalidades de la versión Pro
- [x] **Adjuntos Automáticos**: Albarán en "Entregado", factura en "Facturado"
- [x] **Numeración de Packing Slip**: Sistema completo con prefix, suffix, padding
- [x] **Botones de Descarga**: Acceso directo a PDFs desde lista de pedidos
- [x] **Eliminación de Avisos Pro**: Plugin gratuito sin restricciones
- [x] **Refactor Albarán PRO**: Meta box editable con estructura nativa de la PRO
- [x] **Meta Keys Estándar**: Uso de `_wcpdf_packing-slip_*` en lugar de custom
- [x] **UI Consistente**: Meta box de albarán idéntico al de factura
- [x] **Generación Automática PRO**: Número y fecha usando métodos nativos
- [x] **Columna Fecha Ordenable**: Usando meta keys PRO
- [x] **Template Integration PRO**: Campos en PDF usando métodos nativos
- [x] **PHPCS**: Todo el código relevante cumple los estándares WordPress/WooCommerce
- [x] **Emails nativos**: Implementados y documentados para "Entregado" y "Facturado"
- [x] **Push/documentación**: Flujo de push actualizado y documentado
- [x] Columna de nota de cliente en tabla de pedidos de Mi Cuenta (WooCommerce), truncada a 25 caracteres, tooltip nativo, celda vacía si no hay nota. Ejemplo visual y decisión documentados en CONTEXT.md y README.md (11/07/2025)

## 🔄 En Progreso
- [ ] Próxima funcionalidad a implementar
- [x] Migrar toda la lógica de fecha de entrega a _wcpdf_packing-slip_date
- [x] Eliminar referencias y sincronización con _entregado_date
- [x] Validar que el metabox, tabla y PDF usan solo _wcpdf_packing-slip_date
- [ ] Revisar pedidos antiguos y forzar la creación del meta _wcpdf_packing-slip_date si falta

## 🆕 Recientemente Completado (Julio 2025)
- [x] **Entorno de Desarrollo Local**: Docker completo con sincronización PROD
- [x] **Sincronización de Base de Datos**: Conversión automática de prefijo de tablas
- [x] **Protección de Configuración**: Multi-capa (gitignore, hooks, GitHub Actions)
- [x] **Documentación Actualizada**: CLAUDE.md y local-environment-status.md
- [x] **Configuración Automática**: Scripts dev-local.sh para alternancia segura
- [x] **CI/CD Mejorado**: Verificación automática de configuración en pipeline

---
*Última actualización: 3 de Julio, 2025 - Entorno local completo, protección PROD automática* 