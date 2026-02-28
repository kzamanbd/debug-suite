<?php
/**
 * Integration tests for ApiLogController.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Tests\Integration\API;

use DebugSuite\API\ApiLogController;
use DebugSuite\Core\DatabaseManager;
use DebugSuite\Services\ApiLogService;
use DebugSuite\Tests\Helpers\DebugSuiteTestCase;
use WP_REST_Request;
use WP_REST_Server;

/**
 * ApiLogController integration tests.
 *
 * @covers \DebugSuite\API\ApiLogController
 * @group api
 * @group integration
 * @group api-log
 */
class ApiLogControllerTest extends DebugSuiteTestCase {

	/**
	 * Controller instance.
	 *
	 * @var ApiLogController
	 */
	private ApiLogController $controller;

	/**
	 * Service instance.
	 *
	 * @var ApiLogService
	 */
	private ApiLogService $service;

	/**
	 * Namespace for REST API.
	 *
	 * @var string
	 */
	protected $namespace = 'debug-suite/v1';

	/**
	 * Table name for testing.
	 *
	 * @var string
	 */
	private string $table_name;

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		global $wpdb, $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );

		$this->table_name = $wpdb->prefix . 'debug_suite_api_logs';

		$this->service    = new ApiLogService();
		$this->controller = new ApiLogController( $this->service );

		$this->controller->register_routes();

		DatabaseManager::create_api_logs_table( 'api-logger' );

		$this->create_admin_user();
	}

	/**
	 * Clean up test environment.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$this->table_name}" );

		parent::tear_down();
	}

	/**
	 * Insert a test API log entry.
	 *
	 * @param array $data Override data.
	 * @return int Inserted ID.
	 */
	private function insert_test_api_log( array $data = [] ): int {
		global $wpdb;

		$defaults = [
			'method'           => 'GET',
			'route'            => '/wp/v2/posts',
			'url'              => 'https://example.com/wp-json/wp/v2/posts',
			'request_headers'  => '{}',
			'request_body'     => '{}',
			'request_params'   => '{}',
			'response_status'  => 200,
			'response_headers' => '{}',
			'response_body'    => '{"success":true}',
			'duration'         => 150.5,
			'user_id'          => 1,
			'user_ip'          => '127.0.0.1',
			'source'           => 'wp/v2',
			'created_at'       => current_time( 'mysql' ),
			'updated_at'       => current_time( 'mysql' ),
		];

		$data = wp_parse_args( $data, $defaults );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert( $this->table_name, $data );

		return $wpdb->insert_id;
	}

	// =========================================================================
	// Authentication & Authorization tests
	// =========================================================================

	/**
	 * Test get API logs without authentication.
	 */
	public function test_get_api_logs_without_auth(): void {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', '/' . $this->namespace . '/api-logs' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test get API logs with insufficient permissions.
	 */
	public function test_get_api_logs_insufficient_permissions(): void {
		$user_id = $this->factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $user_id );

		$request  = new WP_REST_Request( 'GET', '/' . $this->namespace . '/api-logs' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test permissions check method.
	 */
	public function test_permissions_check(): void {
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/api-logs' );

		// Test with admin user.
		$this->create_admin_user();
		$result = $this->controller->permissions_check( $request );
		$this->assertTrue( $result );

		// Test with regular user.
		$user_id = $this->factory()->user->create();
		wp_set_current_user( $user_id );
		$result = $this->controller->permissions_check( $request );
		$this->assertTrue( is_wp_error( $result ) || $result === false );

		// Test without authentication.
		wp_set_current_user( 0 );
		$result = $this->controller->permissions_check( $request );
		$this->assertTrue( is_wp_error( $result ) || $result === false );
	}

	// =========================================================================
	// GET /api-logs tests
	// =========================================================================

	/**
	 * Test get API logs with admin user.
	 */
	public function test_get_api_logs_with_admin(): void {
		$this->insert_test_api_log( [ 'method' => 'GET', 'route' => '/wp/v2/posts' ] );
		$this->insert_test_api_log( [ 'method' => 'POST', 'route' => '/wp/v2/users' ] );

		$this->create_admin_user();

		$request  = new WP_REST_Request( 'GET', '/' . $this->namespace . '/api-logs' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'entries', $data );
		$this->assertArrayHasKey( 'total_count', $data );
		$this->assertArrayHasKey( 'pagination', $data );
		$this->assertArrayHasKey( 'stats', $data );
		$this->assertArrayHasKey( 'filter_options', $data );

		$this->assertCount( 2, $data['entries'] );
		$this->assertEquals( 2, $data['total_count'] );
		$this->assertEquals( 1, $data['pagination']['current_page'] );
		$this->assertEquals( 20, $data['pagination']['per_page'] );
		$this->assertFalse( $data['pagination']['has_more'] );

		// Verify stats keys.
		$this->assertArrayHasKey( 'total_requests', $data['stats'] );
		$this->assertArrayHasKey( 'successful', $data['stats'] );
		$this->assertArrayHasKey( 'failed', $data['stats'] );

		// Verify filter options keys.
		$this->assertArrayHasKey( 'routes', $data['filter_options'] );
		$this->assertArrayHasKey( 'methods', $data['filter_options'] );
		$this->assertArrayHasKey( 'statuses', $data['filter_options'] );
	}

	/**
	 * Test get API logs with pagination.
	 */
	public function test_get_api_logs_pagination(): void {
		for ( $i = 1; $i <= 5; $i++ ) {
			$this->insert_test_api_log( [ 'route' => "/wp/v2/posts/{$i}" ] );
		}

		$this->create_admin_user();

		// First page.
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/api-logs' );
		$request->set_param( 'page', 1 );
		$request->set_param( 'per_page', 2 );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertCount( 2, $data['entries'] );
		$this->assertEquals( 5, $data['total_count'] );
		$this->assertEquals( 1, $data['pagination']['current_page'] );
		$this->assertEquals( 3, $data['pagination']['total_pages'] );
		$this->assertTrue( $data['pagination']['has_more'] );

		// Second page.
		$request->set_param( 'page', 2 );
		$response = rest_get_server()->dispatch( $request );

		$data = $response->get_data();
		$this->assertCount( 2, $data['entries'] );
		$this->assertEquals( 2, $data['pagination']['current_page'] );
		$this->assertTrue( $data['pagination']['has_more'] );
	}

	/**
	 * Test get API logs filtered by method.
	 */
	public function test_get_api_logs_filter_by_method(): void {
		$this->insert_test_api_log( [ 'method' => 'GET' ] );
		$this->insert_test_api_log( [ 'method' => 'GET' ] );
		$this->insert_test_api_log( [ 'method' => 'POST' ] );

		$this->create_admin_user();

		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/api-logs' );
		$request->set_param( 'method', 'GET' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertCount( 2, $data['entries'] );

		foreach ( $data['entries'] as $entry ) {
			$this->assertEquals( 'GET', $entry['method'] );
		}
	}

	/**
	 * Test get API logs filtered by status category.
	 */
	public function test_get_api_logs_filter_by_status(): void {
		$this->insert_test_api_log( [ 'response_status' => 200 ] );
		$this->insert_test_api_log( [ 'response_status' => 201 ] );
		$this->insert_test_api_log( [ 'response_status' => 404 ] );
		$this->insert_test_api_log( [ 'response_status' => 500 ] );

		$this->create_admin_user();

		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/api-logs' );
		$request->set_param( 'status', 'success' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertCount( 2, $data['entries'] );

		foreach ( $data['entries'] as $entry ) {
			$this->assertGreaterThanOrEqual( 200, $entry['response_status'] );
			$this->assertLessThan( 300, $entry['response_status'] );
		}
	}

	/**
	 * Test get API logs with search query.
	 */
	public function test_get_api_logs_search(): void {
		$this->insert_test_api_log( [ 'route' => '/wp/v2/users', 'source' => 'wp/v2' ] );
		$this->insert_test_api_log( [ 'route' => '/wp/v2/posts', 'source' => 'wp/v2' ] );
		$this->insert_test_api_log( [ 'route' => '/custom/v1/data', 'source' => 'custom/v1' ] );

		$this->create_admin_user();

		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/api-logs' );
		$request->set_param( 'search', 'users' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertCount( 1, $data['entries'] );
		$this->assertStringContainsString( 'users', $data['entries'][0]['route'] );
	}

	// =========================================================================
	// GET /api-logs/{id} tests
	// =========================================================================

	/**
	 * Test get single API log detail.
	 */
	public function test_get_api_log_detail(): void {
		$id = $this->insert_test_api_log( [
			'method'          => 'POST',
			'route'           => '/wp/v2/posts',
			'response_status' => 201,
			'response_body'   => '{"id":1,"title":"Test Post"}',
		] );

		$this->create_admin_user();

		$request  = new WP_REST_Request( 'GET', '/' . $this->namespace . "/api-logs/{$id}" );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( $id, $data['id'] );
		$this->assertEquals( 'POST', $data['method'] );
		$this->assertEquals( '/wp/v2/posts', $data['route'] );
		$this->assertEquals( 201, $data['response_status'] );
		$this->assertArrayHasKey( 'request_headers', $data );
		$this->assertArrayHasKey( 'request_body', $data );
		$this->assertArrayHasKey( 'response_body', $data );
	}

	/**
	 * Test get API log detail not found.
	 */
	public function test_get_api_log_detail_not_found(): void {
		$this->create_admin_user();

		$request  = new WP_REST_Request( 'GET', '/' . $this->namespace . '/api-logs/99999' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 404, $response->get_status() );
	}

	// =========================================================================
	// DELETE /api-logs/bulk tests
	// =========================================================================

	/**
	 * Test bulk delete API logs.
	 */
	public function test_bulk_delete_api_logs(): void {
		$id1 = $this->insert_test_api_log();
		$id2 = $this->insert_test_api_log();
		$id3 = $this->insert_test_api_log();

		$this->create_admin_user();

		$request = new WP_REST_Request( 'DELETE', '/' . $this->namespace . '/api-logs/bulk' );
		$request->set_param( 'ids', [ $id1, $id2 ] );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'deleted_count', $data );
		$this->assertEquals( 2, $data['deleted_count'] );

		// Verify only 1 entry remains.
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
		$this->assertEquals( 1, (int) $count );
	}

	/**
	 * Test bulk delete with empty IDs.
	 */
	public function test_bulk_delete_empty_ids(): void {
		$this->create_admin_user();

		$request = new WP_REST_Request( 'DELETE', '/' . $this->namespace . '/api-logs/bulk' );
		$request->set_param( 'ids', [] );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
	}

	// =========================================================================
	// DELETE /api-logs/clear tests
	// =========================================================================

	/**
	 * Test clear all API logs.
	 */
	public function test_clear_all_api_logs(): void {
		$this->insert_test_api_log();
		$this->insert_test_api_log();
		$this->insert_test_api_log();

		$this->create_admin_user();

		$request  = new WP_REST_Request( 'DELETE', '/' . $this->namespace . '/api-logs/clear' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Verify table is empty.
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
		$this->assertEquals( 0, (int) $count );
	}
}
