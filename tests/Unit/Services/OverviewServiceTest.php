<?php
/**
 * Unit tests for OverviewService class.
 *
 * @package DebugSuite\Tests\Unit\Services
 * @group   services
 * @group   overview
 */

namespace DebugSuite\Tests\Unit\Services;

use DebugSuite\Tests\Helpers\TestCase;
use DebugSuite\Services\OverviewService;
use DebugSuite\Services\DebugLog\LogsService;
use DebugSuite\Core\ServiceResponse;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test OverviewService functionality.
 */
class OverviewServiceTest extends TestCase {

	/**
	 * OverviewService instance for testing.
	 *
	 * @var OverviewService
	 */
	private $service;

	/**
	 * Mock LogsService instance.
	 *
	 * @var LogsService&MockObject
	 */
	private $mock_file_logs_service;

	/**
	 * Set up test environment.
	 */
	public function set_up() {
		parent::set_up();

		// Create mock LogsService
		$this->mock_file_logs_service = $this->createMock( LogsService::class );

		// Create service instance with mock dependency
		$this->service = new OverviewService( $this->mock_file_logs_service );
	}

	/**
	 * Test get_dashboard_stats method with successful log stats.
	 * 
	 * @covers \DebugSuite\Services\OverviewService::get_dashboard_stats
	 */
	public function test_get_dashboard_stats_success() {
		// Mock log stats response
		$mock_log_stats = [
			'file_path'        => '/path/to/debug.log',
			'file_size'        => 1024000,
			'file_size_human'  => '1.02 MB',
			'total_entries'    => 150,
			'entries_with_stack_traces' => 25,
			'levels'           => [
				'error' => 10,
				'warning' => 20,
				'notice' => 30,
				'info' => 40,
				'debug' => 50,
			],
			'recent_errors'    => 5,
			'last_modified'    => time(),
		];

		$this->mock_file_logs_service
			->expects( $this->once() )
			->method( 'get_log_file_stats' )
			->willReturn( ServiceResponse::success( $mock_log_stats ) );

		// Call method
		$result = $this->service->get_dashboard_stats();

		// Assert the result is successful
		$this->assert_service_result_success( $result );

		// Check the dashboard data structure
		$data = $result->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'logs', $data );
		$this->assertArrayHasKey( 'system', $data );

		// Verify log stats are included
		$this->assertEquals( $mock_log_stats, $data['logs'] );

		// Verify system configuration is included
		$system = $data['system'];
		$this->assertArrayHasKey( 'wp_debug', $system );
		$this->assertArrayHasKey( 'wp_debug_log', $system );
		$this->assertArrayHasKey( 'wp_debug_display', $system );
		$this->assertArrayHasKey( 'php_version', $system );
		$this->assertArrayHasKey( 'wp_version', $system );
	}

	/**
	 * Test get_dashboard_stats method when log stats fail.
	 * 
	 * @covers \DebugSuite\Services\OverviewService::get_dashboard_stats
	 */
	public function test_get_dashboard_stats_log_stats_failure() {
		// Mock log stats failure
		$this->mock_file_logs_service
			->expects( $this->once() )
			->method( 'get_log_file_stats' )
			->willReturn( ServiceResponse::failure( 'Log file not found', 'file_not_found' ) );

		// Call method
		$result = $this->service->get_dashboard_stats();

		// Assert the result is a failure
		$this->assert_service_result_failure( $result );
		$this->assertEquals( 'log_stats_error', $result->get_error_code() );
		$this->assertStringContainsString( 'Failed to retrieve log statistics', $result->get_error_message() );
	}
} 