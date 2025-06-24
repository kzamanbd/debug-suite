<?php
/**
 * Unit tests for WPLogReaderService.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Tests\Unit\Services\DebugLog;

use DebugSuite\Core\ServiceResponse;
use DebugSuite\Services\DebugLog\WPLogReaderService;
use DebugSuite\Tests\Helpers\TestCase;
use ReflectionClass;


/**
 * Test cases for WPLogReaderService.
 *
 * @covers \DebugSuite\Services\DebugLog\WPLogReaderService
 * @group services
 * @group log-reader
 * @group unit
 */
class WPLogReaderServiceTest extends TestCase {

	/**
	 * Temporary log file path.
	 *
	 * @var string
	 */
	private string $temp_log_file;

	/**
	 * WPLogReaderService instance.
	 *
	 * @var WPLogReaderService
	 */
	private WPLogReaderService $service;

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		// Create temporary log file
		$this->temp_log_file = tempnam( sys_get_temp_dir(), 'debug_suite_test_' );
		
		// Initialize service and set custom log path using reflection
		$this->service = new WPLogReaderService();
		$reflection = new ReflectionClass( $this->service );
		$path_property = $reflection->getProperty( 'log_file_path' );
		$path_property->setValue( $this->service, $this->temp_log_file );
	}

	/**
	 * Clean up test environment.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		// Clean up temporary file
		if ( file_exists( $this->temp_log_file ) ) {
			unlink( $this->temp_log_file );
		}

		parent::tear_down();
	}

	/**
	 * Create log file with content.
	 *
	 * @param string $content Log content.
	 * @return void
	 */
	private function create_log_file( string $content ): void {
		file_put_contents( $this->temp_log_file, $content );
	}

	/**
	 * Test service returns log levels correctly.
	 *
	 * @return void
	 */
	public function test_get_log_levels(): void {
		$levels = $this->service->get_log_levels();

		$this->assertIsArray( $levels );
		$this->assertArrayHasKey( 'error', $levels );
		$this->assertArrayHasKey( 'warning', $levels );
		$this->assertArrayHasKey( 'notice', $levels );
		$this->assertArrayHasKey( 'debug', $levels );

		$this->assertEquals( 'Error', $levels['error']['label'] );
		$this->assertEquals( 3, $levels['error']['value'] );
	}

	/**
	 * Test reading non-existent log file returns failure.
	 *
	 * @return void
	 */
	public function test_read_log_entries_file_not_found(): void {
		// Create a service with a non-existent file path
		$non_existent_path = '/non/existent/path/debug.log';
		$service = new WPLogReaderService();
		$reflection = new \ReflectionClass( $service );
		$path_property = $reflection->getProperty( 'log_file_path' );
		$path_property->setValue( $service, $non_existent_path );
		
		$result = $service->read_log_entries();

		$this->assertInstanceOf( ServiceResponse::class, $result );
		$this->assertTrue( $result->is_failure() );
		$this->assertEquals( 'file_not_found', $result->get_error_code() );
		$this->assertStringContainsString( 'Log file not found', $result->get_error_message() );
	}

	/**
	 * Test reading empty log file returns empty entries.
	 *
	 * @return void
	 */
	public function test_read_log_entries_empty_file(): void {
		// Create empty log file
		$this->create_log_file( '' );

		$result = $this->service->read_log_entries();

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertEmpty( $data['entries'] );
		$this->assertEquals( 0, $data['total'] );
	}

	/**
	 * Test parsing single log entry without stack trace.
	 *
	 * @return void
	 */
	public function test_read_log_entries_single_entry(): void {
		$log_content = '[19-Jun-2025 01:30:45 UTC] PHP Warning: Undefined variable $test in /var/www/test.php on line 123';
		$this->create_log_file( $log_content );

		$result = $this->service->read_log_entries();

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertCount( 1, $data['entries'] );

		$entry = $data['entries'][0];
		$this->assertEquals( '2025-06-19 01:30:45', $entry['timestamp'] );
		$this->assertEquals( 'PHP Warning', $entry['type'] );
		$this->assertEquals( 'warning', $entry['level'] );
		$this->assertEquals( 'Undefined variable $test', $entry['message'] );
		$this->assertEquals( '/var/www/test.php', $entry['file'] );
		$this->assertEquals( 123, $entry['line'] );
		$this->assertFalse( $entry['has_stack_trace'] );
	}

	/**
	 * Test parsing log entry with stack trace.
	 *
	 * @return void
	 */
	public function test_read_log_entries_with_stack_trace(): void {
		$log_content = implode( "\n", [
			'[19-Jun-2025 01:30:45 UTC] PHP Fatal error: Uncaught Error: Call to undefined function test_function() in /var/www/test.php on line 123',
			'Stack trace:',
			'#0 /var/www/test.php(123): test_function()',
			'#1 /var/www/index.php(45): include(\'/var/www/test.php\')',
			'#2 {main}',
			'  thrown in /var/www/test.php on line 123',
		] );

		$this->create_log_file( $log_content );

		$result = $this->service->read_log_entries();

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertCount( 1, $data['entries'] );

		$entry = $data['entries'][0];
		$this->assertTrue( $entry['has_stack_trace'] );
		$this->assertIsArray( $entry['stack_trace'] );
		$this->assertArrayHasKey( 'frames', $entry['stack_trace'] );
		$this->assertEquals( 5, $entry['stack_trace']['frame_count'] );
	}

	/**
	 * Test parsing multiple log entries.
	 *
	 * @return void
	 */
	public function test_read_log_entries_multiple_entries(): void {
		$log_content = implode( "\n", [
			'[19-Jun-2025 01:30:45 UTC] PHP Warning: First warning in /var/www/test.php on line 123',
			'[19-Jun-2025 01:31:45 UTC] PHP Notice: Second notice in /var/www/test.php on line 456',
			'[19-Jun-2025 01:32:45 UTC] PHP Error: Third error in /var/www/test.php on line 789',
		] );

		$this->create_log_file( $log_content );

		$result = $this->service->read_log_entries();

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertCount( 3, $data['entries'] );

		// Entries should be in reverse order (most recent first)
		$this->assertEquals( 'error', $data['entries'][0]['level'] );
		$this->assertEquals( 'notice', $data['entries'][1]['level'] );
		$this->assertEquals( 'warning', $data['entries'][2]['level'] );
	}

	/**
	 * Test level filtering.
	 *
	 * @return void
	 */
	public function test_read_log_entries_level_filter(): void {
		$log_content = implode( "\n", [
			'[19-Jun-2025 01:30:45 UTC] PHP Warning: Warning message in /var/www/test.php on line 123',
			'[19-Jun-2025 01:31:45 UTC] PHP Notice: Notice message in /var/www/test.php on line 456',
			'[19-Jun-2025 01:32:45 UTC] PHP Fatal error: Error message in /var/www/test.php on line 789',
		] );

		$this->create_log_file( $log_content );

		// Filter by warning level (should include warning and error)
		$result = $this->service->read_log_entries( [ 'level' => 'warning' ] );

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertCount( 2, $data['entries'] );

		$levels = array_column( $data['entries'], 'level' );
		$this->assertContains( 'warning', $levels );
		$this->assertNotContains( 'notice', $levels );
	}

	/**
	 * Test search filtering.
	 *
	 * @return void
	 */
	public function test_read_log_entries_search_filter(): void {
		$log_content = implode( "\n", [
			'[19-Jun-2025 01:30:45 UTC] PHP Warning: Database connection failed in /var/www/test.php on line 123',
			'[19-Jun-2025 01:31:45 UTC] PHP Notice: User login attempt in /var/www/test.php on line 456',
			'[19-Jun-2025 01:32:45 UTC] PHP Error: Database query error in /var/www/test.php on line 789',
		] );

		$this->create_log_file( $log_content );

		$result = $this->service->read_log_entries( [ 'search' => 'database' ] );

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertCount( 2, $data['entries'] );

		foreach ( $data['entries'] as $entry ) {
			$this->assertStringContainsStringIgnoringCase( 'database', $entry['message'] );
		}
	}

	/**
	 * Test date range filtering.
	 *
	 * @return void
	 */
	public function test_read_log_entries_date_filter(): void {
		$log_content = implode( "\n", [
			'[18-Jun-2025 01:30:45 UTC] PHP Warning: Old warning in /var/www/test.php on line 123',
			'[19-Jun-2025 01:31:45 UTC] PHP Notice: Today notice in /var/www/test.php on line 456',
			'[20-Jun-2025 01:32:45 UTC] PHP Error: Future error in /var/www/test.php on line 789',
		] );

		$this->create_log_file( $log_content );

		$result = $this->service->read_log_entries( [
			'date_from' => '2025-06-19',
			'date_to'   => '2025-06-19',
		] );

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertCount( 1, $data['entries'] );
		$this->assertStringContainsString( 'Today notice', $data['entries'][0]['message'] );
	}

	/**
	 * Test pagination.
	 *
	 * @return void
	 */
	public function test_read_log_entries_pagination(): void {
		$log_entries = [];
		for ( $i = 1; $i <= 5; $i++ ) {
			$log_entries[] = "[19-Jun-2025 01:3{$i}:45 UTC] PHP Notice: Entry {$i} in /var/www/test.php on line {$i}23";
		}

		$this->create_log_file( implode( "\n", $log_entries ) );

		// Get first 2 entries
		$result = $this->service->read_log_entries( [
			'limit'  => 2,
			'offset' => 0,
		] );

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertCount( 2, $data['entries'] );
		$this->assertEquals( 5, $data['total'] ); // Total should be 5

		// Get next 2 entries
		$result = $this->service->read_log_entries( [
			'limit'  => 2,
			'offset' => 2,
		] );

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertCount( 2, $data['entries'] );
	}

	/**
	 * Test log statistics.
	 *
	 * @return void
	 */
	public function test_get_log_statistics(): void {
		$log_content = implode( "\n", [
			'[19-Jun-2025 01:30:45 UTC] PHP Warning: Warning message in /var/www/test.php on line 123',
			'[19-Jun-2025 01:31:45 UTC] PHP Notice: Notice message in /var/www/test.php on line 456',
			'[19-Jun-2025 01:32:45 UTC] PHP Fatal error: Error message in /var/www/test.php on line 789',
		] );

		$this->create_log_file( $log_content );

		$result = $this->service->get_log_statistics();

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();

		$this->assertArrayHasKey( 'total_entries', $data );
		$this->assertArrayHasKey( 'levels', $data );
		$this->assertArrayHasKey( 'file_size', $data );
		$this->assertEquals( 3, $data['total_entries'] );
		$this->assertIsArray( $data['levels'] );
	}

	/**
	 * Test clearing log file.
	 *
	 * @return void
	 */
	public function test_clear_log_file(): void {
		$log_content = '[19-Jun-2025 01:30:45 UTC] PHP Warning: Test warning in /var/www/test.php on line 123';
		$this->create_log_file( $log_content );

		$result = $this->service->clear_log_file();

		$this->assertTrue( $result->is_success() );
		
		// Verify file is cleared
		$cleared_result = $this->service->read_log_entries();
		$this->assertTrue( $cleared_result->is_success() );
		$data = $cleared_result->get_data();
		$this->assertEmpty( $data['entries'] );
	}

	/**
	 * Test export to JSON format.
	 *
	 * @return void
	 */
	public function test_export_log_entries_json(): void {
		$log_content = '[19-Jun-2025 01:30:45 UTC] PHP Warning: Test warning in /var/www/test.php on line 123';
		$this->create_log_file( $log_content );

		$result = $this->service->export_log_entries( [ 'format' => 'json' ] );

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();

		$this->assertArrayHasKey( 'content', $data );
		$this->assertArrayHasKey( 'mime_type', $data );
		$this->assertArrayHasKey( 'filename', $data );
		$this->assertEquals( 'application/json', $data['mime_type'] );
		$this->assertStringEndsWith( '.json', $data['filename'] );

		// Verify JSON is valid
		$decoded = json_decode( $data['content'], true );
		$this->assertIsArray( $decoded );
	}

	/**
	 * Test export to CSV format.
	 *
	 * @return void
	 */
	public function test_export_log_entries_csv(): void {
		$log_content = '[19-Jun-2025 01:30:45 UTC] PHP Warning: Test warning in /var/www/test.php on line 123';
		$this->create_log_file( $log_content );

		$result = $this->service->export_log_entries( [ 'format' => 'csv' ] );

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();

		$this->assertEquals( 'text/csv', $data['mime_type'] );
		$this->assertStringEndsWith( '.csv', $data['filename'] );
		$this->assertStringContainsString( 'Timestamp,Level,Message', $data['content'] );
	}

	/**
	 * Test export to text format.
	 *
	 * @return void
	 */
	public function test_export_log_entries_text(): void {
		$log_content = '[19-Jun-2025 01:30:45 UTC] PHP Warning: Test warning in /var/www/test.php on line 123';
		$this->create_log_file( $log_content );

		$result = $this->service->export_log_entries( [ 'format' => 'txt' ] );

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();

		$this->assertEquals( 'text/plain', $data['mime_type'] );
		$this->assertStringEndsWith( '.txt', $data['filename'] );
		$this->assertStringContainsString( '# Debug Log Export', $data['content'] );
	}

	/**
	 * Test export with invalid format.
	 *
	 * @return void
	 */
	public function test_export_log_entries_invalid_format(): void {
		$log_content = '[19-Jun-2025 01:30:45 UTC] PHP Warning: Test warning in /var/www/test.php on line 123';
		$this->create_log_file( $log_content );

		$result = $this->service->export_log_entries( [ 'format' => 'invalid' ] );

		$this->assertTrue( $result->is_failure() );
		$this->assertEquals( 'invalid_format', $result->get_error_code() );
	}

	/**
	 * Test wrapper methods for compatibility.
	 *
	 * @return void
	 */
	public function test_wrapper_methods(): void {
		$log_content = '[19-Jun-2025 01:30:45 UTC] PHP Warning: Test warning in /var/www/test.php on line 123';
		$this->create_log_file( $log_content );

		// Test get_log_entries wrapper
		$result = $this->service->get_log_entries();
		$this->assertTrue( $result->is_success() );
		$this->assertArrayHasKey( 'entries', $result->get_data() );

		// Test get_log_file_stats wrapper
		$result = $this->service->get_log_file_stats();
		$this->assertTrue( $result->is_success() );
		$this->assertArrayHasKey( 'total_entries', $result->get_data() );

		// Test export_logs wrapper
		$result = $this->service->export_logs( [ 'format' => 'json' ] );
		$this->assertTrue( $result->is_success() );
		$this->assertArrayHasKey( 'content', $result->get_data() );
	}

	/**
	 * Test log level determination.
	 *
	 * @return void
	 */
	public function test_log_level_determination(): void {
		$test_cases = [
			'PHP Fatal error' => 'critical',  // Fatal errors should be critical
			'PHP Parse error' => 'critical',  // Parse errors should be critical
			'Uncaught Error'  => 'error',
			'PHP Warning'     => 'warning',
			'PHP Notice'      => 'notice',
			'PHP Deprecated'  => 'debug',
			'Unknown Type'    => 'info',
		];

		foreach ( $test_cases as $type => $expected_level ) {
			$log_content = "[19-Jun-2025 01:30:45 UTC] {$type}: Test message in /var/www/test.php on line 123";
			$this->create_log_file( $log_content );

			$result = $this->service->read_log_entries();
			$this->assertTrue( $result->is_success() );
			$data = $result->get_data();
			$this->assertCount( 1, $data['entries'] );
			$this->assertEquals( $expected_level, $data['entries'][0]['level'], "Failed for type: {$type}" );
		}
	}

	/**
	 * Test complex stack trace parsing.
	 *
	 * @return void
	 */
	public function test_complex_stack_trace_parsing(): void {
		$log_content = implode( "\n", [
			'[19-Jun-2025 01:30:45 UTC] PHP Fatal error: Uncaught TypeError: Argument 1 passed to test() must be string in /var/www/test.php on line 123',
			'Stack trace:',
			'#0 /var/www/test.php(123): test(123)',
			'#1 /var/www/includes/functions.php(456): include(\'/var/www/test.php\')',
			'#2 [internal function]: wp_hook()',
			'#3 /var/www/wp-includes/plugin.php(789): apply_filters()',
			'#4 {main}',
			'  thrown in /var/www/test.php on line 123',
		] );

		$this->create_log_file( $log_content );

		$result = $this->service->read_log_entries();

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertCount( 1, $data['entries'] );

		$entry = $data['entries'][0];
		$this->assertTrue( $entry['has_stack_trace'] );
		$this->assertIsArray( $entry['stack_trace'] );
		
		$frames = $entry['stack_trace']['frames'];
		$this->assertGreaterThan( 0, count( $frames ) );
		
		// Check that numbered frames are parsed correctly
		$numbered_frames = array_filter( $frames, fn( $frame ) => isset( $frame['number'] ) && is_int( $frame['number'] ) );
		$this->assertGreaterThan( 0, count( $numbered_frames ) );
	}

	/**
	 * Test reading log entries with HTML content and complex messages.
	 *
	 * @covers \DebugSuite\Services\DebugLog\WPLogReaderService::read_log_entries
	 * @covers \DebugSuite\Services\DebugLog\WPLogReaderService::parse_log_entries
	 * @group services
	 * @group unit
	 */
	public function test_read_log_entries_with_html_content(): void {
		// Create test log content with HTML and complex formatting (real-world example)
		$log_content = '[24-Jun-2025 12:16:40 UTC] PHP Deprecated:  Creation of dynamic property WC_Bookings_Google_Calendar_Connection::$redirect_uri_custom is deprecated in /Users/kzaman/Herd/dokan/wp-content/plugins/woocommerce-bookings/includes/class-wc-bookings-google-calendar-connection.php on line 128
[24-Jun-2025 12:16:40 UTC] PHP Notice:  Function _load_textdomain_just_in_time was called <strong>incorrectly</strong>. Translation loading for the <code>woocommerce-bookings</code> domain was triggered too early. This is usually an indicator for some code in the plugin or theme running too early. Translations should be loaded at the <code>init</code> action or later. Please see <a href="https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/">Debugging in WordPress</a> for more information. (This message was added in version 6.7.0.) in /Users/kzaman/Herd/dokan/wp-includes/functions.php on line 6121
[24-Jun-2025 12:16:40 UTC] PHP Deprecated:  Creation of dynamic property WC_Bookings_Google_Calendar_Connection::$client_id is deprecated in /Users/kzaman/Herd/dokan/wp-content/plugins/woocommerce-bookings/includes/class-wc-bookings-google-calendar-connection.php on line 131';

		$this->create_log_file( $log_content );

		$result = $this->service->read_log_entries();

		$this->assertTrue( $result->is_success() );

		$data = $result->get_data();
		$this->assertCount( 3, $data['entries'] );

		// Test first entry (simple deprecated warning)
		$first_entry = $data['entries'][2]; // Reversed order (most recent first)
		$this->assertEquals( 'PHP Deprecated', $first_entry['type'] );
		$this->assertEquals( 'debug', $first_entry['level'] );
		$this->assertStringContainsString( 'Creation of dynamic property', $first_entry['message'] );
		$this->assertEquals( '/Users/kzaman/Herd/dokan/wp-content/plugins/woocommerce-bookings/includes/class-wc-bookings-google-calendar-connection.php', $first_entry['file'] );
		$this->assertEquals( 128, $first_entry['line'] );

		// Test second entry (complex HTML content) - this is the critical test
		$html_entry = $data['entries'][1]; // Reversed order
		$this->assertEquals( 'PHP Notice', $html_entry['type'] );
		$this->assertEquals( 'notice', $html_entry['level'] );
		
		// Verify complete HTML content is preserved
		$this->assertStringContainsString( '<strong>incorrectly</strong>', $html_entry['message'] );
		$this->assertStringContainsString( '<code>woocommerce-bookings</code>', $html_entry['message'] );
		$this->assertStringContainsString( '<a href="https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/">Debugging in WordPress</a>', $html_entry['message'] );
		$this->assertStringContainsString( 'This message was added in version 6.7.0.', $html_entry['message'] );
		
		// Verify file and line information is extracted correctly despite HTML content
		$this->assertEquals( '/Users/kzaman/Herd/dokan/wp-includes/functions.php', $html_entry['file'] );
		$this->assertEquals( 6121, $html_entry['line'] );

		// Test third entry
		$third_entry = $data['entries'][0]; // Reversed order (most recent first)
		$this->assertEquals( 'PHP Deprecated', $third_entry['type'] );
		$this->assertEquals( 'debug', $third_entry['level'] );
		$this->assertStringContainsString( 'client_id is deprecated', $third_entry['message'] );
		$this->assertEquals( 131, $third_entry['line'] );

		// Verify no content was lost
		$total_original_length = strlen( $log_content );
		$total_parsed_length = array_sum( array_map( fn( $entry ) => strlen( $entry['raw_line'] ), $data['entries'] ) );
		
		// The parsed content should contain most of the original content 
		// (allowing for small differences due to processing)
		$this->assertGreaterThan( $total_original_length * 0.95, $total_parsed_length );
	}

	/**
	 * Test reading log entries with diverse formats and complex content.
	 *
	 * @covers \DebugSuite\Services\DebugLog\WPLogReaderService::read_log_entries
	 * @covers \DebugSuite\Services\DebugLog\WPLogReaderService::parse_log_entries
	 * @covers \DebugSuite\Services\DebugLog\WPLogReaderService::determine_type_from_content
	 * @group services
	 * @group unit
	 */
	public function test_read_log_entries_with_diverse_formats(): void {
		// Create test log content with all the diverse formats from real-world logs
		$log_content = '[23-Jun-2025 08:06:59 UTC] E_WARNING: Trying to access array offset on null in /Users/kzaman/Herd/dokan/wp-content/plugins/dokan-lite/includes/Order/VendorBalanceUpdateHandler.php on line 169
[23-Jun-2025 08:12:02 UTC] Array
(
    [paypal_marketplace] => 1
    [net_refund_amount] => 45
    [reversed_admin_fee] => 5
    [reversed_gateway_fee] => 0
    [total_refunded_amount] => 100
    [paypal_refund_id] => 8HM50612WW0503112
    [refund_order_id] => 793
)
[23-Jun-2025 08:26:01 UTC] E_USER_DEPRECATED: Hook woocommerce_rest_api_option_permissions is deprecated since version 6.3.0 with no alternative available. in /Users/kzaman/Herd/dokan/wp-includes/functions.php on line 6121
[23-Jun-2025 15:31:53 UTC] Automatic updates starting...
[23-Jun-2025 15:32:41 UTC] \'###### wp_scraping_result_start:a59e616268ca21b065d06fcbc656f023 ######
true
###### wp_scraping_result_end:a59e616268ca21b065d06fcbc656f023 ######
\'
[23-Jun-2025 16:26:36 UTC] Cron reschedule event error for hook: action_scheduler_run_queue, Error code: could_not_set, Error message: The cron event list could not be saved., Data: {"schedule":"every_minute","args":["WP Cron"],"interval":60}
[23-Jun-2025 17:39:18 UTC] EXCEPTION: Cannot resolve parameter [log_path] for class [DebugSuite\Services\DebugLog\WPLogReaderService]. No type hint, default value, or explicit binding provided. Suggestions: Use -&gt;constructor_parameter(&#039;log_path&#039;, $value), Use -&gt;constructor_parameter_callback(&#039;log_path&#039;, $callback), Add a default value to the parameter in the constructor. in /Users/kzaman/Herd/dokan/wp-content/plugins/debug-suite/includes/Core/Container/Definitions/AutowiredDefinition.php on line 393';

		$this->create_log_file( $log_content );

		$result = $this->service->read_log_entries();

		$this->assertTrue( $result->is_success() );

		$data = $result->get_data();
		$this->assertCount( 7, $data['entries'] );

		// Test E_WARNING entry (standard format)
		$warning_entry = $data['entries'][6]; // Reversed order (most recent first)
		$this->assertEquals( 'E_WARNING', $warning_entry['type'] );
		$this->assertEquals( 'warning', $warning_entry['level'] );
		$this->assertStringContainsString( 'Trying to access array offset on null', $warning_entry['message'] );
		$this->assertEquals( '/Users/kzaman/Herd/dokan/wp-content/plugins/dokan-lite/includes/Order/VendorBalanceUpdateHandler.php', $warning_entry['file'] );
		$this->assertEquals( 169, $warning_entry['line'] );

		// Test Array dump entry (no colon format)
		$array_entry = $data['entries'][5];
		$this->assertEquals( 'Array Dump', $array_entry['type'] );
		$this->assertEquals( 'debug', $array_entry['level'] );
		$this->assertStringContainsString( 'Array', $array_entry['message'] );
		$this->assertStringContainsString( '[paypal_marketplace] => 1', $array_entry['message'] );
		$this->assertStringContainsString( '[net_refund_amount] => 45', $array_entry['message'] );
		$this->assertNull( $array_entry['file'] );
		$this->assertNull( $array_entry['line'] );

		// Test E_USER_DEPRECATED entry
		$deprecated_entry = $data['entries'][4];
		$this->assertEquals( 'E_USER_DEPRECATED', $deprecated_entry['type'] );
		$this->assertEquals( 'debug', $deprecated_entry['level'] );
		$this->assertStringContainsString( 'Hook woocommerce_rest_api_option_permissions is deprecated', $deprecated_entry['message'] );
		$this->assertEquals( '/Users/kzaman/Herd/dokan/wp-includes/functions.php', $deprecated_entry['file'] );
		$this->assertEquals( 6121, $deprecated_entry['line'] );

		// Test simple status message (no colon format)
		$update_entry = $data['entries'][3];
		$this->assertEquals( 'System Update', $update_entry['type'] );
		$this->assertEquals( 'info', $update_entry['level'] );
		$this->assertEquals( 'Automatic updates starting...', $update_entry['message'] );
		$this->assertNull( $update_entry['file'] );
		$this->assertNull( $update_entry['line'] );

		// Test scraping result with complex multiline format
		$scraping_entry = $data['entries'][2];
		$this->assertEquals( 'Scraping Result', $scraping_entry['type'] );
		$this->assertEquals( 'debug', $scraping_entry['level'] );
		$this->assertStringContainsString( 'wp_scraping_result_start', $scraping_entry['message'] );
		$this->assertStringContainsString( 'true', $scraping_entry['message'] );
		$this->assertStringContainsString( 'wp_scraping_result_end', $scraping_entry['message'] );

		// Test cron event with JSON data
		$cron_entry = $data['entries'][1];
		$this->assertEquals( 'Cron Event', $cron_entry['type'] );
		$this->assertEquals( 'warning', $cron_entry['level'] );
		$this->assertStringContainsString( 'Cron reschedule event error', $cron_entry['message'] );
		$this->assertStringContainsString( 'action_scheduler_run_queue', $cron_entry['message'] );
		$this->assertStringContainsString( '{"schedule":"every_minute"', $cron_entry['message'] );

		// Test EXCEPTION with HTML entities
		$exception_entry = $data['entries'][0]; // Most recent
		$this->assertEquals( 'EXCEPTION', $exception_entry['type'] );
		$this->assertEquals( 'error', $exception_entry['level'] );
		$this->assertStringContainsString( 'Cannot resolve parameter', $exception_entry['message'] );
		$this->assertStringContainsString( 'log_path', $exception_entry['message'] );
		$this->assertStringContainsString( '&gt;', $exception_entry['message'] ); // HTML entities preserved
		$this->assertStringContainsString( '&#039;', $exception_entry['message'] ); // HTML entities preserved
		$this->assertEquals( '/Users/kzaman/Herd/dokan/wp-content/plugins/debug-suite/includes/Core/Container/Definitions/AutowiredDefinition.php', $exception_entry['file'] );
		$this->assertEquals( 393, $exception_entry['line'] );

		// Verify all entries have proper timestamps
		foreach ( $data['entries'] as $entry ) {
			$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $entry['timestamp'] );
			$this->assertNotEmpty( $entry['type'] );
			$this->assertNotEmpty( $entry['level'] );
			$this->assertGreaterThan( 0, $entry['line_number'] );
		}
	}
}
