<?php
/**
 * MetricPulse Uninstall Handler.
 * Fired when the plugin is deleted from the WordPress admin.
 *
 * Data is only removed for sites that explicitly opted in via the
 * "Delete all data on uninstall" setting. Multisite installs are handled per-site.
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove all MetricPulse data for the current site, but only if the site opted in.
 */
function metricpulse_uninstall_site() {
	global $wpdb;

	// Cron hooks are site-scoped; always clear them so no orphaned schedules remain.
	wp_clear_scheduled_hook( 'metricpulse_daily_cleanup' );
	wp_clear_scheduled_hook( 'metricpulse_weekly_ip_refresh' );

	// Respect the user's choice: preserve data unless deletion was explicitly enabled.
	if ( 'yes' !== get_option( 'metricpulse_delete_data', 'no' ) ) {
		return;
	}

	$metricpulse_options = array(
		'metricpulse_demo_mode',
		'metricpulse_property_id',
		'metricpulse_credentials',
		'metricpulse_sampling_rate',
		'metricpulse_secure_salt',
		'metricpulse_custom_events',
		'metricpulse_require_consent',
		'metricpulse_delete_data',
		'metricpulse_cf_proxies',
		'metricpulse_cleanup_lock',
		'metricpulse_ip_refresh_lock',
		'metricpulse_db_version',
		'metricpulse_cache_ver',
	);
	foreach ( $metricpulse_options as $metricpulse_option ) {
		delete_option( $metricpulse_option );
	}

	// Catch any remaining prefixed options and transients. Patterns are built with esc_like()
	// and bound through prepare() (LIKE takes a bindable value, unlike a table identifier).
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( 'metricpulse_' ) . '%' ) );

	// Clear transients.
	delete_transient( 'metricpulse_access_token' );
	delete_transient( 'metricpulse_realtime_cache' );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( '_transient_metricpulse_' ) . '%' ) );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( '_transient_timeout_metricpulse_' ) . '%' ) );

	// Drop the custom table for this site (table name uses the site's prefix).
	$metricpulse_table_name = $wpdb->prefix . 'metricpulse_clicks';
	if ( preg_match( '/^[a-zA-Z0-9_]+$/', $metricpulse_table_name ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$wpdb->query( "DROP TABLE IF EXISTS $metricpulse_table_name" );
	}
}

if ( is_multisite() ) {
	// Iterate every site so no sub-site is left with orphaned tables/options.
	$metricpulse_site_ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );
	foreach ( $metricpulse_site_ids as $metricpulse_site_id ) {
		switch_to_blog( $metricpulse_site_id );
		metricpulse_uninstall_site();
		restore_current_blog();
	}
} else {
	metricpulse_uninstall_site();
}
