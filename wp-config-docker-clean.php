<?php
// WordPress Docker configuration
define('DB_NAME', 'palafito_dev');
define('DB_USER', 'palafito_user');
define('DB_PASSWORD', 'palafito_pass');
define('DB_HOST', 'mysql');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

// Debug
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
define('WP_ENVIRONMENT_TYPE', 'local');

// Security keys (simplificadas para desarrollo)
define('AUTH_KEY', 'docker-dev-key');
define('SECURE_AUTH_KEY', 'docker-dev-key');
define('LOGGED_IN_KEY', 'docker-dev-key');
define('NONCE_KEY', 'docker-dev-key');
define('AUTH_SALT', 'docker-dev-salt');
define('SECURE_AUTH_SALT', 'docker-dev-salt');
define('LOGGED_IN_SALT', 'docker-dev-salt');
define('NONCE_SALT', 'docker-dev-salt');

$table_prefix = 'wp_';

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}

require_once ABSPATH . 'wp-settings.php';