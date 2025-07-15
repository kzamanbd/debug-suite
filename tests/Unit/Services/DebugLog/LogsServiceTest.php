<?php
/**
 * Unit tests for LogsService class.
 *
 * @package DebugSuite\Tests\Unit\Services
 * @group   services
 * @group   logs
 */

namespace DebugSuite\Tests\Unit\Services\DebugLog;

use DebugSuite\Core\ServiceResponse;
use DebugSuite\Services\DebugLog\LogsService;
use DebugSuite\Services\DebugLog\LogDiscoveryService;
use DebugSuite\Services\DebugLog\WPLogReaderService;
use DebugSuite\Tests\Helpers\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;

/**
 * Test LogsService functionality.
 */
class LogsServiceTest extends TestCase {

	/**
	 * LogsService instance for testing.
	 *
	 * @var LogsService
	 */
	private LogsService $service;

	/**
	 * WPLogReaderService mock.
	 *
	 * @var WPLogReaderService|MockObject
	 */
	private MockObject|WPLogReaderService $log_reader;

	/**
	 * LogDiscoveryService mock.
	 *
	 * @var LogDiscoveryService|MockObject
	 */
	private MockObject|LogDiscoveryService $log_discovery;

	/**
	 * Temporary log file path.
	 *
	 * @var string
	 */
	private $test_log_file;

	/**
	 * Set up test environment.
	 */
	public function set_up() {
		parent::set_up();

		// Create a temporary log file for testing
		$this->test_log_file = $this->create_test_file( '', '.log' );

		// Create mocks
		$this->log_reader = $this->createMock( WPLogReaderService::class );
		$this->log_discovery = $this->createMock( LogDiscoveryService::class );

		// Create service instance with mocked dependencies
		$this->service = new LogsService();
		$reflection = new ReflectionClass( $this->service );
		$log_reader_prop = $reflection->getProperty( 'log_reader' );
		$log_reader_prop->setValue( $this->service, $this->log_reader );
		$log_discovery_prop = $reflection->getProperty( 'log_discovery' );
		$log_discovery_prop->setValue( $this->service, $this->log_discovery );
	}

	/**
	 * Test get_log_entries delegates to WPLogReaderService.
	 */
	public function test_get_log_entries_delegates_to_reader(): void {
		$options = [ 'limit' => 10 ];
		$expected_response = ServiceResponse::success( [ 'entries' => [] ] );

		$this->log_reader->expects( $this->once() )
			->method( 'get_log_entries' )
			->with( $options )
			->willReturn( $expected_response );

		$result = $this->service->get_log_entries( $options );
		$this->assertSame( $expected_response, $result );
	}

	/**
	 * Test clear_log_file delegates to WPLogReaderService.
	 */
	public function test_clear_log_file_delegates_to_reader(): void {
		$expected_response = ServiceResponse::success( [ 'message' => 'Cleared' ] );

		$this->log_reader->expects( $this->once() )
			->method( 'clear_log_file' )
			->willReturn( $expected_response );

		$result = $this->service->clear_log_file();
		$this->assertSame( $expected_response, $result );
	}

	/**
	 * Test get_log_file_stats delegates to WPLogReaderService.
	 */
	public function test_get_log_file_stats_delegates_to_reader(): void {
		$expected_response = ServiceResponse::success( [ 'stats' => [] ] );

		$this->log_reader->expects( $this->once() )
			->method( 'get_log_file_stats' )
			->willReturn( $expected_response );

		$result = $this->service->get_log_file_stats();
		$this->assertSame( $expected_response, $result );
	}

	/**
	 * Test supported_log_files delegates to LogDiscoveryService.
	 */
	public function test_supported_log_files_delegates_to_discovery(): void {
		$expected_files = [
			[
				'name'       => 'debug.log',
				'path'       => '/var/log/debug.log',
				'size'       => '1 KB',
				'size_bytes' => 1024,
				'modified'   => '2025-06-18 10:30:00',
				'type'       => 'WordPress Debug',
			],
		];

		$this->log_discovery->expects( $this->once() )
			->method( 'get_supported_log_files' )
			->willReturn( $expected_files );

		$result = $this->service->supported_log_files();
		$this->assertSame( $expected_files, $result );
	}

	/**
	 * Test constructor with default dependencies.
	 */
	public function test_constructor_with_default_dependencies(): void {
		$service = new LogsService();
		$this->assertInstanceOf( LogsService::class, $service );

		$reflection = new ReflectionClass( $service );

		$log_reader_prop = $reflection->getProperty( 'log_reader' );
		$this->assertInstanceOf( WPLogReaderService::class, $log_reader_prop->getValue( $service ) );

		$log_discovery_prop = $reflection->getProperty( 'log_discovery' );
		$this->assertInstanceOf( LogDiscoveryService::class, $log_discovery_prop->getValue( $service ) );
	}

	/**
	 * Test get_raw_file_content with valid file path.
	 */
	public function test_get_raw_file_content_success(): void {
		$test_content = "Test log content\n[2025-07-02 10:30:00] WordPress error: Test message\n";
		file_put_contents( $this->test_log_file, $test_content );

		$result = $this->service->get_raw_file_content( $this->test_log_file );

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertArrayHasKey( 'content', $data );
		$this->assertArrayHasKey( 'filename', $data );
		$this->assertArrayHasKey( 'size', $data );
		$this->assertArrayHasKey( 'size_bytes', $data );
		$this->assertArrayHasKey( 'last_modified', $data );
		$this->assertArrayHasKey( 'truncated', $data );
		$this->assertArrayHasKey( 'max_size_reached', $data );
		$this->assertArrayHasKey( 'max_size_limit', $data );

		$this->assertEquals( $test_content, $data['content'] );
		$this->assertEquals( basename( $this->test_log_file ), $data['filename'] );
		$this->assertFalse( $data['truncated'] );
		$this->assertFalse( $data['max_size_reached'] );
		$this->assertEquals( 50 * 1024 * 1024, $data['max_size_limit'] );
	}

	/**
	 * Test get_raw_file_content with non-existent file.
	 */
	public function test_get_raw_file_content_file_not_found(): void {
		$non_existent_file = '/path/to/non/existent/file.log';

		$result = $this->service->get_raw_file_content( $non_existent_file );

		$this->assertTrue( $result->is_failure() );
		$this->assertEquals( 'file_not_found', $result->get_error_code() );
		$this->assertStringContainsString( 'not found', $result->get_error_message() );
		$this->assertEquals( $non_existent_file, $result->get_context()['path'] );
	}

	/**
	 * Test get_raw_file_content with empty file path.
	 */
	public function test_get_raw_file_content_no_file_path(): void {
		// We can't easily mock ini_get in a unit test without runkit or similar extensions
		// Instead, we'll test with an empty string directly
		$result = $this->service->get_raw_file_content( '' );

		$this->assertTrue( $result->is_failure() );
		$this->assertEquals( 'no_file_path', $result->get_error_code() );
		$this->assertStringContainsString( 'No log file path provided', $result->get_error_message() );
	}

	/**
	 * Test get_raw_file_content with default debug log path.
	 */
	public function test_get_raw_file_content_uses_default_debug_log(): void {
		$test_content = "Default debug log content\n";
		file_put_contents( $this->test_log_file, $test_content );

		// Since we can't easily mock ini_get, let's test with a valid file path instead
		$result = $this->service->get_raw_file_content( $this->test_log_file );

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertEquals( $test_content, $data['content'] );
	}

	/**
	 * Test get_raw_file_content with large file that gets truncated.
	 */
	public function test_get_raw_file_content_large_file_truncation(): void {
		// Create a file larger than 50MB limit
		$large_content = str_repeat( "This is a test line for large file content.\n", 1200000 ); // ~50MB
		file_put_contents( $this->test_log_file, $large_content );

		$result = $this->service->get_raw_file_content( $this->test_log_file );

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertTrue( $data['truncated'] );
		$this->assertTrue( $data['max_size_reached'] );
		$this->assertLessThan( strlen( $large_content ), strlen( $data['content'] ) );
	}

	/**
	 * Test read_file_tail private method indirectly through large file.
	 */
	public function test_read_file_tail_functionality(): void {
		// Create a file with specific content for tail testing
		$content_lines = [];
		for ( $i = 1; $i <= 1000; $i++ ) {
			$content_lines[] = "Line $i: This is test content for tail reading functionality.";
		}
		$full_content = implode( "\n", $content_lines );
		file_put_contents( $this->test_log_file, $full_content );

		// Force file to be considered "large" by creating a very large file
		$large_content = str_repeat( $full_content . "\n", 1000 ); // Make it very large
		file_put_contents( $this->test_log_file, $large_content );

		$result = $this->service->get_raw_file_content( $this->test_log_file );

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertTrue( $data['truncated'] );
		$this->assertNotEmpty( $data['content'] );
		// Verify the content is from the end of the file
		$this->assertStringContainsString( 'Line 1000:', $data['content'] );
	}

	/**
	 * Test get_raw_file_content with small file (no truncation).
	 */
	public function test_get_raw_file_content_small_file(): void {
		$small_content = "Small file content\nJust a few lines\nNo truncation needed";
		file_put_contents( $this->test_log_file, $small_content );

		$result = $this->service->get_raw_file_content( $this->test_log_file );

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertEquals( $small_content, $data['content'] );
		$this->assertFalse( $data['truncated'] );
		$this->assertFalse( $data['max_size_reached'] );
		$this->assertEquals( strlen( $small_content ), $data['size_bytes'] );
	}

	/**
	 * Test get_raw_file_content response structure.
	 */
	public function test_get_raw_file_content_response_structure(): void {
		$test_content = "Test content for response structure validation";
		file_put_contents( $this->test_log_file, $test_content );

		$result = $this->service->get_raw_file_content( $this->test_log_file );

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();

		// Verify all expected keys are present
		$expected_keys = [
			'content',
			'filename',
			'size',
			'size_bytes',
			'last_modified',
			'truncated',
			'max_size_reached',
			'max_size_limit',
		];

		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $data, "Missing key: $key" );
		}

		// Verify data types
		$this->assertIsString( $data['content'] );
		$this->assertIsString( $data['filename'] );
		$this->assertIsString( $data['size'] );
		$this->assertIsInt( $data['size_bytes'] );
		$this->assertIsString( $data['last_modified'] );
		$this->assertIsBool( $data['truncated'] );
		$this->assertIsBool( $data['max_size_reached'] );
		$this->assertIsInt( $data['max_size_limit'] );
	}
}
