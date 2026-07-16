<?php
/**
 * Trackly Uninstall Template.
 * Fired when the plugin is deleted from the WordPress admin.
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// 1. Delete Options
$options = array(
	'trackly_demo_mode',
	'trackly_property_id',
	'trackly_credentials',
	'trackly_sampling_rate',
	'trackly_secure_salt',
	'trackly_custom_events',
	'trackly_require_consent',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// 2. Clear Transients
delete_transient( 'g_token' );
delete_transient( 'trackly_access_token' );

global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_trackly_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_trackly_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_g_b_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_g_b_%'" );

// 3. Drop Custom Table
$table_name = $wpdb->prefix . 'trackly_clicks';
$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
