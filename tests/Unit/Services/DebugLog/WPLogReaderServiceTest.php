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
}
