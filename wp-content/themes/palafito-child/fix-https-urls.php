<?php
/**
 * Fix HTTPS URLs in Database
 * 
 * This script converts HTTP URLs to HTTPS in the WordPress database.
 * Run this once to fix Mixed Content warnings.
 * 
 * IMPORTANT: Backup your database before running this script!
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // If not loaded through WordPress, set up basic environment
    if (!file_exists('../../../wp-config.php')) {
        die('This script must be run from within WordPress or from the correct directory.');
    }
    require_once('../../../wp-config.php');
}

// Security check - only allow admin users
if (!current_user_can('administrator')) {
    die('Access denied. Administrator privileges required.');
}

global $wpdb;

echo "<h2>Fixing HTTP URLs to HTTPS</h2>";

// Tables to update
$tables_to_update = [
    $wpdb->posts => ['post_content', 'guid'],
    $wpdb->postmeta => ['meta_value'],
    $wpdb->options => ['option_value']
];

$total_updates = 0;

foreach ($tables_to_update as $table => $columns) {
    foreach ($columns as $column) {
        // Skip guid column for posts as it should remain unchanged
        if ($table === $wpdb->posts && $column === 'guid') {
            continue;
        }
        
        // Update HTTP URLs to HTTPS
        $sql = $wpdb->prepare(
            "UPDATE {$table} SET {$column} = REPLACE({$column}, %s, %s) WHERE {$column} LIKE %s",
            'http://palafitofood.com/',
            'https://palafitofood.com/',
            '%http://palafitofood.com/%'
        );
        
        $result = $wpdb->query($sql);
        
        if ($result !== false) {
            echo "<p>Updated {$result} rows in {$table}.{$column}</p>";
            $total_updates += $result;
        } else {
            echo "<p>Error updating {$table}.{$column}: " . $wpdb->last_error . "</p>";
        }
    }
}

// Clear any caches
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
}

echo "<h3>Total updates: {$total_updates}</h3>";
echo "<p><strong>Database update complete!</strong></p>";
echo "<p>Please clear any caching plugins and test your site.</p>";

// Self-destruct for security
echo "<p><em>This script will be deleted for security reasons.</em></p>";
unlink(__FILE__);
?> 