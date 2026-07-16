<?php
use PHPUnit\Framework\TestCase;
use Trackly\Includes\Database;

class TestDatabase extends TestCase {

	public function test_get_table_name() {
		$table_name = Database::get_table_name();
		$this->assertEquals( 'wp_trackly_clicks', $table_name );
	}

	public function test_log_click() {
		global $wpdb;

		$click_data = array(
			'page_url' => 'https://example.com/test',
			'element_tag' => 'button',
			'element_selector' => '#submit-btn',
			'click_x_pct' => 45.2,
			'click_y_pct' => 88.1,
		);

		$result = Database::log_click( $click_data );
		$this->assertTrue( $result );

		// Verify Mock_WPDB captured the correct insert parameters
		$this->assertEquals( 'wp_trackly_clicks', $wpdb->last_insert['table'] );
		$this->assertEquals( 'https://example.com/test', $wpdb->last_insert['data']['page_url'] );
		$this->assertEquals( 'button', $wpdb->last_insert['data']['element_tag'] );
		$this->assertEquals( '#submit-btn', $wpdb->last_insert['data']['element_selector'] );
		$this->assertEquals( 45.2, $wpdb->last_insert['data']['click_x_pct'] );
		$this->assertEquals( 88.1, $wpdb->last_insert['data']['click_y_pct'] );
	}
}
