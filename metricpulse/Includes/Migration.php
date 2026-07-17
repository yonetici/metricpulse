<?php
namespace MetricPulse\Includes;

/**
 * One-time migration from the legacy "trackly" internal prefix to "metricpulse".
 * Renames options, the click table, cron events, and capabilities in place so
 * existing installs keep their data and settings after the rebrand.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Migration {

	const FLAG   = 'metricpulse_migrated';
	const OLD    = 'trackly_';
	const NEW    = 'metricpulse_';

	/**
	 * Run the migration once. Guarded by an option flag so it never repeats.
	 */
	public static function maybe_migrate(): void {
		if ( get_option( self::FLAG ) ) {
			return;
		}

		global $wpdb;

		// 1. Rename real options (trackly_* -> metricpulse_*), skipping transients (they start with "_").
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$old_options = $wpdb->get_col(
			$wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( self::OLD ) . '%' )
		);
		if ( is_array( $old_options ) ) {
			foreach ( $old_options as $old_name ) {
				$new_name = self::NEW . substr( $old_name, strlen( self::OLD ) );
				if ( false === get_option( $new_name, false ) ) {
					add_option( $new_name, get_option( $old_name ) );
				}
				delete_option( $old_name );
			}
		}

		// 2. Rename the click telemetry table if the legacy one exists and the new one does not.
		$old_table = $wpdb->prefix . 'trackly_clicks';
		$new_table = $wpdb->prefix . 'metricpulse_clicks';
		if ( preg_match( '/^[a-zA-Z0-9_]+$/', $old_table ) && preg_match( '/^[a-zA-Z0-9_]+$/', $new_table ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$has_old = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $old_table ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$has_new = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $new_table ) );
			if ( $has_old && ! $has_new ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$wpdb->query( "RENAME TABLE `$old_table` TO `$new_table`" );
			}
		}

		// 3. Reschedule cron under the new hook names.
		wp_clear_scheduled_hook( 'trackly_daily_cleanup' );
		wp_clear_scheduled_hook( 'trackly_weekly_ip_refresh' );
		Database::schedule_cleanup();

		// 4. Migrate the dashboard-view capability on the default roles.
		foreach ( array( 'administrator', 'editor' ) as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				$role->add_cap( 'metricpulse_view_dashboard' );
				$role->remove_cap( 'trackly_view_dashboard' );
			}
		}

		// 5. Drop stale legacy transients (caches regenerate on demand).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( '_transient_trackly_' ) . '%' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( '_transient_timeout_trackly_' ) . '%' ) );

		update_option( self::FLAG, 1, 'no' );
	}
}
