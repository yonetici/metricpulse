<?php
/**
 * Main loader class for Trackly.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Trackly {

	protected $plugin_name;
	protected $version;

	public function __construct() {
		$this->plugin_name = 'trackly';
		$this->version = TRACKLY_VERSION;
	}

	public function run() {
		// Initialize Database and Cron hooks (Loaded via Autoloader)
		Trackly_DB::init();

		// Initialize Admin Hooks (Loaded via Autoloader)
		$admin = new Trackly_Admin( $this->plugin_name, $this->version );
		$admin->init_hooks();

		// Initialize Public (Frontend) Hooks (Loaded via Autoloader)
		$public = new Trackly_Public( $this->plugin_name, $this->version );
		$public->init_hooks();
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_version() {
		return $this->version;
	}
}

