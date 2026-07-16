<?php
/**
 * Plugin Name:       Trackly
 * Plugin URI:        https://trackly.io
 * Description:       A modern, stunning Google Analytics 4 dashboard and page-level statistics client for WordPress with Heatmaps and AI Insights.
 * Version:           1.0.0
 * Author:            Trackly Team
 * Author URI:        https://trackly.io
 * License:           GPLv2 or later
 * Text Domain:       trackly
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'TRACKLY_VERSION', '1.0.0' );
define( 'TRACKLY_PATH', plugin_dir_path( __FILE__ ) );
define( 'TRACKLY_URL', plugin_dir_url( __FILE__ ) );

// 1. PSR-4 style Class Autoloader to support namespace class loading
spl_autoload_register( function ( $class ) {
	$prefix = 'Trackly\\';
	$base_dir = TRACKLY_PATH;

	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, $len );

	if ( strpos( $relative_class, '\\' ) === false ) {
		// Core classes mapped directly to includes/
		$file = $base_dir . 'includes/' . $relative_class . '.php';
	} else {
		// Namespaced sub-classes mapping to lowercase folder names (e.g., Admin\Admin -> admin/Admin.php)
		$parts = explode( '\\', $relative_class );
		$parts[0] = strtolower( $parts[0] );
		$file = $base_dir . implode( '/', $parts ) . '.php';
	}

	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

// Activate / Deactivate hooks
function activate_trackly() {
	// Trigger DB table creation
	Trackly\Database::create_tables();
	Trackly\Database::schedule_cleanup();

	// Generate a unique dynamic fallback encryption key if not exists (Enterprise Security)
	if ( ! get_option( 'trackly_secure_salt' ) ) {
		$secure_key = wp_generate_password( 64, true, true );
		update_option( 'trackly_secure_salt', $secure_key, 'no' ); // Saved with autoload=no
	}
}
register_activation_hook( __FILE__, 'activate_trackly' );

function deactivate_trackly() {
	Trackly\Database::unschedule_cleanup();
}
register_deactivation_hook( __FILE__, 'deactivate_trackly' );

// Run the plugin
function run_trackly() {
	$plugin = new Trackly\Core();
	$plugin->run();
}
run_trackly();

// Load textdomain for i18n support
function load_trackly_textdomain() {
	load_plugin_textdomain( 'trackly', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'load_trackly_textdomain' );

