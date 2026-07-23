<?php
/**
 * Tests for LogDiscoveryService.
 *
 * @package DebugSuite\Tests\Unit\Services\DebugLog
 */

namespace DebugSuite\Tests\Unit\Services;

use DebugSuite\Services\DebugLog\LogDiscoveryService;
use DebugSuite\Tests\Helpers\DebugSuiteTestCase;

/**
 * @covers \DebugSuite\Services\DebugLog\LogDiscoveryService
 * @group services
 * @group unit
 */
class LogDiscoveryServiceTest extends DebugSuiteTestCase {

	/**
	 * Test service instance.
	 *
	 * @var LogDiscoveryService
	 */
	private LogDiscoveryService $service;

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->service = new LogDiscoveryService();
	}

	/**
	 * Test get_supported_log_files returns expected structure.
	 */
	public function test_get_supported_log_files_returns_expected_structure(): void {
		// Create temporary test files
		$temp_dir = sys_get_temp_dir();
		$test_files = [
			'debug.log'    => 'WordPress Debug',
			'error.log'    => 'Apache',
			'php-fpm.log'  => 'PHP-FPM',
			'wc-debug.log' => 'WooCommerce',
		];

		$created_files = [];
		foreach ( $test_files as $file => $type ) {
			$path = $temp_dir . '/' . $file;
			file_put_contents( $path, 'Test log content' );
			$created_files[] = $path;
		}

		// Get log files
		$log_files = $this->service->get_supported_log_files();

		// Clean up test files
		foreach ( $created_files as $file ) {
			unlink( $file );
		}

		// Verify structure
		$this->assertIsArray( $log_files );
		foreach ( $log_files as $log_file ) {
			$this->assertArrayHasKey( 'name', $log_file );
			$this->assertArrayHasKey( 'path', $log_file );
			$this->assertArrayHasKey( 'size', $log_file );
			$this->assertArrayHasKey( 'size_bytes', $log_file );
			$this->assertArrayHasKey( 'modified', $log_file );
			$this->assertArrayHasKey( 'type', $log_file );
		}
	}

	/**
	 * Test log type detection.
	 */
	public function test_detect_log_type(): void {
		$test_cases = [
			'/var/log/debug.log'      => 'WordPress Debug',
			'/var/log/wc-debug.log'   => 'WooCommerce',
			'/var/log/apache2/error.log' => 'Apache',
			'/var/log/nginx/error.log' => 'Nginx',
			'/var/log/redis/redis.log' => 'Redis',
			'/var/log/php-fpm.log'    => 'PHP-FPM',
			'/var/log/unknown.log'    => 'Unknown',
		];

		$reflection = new \ReflectionClass( $this->service );
		$method = $reflection->getMethod( 'detect_log_type' );
		$method->setAccessible( true );

		foreach ( $test_cases as $path => $expected_type ) {
			$actual_type = $method->invoke( $this->service, $path );
			$this->assertEquals(
				$expected_type,
				$actual_type,
				"Failed to detect correct type for {$path}"
			);
		}
	}

} 