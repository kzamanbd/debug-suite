<?php
/**
 * Base test case for Debug Suite plugin integration tests (with WordPress).
 *
 * @package DebugSuite\Tests\Helpers
 */

namespace DebugSuite\Tests\Helpers;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use WP_UnitTestCase;
use DebugSuite\Core\Container\Container;

/**
 * Base test case for integration tests.
 * 
 * Extends WP_UnitTestCase to get WordPress testing features.
 * Use this class for tests that need WordPress core.
 */
class DebugSuiteTestCase extends WP_UnitTestCase {

	/**
	 * Container instance for testing.
	 *
	 * @var Container|null
	 */
	protected $container = null;

	/**
	 * Test files to clean up.
	 *
	 * @var array
	 */
	protected $test_files = [];

	/**
	 * Set up test environment before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->reset_container();
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		$this->clean_up_test_files();
		$this->reset_container();
		parent::tear_down();
	}

	/**
	 * Reset the container to a clean state.
	 *
	 * @return void
	 */
	protected function reset_container() {
		if ( ! class_exists( 'DebugSuite\Core\Container\Container' ) ) {
			return;
		}

		$reflection = new ReflectionClass( Container::class );
		if ( $reflection->hasProperty( 'instance' ) ) {
			$instance_property = $reflection->getProperty( 'instance' );
			$instance_property->setAccessible( true );
			$instance_property->setValue( null, null );
		}
		
		$this->container = null;
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
	 * Create a temporary test file with content.
	 *
	 * @param string $content File content.
	 * @param string $suffix  File suffix (default: .tmp).
	 *
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
	 *
	 * @return string Path to created directory.
	 */
	protected function create_test_directory( string $prefix = 'debug_suite_test_' ): string {
		$temp_dir = sys_get_temp_dir() . '/' . uniqid( $prefix );
		mkdir( $temp_dir, 0755, true );
		$this->test_files[] = $temp_dir;
		return $temp_dir;
	}

	/**
	 * Assert that a ServiceResult is successful.
	 *
	 * @param mixed  $result  Service result to check.
	 * @param string $message Optional failure message.
	 * @return void
	 */
	protected function assert_service_result_success( $result, $message = '' ): void {
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
	protected function assert_service_result_failure( $result, $message = '' ): void {
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
	 * @return void
	 */
	protected function assert_service_result_error_code( $result, $expected_code, $message = '' ): void {
		$this->assert_service_result_failure( $result );
		$this->assertEquals(
			$expected_code,
			$result->get_error_code(),
			$message ?: "Expected error code '{$expected_code}', got '{$result->get_error_code()}'"
		);
	}

	/**
	 * Create a test admin user and set as current.
	 *
	 * @return int Admin user ID.
	 */
	protected function create_admin_user() {
		$admin_id = $this->factory->user->create();
		$user = get_user_by( 'id', $admin_id );
		$user->add_cap( 'manage_options' );
		wp_set_current_user( $admin_id );
		return $admin_id;
	}

	/**
	 * Clean up test files.
	 *
	 * @return void
	 */
	private function clean_up_test_files() {
		foreach ( $this->test_files as $file ) {
			$this->remove_test_path( $file );
		}
		$this->test_files = [];
	}

	/**
	 * Remove a test file or directory recursively.
	 *
	 * @param string $path Path to remove.
	 * @return void
	 */
	private function remove_test_path( $path ) {
		if ( ! file_exists( $path ) ) {
			return;
		}

		if ( is_dir( $path ) ) {
			$iterator = new RecursiveDirectoryIterator( $path, RecursiveDirectoryIterator::SKIP_DOTS );
			$files = new RecursiveIteratorIterator( $iterator, RecursiveIteratorIterator::CHILD_FIRST );

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
}
