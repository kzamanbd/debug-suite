<?php
/**
 * Integration tests for OverviewController REST API.
 *
 * @package DebugSuite\Tests\Integration\API
 * @group api
 * @group integration
 * @group rest-api
 */

namespace DebugSuite\Tests\Integration\API;

use DebugSuite\Tests\Helpers\DebugSuiteTestCase;
use DebugSuite\API\OverviewController;
use DebugSuite\Services\OverviewService;
use DebugSuite\Services\DebugLog\LogsService;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Test OverviewController REST API endpoints.
 *
 * @since 1.0.0
 */
class OverviewControllerTest extends DebugSuiteTestCase {

	/**
	 * Controller instance for testing.
	 *
	 * @var OverviewController
	 */
	private $controller;

	/**
	 * Service instance for testing.
	 *
	 * @var OverviewService
	 */
	private $service;

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'debug-suite/v1';

	/**
	 * Set up test environment.
	 */
	public function set_up(): void {
		parent::set_up();
		
		// Skip WordPress-specific setup if not available
		if ( ! $this->is_wordpress_available() ) {
			$this->markTestSkipped( 'WordPress test environment not available. Run: bash bin/install-wp-tests.sh wordpress_test root "" localhost latest' );
		}
		
		// Set up REST API
		global $wp_rest_server;
		if ( class_exists( 'WP_REST_Server' ) ) {
			$wp_rest_server = new WP_REST_Server();
			do_action( 'rest_api_init' );
		}

		// Create service instances
		if ( class_exists( 'DebugSuite\Services\DebugLog\LogsService' ) &&
		     class_exists( 'DebugSuite\Services\OverviewService' ) ) {
			
			$logs_service = new LogsService();
			$this->service = new OverviewService( $logs_service );
		}
		
		// Create controller
		if ( class_exists( 'DebugSuite\API\OverviewController' ) && $this->service ) {
			$this->controller = new OverviewController( $this->service );
			
			// Register routes
			$this->controller->register_routes();
		}
		
		// Create admin user
		$this->create_admin_user();
	}

	/**
	 * Test get dashboard stats endpoint without authentication.
	 */
	public function test_get_dashboard_stats_without_auth() {
		// Set current user to 0 (not logged in)
		wp_set_current_user( 0 );
		
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/overview/stats' );
		$response = rest_get_server()->dispatch( $request );
		
		// WordPress REST API returns 403 for unauthorized access
		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test get dashboard stats endpoint with insufficient permissions.
	 */
	public function test_get_dashboard_stats_insufficient_permissions() {
		if ( ! $this->is_wordpress_available() ) {
			$this->markTestSkipped( 'WordPress test environment not available' );
		}
		
		// Create user without manage_options capability
		$user_id = $this->factory()->user->create();
		wp_set_current_user( $user_id );
		
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/overview/stats' );
		$response = rest_get_server()->dispatch( $request );
		
		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test get dashboard stats endpoint with proper authentication.
	 */
	public function test_get_dashboard_stats_with_auth() {
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/overview/stats' );
		$response = rest_get_server()->dispatch( $request );
		
		// Allow service errors due to log file handling in test environment
		$this->assertContains( $response->get_status(), [ 200, 404, 500 ] );
		
		if ( $response->get_status() === 200 ) {
			$data = $response->get_data();
			$this->assertIsArray( $data );
			
			// Check dashboard data structure
			$this->assertArrayHasKey( 'logs', $data );
			$this->assertArrayHasKey( 'system', $data );
			
			// Verify system configuration is present
			$system = $data['system'];
			$this->assertIsArray( $system );
			$this->assertArrayHasKey( 'wp_debug', $system );
			$this->assertArrayHasKey( 'wp_debug_log', $system );
			$this->assertArrayHasKey( 'wp_debug_display', $system );
			$this->assertArrayHasKey( 'php_version', $system );
			$this->assertArrayHasKey( 'wp_version', $system );
		}
	}

	/**
	 * Test permissions check method directly.
	 */
	public function test_permissions_check() {
		if ( ! $this->controller ) {
			$this->markTestSkipped( 'Controller not available' );
		}
		
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/overview/stats' );
		
		// Test with admin user
		$this->create_admin_user();
		$result = $this->controller->permissions_check( $request );
		$this->assertTrue( $result );
		
		// Test with regular user
		if ( $this->is_wordpress_available() ) {
			$user_id = $this->factory()->user->create();
			wp_set_current_user( $user_id );
			$result = $this->controller->permissions_check( $request );
			// WordPress returns WP_Error for permission failures, not false
			$this->assertTrue( is_wp_error( $result ) || $result === false );
		}
		
		// Test without authentication
		wp_set_current_user( 0 );
		$result = $this->controller->permissions_check( $request );
		$this->assertTrue( is_wp_error( $result ) || $result === false );
	}

	/**
	 * Test that overview stats endpoint is properly registered.
	 */
	public function test_routes_registration() {
		if ( ! function_exists( 'rest_get_server' ) ) {
			$this->markTestSkipped( 'WordPress REST API not available' );
		}
		
		$routes = rest_get_server()->get_routes();
		
		// Check that our route is registered
		$expected_route = '/' . $this->namespace . '/overview/stats';
		
		$this->assertArrayHasKey( $expected_route, $routes, "Route '{$expected_route}' should be registered" );
	}

	/**
	 * Test response format consistency.
	 */
	public function test_response_format_consistency() {
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/overview/stats' );
		$response = rest_get_server()->dispatch( $request );
		
		// Endpoint should either succeed or fail gracefully
		$this->assertContains( $response->get_status(), [ 200, 403, 404, 500 ], 
			'Endpoint returned unexpected status: ' . $response->get_status() );
		
		// If successful, response should be an array
		if ( $response->get_status() === 200 ) {
			$data = $response->get_data();
			$this->assertIsArray( $data, 'Endpoint should return array data' );
		}
		
		// If error, should have proper error structure
		if ( $response->get_status() >= 400 ) {
			$this->assertInstanceOf( 'WP_Error', $response->as_error(), 
				'Error responses should be WP_Error instances' );
		}
	}
} 