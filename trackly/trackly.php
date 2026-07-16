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

// 1. PSR-4 style Class Autoloader to remove procedural require_once calls
spl_autoload_register( function ( $class_name ) {
	// Only load classes belonging to this plugin
	if ( strpos( $class_name, 'Trackly' ) === 0 ) {
		$class_slug = strtolower( str_replace( '_', '-', $class_name ) );
		$file_name = 'class-' . $class_slug . '.php';

		// Determine module directory based on naming slug
		if ( strpos( $class_slug, 'trackly-admin' ) === 0 ) {
			$file = TRACKLY_PATH . 'admin/' . $file_name;
		} elseif ( strpos( $class_slug, 'trackly-public' ) === 0 ) {
			$file = TRACKLY_PATH . 'public/' . $file_name;
		} else {
			$file = TRACKLY_PATH . 'includes/' . $file_name;
		}

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
} );

// Activate / Deactivate hooks
function activate_trackly() {
	// Trigger DB table creation
	Trackly_DB::create_tables();
	Trackly_DB::schedule_cleanup();

	// Generate a unique dynamic fallback encryption key if not exists (Enterprise Security)
	if ( ! get_option( 'trackly_secure_salt' ) ) {
		$secure_key = wp_generate_password( 64, true, true );
		update_option( 'trackly_secure_salt', $secure_key, 'no' ); // Saved with autoload=no
	}
}
register_activation_hook( __FILE__, 'activate_trackly' );

function deactivate_trackly() {
	Trackly_DB::unschedule_cleanup();
}
register_deactivation_hook( __FILE__, 'deactivate_trackly' );

// Run the plugin
function run_trackly() {
	$plugin = new Trackly();
	$plugin->run();
}
run_trackly();

// Load textdomain for i18n support
function load_trackly_textdomain() {
	load_plugin_textdomain( 'trackly', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'load_trackly_textdomain' );

