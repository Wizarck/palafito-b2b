-- Configuración inicial de base de datos para Palafito B2B

-- Crear base de datos de desarrollo
CREATE DATABASE IF NOT EXISTS `palafito_dev` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crear base de datos de testing
CREATE DATABASE IF NOT EXISTS `palafito_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crear usuario y otorgar permisos
CREATE USER IF NOT EXISTS 'palafito_user'@'%' IDENTIFIED BY 'palafito_pass';
GRANT ALL PRIVILEGES ON `palafito_dev`.* TO 'palafito_user'@'%';
GRANT ALL PRIVILEGES ON `palafito_test`.* TO 'palafito_user'@'%';

-- Otorgar permisos de testing
GRANT ALL PRIVILEGES ON `test_%`.* TO 'palafito_user'@'%';

-- Configuraciones específicas para WooCommerce
SET GLOBAL sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';

-- Flush privileges
FLUSH PRIVILEGES;

-- Configurar timezone
SET GLOBAL time_zone = '+01:00';

-- Optimizaciones para desarrollo
SET GLOBAL query_cache_size = 33554432;
SET GLOBAL query_cache_type = 1;

-- Log de configuración
SELECT 'Palafito B2B Database Setup Complete' AS Status;