# TO-DO List - Palafito B2B

## 🚀 Próximas Implementaciones

### 1. Hardening / Seguridad básica
- [ ] Restringir edición de archivos vía wp-admin (`DISALLOW_FILE_EDIT`)
- [ ] Desactivar XML-RPC si no se necesita
- [ ] Asegurar claves y secrets (mover a `.env` o variables de entorno)
- [ ] Revisar permisos de archivos y carpetas sensibles

### 2. 🧪 Testing y control de calidad
- [x] Configurar PHPUnit (tests unitarios para tu plugin o funciones)
- [ ] Automatizar tests con GitHub Actions
- [ ] Validar calidad de código continuo (PHPCS, PHPStan, etc.)
- [ ] Cobertura de tests (coverage report)
- [ ] Tests de integración/end-to-end (opcional)

### 3. 🔁 Flujo completo de desarrollo
- [ ] Definir branch strategy (main, develop, feature/*, release/*, hotfix/*)
- [ ] Configurar pre-commit hooks (lint automático, tests)
- [ ] Versionado semántico (git tag, CHANGELOG.md, releases)
- [ ] Documentar el flujo de trabajo en el README o en CONTRIBUTING.md

### 4. 🤖 Mejorar el deploy
- [ ] Añadir logs detallados a los scripts de deploy
- [ ] Notificar vía email o Slack en cada deploy (éxito/fallo)
- [ ] Hacer rollback automático en caso de error en el deploy
- [ ] Deploy automatizado a staging y producción (con aprobación manual)

### 5. 🚦 Flujo de estados y pagos en pedidos WooCommerce
- [ ] **5.1. Crear/ajustar estados personalizados necesarios**
  - [ ] Registrar los estados "Entregado" y "Facturado" solo si son imprescindibles.
  - [ ] Asegurar el orden correcto de los estados en el admin.
- [ ] **5.2. Lógica de transición automática tras checkout**
  - [ ] Todos los pedidos nuevos se crean en "Pendiente de pago" (`pending`).
  - [ ] Si el cliente elige "Pago por tarjeta" y el cobro es exitoso, pasar a "Procesando" (`processing`).
  - [ ] Si el cliente elige "Pago mensual", pasar a "En espera" (`on-hold`).
  - [ ] Si el pago por tarjeta falla, pasar a "Fallido" (`failed`).
- [ ] **5.3. Flujo manual del administrador**
  - [ ] Permitir al admin pasar manualmente de "En espera" a "Procesando" tras validar la orden y el stock.
- [ ] **5.4. Albarán**
  - [ ] Permitir descarga del albarán solo en "Procesando" (solo admin).
  - [ ] Al pasar a "Entregado", enviar el albarán al cliente por email y permitir su descarga en el portal.
  - [ ] Guardar la fecha de entrega del albarán al cambiar a "Entregado".
- [ ] **5.5. Facturación**
  - [ ] Al pasar a "Facturado", generar la factura.
  - [ ] Permitir descarga de la factura tanto al admin como al cliente.
- [ ] **5.6. Estado final**
  - [ ] El pedido pasa a "Completado" (`completed`) como estado final.
- [ ] **5.7. Documentación**
  - [ ] Documentar el flujo y las transiciones en el README/CONTEXT.md.

## ✅ Completado
- [x] Estructura base del plugin
- [x] Checkout customizations básicas
- [x] Tests unitarios con PHPUnit
- [x] Limpieza de funcionalidades innecesarias (RFC, B2B pricing)
- [x] Modificar campos de apellidos en checkout (no mandatory)
- [x] Checkout visual B2B minimalista (solo dirección de envío y métodos de pago en dos columnas)
- [x] Restauración de la dinámica de métodos de pago (Stripe, Apple Pay, Google Pay, Pago mensual, etc.)
- [x] Uso de mensajes de commit solo en ASCII para evitar problemas de codificación
- [x] Este archivo TODO.md ahora está en la raíz del proyecto

## 🔄 En Progreso
- [ ] Próxima funcionalidad a implementar

---
*Última actualización: $(date)* 