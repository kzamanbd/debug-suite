<?php
/**
 * Tests for QueryBuilder class.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Tests\Unit\Models;

use DebugSuite\Core\DatabaseManager;
use DebugSuite\Models\ApiLog;
use DebugSuite\Tests\Helpers\DebugSuiteTestCase;

/**
 * Test QueryBuilder fluent query building.
 *
 * @covers \DebugSuite\Models\QueryBuilder
 * @group models
 * @group query-builder
 */
class QueryBuilderTest extends DebugSuiteTestCase {

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
	// where() tests
	// =========================================================================

	/**
	 * Test where equality (2-arg form).
	 */
	public function test_where_equality(): void {
		$this->insert_test_api_log( [ 'method' => 'GET' ] );
		$this->insert_test_api_log( [ 'method' => 'POST' ] );
		$this->insert_test_api_log( [ 'method' => 'GET' ] );

		$results = ApiLog::query()
			->where( 'method', 'GET' )
			->get();

		$this->assertCount( 2, $results );

		foreach ( $results as $result ) {
			$this->assertEquals( 'GET', $result->method );
		}
	}

	/**
	 * Test where with comparison operator (3-arg form).
	 */
	public function test_where_with_operator(): void {
		$this->insert_test_api_log( [ 'response_status' => 200 ] );
		$this->insert_test_api_log( [ 'response_status' => 404 ] );
		$this->insert_test_api_log( [ 'response_status' => 500 ] );

		$results = ApiLog::query()
			->where( 'response_status', '>=', 400 )
			->get();

		$this->assertCount( 2, $results );

		foreach ( $results as $result ) {
			$this->assertGreaterThanOrEqual( 400, $result->response_status );
		}
	}

	/**
	 * Test where with LIKE operator.
	 */
	public function test_where_like(): void {
		$this->insert_test_api_log( [ 'route' => '/wp/v2/posts' ] );
		$this->insert_test_api_log( [ 'route' => '/wp/v2/users' ] );
		$this->insert_test_api_log( [ 'route' => '/wp/v2/posts/123' ] );

		$results = ApiLog::query()
			->where( 'route', 'LIKE', 'posts' )
			->get();

		$this->assertCount( 2, $results );

		foreach ( $results as $result ) {
			$this->assertStringContainsString( 'posts', $result->route );
		}
	}

	/**
	 * Test where with not-equal operator.
	 */
	public function test_where_not_equal(): void {
		$this->insert_test_api_log( [ 'method' => 'GET' ] );
		$this->insert_test_api_log( [ 'method' => 'POST' ] );
		$this->insert_test_api_log( [ 'method' => 'PUT' ] );

		$results = ApiLog::query()
			->where( 'method', '!=', 'GET' )
			->get();

		$this->assertCount( 2, $results );

		foreach ( $results as $result ) {
			$this->assertNotEquals( 'GET', $result->method );
		}
	}

	/**
	 * Test chaining multiple where calls combines with AND.
	 */
	public function test_where_chaining(): void {
		$this->insert_test_api_log( [ 'method' => 'GET', 'response_status' => 200 ] );
		$this->insert_test_api_log( [ 'method' => 'GET', 'response_status' => 404 ] );
		$this->insert_test_api_log( [ 'method' => 'POST', 'response_status' => 200 ] );

		$results = ApiLog::query()
			->where( 'method', 'GET' )
			->where( 'response_status', 200 )
			->get();

		$this->assertCount( 1, $results );
		$this->assertEquals( 'GET', $results[0]->method );
		$this->assertEquals( 200, $results[0]->response_status );
	}

	// =========================================================================
	// where_any() tests
	// =========================================================================

	/**
	 * Test where_any equality (2-arg form).
	 */
	public function test_where_any_equality(): void {
		$this->insert_test_api_log( [ 'route' => 'test-route', 'source' => 'other/v1' ] );
		$this->insert_test_api_log( [ 'route' => '/wp/v2/posts', 'source' => 'test-route' ] );
		$this->insert_test_api_log( [ 'route' => '/wp/v2/users', 'source' => 'wp/v2' ] );

		$results = ApiLog::query()
			->where_any( [ 'route', 'source' ], 'test-route' )
			->get();

		$this->assertCount( 2, $results );
	}

	/**
	 * Test where_any with LIKE operator (3-arg form).
	 */
	public function test_where_any_with_operator(): void {
		$this->insert_test_api_log( [ 'route' => '/wp/v2/posts', 'url' => 'https://example.com/wp-json/wp/v2/posts' ] );
		$this->insert_test_api_log( [ 'route' => '/wp/v2/users', 'url' => 'https://example.com/wp-json/wp/v2/users' ] );
		$this->insert_test_api_log( [ 'route' => '/custom/v1/data', 'url' => 'https://example.com/wp-json/custom/v1/data' ] );

		$results = ApiLog::query()
			->where_any( [ 'route', 'url' ], 'LIKE', 'posts' )
			->get();

		$this->assertCount( 1, $results );
		$this->assertStringContainsString( 'posts', $results[0]->route );
	}

	// =========================================================================
	// where_in() tests
	// =========================================================================

	/**
	 * Test where_in with multiple values.
	 */
	public function test_where_in(): void {
		$this->insert_test_api_log( [ 'method' => 'GET' ] );
		$this->insert_test_api_log( [ 'method' => 'POST' ] );
		$this->insert_test_api_log( [ 'method' => 'PUT' ] );
		$this->insert_test_api_log( [ 'method' => 'DELETE' ] );

		$results = ApiLog::query()
			->where_in( 'method', [ 'GET', 'POST' ] )
			->get();

		$this->assertCount( 2, $results );

		$methods = array_map( fn( $r ) => $r->method, $results );
		$this->assertContains( 'GET', $methods );
		$this->assertContains( 'POST', $methods );
	}

	/**
	 * Test where_in with empty array returns no results.
	 */
	public function test_where_in_empty(): void {
		$this->insert_test_api_log( [ 'method' => 'GET' ] );

		$results = ApiLog::query()
			->where_in( 'method', [] )
			->get();

		$this->assertCount( 0, $results );
	}

	// =========================================================================
	// where_not_empty() tests
	// =========================================================================

	/**
	 * Test where_not_empty filters out empty values.
	 */
	public function test_where_not_empty(): void {
		$this->insert_test_api_log( [ 'source' => 'wp/v2' ] );
		$this->insert_test_api_log( [ 'source' => '' ] );
		$this->insert_test_api_log( [ 'source' => 'custom/v1' ] );

		$results = ApiLog::query()
			->where_not_empty( 'source' )
			->get();

		$this->assertCount( 2, $results );

		foreach ( $results as $result ) {
			$this->assertNotEmpty( $result->source );
		}
	}

	// =========================================================================
	// where_raw() tests
	// =========================================================================

	/**
	 * Test where_raw with prepared values.
	 */
	public function test_where_raw(): void {
		$this->insert_test_api_log( [ 'response_status' => 200 ] );
		$this->insert_test_api_log( [ 'response_status' => 301 ] );
		$this->insert_test_api_log( [ 'response_status' => 404 ] );
		$this->insert_test_api_log( [ 'response_status' => 500 ] );

		$results = ApiLog::query()
			->where_raw( '(response_status >= %d AND response_status < %d)', [ 200, 300 ] )
			->get();

		$this->assertCount( 1, $results );
		$this->assertEquals( 200, $results[0]->response_status );
	}

	// =========================================================================
	// Ordering & Pagination tests
	// =========================================================================

	/**
	 * Test order_by ascending.
	 */
	public function test_order_by_asc(): void {
		$this->insert_test_api_log( [ 'method' => 'POST' ] );
		$this->insert_test_api_log( [ 'method' => 'GET' ] );
		$this->insert_test_api_log( [ 'method' => 'PUT' ] );

		$results = ApiLog::query()
			->order_by( 'method', 'ASC' )
			->get();

		$this->assertCount( 3, $results );
		$this->assertEquals( 'GET', $results[0]->method );
		$this->assertEquals( 'POST', $results[1]->method );
		$this->assertEquals( 'PUT', $results[2]->method );
	}

	/**
	 * Test order_by descending.
	 */
	public function test_order_by_desc(): void {
		$this->insert_test_api_log( [ 'duration' => 100.0 ] );
		$this->insert_test_api_log( [ 'duration' => 300.0 ] );
		$this->insert_test_api_log( [ 'duration' => 200.0 ] );

		$results = ApiLog::query()
			->order_by( 'duration', 'DESC' )
			->get();

		$this->assertCount( 3, $results );
		$this->assertEquals( 300.0, $results[0]->duration );
		$this->assertEquals( 200.0, $results[1]->duration );
		$this->assertEquals( 100.0, $results[2]->duration );
	}

	/**
	 * Test limit.
	 */
	public function test_limit(): void {
		$this->insert_test_api_log();
		$this->insert_test_api_log();
		$this->insert_test_api_log();
		$this->insert_test_api_log();
		$this->insert_test_api_log();

		$results = ApiLog::query()
			->limit( 3 )
			->get();

		$this->assertCount( 3, $results );
	}

	/**
	 * Test limit with offset for pagination.
	 */
	public function test_limit_and_offset(): void {
		for ( $i = 1; $i <= 5; $i++ ) {
			$this->insert_test_api_log( [ 'route' => "/wp/v2/posts/{$i}" ] );
		}

		$results = ApiLog::query()
			->order_by( 'id', 'ASC' )
			->limit( 2 )
			->offset( 2 )
			->get();

		$this->assertCount( 2, $results );
		$this->assertEquals( '/wp/v2/posts/3', $results[0]->route );
		$this->assertEquals( '/wp/v2/posts/4', $results[1]->route );
	}

	// =========================================================================
	// Execution methods tests
	// =========================================================================

	/**
	 * Test get() returns array of model instances.
	 */
	public function test_get_returns_model_instances(): void {
		$this->insert_test_api_log();
		$this->insert_test_api_log();

		$results = ApiLog::query()->get();

		$this->assertCount( 2, $results );

		foreach ( $results as $result ) {
			$this->assertInstanceOf( ApiLog::class, $result );
		}
	}

	/**
	 * Test first() returns single model or null.
	 */
	public function test_first_returns_single_model(): void {
		$this->insert_test_api_log( [ 'method' => 'POST' ] );
		$this->insert_test_api_log( [ 'method' => 'GET' ] );

		$result = ApiLog::query()
			->where( 'method', 'POST' )
			->first();

		$this->assertInstanceOf( ApiLog::class, $result );
		$this->assertEquals( 'POST', $result->method );

		// Test null when no match.
		$result = ApiLog::query()
			->where( 'method', 'PATCH' )
			->first();

		$this->assertNull( $result );
	}

	/**
	 * Test count() returns correct integer.
	 */
	public function test_count(): void {
		$this->insert_test_api_log( [ 'method' => 'GET' ] );
		$this->insert_test_api_log( [ 'method' => 'GET' ] );
		$this->insert_test_api_log( [ 'method' => 'POST' ] );

		$total = ApiLog::query()->count();
		$this->assertEquals( 3, $total );

		$get_count = ApiLog::query()
			->where( 'method', 'GET' )
			->count();
		$this->assertEquals( 2, $get_count );
	}

	/**
	 * Test pluck() returns flat array of column values.
	 */
	public function test_pluck(): void {
		$this->insert_test_api_log( [ 'method' => 'GET' ] );
		$this->insert_test_api_log( [ 'method' => 'POST' ] );
		$this->insert_test_api_log( [ 'method' => 'PUT' ] );

		$methods = ApiLog::query()
			->order_by( 'method', 'ASC' )
			->pluck( 'method' );

		$this->assertEquals( [ 'GET', 'POST', 'PUT' ], $methods );
	}

	/**
	 * Test get_row() returns raw associative array.
	 */
	public function test_get_row(): void {
		$this->insert_test_api_log( [ 'method' => 'GET', 'duration' => 100.0 ] );
		$this->insert_test_api_log( [ 'method' => 'POST', 'duration' => 200.0 ] );

		$row = ApiLog::query()
			->select_raw( 'COUNT(*) as total, ROUND(AVG(duration), 2) as avg_duration' )
			->get_row();

		$this->assertIsArray( $row );
		$this->assertEquals( 2, $row['total'] );
		$this->assertEquals( 150.0, (float) $row['avg_duration'] );
	}

	/**
	 * Test distinct() removes duplicate values.
	 */
	public function test_distinct(): void {
		$this->insert_test_api_log( [ 'method' => 'GET' ] );
		$this->insert_test_api_log( [ 'method' => 'GET' ] );
		$this->insert_test_api_log( [ 'method' => 'POST' ] );

		$methods = ApiLog::query()
			->distinct()
			->order_by( 'method', 'ASC' )
			->pluck( 'method' );

		$this->assertCount( 2, $methods );
		$this->assertEquals( [ 'GET', 'POST' ], $methods );
	}

	/**
	 * Test select_raw() with aggregates.
	 */
	public function test_select_raw_with_aggregates(): void {
		$this->insert_test_api_log( [ 'response_status' => 200 ] );
		$this->insert_test_api_log( [ 'response_status' => 201 ] );
		$this->insert_test_api_log( [ 'response_status' => 404 ] );
		$this->insert_test_api_log( [ 'response_status' => 500 ] );

		$row = ApiLog::query()
			->select_raw(
				'COUNT(*) as total, '
				. 'SUM(CASE WHEN response_status >= 200 AND response_status < 300 THEN 1 ELSE 0 END) as successful, '
				. 'SUM(CASE WHEN response_status >= 400 THEN 1 ELSE 0 END) as failed'
			)
			->get_row();

		$this->assertIsArray( $row );
		$this->assertEquals( 4, (int) $row['total'] );
		$this->assertEquals( 2, (int) $row['successful'] );
		$this->assertEquals( 2, (int) $row['failed'] );
	}

	/**
	 * Test get() on empty table returns empty array.
	 */
	public function test_get_empty_table(): void {
		$results = ApiLog::query()->get();
		$this->assertIsArray( $results );
		$this->assertCount( 0, $results );
	}

	/**
	 * Test count() on empty table returns zero.
	 */
	public function test_count_empty_table(): void {
		$count = ApiLog::query()->count();
		$this->assertEquals( 0, $count );
	}
}
