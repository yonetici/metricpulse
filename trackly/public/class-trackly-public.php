<?php
/**
 * Public Front-End hooks and rendering handlers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Trackly_Public {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function init_hooks() {
		// Enqueue scripts & styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_and_scripts' ) );

		// Render Floating Analytics Bar in footer
		add_action( 'wp_footer', array( $this, 'render_floating_stats_bar' ) );

		// Enqueue GA4 Custom event tags if any exist
		add_action( 'wp_head', array( $this, 'inject_custom_ga4_events' ) );
	}

	/**
	 * Enqueue Frontend Assets.
	 */
	public function enqueue_styles_and_scripts() {
		global $wp;
		$current_url = home_url( add_query_arg( array(), $wp->request ) );
		$current_url = trailingslashit( $current_url );

		$sampling_rate = get_option( 'trackly_sampling_rate', '100' );
		$require_consent = get_option( 'trackly_require_consent', 'yes' ) === 'yes' ? 1 : 0;

		// 1. Enqueue lightweight click tracker script for EVERYONE (no jQuery dependency)
		wp_enqueue_script( $this->plugin_name . '-tracker-js', TRACKLY_URL . 'public/js/trackly-tracker.js', array(), $this->version, true );
		
		wp_localize_script( $this->plugin_name . '-tracker-js', 'tracklyTrackerData', array(
			'rest_url'        => esc_url_raw( rest_url( 'trackly/v1' ) ),
			'page_url'        => $current_url,
			'sampling_rate'   => intval( $sampling_rate ), // Passes rate (e.g. 10, 25, 50, 100)
			'require_consent' => $require_consent,
			'nonce'           => wp_create_nonce( 'trackly_public_clicks' ),
		) );

		// 2. Load heavy admin panel JS/CSS ONLY for logged-in administrators (Core Web Vitals Optimisation)
		if ( current_user_can( 'manage_options' ) ) {
			wp_enqueue_style( $this->plugin_name . '-public-css', TRACKLY_URL . 'public/css/trackly-public.css', array(), $this->version );
			wp_enqueue_script( $this->plugin_name . '-public-js', TRACKLY_URL . 'public/js/trackly-public.js', array( 'jquery' ), $this->version, true );

			wp_localize_script( $this->plugin_name . '-public-js', 'tracklyPublicData', array(
				'rest_url'   => esc_url_raw( rest_url( 'trackly/v1' ) ),
				'rest_nonce' => wp_create_nonce( 'wp_rest' ),
				'page_url'   => $current_url,
				'is_admin'   => 1,
				'admin_url'  => esc_url( admin_url( 'admin.php?page=' . $this->plugin_name ) ),
			) );
		}
	}

	/**
	 * Render the gorgeous glassmorphism overlay bar in the footer for administrators.
	 */
	public function render_floating_stats_bar() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wp;
		$current_path = wp_parse_url( home_url( add_query_arg( array(), $wp->request ) ), PHP_URL_PATH );
		if ( empty( $current_path ) ) {
			$current_path = '/';
		}

		?>
		<div id="trackly-stats-bar-wrapper">
			<!-- Floating Toggle Button -->
			<button id="trackly-stats-toggle-btn" title="<?php esc_attr_e( 'Trackly', 'trackly' ); ?>">
				<span class="dashicons dashicons-chart-area"></span>
			</button>

			<!-- Main Panel -->
			<div id="trackly-stats-panel">
				<!-- Panel Header -->
				<div class="trackly-panel-header">
					<div class="trackly-panel-logo">
						<span class="dashicons dashicons-chart-area"></span>
						<h4><?php _e( 'Trackly', 'trackly' ); ?></h4>
					</div>
					<div class="trackly-panel-controls">
						<button id="trackly-panel-minimize-btn" title="<?php esc_attr_e( 'Hide', 'trackly' ); ?>">
							<span class="dashicons dashicons-minus"></span>
						</button>
					</div>
				</div>

				<!-- Tabs -->
				<div class="trackly-panel-tabs">
					<button class="trackly-panel-tab active" data-tab="stats"><?php _e( 'Statistics', 'trackly' ); ?></button>
					<button class="trackly-panel-tab" data-tab="heatmap"><?php _e( 'Click Heatmap', 'trackly' ); ?></button>
					<button class="trackly-panel-tab" data-tab="builder"><?php _e( 'Event Builder', 'trackly' ); ?></button>
					<button class="trackly-panel-tab" data-tab="ai"><?php _e( 'AI Analysis', 'trackly' ); ?></button>
				</div>

				<!-- Stats Tab Content -->
				<div class="trackly-panel-tab-content active" id="trackly-tab-stats">
					<p class="trackly-url-indicator"><?php _e( 'This Page:', 'trackly' ); ?> <code><?php echo esc_html( $current_path ); ?></code></p>
					
					<div class="trackly-panel-metrics-grid">
						<div class="trackly-panel-metric-card">
							<span class="label"><?php _e( 'Pageviews', 'trackly' ); ?></span>
							<h3 id="trackly-p-views">--</h3>
						</div>
						<div class="trackly-panel-metric-card">
							<span class="label"><?php _e( 'Users', 'trackly' ); ?></span>
							<h3 id="trackly-p-users">--</h3>
						</div>
						<div class="trackly-panel-metric-card">
							<span class="label"><?php _e( 'Bounce', 'trackly' ); ?></span>
							<h3 id="trackly-p-bounce">--</h3>
						</div>
						<div class="trackly-panel-metric-card">
							<span class="label"><?php _e( 'Avg. Duration', 'trackly' ); ?></span>
							<h3 id="trackly-p-duration">--</h3>
						</div>
					</div>

					<div class="trackly-panel-info-box">
						<span class="dashicons dashicons-info"></span>
						<p><?php _e( 'Data reflects the average of the last 7 days. Update interval: 1 hour.', 'trackly' ); ?></p>
					</div>
				</div>

				<!-- Heatmap Tab Content -->
				<div class="trackly-panel-tab-content" id="trackly-tab-heatmap">
					<h5><?php _e( 'Local Click Heatmap', 'trackly' ); ?></h5>
					<p><?php _e( 'Visually track the click density of elements on this page.', 'trackly' ); ?></p>
					
					<div class="trackly-action-buttons">
						<button id="trackly-toggle-heatmap-btn" class="trackly-p-btn">
							<span class="dashicons dashicons-visibility"></span> <?php _e( 'Show Heatmap', 'trackly' ); ?>
						</button>
						<button id="trackly-clear-heatmap-btn" class="trackly-p-btn secondary"><?php _e( 'Clear', 'trackly' ); ?></button>
					</div>
					<div class="heatmap-info-stats" style="margin-top: 10px; font-size: 12px; display:none;">
						<?php _e( 'Recorded Clicks:', 'trackly' ); ?> <strong id="trackly-heatmap-click-count">0</strong>
					</div>
				</div>

				<!-- Event Builder Tab Content -->
				<div class="trackly-panel-tab-content" id="trackly-tab-builder">
					<h5><?php _e( 'GA4 Event Builder', 'trackly' ); ?></h5>
					<p><?php _e( 'Create custom GA4 tracking events by selecting buttons or links on the page.', 'trackly' ); ?></p>
					
					<div id="trackly-builder-setup">
						<button id="trackly-start-selector-btn" class="trackly-p-btn">
							<span class="dashicons dashicons-mouse"></span> <?php _e( 'Start Element Selection', 'trackly' ); ?>
						</button>
						<p class="selector-notice description"><?php _e( 'Click the button, then hover over any button/link you wish to track on the page.', 'trackly' ); ?></p>
					</div>

					<div id="trackly-builder-form" style="display: none;">
						<div class="trackly-p-form-group">
							<label><?php _e( 'Selected Element:', 'trackly' ); ?></label>
							<code id="trackly-selected-selector-display">div > a.btn</code>
						</div>
						<div class="trackly-p-form-group">
							<label for="trackly-p-event-name"><?php _e( 'GA4 Event Name:', 'trackly' ); ?></label>
							<input type="text" id="trackly-p-event-name" placeholder="<?php esc_attr_e( 'e.g., cta_button_click', 'trackly' ); ?>">
						</div>
						<div class="trackly-action-buttons">
							<button id="trackly-save-event-btn" class="trackly-p-btn"><?php _e( 'Save', 'trackly' ); ?></button>
							<button id="trackly-cancel-event-btn" class="trackly-p-btn secondary"><?php _e( 'Cancel', 'trackly' ); ?></button>
						</div>
					</div>
				</div>

				<!-- AI Insights Tab Content -->
				<div class="trackly-panel-tab-content" id="trackly-tab-ai">
					<h5><?php _e( 'AI-Powered Page Analysis', 'trackly' ); ?></h5>
					<div class="trackly-ai-container">
						<div id="trackly-ai-insights-content">
							<div class="ai-insight-item">
								<span class="dashicons dashicons-awards ai-icon purple"></span>
								<div class="ai-text">
									<strong><?php _e( 'Content Performance', 'trackly' ); ?></strong>
									<p><?php _e( 'AI analysis is calculating page statistics...', 'trackly' ); ?></p>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Footer links -->
				<div class="trackly-panel-footer">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $this->plugin_name ) ); ?>" target="_blank">
						<span class="dashicons dashicons-external"></span> <?php _e( 'Go to Dashboard', 'trackly' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Inject custom tracked GA4 events into page header.
	 */
	public function inject_custom_ga4_events() {
		$saved_events = get_option( 'trackly_custom_events', array() );
		if ( empty( $saved_events ) ) {
			return;
		}

		$json_events = wp_json_encode( $saved_events, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
		if ( false === $json_events ) {
			return;
		}

		?>
		<!-- Trackly Custom GA4 Tracking Events -->
		<script type="text/javascript">
			document.addEventListener('DOMContentLoaded', function() {
				const customEvents = <?php echo $json_events; ?>;
				if (!customEvents || !Array.isArray(customEvents)) return;

				customEvents.forEach(function(item) {
					document.querySelectorAll(item.selector).forEach(function(el) {
						el.addEventListener('click', function() {
							if (typeof gtag === 'function') {
								gtag('event', item.event_name, {
									'event_category': 'trackly_custom',
									'event_label': item.selector
								});
								console.log('Trackly GA4 Event Tracked: ' + item.event_name);
							}
						});
					});
				});
			});
		</script>
		<?php
	}
}
