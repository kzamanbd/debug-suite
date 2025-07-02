<?php
/**
 * Unit tests for FileLogsService class.
 *
 * @package DebugSuite\Tests\Unit\Services
 * @group   services
 * @group   logs
 */

namespace DebugSuite\Tests\Unit\Services;

use DebugSuite\Tests\Helpers\TestCase;
use DebugSuite\Services\DebugLog\FileLogsService;
use DebugSuite\Services\DebugLog\WPLogReaderService;
use DebugSuite\Services\DebugLog\LogFileDiscoveryService;
use DebugSuite\Core\ServiceResponse;
use ReflectionClass;

/**
 * Test FileLogsService functionality.
 */
class FileLogsServiceTest extends TestCase {

	/**
	 * FileLogsService instance for testing.
	 *
	 * @var FileLogsService
	 */
	private $service;

	/**
	 * WPLogReaderService mock.
	 *
	 * @var WPLogReaderService|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $log_reader;

	/**
	 * LogFileDiscoveryService mock.
	 *
	 * @var LogFileDiscoveryService|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $file_discovery;

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
		$this->file_discovery = $this->createMock( LogFileDiscoveryService::class );

		// Create service instance with mocked dependencies
		$this->service = new FileLogsService();
		$reflection = new ReflectionClass( $this->service );
		$log_reader_prop = $reflection->getProperty( 'log_reader' );
		$log_reader_prop->setValue( $this->service, $this->log_reader );
		$file_discovery_prop = $reflection->getProperty( 'file_discovery' );
		$file_discovery_prop->setValue( $this->service, $this->file_discovery );
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
	 * Test export_logs delegates to WPLogReaderService.
	 */
	public function test_export_logs_delegates_to_reader(): void {
		$options = [ 'format' => 'json' ];
		$expected_response = ServiceResponse::success( [ 'content' => '{}' ] );

		$this->log_reader->expects( $this->once() )
			->method( 'export_logs' )
			->with( $options )
			->willReturn( $expected_response );

		$result = $this->service->export_logs( $options );
		$this->assertSame( $expected_response, $result );
	}

	/**
	 * Test supported_log_files delegates to LogFileDiscoveryService.
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

		$this->file_discovery->expects( $this->once() )
			->method( 'get_supported_log_files' )
			->willReturn( $expected_files );

		$result = $this->service->supported_log_files();
		$this->assertSame( $expected_files, $result );
	}

	/**
	 * Test constructor with default dependencies.
	 */
	public function test_constructor_with_default_dependencies(): void {
		$service = new FileLogsService();
		$this->assertInstanceOf( FileLogsService::class, $service );

		$reflection = new ReflectionClass( $service );

		$log_reader_prop = $reflection->getProperty( 'log_reader' );
		$this->assertInstanceOf( WPLogReaderService::class, $log_reader_prop->getValue( $service ) );

		$file_discovery_prop = $reflection->getProperty( 'file_discovery' );
		$this->assertInstanceOf( LogFileDiscoveryService::class, $file_discovery_prop->getValue( $service ) );
	}
}
