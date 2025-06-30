# TO-DO List - Palafito WC Extensions

## 🚀 Próximas Implementaciones

### Entorno Local con Contenedores y CI/CD
- [ ] **Configurar entorno local con Docker/Docker Compose**
  - [ ] Crear Dockerfile para WordPress + WooCommerce
  - [ ] Configurar base de datos MySQL/MariaDB
  - [ ] Configurar Nginx/Apache como servidor web
  - [ ] Crear docker-compose.yml para desarrollo local

- [ ] **Implementar Pipeline CI/CD**
  - [ ] Configurar GitHub Actions para tests automáticos
  - [ ] Pipeline de staging para pruebas
  - [ ] Pipeline de producción con aprobación manual
  - [ ] Tests automáticos antes del deploy

- [ ] **Flujo de Desarrollo**
  - [ ] Desarrollo local → Tests → Staging → Producción
  - [ ] Visualización de cambios en entorno local
  - [ ] Aprobación manual antes de push a producción

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