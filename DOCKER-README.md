# 🐳 Entorno Docker para Palafito B2B

Este proyecto incluye un entorno de desarrollo completo con Docker optimizado para WordPress + WooCommerce con las mejores prácticas de DevOps.

## 🚀 Inicio Rápido

### Prerequisitos
- Docker Desktop instalado
- Git configurado
- Al menos 4GB RAM disponible

### Iniciar el entorno
```bash
# Clonar y acceder al proyecto
git clone <repository-url>
cd palafito-b2b

# Iniciar servicios
./docker-dev.sh up

# Acceder al sitio
open http://localhost:8080
```

## 🛠️ Servicios Incluidos

| Servicio | Puerto | Descripción | URL |
|----------|--------|-------------|-----|
| **WordPress** | 8080 | Sitio principal | http://localhost:8080 |
| **MySQL** | 3306 | Base de datos | - |
| **Redis** | 6379 | Cache/Sesiones | - |
| **MailHog** | 8025 | Testing emails | http://localhost:8025 |
| **phpMyAdmin** | 8081 | Gestión BD | http://localhost:8081 |
| **Nginx** | 80/443 | Proxy reverso | http://localhost |

### Credenciales por defecto
- **WordPress Admin**: admin / admin123
- **MySQL Root**: root / root_password
- **MySQL User**: palafito_user / palafito_pass
- **Base de datos**: palafito_dev

## 📋 Comandos Principales

### Gestión de servicios
```bash
./docker-dev.sh up        # Iniciar todos los servicios
./docker-dev.sh down      # Detener servicios
./docker-dev.sh restart   # Reiniciar servicios
./docker-dev.sh status    # Ver estado de servicios
```

### Desarrollo
```bash
./docker-dev.sh shell     # Acceder al shell de WordPress
./docker-dev.sh wp        # Ejecutar comandos WP-CLI
./docker-dev.sh logs      # Ver logs de todos los servicios
./docker-dev.sh debug     # Herramientas de debugging
```

### Testing
```bash
./docker-dev.sh test      # Ejecutar suite de tests
./docker-dev.sh wp test-sync  # Test específico de sincronización
```

### Base de datos
```bash
./docker-dev.sh mysql     # Acceder a MySQL CLI
./docker-dev.sh backup    # Crear backup
./docker-dev.sh restore backup.sql  # Restaurar backup
```

## 🔧 Comandos WP-CLI Específicos

### Palafito Debug
```bash
# Acceder al contenedor WP-CLI
./docker-dev.sh wp

# Comandos específicos de Palafito
wp palafito-status           # Estado del plugin
wp palafito-test-orders      # Crear pedidos de prueba
wp palafito-logs             # Ver logs en tiempo real

# Debugging
palafito-debug logs          # Monitorear logs
palafito-debug hooks         # Ver hooks activos
palafito-debug orders        # Analizar pedidos
palafito-debug test-sync     # Test de sincronización
```

### Configuración rápida
```bash
wp dev-setup                 # Configurar entorno de desarrollo
wp reset-db                  # Reset completo de BD
wp clean-all                 # Limpiar cache y logs
```

## 🧪 Testing y QA

### Tests automatizados
```bash
# Ejecutar todos los tests
./docker-dev.sh test

# Tests específicos en contenedor
docker-compose exec testing phpunit
docker-compose exec testing run-tests
```

### Debugging en tiempo real
```bash
# Logs de Palafito
./docker-dev.sh wp exec wp-cli palafito-debug logs

# Logs generales
./docker-dev.sh logs wordpress

# Debug específico
./docker-dev.sh debug
```

## 🔍 Desarrollo con VS Code

### Extensiones recomendadas
- PHP Intelephense
- Docker
- WordPress Snippets
- phpcs
- GitLens
- Thunder Client (para API testing)

### Debugging con Xdebug
1. Abrir VS Code en el directorio del proyecto
2. Configurar breakpoints en el código PHP
3. Ejecutar "Listen for Xdebug (Docker)" en el panel Debug
4. Navegar al sitio para activar debugging

### Tasks disponibles
- `Ctrl+Shift+P` → "Tasks: Run Task"
- Seleccionar entre las tareas configuradas:
  - 🐳 Docker: Start Development
  - 🧪 Run Tests
  - 🔍 PHPCS Lint
  - 🔧 PHPCS Fix
  - 📋 View Logs

## 🔄 CI/CD Pipeline

### Workflows disponibles
- **CI/CD Principal**: `.github/workflows/ci-cd.yml`
- **Testing matriz**: PHP 8.1-8.3, WordPress 6.3+, WooCommerce 8.0+
- **Deployment automático**: Staging en PR, Producción en merge

### Stages del pipeline
1. **Code Quality**: PHPCS, sintaxis, security scan
2. **Testing**: PHPUnit, functional tests, matriz de versiones
3. **Security**: Semgrep, WPScan
4. **Deploy Staging**: Automático en PR
5. **Deploy Production**: Manual approval + health checks

## 📦 Estructura de Archivos Docker

```
docker/
├── wordpress/          # WordPress + PHP-FPM
│   ├── Dockerfile
│   ├── php.ini
│   ├── xdebug.ini
│   └── entrypoint.sh
├── mysql/              # MySQL configuración
│   ├── conf.d/
│   └── init/
├── redis/              # Redis configuración
│   └── redis.conf
├── wp-cli/             # WP-CLI + scripts
│   ├── Dockerfile
│   ├── wp-cli.yml
│   └── scripts/
├── testing/            # Testing environment
│   ├── Dockerfile
│   ├── phpunit.xml
│   └── setup-tests.sh
└── nginx/              # Nginx (opcional)
    └── nginx.conf
```

## 🚨 Troubleshooting

### Servicios no inician
```bash
# Verificar logs
./docker-dev.sh logs

# Reconstruir imágenes
./docker-dev.sh rebuild

# Verificar puertos ocupados
netstat -tulpn | grep :8080
```

### WordPress no conecta a MySQL
```bash
# Verificar servicios
docker-compose ps

# Acceder a logs de MySQL
./docker-dev.sh logs mysql

# Test de conexión manual
./docker-dev.sh mysql
```

### Xdebug no funciona
```bash
# Verificar configuración PHP
docker-compose exec wordpress php -m | grep xdebug

# Ver logs de Xdebug
docker-compose exec wordpress cat wp-content/xdebug.log
```

### Performance lenta
```bash
# Verificar recursos
./docker-dev.sh status

# Optimizar MySQL
./docker-dev.sh mysql
OPTIMIZE TABLE wp_options;

# Limpiar Redis
./docker-dev.sh redis
FLUSHALL
```

## 📊 Monitoreo y Métricas

### Health checks
```bash
# Verificar estado general
./docker-dev.sh wp health

# Verificar checksums
wp core verify-checksums --allow-root
wp plugin verify-checksums --all --allow-root
```

### Performance
```bash
# Query Monitor (instalado automáticamente)
# Acceder a: http://localhost:8080/?qm=1

# Profiling con WP-CLI
wp profile stage --all --allow-root
```

## 🔐 Seguridad

### Escaneos automáticos
- PHPCS con reglas de WordPress
- Semgrep para vulnerabilidades
- Composer audit para dependencias
- WPScan en CI/CD

### Configuración de seguridad
- Xdebug solo en desarrollo
- Redis sin contraseña (solo local)
- WordPress debug activado
- HTTPS opcional con certificados locales

## 🔄 Backup y Restauración

### Backup automático
```bash
# Backup completo
./docker-dev.sh backup

# Backup manual con fecha
docker-compose exec mysql mysqldump -u palafito_user -ppalafito_pass palafito_dev > "backup-$(date +%Y%m%d).sql"
```

### Restauración
```bash
# Restaurar desde archivo
./docker-dev.sh restore backup-20240101.sql

# Importar datos de producción
wp db import production-backup.sql --allow-root
```

---

## 📞 Soporte

Para problemas o mejoras:
1. Revisar logs: `./docker-dev.sh logs`
2. Consultar documentación en `/DEVELOPMENT.md`
3. Crear issue en el repositorio
4. Verificar configuración con `./docker-dev.sh status`

**¡Happy coding! 🚀**