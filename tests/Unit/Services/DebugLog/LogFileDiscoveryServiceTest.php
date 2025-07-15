<?php
/**
 * Tests for LogFileDiscoveryService.
 *
 * @package DebugSuite\Tests\Unit\Services\DebugLog
 */

namespace DebugSuite\Tests\Unit\Services\DebugLog;

use DebugSuite\Services\DebugLog\LogFileDiscoveryService;
use DebugSuite\Tests\Helpers\DebugSuiteTestCase;

/**
 * @covers \DebugSuite\Services\DebugLog\LogFileDiscoveryService
 * @group services
 * @group unit
 */
class LogFileDiscoveryServiceTest extends DebugSuiteTestCase {

	/**
	 * Test service instance.
	 *
	 * @var LogFileDiscoveryService
	 */
	private LogFileDiscoveryService $service;

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->service = new LogFileDiscoveryService();
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

	/**
	 * Test finding PHP-FPM log file.
	 */
	public function test_find_php_fpm_error_log(): void {
		// Create a temporary PHP-FPM log file
		$temp_dir = sys_get_temp_dir();
		$test_file = $temp_dir . '/php-fpm.log';
		file_put_contents( $test_file, 'Test PHP-FPM log content' );
		chmod( $test_file, 0644 );

		$reflection = new \ReflectionClass( $this->service );
		$method = $reflection->getMethod( 'find_php_fpm_error_log' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->service );
		unlink( $test_file );

		$this->assertNotNull( $result );
		$this->assertIsString( $result );
		$this->assertStringEndsWith( 'php-fpm.log', $result );
	}

	/**
	 * Test finding Apache log file.
	 */
	public function test_find_apache_log_file(): void {
		// Create a temporary Apache log file
		$temp_dir = sys_get_temp_dir();
		$test_file = $temp_dir . '/apache2/error.log';
		@mkdir( dirname( $test_file ), 0755, true );
		file_put_contents( $test_file, 'Test Apache log content' );
		chmod( $test_file, 0644 );

		$reflection = new \ReflectionClass( $this->service );
		$method = $reflection->getMethod( 'find_apache_log_file' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->service );
		unlink( $test_file );
		rmdir( dirname( $test_file ) );

		$this->assertNotNull( $result );
		$this->assertIsString( $result );
		$this->assertStringEndsWith( 'error.log', $result );
	}

	/**
	 * Test finding Nginx log file.
	 */
	public function test_find_nginx_log_file(): void {
		// Create a temporary Nginx log file
		$temp_dir = sys_get_temp_dir();
		$test_file = $temp_dir . '/nginx/error.log';
		@mkdir( dirname( $test_file ), 0755, true );
		file_put_contents( $test_file, 'Test Nginx log content' );
		chmod( $test_file, 0644 );

		$reflection = new \ReflectionClass( $this->service );
		$method = $reflection->getMethod( 'find_nginx_log_file' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->service );
		unlink( $test_file );
		rmdir( dirname( $test_file ) );

		$this->assertNotNull( $result );
		$this->assertIsString( $result );
		$this->assertStringEndsWith( 'error.log', $result );
	}

	/**
	 * Test finding Redis log file.
	 */
	public function test_find_redis_log_file(): void {
		// Create a temporary Redis log file
		$temp_dir = sys_get_temp_dir();
		$test_file = $temp_dir . '/redis/redis-server.log';
		@mkdir( dirname( $test_file ), 0755, true );
		file_put_contents( $test_file, 'Test Redis log content' );
		chmod( $test_file, 0644 );

		$reflection = new \ReflectionClass( $this->service );
		$method = $reflection->getMethod( 'find_redis_log_file' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->service );
		unlink( $test_file );
		rmdir( dirname( $test_file ) );

		$this->assertNotNull( $result );
		$this->assertIsString( $result );
		$this->assertStringEndsWith( 'redis-server.log', $result );
	}
} 