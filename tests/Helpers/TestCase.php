<?php
/**
 * Base test case for Debug Suite plugin unit tests (without WordPress).
 *
 * @package DebugSuite\Tests\Helpers
 */

namespace DebugSuite\Tests\Helpers;

use DebugSuite\Core\Container\Container;
use DebugSuite\Core\Container\ServiceManager;
use Yoast\PHPUnitPolyfills\TestCases\TestCase as YoastTestCase;

/**
 * Base test case for unit tests.
 * 
 * Extends YoastTestCase to get cross-version compatibility with PHPUnit.
 * Use this class for tests that don't need WordPress core.
 */
class TestCase extends YoastTestCase {
	
	/**
	 * Container instance for testing.
	 *
	 * @var Container|null
	 */
	protected $container = null;
	
	/**
	 * Service manager instance for testing.
	 *
	 * @var ServiceManager|null
	 */
	protected $service_manager = null;
	
	/**
	 * Test files to clean up.
	 *
	 * @var array
	 */
	protected $test_files = [];
	
	/**
	 * Set up before class.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
	}
	
	/**
	 * Set up test environment.
	 */
	public function set_up() {
		parent::set_up();
		
		// Reset container state for each test
		$this->reset_container();
		
		// Set up clean test environment
		$this->set_up_test_environment();
	}
	
	/**
	 * Tear down after test.
	 */
	public function tear_down() {
		// Clean up test files and state
		$this->clean_up_test_environment();
		
		// Reset container
		$this->reset_container();
		
		parent::tear_down();
	}
	
	/**
	 * Reset the container to a clean state.
	 *
	 * @return void
	 */
	protected function reset_container() {
		// Reset container singleton if it exists
		if ( class_exists( 'DebugSuite\Core\Container\Container' ) ) {
			$reflection = new \ReflectionClass( Container::class );
			if ( $reflection->hasProperty( 'instance' ) ) {
				$instance_property = $reflection->getProperty( 'instance' );
				$instance_property->setAccessible( true );
				$instance_property->setValue( null, null );
			}
		}
		
		$this->container = null;
		$this->service_manager = null;
	}
	
	/**
	 * Get a fresh container instance for testing.
	 *
	 * @return Container
	 */
	protected function get_container() {
		if ( null === $this->container ) {
			$this->container = Container::get_instance();
		}
		
		return $this->container;
	}

	/**
	 * Get a fresh service manager instance for testing.
	 *
	 * @return ServiceManager
	 */
	protected function get_service_manager() {
		if ( null === $this->service_manager ) {
			$this->service_manager = new ServiceManager( $this->get_container() );
		}
		
		return $this->service_manager;
	}

	/**
	 * Create a temporary test file with content.
	 *
	 * @param string $content File content.
	 * @param string $suffix  File suffix (default: .tmp).
	 * @return string Path to created file.
	 */
	protected function create_test_file( string $content = '', string $suffix = '.tmp' ): string {
		$temp_file = tempnam( sys_get_temp_dir(), 'debug_suite_test_' ) . $suffix;
		file_put_contents( $temp_file, $content );
		$this->test_files[] = $temp_file;
		return $temp_file;
	}

	/**
	 * Create a temporary test directory.
	 *
	 * @param string $prefix Directory prefix.
	 * @return string Path to created directory.
	 */
	protected function create_test_directory( string $prefix = 'debug_suite_test_' ): string {
		$temp_dir = sys_get_temp_dir() . '/' . uniqid( $prefix );
		mkdir( $temp_dir, 0755, true );
		$this->test_files[] = $temp_dir;
		return $temp_dir;
	}
	
	/**
	 * Remove a test file or directory recursively.
	 *
	 * @param string $path Path to remove.
	 * @return void
	 */
	protected function remove_test_path( string $path ): void {
		if ( ! file_exists( $path ) ) {
			return;
		}

		if ( is_dir( $path ) ) {
			$iterator = new \RecursiveDirectoryIterator( $path, \RecursiveDirectoryIterator::SKIP_DOTS );
			$files = new \RecursiveIteratorIterator( $iterator, \RecursiveIteratorIterator::CHILD_FIRST );

			foreach ( $files as $file ) {
				if ( $file->isDir() ) {
					rmdir( $file->getRealPath() );
				} else {
					unlink( $file->getRealPath() );
				}
			}

			rmdir( $path );
		} else {
			unlink( $path );
		}
	}
	
	/**
	 * Set up clean test environment.
	 *
	 * Override this in specific test classes if needed.
	 * 
	 * @return void
	 */
	protected function set_up_test_environment() {
		// Default implementation does nothing
	}

	/**
	 * Clean up test environment.
	 *
	 * @return void
	 */
	protected function clean_up_test_environment() {
		// Clean up any test files
		foreach ( $this->test_files as $file ) {
			$this->remove_test_path( $file );
		}
		$this->test_files = [];
	}
	
	/**
	 * Assert that a ServiceResult is successful.
	 *
	 * @param mixed  $result  Service result to check.
	 * @param string $message Optional failure message.
	 * @return void
	 */
	protected function assert_service_result_success( $result, $message = '' ) {
		$this->assertTrue(
			$result->is_success(),
			$message ?: 'Expected ServiceResult to be successful, but it failed with: ' . $result->get_error_message()
		);
	}

	/**
	 * Assert that a ServiceResult is a failure.
	 *
	 * @param mixed  $result  Service result to check.
	 * @param string $message Optional failure message.
	 * @return void
	 */
	protected function assert_service_result_failure( $result, $message = '' ) {
		$this->assertTrue(
			$result->is_failure(),
			$message ?: 'Expected ServiceResult to be a failure, but it was successful'
		);
	}
	
	/**
	 * Assert that a ServiceResult has a specific error code.
	 *
	 * @param mixed  $result        Service result to check.
	 * @param string $expected_code Expected error code.
	 * @param string $message       Optional failure message.
	 *
	 * @return void
	 */
	protected function assert_service_result_error_code( mixed $result, string $expected_code, string $message = '' ) {
		$this->assert_service_result_failure( $result );
		$this->assertEquals(
			$expected_code,
			$result->get_error_code(),
			$message ?: "Expected error code '$expected_code', got '{$result->get_error_code()}'"
		);
	}
	
	/**
	 * Assert that two arrays are equal as sets (ignoring order).
	 *
	 * @param array $expected Expected array.
	 * @param array $actual   Actual array.
	 * @param string $message  Optional failure message.
	 *
	 * @return void
	 */
	protected function assert_equal_sets( array $expected, array $actual, string $message = '' ) {
		sort( $expected );
		sort( $actual );
		$this->assertEquals( $expected, $actual, $message );
	}
}
