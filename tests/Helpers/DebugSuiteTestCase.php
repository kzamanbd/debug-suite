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
use DebugSuite\Core\Container\Container;
use WP_UnitTestCase;

/**
 * Base test case for integration tests.
 * 
 * Extends WP_UnitTestCase when available, otherwise falls back to TestCase.
 * Use this class for tests that need WordPress core, REST API testing,
 * or container integration.
 * 
 * Features included:
 * - REST API testing helpers (GET, POST, PUT, DELETE methods)
 * - Container reset and management
 * - Test file and directory management
 * - User setup (admin and customer)
 * - ServiceResponse assertions
 * - Response assertions
 * - File content assertions
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
	 * Admin user ID.
	 *
	 * @var int
	 */
	protected $admin_id;
	
	/**
	 * Customer user ID.
	 *
	 * @var int
	 */
	protected $customer_id;
	
	/**
	 * REST API server instance.
	 *
	 * @var object|null The REST server instance.
	 */
	protected $server;
	
	/**
	 * The namespace of the REST route.
	 *
	 * @var string Debug Suite API Namespace
	 */
	protected $namespace = 'debug-suite/v1';

	/**
	 * Set up test environment before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->reset_container();
		
		if ( ! $this->is_wordpress_available() ) {
			// Skip WordPress-specific setup if not available
			return;
		}
		
		// Initiating the REST API
		global $wp_rest_server;
		
		if ( class_exists( 'WP_REST_Server' ) ) {
			$wp_rest_server = new \WP_REST_Server();
			$this->server = $wp_rest_server;
			do_action( 'rest_api_init' );
		}
		
		// Setup test users
		$this->setup_users();
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
	protected function reset_container(): void {
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
	 * Assert that a ServiceResponse is successful.
	 *
	 * @param mixed  $result  Service result to check.
	 * @param string $message Optional failure message.
	 * @return void
	 */
	protected function assert_service_result_success( $result, $message = '' ): void {
		$this->assertTrue(
			$result->is_success(),
			$message ?: 'Expected ServiceResponse to be successful, but it failed with: ' . $result->get_error_message()
		);
	}

	/**
	 * Assert that a ServiceResponse is a failure.
	 *
	 * @param mixed  $result  Service result to check.
	 * @param string $message Optional failure message.
	 * @return void
	 */
	protected function assert_service_result_failure( $result, $message = '' ): void {
		$this->assertTrue(
			$result->is_failure(),
			$message ?: 'Expected ServiceResponse to be a failure, but it was successful'
		);
	}

	/**
	 * Assert that a ServiceResponse has a specific error code.
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
	 * Check if WordPress test environment is available.
	 *
	 * @return bool
	 */
	public function is_wordpress_available(): bool {
		return class_exists( 'WP_UnitTestCase' ) && function_exists( 'wp_set_current_user' );
	}

	/**
	 * Create a test admin user and set as current.
	 *
	 * @return int Admin user ID.
	 */
	protected function create_admin_user() {
		if ( ! $this->is_wordpress_available() ) {
			return 123; // Mock user ID
		}
		
		$admin_id = $this->factory()->user->create();
		$user = get_user_by( 'id', $admin_id );
		$user->add_cap( 'manage_options' );
		wp_set_current_user( $admin_id );
		
		return $admin_id;
	}

	/**
	 * Setup test users for REST API testing.
	 *
	 * @return void
	 */
	protected function setup_users(): void {
		if ( ! $this->is_wordpress_available() ) {
			// Skip user setup if WordPress is not available
			$this->admin_id = 123;
			$this->customer_id = 124;
			return;
		}
		
		$this->admin_id = $this->factory()->user->create(
			array(
				'role' => 'administrator',
			)
		);
		
		$this->customer_id = $this->factory()->user->create(
			array(
				'role' => 'subscriber',
			)
		);
	}

	/**
	 * Get the full route namespace for the given route.
	 *
	 * @param string $route The REST API route.
	 * @return string The full route with namespace.
	 */
	protected function get_rest_namespace( string $route ): string {
		return $this->namespace . $route;
	}

	/**
	 * Get route with namespace.
	 *
	 * @param string $route_name REST API route name.
	 * @return string The full route with namespace.
	 */
	protected function get_route( string $route_name ): string {
		$namespace = '/' . trim( $this->namespace, '/' );
		$route_name = '/' . trim( $route_name, '/' );

		// If namespace is already exist.
		if ( str_starts_with( $route_name, $namespace ) ) {
			return $route_name;
		}

		return $namespace . $route_name;
	}

	/**
	 * Perform a GET request on the given route with parameters.
	 *
	 * @param string $route   The REST API route.
	 * @param array  $params  Optional query parameters.
	 * @param array  $headers Optional headers.
	 * @param bool   $header_override Optional header override status.
	 *
	 * @return object The response object.
	 */
	protected function get_request( string $route, array $params = [], array $headers = [], bool $header_override = true ) {
		if ( ! class_exists( 'WP_REST_Request' ) ) {
			return $this->create_mock_response( 200, [ 'data' => 'mocked' ] );
		}
		
		$request = new \WP_REST_Request( 'GET', $this->get_route( $route ) );
		$request->set_query_params( $params );
		$request->set_headers( $headers, $header_override );

		return $this->server->dispatch( $request );
	}

	/**
	 * Perform a POST request on the given route with parameters.
	 *
	 * @param string $route   The REST API route.
	 * @param array  $params  Optional body parameters.
	 * @param array  $headers Optional headers.
	 * @param bool   $header_override Optional header override status.
	 * 
	 * @return object The response object.
	 */
	protected function post_request( string $route, array $params = [], array $headers = [], bool $header_override = true ) {
		if ( ! class_exists( 'WP_REST_Request' ) ) {
			return $this->create_mock_response( 200, [ 'data' => 'mocked' ] );
		}
		
		$request = new \WP_REST_Request( 'POST', $this->get_route( $route ) );
		$request->set_body_params( $params );
		$request->set_headers( $headers, $header_override );

		return $this->server->dispatch( $request );
	}

	/**
	 * Perform a PUT request on the given route with parameters.
	 *
	 * @param string $route   The REST API route.
	 * @param array  $params  Optional body parameters.
	 * @param array  $headers Optional headers.
	 * @param bool   $header_override Optional header override status.
	 * 
	 * @return object The response object.
	 */
	protected function put_request( string $route, array $params = [], array $headers = [], bool $header_override = true ) {
		if ( ! class_exists( 'WP_REST_Request' ) ) {
			return $this->create_mock_response( 200, [ 'data' => 'mocked' ] );
		}
		
		$request = new \WP_REST_Request( 'PUT', $this->get_route( $route ) );
		$request->set_body_params( $params );
		$request->set_headers( $headers, $header_override );

		return $this->server->dispatch( $request );
	}

	/**
	 * Perform a DELETE request on the given route with parameters.
	 *
	 * @param string $route   The REST API route.
	 * @param array  $params  Optional body parameters.
	 * @param array  $headers Optional headers.
	 * @param bool   $header_override Optional header override status.
	 * 
	 * @return object The response object.
	 */
	protected function delete_request( string $route, array $params = [], array $headers = [], bool $header_override = true ) {
		if ( ! class_exists( 'WP_REST_Request' ) ) {
			return $this->create_mock_response( 200, [ 'data' => 'mocked' ] );
		}
		
		$request = new \WP_REST_Request( 'DELETE', $this->get_route( $route ) );
		$request->set_body_params( $params );
		$request->set_headers( $headers, $header_override );

		return $this->server->dispatch( $request );
	}
	
	/**
	 * Assert that a response has a specific status code.
	 *
	 * @param object $response The response object.
	 * @param int    $status   Expected status code.
	 * @param string $message  Optional failure message.
	 * 
	 * @return void
	 */
	protected function assert_response_status( $response, int $status, string $message = '' ): void {
		$actual_status = method_exists( $response, 'get_status' ) ? $response->get_status() : 200;
		$this->assertEquals(
			$status,
			$actual_status,
			$message ?: "Expected status code {$status}, got {$actual_status}"
		);
	}
	
	/**
	 * Assert that a response contains specific data.
	 *
	 * @param object $response      The response object.
	 * @param array  $expected_data Expected data (partial match).
	 * @param string $message       Optional failure message.
	 * 
	 * @return void
	 */
	protected function assert_response_data_contains( $response, array $expected_data, string $message = '' ): void {
		$data = method_exists( $response, 'get_data' ) ? $response->get_data() : [];
		
		foreach ( $expected_data as $key => $value ) {
			$this->assertArrayHasKey(
				$key,
				$data,
				$message ?: "Response data does not contain key '{$key}'"
			);
			
			$this->assertEquals(
				$value,
				$data[$key],
				$message ?: "Response data key '{$key}' has incorrect value"
			);
		}
	}

	/**
	 * Assert that a file contains a specific string.
	 *
	 * @param string $file_path Path to the file.
	 * @param string $expected  Expected content substring.
	 * @param string $message   Optional failure message.
	 * 
	 * @return void
	 */
	protected function assert_file_contains( string $file_path, string $expected, string $message = '' ): void {
		$this->assertFileExists( $file_path, 'File does not exist: ' . $file_path );
		$content = file_get_contents( $file_path );
		$this->assertStringContainsString(
			$expected,
			$content,
			$message ?: "File does not contain expected content: '{$expected}'"
		);
	}

	/**
	 * Create a test REST request with parameters.
	 *
	 * @param string $method HTTP method (GET, POST, PUT, DELETE, etc.)
	 * @param string $route  The REST API route.
	 * @param array  $params Request parameters.
	 * 
	 * @return object The created request object.
	 */
	protected function create_rest_request( string $method, string $route, array $params = [] ) {
		if ( ! class_exists( 'WP_REST_Request' ) ) {
			return $this->create_mock_request( $method, $route, $params );
		}
		
		$request = new \WP_REST_Request( $method, $this->get_route( $route ) );
		
		if ( 'GET' === $method ) {
			$request->set_query_params( $params );
		} else {
			$request->set_body_params( $params );
		}
		
		return $request;
	}

	/**
	 * Create a mock response for non-WordPress environments.
	 *
	 * @param int   $status Status code.
	 * @param array $data   Response data.
	 * 
	 * @return object Mock response object.
	 */
	private function create_mock_response( int $status, array $data ) {
		return (object) [
			'get_status' => function() use ( $status ) { return $status; },
			'get_data' => function() use ( $data ) { return $data; },
		];
	}

	/**
	 * Create a mock request for non-WordPress environments.
	 *
	 * @param string $method HTTP method.
	 * @param string $route  Route path.
	 * @param array  $params Parameters.
	 * 
	 * @return object Mock request object.
	 */
	private function create_mock_request( string $method, string $route, array $params ) {
		return (object) [
			'method' => $method,
			'route' => $route,
			'params' => $params,
		];
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
	protected function remove_test_path( string $path ): void {
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
