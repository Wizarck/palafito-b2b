# TO-DO List - Palafito WC Extensions

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

## ✅ Completado
- [x] Estructura base del plugin
- [x] Checkout customizations básicas
- [x] Tests unitarios con PHPUnit
- [x] Limpieza de funcionalidades innecesarias (RFC, B2B pricing)
- [x] Modificar campos de apellidos en checkout (no mandatory)

## 🔄 En Progreso
- [ ] Próxima funcionalidad a implementar

---
*Última actualización: $(date)* 