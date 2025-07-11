<?php
/**
 * Automation by Installatron
 *
 * Handles Installatron automation tasks.
 *
 * @package WordPress
 * @since 1.0.0
 */

// This plugin was added because Automatic Updates, Automatic Backups, and other automation features are enabled.
// Do not remove this file unless you want to disable Installatron automation.

/**
 * Example function for Installatron automation.
 *
 * @since 1.0.0
 */
function installatron_automation_example() {
	// Example automation task.
}

// To block this plugin, create an empty file in the same directory named:
// block-automation-by-installatron.php

// Turn off WordPress automatic updates since these are managed externally.
// If you remove this to re-enable WordPress's automatic updates then it's
// advised to disable auto-updating in Installatron.
add_filter( 'auto_update_core', '__return_false', -9999 );
add_filter( 'allow_dev_auto_core_updates', '__return_false', -9999 );
add_filter( 'allow_minor_auto_core_updates', '__return_false', -9999 );
add_filter( 'allow_major_auto_core_updates', '__return_false', -9999 );
add_filter( 'auto_update_translation', '__return_false', -9999 );

// Disable WordPress site health test for Automatic Updates since these are
// managed externally by Installatron.
function installatron_filter_site_status_tests( $tests ) {
	unset( $tests['async']['background_updates'] );
	return $tests;
}
add_filter( 'site_status_tests', 'installatron_filter_site_status_tests' );
