<?php
/**
 * Tests for ApiLogService class.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Tests\Unit\Services;

use DebugSuite\Core\DatabaseManager;
use DebugSuite\Core\ServiceResponse;
use DebugSuite\Services\ApiLogService;
use DebugSuite\Tests\Helpers\DebugSuiteTestCase;

/**
 * Test ApiLogService functionality.
 *
 * @covers \DebugSuite\Services\ApiLogService
 * @group services
 * @group api-log
 */
class ApiLogServiceTest extends DebugSuiteTestCase {

	/**
	 * ApiLogService instance.
	 *
	 * @var ApiLogService
	 */
	private ApiLogService $service;

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

		global $wpdb;
		$this->table_name = $wpdb->prefix . 'debug_suite_api_logs';

		DatabaseManager::create_api_logs_table( 'api-logger' );

		$this->service = new ApiLogService();
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
	// Hook registration tests
	// =========================================================================

	/**
	 * Test register hooks.
	 */
	public function test_register_hooks(): void {
		$this->service->register_hooks();

		$this->assertNotFalse( has_filter( 'rest_pre_dispatch', [ $this->service, 'capture_request' ] ) );
		$this->assertNotFalse( has_filter( 'rest_post_dispatch', [ $this->service, 'log_response' ] ) );
	}

	// =========================================================================
	// Get entries tests
	// =========================================================================

	/**
	 * Test get API log entries returns success.
	 */
	public function test_get_api_log_entries_returns_success(): void {
		$this->insert_test_api_log();
		$this->insert_test_api_log();

		$result = $this->service->get_api_log_entries();

		$this->assertInstanceOf( ServiceResponse::class, $result );
		$this->assert_service_result_success( $result );

		$data = $result->get_data();
		$this->assertArrayHasKey( 'entries', $data );
		$this->assertArrayHasKey( 'pagination', $data );
		$this->assertArrayHasKey( 'total_count', $data );
		$this->assertCount( 2, $data['entries'] );
		$this->assertEquals( 2, $data['total_count'] );
	}

	/**
	 * Test get API log entries with pagination.
	 */
	public function test_get_api_log_entries_with_pagination(): void {
		for ( $i = 1; $i <= 5; $i++ ) {
			$this->insert_test_api_log( [ 'route' => "/wp/v2/posts/{$i}" ] );
		}

		// First page.
		$result = $this->service->get_api_log_entries( [
			'per_page' => 2,
			'page'     => 1,
		] );

		$this->assert_service_result_success( $result );
		$data = $result->get_data();

		$this->assertCount( 2, $data['entries'] );
		$this->assertEquals( 5, $data['total_count'] );
		$this->assertEquals( 1, $data['pagination']['current_page'] );
		$this->assertEquals( 2, $data['pagination']['per_page'] );
		$this->assertEquals( 3, $data['pagination']['total_pages'] );
		$this->assertTrue( $data['pagination']['has_more'] );

		// Second page.
		$result = $this->service->get_api_log_entries( [
			'per_page' => 2,
			'page'     => 2,
		] );

		$data = $result->get_data();
		$this->assertCount( 2, $data['entries'] );
		$this->assertEquals( 2, $data['pagination']['current_page'] );
		$this->assertTrue( $data['pagination']['has_more'] );

		// Last page.
		$result = $this->service->get_api_log_entries( [
			'per_page' => 2,
			'page'     => 3,
		] );

		$data = $result->get_data();
		$this->assertCount( 1, $data['entries'] );
		$this->assertFalse( $data['pagination']['has_more'] );
	}

	/**
	 * Test get API log entries when empty.
	 */
	public function test_get_api_log_entries_empty(): void {
		$result = $this->service->get_api_log_entries();

		$this->assert_service_result_success( $result );
		$data = $result->get_data();

		$this->assertCount( 0, $data['entries'] );
		$this->assertEquals( 0, $data['total_count'] );
	}

	// =========================================================================
	// Get detail tests
	// =========================================================================

	/**
	 * Test get API log detail success.
	 */
	public function test_get_api_log_detail_success(): void {
		$id = $this->insert_test_api_log( [
			'method'          => 'POST',
			'route'           => '/wp/v2/posts',
			'response_status' => 201,
		] );

		$result = $this->service->get_api_log_detail( $id );

		$this->assert_service_result_success( $result );

		$data = $result->get_data();
		$this->assertEquals( $id, $data['id'] );
		$this->assertEquals( 'POST', $data['method'] );
		$this->assertEquals( '/wp/v2/posts', $data['route'] );
		$this->assertEquals( 201, $data['response_status'] );
	}

	/**
	 * Test get API log detail not found.
	 */
	public function test_get_api_log_detail_not_found(): void {
		$result = $this->service->get_api_log_detail( 99999 );

		$this->assert_service_result_failure( $result );
		$this->assert_service_result_error_code( $result, 'not_found' );
	}

	// =========================================================================
	// Filter options tests
	// =========================================================================

	/**
	 * Test get filter options.
	 */
	public function test_get_filter_options(): void {
		$this->insert_test_api_log( [ 'route' => '/wp/v2/posts' ] );
		$this->insert_test_api_log( [ 'route' => '/wp/v2/users' ] );

		$result = $this->service->get_filter_options();

		$this->assert_service_result_success( $result );

		$data = $result->get_data();
		$this->assertArrayHasKey( 'routes', $data );
		$this->assertArrayHasKey( 'methods', $data );
		$this->assertArrayHasKey( 'statuses', $data );

		// Should have 2 unique routes.
		$this->assertCount( 2, $data['routes'] );

		// Methods and statuses should have predefined options.
		$this->assertNotEmpty( $data['methods'] );
		$this->assertNotEmpty( $data['statuses'] );
	}

	// =========================================================================
	// Statistics tests
	// =========================================================================

	/**
	 * Test get API statistics.
	 */
	public function test_get_api_statistics(): void {
		$this->insert_test_api_log( [ 'response_status' => 200, 'duration' => 100.0 ] );
		$this->insert_test_api_log( [ 'response_status' => 201, 'duration' => 200.0 ] );
		$this->insert_test_api_log( [ 'response_status' => 404, 'duration' => 50.0 ] );
		$this->insert_test_api_log( [ 'response_status' => 500, 'duration' => 300.0 ] );

		$result = $this->service->get_api_statistics();

		$this->assert_service_result_success( $result );

		$data = $result->get_data();
		$this->assertEquals( 4, $data['total_requests'] );
		$this->assertEquals( 2, $data['successful'] );
		$this->assertEquals( 2, $data['failed'] );
		$this->assertArrayHasKey( 'avg_duration', $data );
		$this->assertGreaterThan( 0, $data['avg_duration'] );
	}

	/**
	 * Test get API statistics on empty table.
	 */
	public function test_get_api_statistics_empty(): void {
		$result = $this->service->get_api_statistics();

		$this->assert_service_result_success( $result );

		$data = $result->get_data();
		$this->assertEquals( 0, $data['total_requests'] );
		$this->assertEquals( 0, $data['successful'] );
		$this->assertEquals( 0, $data['failed'] );
	}

	// =========================================================================
	// Bulk operations tests
	// =========================================================================

	/**
	 * Test bulk delete logs.
	 */
	public function test_bulk_delete_logs(): void {
		$id1 = $this->insert_test_api_log();
		$id2 = $this->insert_test_api_log();
		$id3 = $this->insert_test_api_log();

		$result = $this->service->bulk_delete_logs( [ $id1, $id2 ] );

		$this->assert_service_result_success( $result );

		$data = $result->get_data();
		$this->assertEquals( 2, $data['deleted_count'] );

		// Verify only 1 remains.
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
		$this->assertEquals( 1, (int) $count );
	}

	/**
	 * Test bulk delete with empty IDs returns failure.
	 */
	public function test_bulk_delete_logs_empty_ids(): void {
		$result = $this->service->bulk_delete_logs( [] );
		$this->assert_service_result_failure( $result );
	}

	/**
	 * Test clear all logs.
	 */
	public function test_clear_all_logs(): void {
		$this->insert_test_api_log();
		$this->insert_test_api_log();
		$this->insert_test_api_log();

		$result = $this->service->clear_all_logs();

		$this->assert_service_result_success( $result );

		// Verify table is empty.
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
		$this->assertEquals( 0, (int) $count );
	}
}
