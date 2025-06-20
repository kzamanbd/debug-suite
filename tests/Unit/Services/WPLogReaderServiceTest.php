<?php
/**
 * Test file for Advanced Log Reader Service.
 *
 * @package DebugSuite\Tests
 */

namespace DebugSuite\Tests\Unit\Services;

use DebugSuite\Services\DebugLog\WPLogReaderService;
use DebugSuite\Tests\Helpers\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Test class for WPLogReaderService.
 *
 * @covers \DebugSuite\Services\DebugLog\WPLogReaderService
 * @group services
 * @group wp-log-reader
 */
class WPLogReaderServiceTest extends TestCase {

	/**
	 * WP log reader service instance.
	 *
	 * @var WPLogReaderService
	 */
	private WPLogReaderService $service;

	/**
	 * Test log file path.
	 *
	 * @var string
	 */
	private string $test_log_file;

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		
		// Create a temporary log file for testing
		$this->test_log_file = sys_get_temp_dir() . '/debug-suite-test.log';
		$this->service = new WPLogReaderService( $this->test_log_file );
	}

	/**
	 * Clean up test environment.
	 *
	 * @return void
	 */
	public function tear_down() {
		if ( file_exists( $this->test_log_file ) ) {
			unlink( $this->test_log_file );
		}
		parent::tear_down();
	}

	/**
	 * Test service instantiation.
	 *
	 * @return void
	 */
	public function test_service_instantiation(): void {
		$this->assertInstanceOf( WPLogReaderService::class, $this->service );
	}

	/**
	 * Test get_log_entries with missing file.
	 *
	 * @return void
	 */
	public function test_get_log_entries_missing_file(): void {
		$result = $this->service->get_log_entries();
		
		$this->assertTrue( $result->is_failure() );
		$this->assertEquals( 'file_not_found', $result->get_error_code() );
	}

	/**
	 * Test get_log_entries with valid log file.
	 *
	 * @return void
	 */
	public function test_get_log_entries_valid_file(): void {
		// Create test log content
		$log_content = "[19-Jun-2025 10:30:00 UTC] PHP Fatal error: Test error message in /test/file.php on line 123\n";
		$log_content .= "[19-Jun-2025 10:31:00 UTC] PHP Warning: Test warning message\n";
		$log_content .= "[19-Jun-2025 10:32:00 UTC] PHP Notice: Test notice message\n";
		
		file_put_contents( $this->test_log_file, $log_content );
		
		$result = $this->service->get_log_entries();
		
		$this->assertTrue( $result->is_success() );
		
		$data = $result->get_data();
		$this->assertArrayHasKey( 'entries', $data );
		$this->assertArrayHasKey( 'total', $data );
		$this->assertGreaterThan( 0, $data['total'] );
		
		// Check that entries have the expected structure
		$entries = $data['entries'];
		$this->assertNotEmpty( $entries );
		
		$first_entry = $entries[0];
		$this->assertArrayHasKey( 'level', $first_entry );
		$this->assertArrayHasKey( 'message', $first_entry );
		$this->assertArrayHasKey( 'timestamp', $first_entry );
	}

	/**
	 * Test export logs functionality.
	 *
	 * @return void
	 */
	public function test_export_logs(): void {
		// Create test log content
		$log_content = "[19-Jun-2025 10:30:00 UTC] PHP Error: Test error message\n";
		file_put_contents( $this->test_log_file, $log_content );
		
		$result = $this->service->export_logs( [ 'format' => 'json' ] );
		
		$this->assertTrue( $result->is_success() );
		
		$data = $result->get_data();
		// The export method returns 'content', 'filename', 'mime_type', 'size', 'entries'
		$this->assertArrayHasKey( 'content', $data );
		$this->assertArrayHasKey( 'mime_type', $data );
		$this->assertEquals( 'application/json', $data['mime_type'] );
		$this->assertArrayHasKey( 'filename', $data );
		$this->assertArrayHasKey( 'size', $data );
		$this->assertArrayHasKey( 'entries', $data );
	}

	/**
	 * Test clear log file functionality.
	 *
	 * @return void
	 */
	public function test_clear_log_file(): void {
		// Create test log content
		$log_content = "[19-Jun-2025 10:30:00 UTC] PHP Error: Test error message\n";
		file_put_contents( $this->test_log_file, $log_content );
		
		$this->assertFileExists( $this->test_log_file );
		$this->assertNotEmpty( file_get_contents( $this->test_log_file ) );
		
		$result = $this->service->clear_log_file();
		
		$this->assertTrue( $result->is_success() );
		$this->assertEmpty( file_get_contents( $this->test_log_file ) );
	}

	/**
	 * Test level filtering.
	 *
	 * @return void
	 */
	public function test_level_filtering(): void {
		// Create test log content with different levels
		$log_content = "[19-Jun-2025 10:30:00 UTC] PHP Fatal error: Test fatal error\n";
		$log_content .= "[19-Jun-2025 10:31:00 UTC] PHP Warning: Test warning\n";
		$log_content .= "[19-Jun-2025 10:32:00 UTC] PHP Notice: Test notice\n";
		
		file_put_contents( $this->test_log_file, $log_content );
		
		// Test filtering by notice level (this should include notice, info, debug but not warning, error, critical)
		// Level filtering works by severity: notice (5) includes all levels <= 5
		$result = $this->service->get_log_entries( [ 'level' => 'notice' ] );
		
		$this->assertTrue( $result->is_success() );
		
		$data = $result->get_data();
		$entries = $data['entries'];
		
		$found_levels = array_map( function( $entry ) { return $entry['level']; }, $entries );
		$unique_levels = array_unique($found_levels);
		
		// With notice level filter (5), we should get:
		// - critical (2) ✓
		// - warning (4) ✓  
		// - notice (5) ✓
		// So all three entries should be included
		$this->assertContains( 'critical', $unique_levels, 'Should contain critical level. Found levels: ' . implode(', ', $unique_levels) );
		$this->assertContains( 'warning', $unique_levels, 'Should contain warning level. Found levels: ' . implode(', ', $unique_levels) );
		$this->assertContains( 'notice', $unique_levels, 'Should contain notice level. Found levels: ' . implode(', ', $unique_levels) );
		
		// Test with more restrictive filter (error level = 3)
		$error_result = $this->service->get_log_entries( [ 'level' => 'error' ] );
		$this->assertTrue( $error_result->is_success() );
		$error_data = $error_result->get_data();
		$error_levels = array_unique(array_map( function( $entry ) { return $entry['level']; }, $error_data['entries'] ));
		
		// Error level (3) should include critical (2) and exclude warning (4) and notice (5)
		$this->assertContains( 'critical', $error_levels, 'Error filter should include critical' );
		$this->assertNotContains( 'warning', $error_levels, 'Error filter should exclude warning' );
		$this->assertNotContains( 'notice', $error_levels, 'Error filter should exclude notice' );
	}

	/**
	 * Test search functionality.
	 *
	 * @return void
	 */
	public function test_search_functionality(): void {
		// Create test log content
		$log_content = "[19-Jun-2025 10:30:00 UTC] PHP Error: Database connection failed\n";
		$log_content .= "[19-Jun-2025 10:31:00 UTC] PHP Warning: Memory usage high\n";
		$log_content .= "[19-Jun-2025 10:32:00 UTC] PHP Notice: Cache miss\n";
		
		file_put_contents( $this->test_log_file, $log_content );
		
		// Test searching for specific term
		$result = $this->service->get_log_entries( [ 'search' => 'database' ] );
		
		$this->assertTrue( $result->is_success() );
		
		$data = $result->get_data();
		$entries = $data['entries'];
		
		// Should only have entries containing 'database'
		foreach ( $entries as $entry ) {
			$this->assertStringContainsStringIgnoringCase( 'database', $entry['message'] );
		}
	}
}
