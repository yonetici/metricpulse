<?php
/**
 * Admin Panel controls and REST API handlers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Trackly_Admin {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function init_hooks() {
		// Admin Menu
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
		// Register Admin Assets
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_and_scripts' ) );
		// Register REST API Endpoints
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		// Register Settings API
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register settings under WordPress Settings API.
	 */
	public function register_settings() {
		register_setting( 'trackly_settings_group', 'trackly_demo_mode', array(
			'type'              => 'string',
			'default'           => 'yes',
			'sanitize_callback' => 'sanitize_text_field',
		) );
		register_setting( 'trackly_settings_group', 'trackly_property_id', array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		) );
		register_setting( 'trackly_settings_group', 'trackly_credentials', array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => array( $this, 'sanitize_and_encrypt_credentials' ),
		) );
		// Sampling rate option to prevent database table bloat
		register_setting( 'trackly_settings_group', 'trackly_sampling_rate', array(
			'type'              => 'string',
			'default'           => '100',
			'sanitize_callback' => 'sanitize_text_field',
		) );
	}

	/**
	 * Settings callback to validate JSON and encrypt private key.
	 */
	public function sanitize_and_encrypt_credentials( $value ) {
		$value = wp_unslash( trim( $value ) );
		if ( empty( $value ) ) {
			return '';
		}

		$decoded = json_decode( $value, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			// If it's already encrypted, keep it
			$decrypted = Trackly_API::decrypt_data( $value );
			if ( ! empty( $decrypted ) && null !== json_decode( $decrypted ) ) {
				return $value;
			}
			add_settings_error( 'trackly_credentials', 'invalid_json', __( 'Invalid JSON format.', 'trackly' ) );
			return get_option( 'trackly_credentials', '' );
		}

		// Save encrypted JSON
		return Trackly_API::encrypt_data( $value );
	}

	/**
	 * Register Admin Menu Page.
	 */
	public function add_plugin_admin_menu() {
		add_menu_page(
			__( 'Trackly', 'trackly' ),
			__( 'Trackly', 'trackly' ),
			'manage_options',
			$this->plugin_name,
			array( $this, 'display_plugin_admin_page' ),
			'dashicons-chart-area',
			30
		);
	}

	/**
	 * Enqueue Styles and Scripts for Admin page.
	 */
	public function enqueue_styles_and_scripts( $hook ) {
		if ( 'toplevel_page_trackly' !== $hook ) {
			return;
		}

		// Enqueue Localized ApexCharts (No longer loading from CDN)
		wp_enqueue_script( 'apexcharts', TRACKLY_URL . 'admin/js/vendor/apexcharts.min.js', array(), '3.41.0', true );

		// Local Admin CSS & JS
		wp_enqueue_style( $this->plugin_name . '-admin-css', TRACKLY_URL . 'admin/css/trackly-admin.css', array(), $this->version );
		wp_enqueue_script( $this->plugin_name . '-admin-js', TRACKLY_URL . 'admin/js/trackly-admin.js', array( 'jquery', 'apexcharts' ), $this->version, true );

		// Localize Script for REST API URL & Nonce
		wp_localize_script( $this->plugin_name . '-admin-js', 'tracklyData', array(
			'rest_url'   => esc_url_raw( rest_url( 'trackly/v1' ) ),
			'rest_nonce' => wp_create_nonce( 'wp_rest' ),
		) );
	}

	/**
	 * Render the main Admin Dashboard page.
	 */
	public function display_plugin_admin_page() {
		// Clear GA transient cache if settings just updated & verify authority
		if ( isset( $_GET['settings-updated'] ) && current_user_can( 'manage_options' ) ) {
			delete_transient( 'g_token' );
			global $wpdb;
			$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_g_b_%'" );
		}

		$demo_mode = get_option( 'trackly_demo_mode', 'yes' );
		$property_id = get_option( 'trackly_property_id', '' );
		$credentials_encrypted = get_option( 'trackly_credentials', '' );
		$sampling_rate = get_option( 'trackly_sampling_rate', '100' );
		
		// Decrypt credentials to show in textarea
		$credentials = Trackly_API::decrypt_data( $credentials_encrypted );
		$is_connected = ! empty( $property_id ) && ! empty( $credentials_encrypted );

		?>
		<div class="trackly-admin-wrapper">
			<!-- Settings errors output added here (Fixes hidden validation errors QA bug) -->
			<?php settings_errors( 'trackly_credentials' ); ?>

			<!-- Header -->
			<header class="trackly-header">
				<div class="trackly-logo-area">
					<span class="dashicons dashicons-chart-area trackly-logo-icon"></span>
					<h1><?php _e( 'Trackly', 'trackly' ); ?> <span class="trackly-badge">v<?php echo esc_html( $this->version ); ?></span></h1>
				</div>
				<div class="trackly-status-indicator">
					<?php if ( $demo_mode === 'yes' ) : ?>
						<span class="trackly-status demo"><span class="dot"></span> <?php _e( 'Demo Mode Active', 'trackly' ); ?></span>
					<?php elseif ( $is_connected ) : ?>
						<span class="trackly-status connected"><span class="dot"></span> <?php _e( 'GA4 Connected', 'trackly' ); ?></span>
					<?php else : ?>
						<span class="trackly-status disconnected"><span class="dot"></span> <?php _e( 'GA4 Disconnected', 'trackly' ); ?></span>
					<?php endif; ?>
				</div>
			</header>

			<!-- Tabs/Navigation -->
			<div class="trackly-tabs">
				<button class="trackly-tab-btn active" data-target="dashboard-tab">
					<span class="dashicons dashicons-dashboard"></span> <?php _e( 'Dashboard', 'trackly' ); ?>
				</button>
				<button class="trackly-tab-btn" data-target="settings-tab">
					<span class="dashicons dashicons-admin-generic"></span> <?php _e( 'Settings', 'trackly' ); ?>
				</button>
			</div>

			<!-- Dashboard Tab Content -->
			<div id="dashboard-tab" class="trackly-tab-content active">
				<!-- Real-time Banner -->
				<div class="trackly-card trackly-realtime-card">
					<div class="trackly-realtime-content">
						<h3><?php _e( 'Active Visitors', 'trackly' ); ?></h3>
						<div class="trackly-realtime-value">
							<span id="trackly-active-users">--</span>
							<span class="pulse-ring"></span>
						</div>
						<p><?php _e( 'Active Visitors', 'trackly' ); ?></p>
					</div>
					<div class="trackly-realtime-spark"></div>
				</div>

				<!-- Stats Grid -->
				<div class="trackly-grid">
					<div class="trackly-card stat-card">
						<div class="stat-info">
							<h4><?php _e( 'Pageviews', 'trackly' ); ?></h4>
							<h2 id="trackly-stat-views">--</h2>
						</div>
						<span class="dashicons dashicons-visibility stat-icon views"></span>
					</div>
					<div class="trackly-card stat-card">
						<div class="stat-info">
							<h4><?php _e( 'Unique Visitors', 'trackly' ); ?></h4>
							<h2 id="trackly-stat-users">--</h2>
						</div>
						<span class="dashicons dashicons-groups stat-icon users"></span>
					</div>
					<div class="trackly-card stat-card">
						<div class="stat-info">
							<h4><?php _e( 'Bounce Rate (Unengaged)', 'trackly' ); ?></h4>
							<h2 id="trackly-stat-bounce">--</h2>
						</div>
						<span class="dashicons dashicons-exit stat-icon bounce"></span>
					</div>
					<div class="trackly-card stat-card">
						<div class="stat-info">
							<h4><?php _e( 'Average Session Duration', 'trackly' ); ?></h4>
							<h2 id="trackly-stat-duration">--</h2>
						</div>
						<span class="dashicons dashicons-clock stat-icon duration"></span>
					</div>
				</div>

				<!-- GA4 Processing Latency Alert Box -->
				<div class="trackly-card trackly-latency-notice">
					<span class="dashicons dashicons-info trackly-notice-icon"></span>
					<div class="trackly-notice-text">
						<strong><?php _e( 'GA4 Data Latency Notice', 'trackly' ); ?></strong>
						<p><?php _e( 'Google Analytics 4 (GA4) requires 24-48 hours to process data. Therefore, traffic metrics for yesterday and today may appear lower or incomplete. This latency is temporary.', 'trackly' ); ?></p>
					</div>
				</div>

				<!-- Main Graph -->
				<div class="trackly-card main-chart-card">
					<div class="chart-header">
						<h3><?php _e( 'Visitor Traffic Trend', 'trackly' ); ?></h3>
						<div class="chart-actions">
							<button class="trackly-chart-filter-btn active" data-days="7"><?php _e( 'Last 7 Days', 'trackly' ); ?></button>
							<button class="trackly-chart-filter-btn" data-days="30"><?php _e( 'Last 30 Days', 'trackly' ); ?></button>
						</div>
					</div>
					<div class="chart-container">
						<div id="trackly-main-chart"></div>
					</div>
				</div>

				<!-- Bottom Grid (Sources & Devices) -->
				<div class="trackly-grid double">
					<div class="trackly-card chart-half">
						<h3><?php _e( 'Traffic Sources', 'trackly' ); ?></h3>
						<div id="trackly-source-chart"></div>
					</div>
					<div class="trackly-card chart-half">
						<h3><?php _e( 'Device Distribution', 'trackly' ); ?></h3>
						<div id="trackly-device-chart"></div>
					</div>
				</div>

				<!-- Top Pages Table -->
				<div class="trackly-card table-card">
					<h3><?php _e( 'Top Viewed Pages', 'trackly' ); ?></h3>
					<div class="trackly-table-wrapper">
						<table class="trackly-table" id="trackly-pages-table">
							<thead>
								<tr>
									<th><?php _e( 'Page URL', 'trackly' ); ?></th>
									<th><?php _e( 'Pageviews', 'trackly' ); ?></th>
									<th><?php _e( 'Users', 'trackly' ); ?></th>
									<th><?php _e( 'Bounce Rate', 'trackly' ); ?></th>
									<th><?php _e( 'Avg. Duration', 'trackly' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td colspan="5" class="loading-td"><?php _e( 'Loading...', 'trackly' ); ?></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>

			<!-- Settings Tab Content (WordPress Settings API Integrated) -->
			<div id="settings-tab" class="trackly-tab-content">
				<div class="trackly-card settings-card">
					<h3><?php _e( 'Google Analytics Integration Settings', 'trackly' ); ?></h3>
					<form method="post" action="options.php">
						<?php
						settings_fields( 'trackly_settings_group' );
						do_settings_sections( 'trackly_settings_group' );
						?>

						<div class="trackly-form-group">
							<label class="trackly-switch-label">
								<input type="checkbox" name="trackly_demo_mode" value="yes" <?php checked( $demo_mode, 'yes' ); ?>>
								<span class="trackly-switch-slider"></span>
								<div class="label-text">
									<strong><?php _e( 'Enable Demo (Mock) Mode', 'trackly' ); ?></strong>
									<p class="description"><?php _e( 'Keep this enabled to test the plugin with mock data without connecting to Google Analytics.', 'trackly' ); ?></p>
								</div>
							</label>
						</div>

						<div class="trackly-form-group">
							<label class="trackly-switch-label">
								<input type="hidden" name="trackly_demo_mode" value="no" style="display:none;">
							</label>
							<label for="trackly_property_id"><?php _e( 'GA4 Property ID', 'trackly' ); ?></label>
							<input type="text" id="trackly_property_id" name="trackly_property_id" value="<?php echo esc_attr( $property_id ); ?>" placeholder="e.g. 382901248" class="regular-text">
							<p class="description"><?php _e( 'The numeric Property ID of your Google Analytics property (Can be found in Admin > Property Settings).', 'trackly' ); ?></p>
						</div>

						<div class="trackly-form-group">
							<label for="trackly_credentials"><?php _e( 'Service Account JSON Key', 'trackly' ); ?></label>
							<textarea id="trackly_credentials" name="trackly_credentials" rows="8" class="large-text code" placeholder='{"type": "service_account", ...}'><?php echo esc_textarea( $credentials ); ?></textarea>
							<p class="description"><?php _e( 'Paste the contents of the Service Account JSON key file you created in the Google Cloud Console. Make sure to add this service account\'s email as a Viewer to your GA4 property.', 'trackly' ); ?></p>
						</div>

						<!-- Sampling Rate Option Selector to prevent DB bloat -->
						<div class="trackly-form-group">
							<label for="trackly_sampling_rate"><?php _e( 'Click Sampling Rate', 'trackly' ); ?></label>
							<select id="trackly_sampling_rate" name="trackly_sampling_rate" style="width: 100%; max-width: 25rem; height: 2.5rem; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px;">
								<option value="100" <?php selected( $sampling_rate, '100' ); ?>><?php _e( '100% (Record all clicks)', 'trackly' ); ?></option>
								<option value="50" <?php selected( $sampling_rate, '50' ); ?>><?php _e( '50% (Record half of all clicks)', 'trackly' ); ?></option>
								<option value="25" <?php selected( $sampling_rate, '25' ); ?>><?php _e( '25% (Record a quarter of all clicks)', 'trackly' ); ?></option>
								<option value="10" <?php selected( $sampling_rate, '10' ); ?>><?php _e( '10% (Record only 10% of clicks - Recommended for high traffic sites)', 'trackly' ); ?></option>
							</select>
							<p class="description"><?php _e( 'For high traffic sites, you can reduce the click tracking frequency to prevent database bloat. Sampling runs on a per-session basis.', 'trackly' ); ?></p>
						</div>

						<button type="submit" class="trackly-btn trackly-btn-primary"><?php _e( 'Save Settings', 'trackly' ); ?></button>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Register WordPress REST API Routes.
	 */
	public function register_rest_routes() {
		register_rest_route( 'trackly/v1', '/stats', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_stats_callback' ),
			'permission_callback' => array( $this, 'check_admin_permissions' ),
		) );

		register_rest_route( 'trackly/v1', '/realtime', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_realtime_callback' ),
			'permission_callback' => array( $this, 'check_admin_permissions' ),
		) );

		register_rest_route( 'trackly/v1', '/page-stats', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_page_stats_callback' ),
			'permission_callback' => array( $this, 'check_admin_permissions' ),
		) );

		register_rest_route( 'trackly/v1', '/record-click', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'record_click_callback' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( 'trackly/v1', '/clicks', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_clicks_callback' ),
			'permission_callback' => array( $this, 'check_admin_permissions' ),
		) );

		register_rest_route( 'trackly/v1', '/save-event', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'save_event_callback' ),
			'permission_callback' => array( $this, 'check_admin_permissions' ),
		) );
	}

	/**
	 * Verify if current user is admin.
	 */
	public function check_admin_permissions() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Robust client IP retriever.
	 */
	private function get_ip_address() {
		foreach ( array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR' ) as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
					$ip = trim( $ip );
					if ( filter_var( $ip, FILTER_VALIDATE_IP ) !== false ) {
						return $ip;
					}
				}
			}
		}
		return '0.0.0.0';
	}

	/**
	 * REST Callback for overall site statistics.
	 */
	public function get_stats_callback( $request ) {
		$days = intval( $request->get_param( 'days' ) );
		if ( ! in_array( $days, array( 7, 30 ) ) ) {
			$days = 7;
		}

		$start_date = $days === 30 ? '30daysAgo' : '7daysAgo';

		$summary_req = array(
			'dateRanges' => array( array( 'startDate' => $start_date, 'endDate' => 'yesterday' ) ),
			'metrics'    => array(
				array( 'name' => 'screenPageViews' ),
				array( 'name' => 'activeUsers' ),
				array( 'name' => 'bounceRate' ),
				array( 'name' => 'averageSessionDuration' ),
			),
		);

		$chart_req = array(
			'dateRanges' => array( array( 'startDate' => $start_date, 'endDate' => 'yesterday' ) ),
			'dimensions' => array( array( 'name' => 'date' ) ),
			'metrics'    => array(
				array( 'name' => 'screenPageViews' ),
				array( 'name' => 'activeUsers' ),
				array( 'name' => 'bounceRate' ),
				array( 'name' => 'averageSessionDuration' ),
			),
		);

		$sources_req = array(
			'dateRanges' => array( array( 'startDate' => $start_date, 'endDate' => 'yesterday' ) ),
			'dimensions' => array( array( 'name' => 'sessionDefaultChannelGroup' ) ),
			'metrics'    => array( array( 'name' => 'activeUsers' ) ),
		);

		$devices_req = array(
			'dateRanges' => array( array( 'startDate' => $start_date, 'endDate' => 'yesterday' ) ),
			'dimensions' => array( array( 'name' => 'deviceCategory' ) ),
			'metrics'    => array( array( 'name' => 'activeUsers' ) ),
		);

		$pages_req = array(
			'dateRanges' => array( array( 'startDate' => $start_date, 'endDate' => 'yesterday' ) ),
			'dimensions' => array( array( 'name' => 'pagePath' ) ),
			'metrics'    => array(
				array( 'name' => 'screenPageViews' ),
				array( 'name' => 'activeUsers' ),
				array( 'name' => 'bounceRate' ),
				array( 'name' => 'averageSessionDuration' ),
			),
		);

		$batch_report = Trackly_API::batch_run_reports( array(
			$summary_req,
			$chart_req,
			$sources_req,
			$devices_req,
			$pages_req,
		) );

		if ( is_wp_error( $batch_report ) ) {
			return new WP_REST_Response( array( 'success' => false, 'error' => $batch_report->get_error_message() ), 500 );
		}

		$realtime_users = Trackly_API::get_realtime_users();

		return new WP_REST_Response( array(
			'success'        => true,
			'summary'        => isset( $batch_report['reports'][0] ) ? $batch_report['reports'][0] : array(),
			'chart'          => isset( $batch_report['reports'][1] ) ? $batch_report['reports'][1] : array(),
			'sources'        => isset( $batch_report['reports'][2] ) ? $batch_report['reports'][2] : array(),
			'devices'        => isset( $batch_report['reports'][3] ) ? $batch_report['reports'][3] : array(),
			'pages'          => isset( $batch_report['reports'][4] ) ? $batch_report['reports'][4] : array(),
			'realtime_users' => $realtime_users,
		), 200 );
	}

	/**
	 * REST Callback for lightweight realtime polling.
	 */
	public function get_realtime_callback( $request ) {
		$realtime_users = Trackly_API::get_realtime_users();
		return new WP_REST_Response( array(
			'success'        => true,
			'realtime_users' => $realtime_users,
		), 200 );
	}

	/**
	 * REST Callback for single page stats.
	 */
	public function get_page_stats_callback( $request ) {
		$url = esc_url_raw( $request->get_param( 'url' ) );
		if ( empty( $url ) ) {
			return new WP_REST_Response( array( 'success' => false, 'error' => __( 'URL parameter required.', 'trackly' ) ), 400 );
		}

		$path = wp_parse_url( $url, PHP_URL_PATH );
		if ( empty( $path ) ) {
			$path = '/';
		}

		$report = Trackly_API::get_report( array(
			'dateRanges'      => array( array( 'startDate' => '7daysAgo', 'endDate' => 'yesterday' ) ),
			'dimensions'      => array( array( 'name' => 'pagePath' ) ),
			'metrics'         => array(
				array( 'name' => 'screenPageViews' ),
				array( 'name' => 'activeUsers' ),
				array( 'name' => 'bounceRate' ),
				array( 'name' => 'averageSessionDuration' ),
			),
			'dimensionFilter' => array(
				'filter' => array(
					'fieldName' => 'pagePath',
					'stringFilter' => array(
						'matchType' => 'EXACT',
						'value'     => $path,
					),
				),
			),
		) );

		if ( is_wp_error( $report ) ) {
			return new WP_REST_Response( array( 'success' => false, 'error' => $report->get_error_message() ), 500 );
		}

		return new WP_REST_Response( array(
			'success' => true,
			'path'    => $path,
			'report'  => $report,
		), 200 );
	}

	/**
	 * REST Callback to record a visitor click.
	 */
	public function record_click_callback( $request ) {
		$ip = $this->get_ip_address();
		$transient_key = 'trackly_rate_' . md5( $ip );
		$clicks = get_transient( $transient_key );

		if ( false === $clicks ) {
			set_transient( $transient_key, 1, 60 );
		} elseif ( $clicks >= 10 ) {
			return new WP_REST_Response( array( 'success' => false, 'error' => __( 'Rate limit exceeded.', 'trackly' ) ), 429 );
		} else {
			set_transient( $transient_key, $clicks + 1, 60 );
		}

		$params = $request->get_json_params();
		if ( empty( $params['page_url'] ) || ! isset( $params['click_x_pct'] ) || ! isset( $params['click_y_pct'] ) ) {
			return new WP_REST_Response( array( 'success' => false, 'error' => __( 'Invalid click data.', 'trackly' ) ), 400 );
		}

		$log_result = Trackly_DB::log_click( array(
			'page_url'         => $params['page_url'],
			'element_tag'      => isset( $params['element_tag'] ) ? sanitize_text_field( $params['element_tag'] ) : 'unknown',
			'element_selector' => isset( $params['element_selector'] ) ? sanitize_text_field( $params['element_selector'] ) : '',
			'click_x_pct'      => floatval( $params['click_x_pct'] ),
			'click_y_pct'      => floatval( $params['click_y_pct'] ),
		) );

		return new WP_REST_Response( array( 'success' => (bool) $log_result ), 200 );
	}

	/**
	 * REST Callback to retrieve clicks.
	 */
	public function get_clicks_callback( $request ) {
		$url = esc_url_raw( $request->get_param( 'url' ) );
		if ( empty( $url ) ) {
			return new WP_REST_Response( array( 'success' => false, 'error' => __( 'URL parameter required.', 'trackly' ) ), 400 );
		}

		$clicks = Trackly_DB::get_clicks_for_page( $url );

		return new WP_REST_Response( array(
			'success' => true,
			'clicks'  => $clicks,
		), 200 );
	}

	/**
	 * REST Callback to save GA4 event mapping.
	 * Relaxed regex to only block HTML tag enjections '<' and '>', resolving quotes regression.
	 */
	public function save_event_callback( $request ) {
		$params = $request->get_json_params();
		if ( empty( $params['selector'] ) || empty( $params['event_name'] ) ) {
			return new WP_REST_Response( array( 'success' => false, 'error' => __( 'Selector and Event Name required.', 'trackly' ) ), 400 );
		}

		$selector = sanitize_text_field( $params['selector'] );
		// Allows single and double quotes for attribute selectors, blocks HTML brackets
		if ( preg_match( '/[<>]/i', $selector ) ) {
			return new WP_REST_Response( array( 'success' => false, 'error' => __( 'Invalid CSS Selector payload.', 'trackly' ) ), 400 );
		}

		$saved_events = get_option( 'trackly_custom_events', array() );
		$saved_events[] = array(
			'selector'   => $selector,
			'event_name' => sanitize_key( $params['event_name'] ),
			'created_at' => current_time( 'mysql' ),
		);

		update_option( 'trackly_custom_events', $saved_events );

		return new WP_REST_Response( array( 'success' => true, 'events' => $saved_events ), 200 );
	}
}
