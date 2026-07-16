<?php
namespace Trackly;

use Trackly\Admin\Admin;
use Trackly\Frontend\Tracker;

/**
 * Main loader class for Trackly.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Core {

	protected $plugin_name;
	protected $version;

	public function __construct() {
		$this->plugin_name = 'trackly';
		$this->version = TRACKLY_VERSION;
	}

	public function run() {
		// Initialize Database and Cron hooks (Loaded via Autoloader)
		Database::init();

		// Initialize Admin Hooks (Loaded via Autoloader)
		$admin = new Admin( $this->plugin_name, $this->version );
		$admin->init_hooks();

		// Initialize Public (Frontend) Hooks (Loaded via Autoloader)
		$public = new Tracker( $this->plugin_name, $this->version );
		$public->init_hooks();
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_version() {
		return $this->version;
	}
}

