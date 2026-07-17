<?php
use PHPUnit\Framework\TestCase;
use Trackly\Includes\Service\GoogleAnalyticsService;

/**
 * GoogleAnalyticsServiceTest runs unit and integration checks for GA4 connection.
 */
class GoogleAnalyticsServiceTest extends TestCase {

	private $service;

	protected function setUp(): void {
		// Mock encryption salt
		update_option( 'trackly_secure_salt', str_repeat( 's', 64 ) );
		$this->service = new GoogleAnalyticsService();
	}

	public function test_is_demo_mode_default() {
		update_option( 'trackly_demo_mode', 'yes' );
		$this->assertTrue( $this->service->is_demo_mode() );
	}

	public function test_is_demo_mode_false_when_configured() {
		update_option( 'trackly_demo_mode', 'no' );
		update_option( 'trackly_property_id', '123456789' );
		
		// Encrypt valid dummy JSON credentials so decryption passes
		$encrypted = $this->service->encrypt_data( '{"private_key": "mysecretkey", "client_email": "myemail"}' );
		update_option( 'trackly_credentials', $encrypted );
		
		$this->assertFalse( $this->service->is_demo_mode() );
	}

	public function test_remote_ga_api_integration() {
		// Skip unless secure JSON config environment variable is explicitly provided (Step 7: Integration testing)
		$json_config = getenv( 'TRACKLY_GA_JSON' );
		if ( ! $json_config && defined( 'TRACKLY_GA_JSON' ) ) {
			$json_config = TRACKLY_GA_JSON;
		}

		if ( ! $json_config ) {
			$this->markTestSkipped( 'TRACKLY_GA_JSON not defined. Skipping live Google Analytics API integration test.' );
		}

		// Configure live service credentials from constant/env
		if ( ! defined( 'TRACKLY_GA_JSON' ) ) {
			define( 'TRACKLY_GA_JSON', $json_config );
		}
		update_option( 'trackly_property_id', getenv( 'TRACKLY_GA_PROPERTY_ID' ) ?: '123456789' );

		$report = $this->service->get_report( array(
			'dateRanges' => array( array( 'startDate' => '7daysAgo', 'endDate' => 'yesterday' ) ),
			'metrics'    => array( array( 'name' => 'screenPageViews' ) ),
		) );

		$this->assertNotInstanceOf( 'WP_Error', $report );
		$this->assertArrayHasKey( 'rowCount', $report );
	}
}
