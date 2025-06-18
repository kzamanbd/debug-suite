<?php
/**
 * Integration tests for FileLogsController REST API.
 *
 * @package DebugSuite\Tests\Integration\API
 * @group api
 * @group integration
 * @group rest-api
 */

namespace DebugSuite\Tests\Integration\API;

use DebugSuite\Tests\Helpers\DebugSuiteTestCase;
use DebugSuite\API\FileLogsController;
use DebugSuite\Services\FileLogsService;
use WP_REST_Request;

/**
 * Test FileLogsController REST API endpoints.
 *
 * @since 1.0.0
 */
class FileLogsControllerTest extends DebugSuiteTestCase {

	/**
	 * Controller instance for testing.
	 *
	 * @var FileLogsController
	 */
	private $controller;

	/**
	 * Service instance for testing.
	 *
	 * @var FileLogsService
	 */
	private $service;

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	private $namespace = 'debug-suite/v1';

	/**
	 * Set up test environment.
	 */
	public function set_up(): void {
		parent::set_up();
		
		// Set up REST API
		global $wp_rest_server;
		$wp_rest_server = new \WP_REST_Server();
		do_action( 'rest_api_init' );
		
		// Create service and controller
		$this->service = new FileLogsService();
		$this->controller = new FileLogsController( $this->service );
		
		// Register routes
		$this->controller->register_routes();
		
		// Create admin user
		$this->create_admin_user();
	}

	/**
	 * Test get file logs endpoint without authentication.
	 */
	public function test_get_file_logs_without_auth() {
		// Set current user to 0 (not logged in)
		wp_set_current_user( 0 );
		
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/logs' );
		$response = rest_get_server()->dispatch( $request );
		
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test get file logs endpoint with insufficient permissions.
	 */
	public function test_get_file_logs_insufficient_permissions() {
		// Create user without manage_options capability
		$user_id = $this->factory->user->create();
		wp_set_current_user( $user_id );
		
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/logs' );
		$response = rest_get_server()->dispatch( $request );
		
		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test get file logs endpoint with proper authentication.
	 */
	public function test_get_file_logs_with_auth() {
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/logs' );
		$response = rest_get_server()->dispatch( $request );
		
		// Should return 200 even if log file doesn't exist (returns empty array)
		$this->assertContains( $response->get_status(), [ 200, 404 ] );
		
		if ( $response->get_status() === 200 ) {
			$data = $response->get_data();
			$this->assertArrayHasKey( 'success', $data );
		}
	}

	/**
	 * Test get file logs with query parameters.
	 */
	public function test_get_file_logs_with_parameters() {
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/logs' );
		$request->set_param( 'limit', 10 );
		$request->set_param( 'level', 'ERROR' );
		$request->set_param( 'search', 'test' );
		
		$response = rest_get_server()->dispatch( $request );
		
		// Should handle parameters without error
		$this->assertContains( $response->get_status(), [ 200, 404 ] );
	}

	/**
	 * Test get log stats endpoint.
	 */
	public function test_get_log_stats() {
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/logs/stats' );
		$response = rest_get_server()->dispatch( $request );
		
		$this->assertContains( $response->get_status(), [ 200, 404 ] );
		
		if ( $response->get_status() === 200 ) {
			$data = $response->get_data();
			$this->assertArrayHasKey( 'success', $data );
		}
	}

	/**
	 * Test clear log file endpoint.
	 */
	public function test_clear_log_file() {
		// Create a test log file with content
		$log_file = WP_CONTENT_DIR . '/debug.log';
		$test_content = 'Test log content';
		file_put_contents( $log_file, $test_content );
		
		$request = new WP_REST_Request( 'DELETE', '/' . $this->namespace . '/logs/clear' );
		$response = rest_get_server()->dispatch( $request );
		
		$this->assertEquals( 200, $response->get_status() );
		
		$data = $response->get_data();
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
		
		// Verify file was cleared
		if ( file_exists( $log_file ) ) {
			$this->assertEquals( 0, filesize( $log_file ) );
		}
	}

	/**
	 * Test endpoint with malformed request.
	 */
	public function test_malformed_request() {
		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/logs/invalid' );
		$response = rest_get_server()->dispatch( $request );
		
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test permissions check method directly.
	 */
	public function test_permissions_check() {
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/logs' );
		
		// Test with admin user
		$this->create_admin_user();
		$this->assertTrue( $this->controller->permissions_check( $request ) );
		
		// Test with regular user
		$user_id = $this->factory->user->create();
		wp_set_current_user( $user_id );
		$this->assertFalse( $this->controller->permissions_check( $request ) );
		
		// Test without authentication
		wp_set_current_user( 0 );
		$this->assertFalse( $this->controller->permissions_check( $request ) );
	}
}
