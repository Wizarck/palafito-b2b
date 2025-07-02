<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */
@ini_set('log_errors', 1);
@ini_set('error_log', dirname(__FILE__) . '/php_error.log');

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'dbs13378788' );

/** Database username */
define( 'DB_USER', 'dbu714034' );

/** Database password */
define( 'DB_PASSWORD', 'cdef0705-6da3-40f2-a10b-a7967d444148' );

/** Database hostname */
define( 'DB_HOST', 'db5016482050.hosting-data.io' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'hKmsUemQS7sEsRVBQVOk5Any7rc8TBtJrsMLCQ7S54CC0MVaNB7q7xLiNu9gqPpM');
define('SECURE_AUTH_KEY',  'Tha2Epf8p426hN4QbiliYMO69dEMq9ANzOqKBrZvVQKisPDoTuurizJbZKnGMCKn');
define('LOGGED_IN_KEY',    'S7hZ8EnoHKm1QBH6HyS7ICO9IcsYaIXjlbbp7XUzP4qjVBSDrBNw44DHdDOLrirK');
define('NONCE_KEY',        'DnUMdkDJHIacxSrq2342uYELCzO86MSceI9mhK0RT3i7ydK770hG9LT6lZB5zBo3');
define('AUTH_SALT',        'jaC48eTy1P46JqSw3X4HqkU4KxVpV9u69fOXjFH8N0HRFxUoMjgjTj4JY3RJKclT');
define('SECURE_AUTH_SALT', 'VrW2GZ9oKxSGthiugDZiBCM9lnJ9H0vxxsVW3gzQAKf0s8Du0oaK661EPr6aWGPU');
define('LOGGED_IN_SALT',   'QZYMxtDEXKPBJsSZ7kLqhDBuNRSqrujoDf5A2JGScy5T5YOMN6GTst7rRHf8quac');
define('NONCE_SALT',       'zOesd0hohwyB7l8hpmgYjjKOowoGaE70d6aqrnsDHSHVaxirsh7lvpTx5YezSSWw');

/**
 * Other customizations.
 */
define('WP_TEMP_DIR',dirname(__FILE__).'/wp-content/uploads');


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'pnsc_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
// === CONFIGURACIÓN DE PRODUCCIÓN ===
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true ); // Guarda errores en wp-content/debug.log
define( 'WP_DEBUG_DISPLAY', false );
@ini_set( 'display_errors', 0 );
/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
