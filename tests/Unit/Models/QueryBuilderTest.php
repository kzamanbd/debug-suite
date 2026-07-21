<?php
/**
 * Tests for QueryBuilder class.
 *
 * Exercised against a feature-agnostic fixture model (QbFixture) backed by a
 * throwaway `debug_suite_qb_fixtures` table, so QueryBuilder coverage does not
 * depend on any shipped feature model.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Tests\Unit\Models;

use DebugSuite\Tests\Helpers\DebugSuiteTestCase;
use DebugSuite\Tests\Helpers\QbFixture;

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

		$this->table_name = ( new QbFixture() )->get_table_name();
		QbFixture::create_table();
	}

	/**
	 * Clean up test environment.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		QbFixture::drop_table();

		parent::tear_down();
	}

	/**
	 * Insert a test fixture row.
	 *
	 * @param array $data Override data.
	 * @return int Inserted ID.
	 */
	private function insert_fixture( array $data = [] ): int {
		global $wpdb;

		$defaults = [
			'name'       => 'record',
			'category'   => 'a',
			'value'      => 200,
			'score'      => 150.5,
			'label'      => 'default-label',
			'created_at' => current_time( 'mysql' ),
			'updated_at' => current_time( 'mysql' ),
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
		$this->insert_fixture( [ 'category' => 'a' ] );
		$this->insert_fixture( [ 'category' => 'b' ] );
		$this->insert_fixture( [ 'category' => 'a' ] );

		$results = QbFixture::query()
			->where( 'category', 'a' )
			->get();

		$this->assertCount( 2, $results );

		foreach ( $results as $result ) {
			$this->assertEquals( 'a', $result->category );
		}
	}

	/**
	 * Test where with comparison operator (3-arg form).
	 */
	public function test_where_with_operator(): void {
		$this->insert_fixture( [ 'value' => 200 ] );
		$this->insert_fixture( [ 'value' => 404 ] );
		$this->insert_fixture( [ 'value' => 500 ] );

		$results = QbFixture::query()
			->where( 'value', '>=', 400 )
			->get();

		$this->assertCount( 2, $results );

		foreach ( $results as $result ) {
			$this->assertGreaterThanOrEqual( 400, $result->value );
		}
	}

	/**
	 * Test where with LIKE operator.
	 */
	public function test_where_like(): void {
		$this->insert_fixture( [ 'name' => 'posts-index' ] );
		$this->insert_fixture( [ 'name' => 'users-index' ] );
		$this->insert_fixture( [ 'name' => 'posts-detail' ] );

		$results = QbFixture::query()
			->where( 'name', 'LIKE', 'posts' )
			->get();

		$this->assertCount( 2, $results );

		foreach ( $results as $result ) {
			$this->assertStringContainsString( 'posts', $result->name );
		}
	}

	/**
	 * Test where with not-equal operator.
	 */
	public function test_where_not_equal(): void {
		$this->insert_fixture( [ 'category' => 'a' ] );
		$this->insert_fixture( [ 'category' => 'b' ] );
		$this->insert_fixture( [ 'category' => 'c' ] );

		$results = QbFixture::query()
			->where( 'category', '!=', 'a' )
			->get();

		$this->assertCount( 2, $results );

		foreach ( $results as $result ) {
			$this->assertNotEquals( 'a', $result->category );
		}
	}

	/**
	 * Test chaining multiple where calls combines with AND.
	 */
	public function test_where_chaining(): void {
		$this->insert_fixture( [ 'category' => 'a', 'value' => 200 ] );
		$this->insert_fixture( [ 'category' => 'a', 'value' => 404 ] );
		$this->insert_fixture( [ 'category' => 'b', 'value' => 200 ] );

		$results = QbFixture::query()
			->where( 'category', 'a' )
			->where( 'value', 200 )
			->get();

		$this->assertCount( 1, $results );
		$this->assertEquals( 'a', $results[0]->category );
		$this->assertEquals( 200, $results[0]->value );
	}

	// =========================================================================
	// where_any() tests
	// =========================================================================

	/**
	 * Test where_any equality (2-arg form).
	 */
	public function test_where_any_equality(): void {
		$this->insert_fixture( [ 'name' => 'match', 'label' => 'other' ] );
		$this->insert_fixture( [ 'name' => 'x', 'label' => 'match' ] );
		$this->insert_fixture( [ 'name' => 'y', 'label' => 'z' ] );

		$results = QbFixture::query()
			->where_any( [ 'name', 'label' ], 'match' )
			->get();

		$this->assertCount( 2, $results );
	}

	/**
	 * Test where_any with LIKE operator (3-arg form).
	 */
	public function test_where_any_with_operator(): void {
		$this->insert_fixture( [ 'name' => 'posts-a', 'label' => 'l1' ] );
		$this->insert_fixture( [ 'name' => 'users-a', 'label' => 'l2' ] );
		$this->insert_fixture( [ 'name' => 'data-a', 'label' => 'l3' ] );

		$results = QbFixture::query()
			->where_any( [ 'name', 'label' ], 'LIKE', 'posts' )
			->get();

		$this->assertCount( 1, $results );
		$this->assertStringContainsString( 'posts', $results[0]->name );
	}

	// =========================================================================
	// where_in() tests
	// =========================================================================

	/**
	 * Test where_in with multiple values.
	 */
	public function test_where_in(): void {
		$this->insert_fixture( [ 'category' => 'a' ] );
		$this->insert_fixture( [ 'category' => 'b' ] );
		$this->insert_fixture( [ 'category' => 'c' ] );
		$this->insert_fixture( [ 'category' => 'd' ] );

		$results = QbFixture::query()
			->where_in( 'category', [ 'a', 'b' ] )
			->get();

		$this->assertCount( 2, $results );

		$categories = array_map( fn( $r ) => $r->category, $results );
		$this->assertContains( 'a', $categories );
		$this->assertContains( 'b', $categories );
	}

	/**
	 * Test where_in with empty array returns no results.
	 */
	public function test_where_in_empty(): void {
		$this->insert_fixture( [ 'category' => 'a' ] );

		$results = QbFixture::query()
			->where_in( 'category', [] )
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
		$this->insert_fixture( [ 'label' => 'x' ] );
		$this->insert_fixture( [ 'label' => '' ] );
		$this->insert_fixture( [ 'label' => 'y' ] );

		$results = QbFixture::query()
			->where_not_empty( 'label' )
			->get();

		$this->assertCount( 2, $results );

		foreach ( $results as $result ) {
			$this->assertNotEmpty( $result->label );
		}
	}

	// =========================================================================
	// where_raw() tests
	// =========================================================================

	/**
	 * Test where_raw with prepared values.
	 */
	public function test_where_raw(): void {
		$this->insert_fixture( [ 'value' => 200 ] );
		$this->insert_fixture( [ 'value' => 301 ] );
		$this->insert_fixture( [ 'value' => 404 ] );
		$this->insert_fixture( [ 'value' => 500 ] );

		$results = QbFixture::query()
			->where_raw( '(value >= %d AND value < %d)', [ 200, 300 ] )
			->get();

		$this->assertCount( 1, $results );
		$this->assertEquals( 200, $results[0]->value );
	}

	// =========================================================================
	// Ordering & Pagination tests
	// =========================================================================

	/**
	 * Test order_by ascending.
	 */
	public function test_order_by_asc(): void {
		$this->insert_fixture( [ 'category' => 'c' ] );
		$this->insert_fixture( [ 'category' => 'a' ] );
		$this->insert_fixture( [ 'category' => 'b' ] );

		$results = QbFixture::query()
			->order_by( 'category', 'ASC' )
			->get();

		$this->assertCount( 3, $results );
		$this->assertEquals( 'a', $results[0]->category );
		$this->assertEquals( 'b', $results[1]->category );
		$this->assertEquals( 'c', $results[2]->category );
	}

	/**
	 * Test order_by descending.
	 */
	public function test_order_by_desc(): void {
		$this->insert_fixture( [ 'score' => 100.0 ] );
		$this->insert_fixture( [ 'score' => 300.0 ] );
		$this->insert_fixture( [ 'score' => 200.0 ] );

		$results = QbFixture::query()
			->order_by( 'score', 'DESC' )
			->get();

		$this->assertCount( 3, $results );
		$this->assertEquals( 300.0, $results[0]->score );
		$this->assertEquals( 200.0, $results[1]->score );
		$this->assertEquals( 100.0, $results[2]->score );
	}

	/**
	 * Test limit.
	 */
	public function test_limit(): void {
		$this->insert_fixture();
		$this->insert_fixture();
		$this->insert_fixture();
		$this->insert_fixture();
		$this->insert_fixture();

		$results = QbFixture::query()
			->limit( 3 )
			->get();

		$this->assertCount( 3, $results );
	}

	/**
	 * Test limit with offset for pagination.
	 */
	public function test_limit_and_offset(): void {
		for ( $i = 1; $i <= 5; $i++ ) {
			$this->insert_fixture( [ 'name' => "record-{$i}" ] );
		}

		$results = QbFixture::query()
			->order_by( 'id', 'ASC' )
			->limit( 2 )
			->offset( 2 )
			->get();

		$this->assertCount( 2, $results );
		$this->assertEquals( 'record-3', $results[0]->name );
		$this->assertEquals( 'record-4', $results[1]->name );
	}

	// =========================================================================
	// Execution methods tests
	// =========================================================================

	/**
	 * Test get() returns array of model instances.
	 */
	public function test_get_returns_model_instances(): void {
		$this->insert_fixture();
		$this->insert_fixture();

		$results = QbFixture::query()->get();

		$this->assertCount( 2, $results );

		foreach ( $results as $result ) {
			$this->assertInstanceOf( QbFixture::class, $result );
		}
	}

	/**
	 * Test first() returns single model or null.
	 */
	public function test_first_returns_single_model(): void {
		$this->insert_fixture( [ 'category' => 'b' ] );
		$this->insert_fixture( [ 'category' => 'a' ] );

		$result = QbFixture::query()
			->where( 'category', 'b' )
			->first();

		$this->assertInstanceOf( QbFixture::class, $result );
		$this->assertEquals( 'b', $result->category );

		// Test null when no match.
		$result = QbFixture::query()
			->where( 'category', 'z' )
			->first();

		$this->assertNull( $result );
	}

	/**
	 * Test count() returns correct integer.
	 */
	public function test_count(): void {
		$this->insert_fixture( [ 'category' => 'a' ] );
		$this->insert_fixture( [ 'category' => 'a' ] );
		$this->insert_fixture( [ 'category' => 'b' ] );

		$total = QbFixture::query()->count();
		$this->assertEquals( 3, $total );

		$a_count = QbFixture::query()
			->where( 'category', 'a' )
			->count();
		$this->assertEquals( 2, $a_count );
	}

	/**
	 * Test pluck() returns flat array of column values.
	 */
	public function test_pluck(): void {
		$this->insert_fixture( [ 'category' => 'a' ] );
		$this->insert_fixture( [ 'category' => 'b' ] );
		$this->insert_fixture( [ 'category' => 'c' ] );

		$categories = QbFixture::query()
			->order_by( 'category', 'ASC' )
			->pluck( 'category' );

		$this->assertEquals( [ 'a', 'b', 'c' ], $categories );
	}

	/**
	 * Test get_row() returns raw associative array.
	 */
	public function test_get_row(): void {
		$this->insert_fixture( [ 'category' => 'a', 'score' => 100.0 ] );
		$this->insert_fixture( [ 'category' => 'b', 'score' => 200.0 ] );

		$row = QbFixture::query()
			->select_raw( 'COUNT(*) as total, ROUND(AVG(score), 2) as avg_score' )
			->get_row();

		$this->assertIsArray( $row );
		$this->assertEquals( 2, $row['total'] );
		$this->assertEquals( 150.0, (float) $row['avg_score'] );
	}

	/**
	 * Test distinct() removes duplicate values.
	 */
	public function test_distinct(): void {
		$this->insert_fixture( [ 'category' => 'a' ] );
		$this->insert_fixture( [ 'category' => 'a' ] );
		$this->insert_fixture( [ 'category' => 'b' ] );

		$categories = QbFixture::query()
			->distinct()
			->order_by( 'category', 'ASC' )
			->pluck( 'category' );

		$this->assertCount( 2, $categories );
		$this->assertEquals( [ 'a', 'b' ], $categories );
	}

	/**
	 * Test select_raw() with aggregates.
	 */
	public function test_select_raw_with_aggregates(): void {
		$this->insert_fixture( [ 'value' => 200 ] );
		$this->insert_fixture( [ 'value' => 201 ] );
		$this->insert_fixture( [ 'value' => 404 ] );
		$this->insert_fixture( [ 'value' => 500 ] );

		$row = QbFixture::query()
			->select_raw(
				'COUNT(*) as total, '
				. 'SUM(CASE WHEN value >= 200 AND value < 300 THEN 1 ELSE 0 END) as successful, '
				. 'SUM(CASE WHEN value >= 400 THEN 1 ELSE 0 END) as failed'
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
		$results = QbFixture::query()->get();
		$this->assertIsArray( $results );
		$this->assertCount( 0, $results );
	}

	/**
	 * Test count() on empty table returns zero.
	 */
	public function test_count_empty_table(): void {
		$count = QbFixture::query()->count();
		$this->assertEquals( 0, $count );
	}

	// =========================================================================
	// delete() / truncate() tests
	// =========================================================================

	/**
	 * Test delete() removes only rows matching the WHERE clause.
	 */
	public function test_delete_removes_matching_rows(): void {
		$this->insert_fixture( [ 'category' => 'a' ] );
		$this->insert_fixture( [ 'category' => 'b' ] );
		$this->insert_fixture( [ 'category' => 'a' ] );

		$deleted = QbFixture::query()
			->where( 'category', 'a' )
			->delete();

		$this->assertEquals( 2, $deleted );
		$this->assertEquals( 1, QbFixture::query()->count() );
		$this->assertEquals( 'b', QbFixture::query()->first()->category );
	}

	/**
	 * Test delete() with where_in returns affected count.
	 */
	public function test_delete_with_where_in(): void {
		$id1 = $this->insert_fixture();
		$id2 = $this->insert_fixture();
		$this->insert_fixture();

		$deleted = QbFixture::query()
			->where_in( 'id', [ $id1, $id2 ] )
			->delete();

		$this->assertEquals( 2, $deleted );
		$this->assertEquals( 1, QbFixture::query()->count() );
	}

	/**
	 * Test delete() without a WHERE clause throws to prevent a full-table wipe.
	 */
	public function test_delete_without_where_throws(): void {
		$this->insert_fixture();

		$this->expectException( \RuntimeException::class );

		QbFixture::query()->delete();
	}

	/**
	 * Test truncate() empties the table.
	 */
	public function test_truncate_empties_table(): void {
		$this->insert_fixture();
		$this->insert_fixture();
		$this->insert_fixture();

		$result = QbFixture::query()->truncate();

		$this->assertTrue( $result );
		$this->assertEquals( 0, QbFixture::query()->count() );
	}
}
