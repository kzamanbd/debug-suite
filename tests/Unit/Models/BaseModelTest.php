<?php
/**
 * Tests for BaseModel finder delegation and starter forwarding.
 *
 * Exercised against the feature-agnostic QbFixture model backed by the
 * throwaway `debug_suite_qb_fixtures` table.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Tests\Unit\Models;

use DebugSuite\Tests\Helpers\DebugSuiteTestCase;
use DebugSuite\Tests\Helpers\QbFixture;

/**
 * Test BaseModel terminal finders + QueryBuilder starter forwarding.
 *
 * @covers \DebugSuite\Models\BaseModel
 * @group models
 * @group base-model
 */
class BaseModelTest extends DebugSuiteTestCase {

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();
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
	 * Persist a fixture row via the model.
	 *
	 * @param array $data Override data.
	 * @return QbFixture
	 */
	private function seed( array $data = [] ): QbFixture {
		$defaults = [
			'name'     => 'rec',
			'category' => 'a',
			'value'    => 1,
			'score'    => 1.0,
			'label'    => 'lbl',
		];

		return QbFixture::create( wp_parse_args( $data, $defaults ) );
	}

	// =========================================================================
	// find / find_many
	// =========================================================================

	/**
	 * Test find() returns the model for a primary key.
	 */
	public function test_find_returns_model_by_pk(): void {
		$row = $this->seed( [ 'category' => 'x' ] );

		$found = QbFixture::find( $row->id );

		$this->assertInstanceOf( QbFixture::class, $found );
		$this->assertEquals( 'x', $found->category );
	}

	/**
	 * Test find() returns null when no row matches.
	 */
	public function test_find_returns_null_when_missing(): void {
		$this->assertNull( QbFixture::find( 99999 ) );
	}

	/**
	 * Test find_many() returns models for the given IDs.
	 */
	public function test_find_many_returns_models_for_ids(): void {
		$a = $this->seed();
		$b = $this->seed();
		$this->seed();

		$rows = QbFixture::find_many( [ $a->id, $b->id ] );

		$this->assertCount( 2, $rows );
		$this->assertContainsOnlyInstancesOf( QbFixture::class, $rows );
	}

	// =========================================================================
	// all / first / count
	// =========================================================================

	/**
	 * Test all() returns every row ordered by primary key DESC.
	 */
	public function test_all_returns_every_row_pk_desc(): void {
		$this->seed( [ 'name' => 'first' ] );
		$b = $this->seed( [ 'name' => 'second' ] );

		$rows = QbFixture::all();

		$this->assertCount( 2, $rows );
		$this->assertEquals( $b->id, $rows[0]->id );
	}

	/**
	 * Test first() returns a model instance.
	 */
	public function test_first_returns_a_model(): void {
		$this->seed();

		$this->assertInstanceOf( QbFixture::class, QbFixture::first() );
	}

	/**
	 * Test first() returns null on an empty table.
	 */
	public function test_first_returns_null_on_empty(): void {
		$this->assertNull( QbFixture::first() );
	}

	/**
	 * Test count() returns the total row count.
	 */
	public function test_count_returns_total(): void {
		$this->seed();
		$this->seed();
		$this->seed();

		$this->assertEquals( 3, QbFixture::count() );
	}

	// =========================================================================
	// latest / oldest
	// =========================================================================

	/**
	 * Test latest() returns the highest value for a valid column.
	 */
	public function test_latest_returns_most_recent_by_column(): void {
		$this->seed( [ 'score' => 1.0 ] );
		$this->seed( [ 'score' => 9.0 ] );
		$this->seed( [ 'score' => 5.0 ] );

		$this->assertEquals( 9.0, QbFixture::latest( 'score' )->score );
	}

	/**
	 * Test oldest() returns the lowest value for a valid column.
	 */
	public function test_oldest_returns_earliest_by_column(): void {
		$this->seed( [ 'score' => 1.0 ] );
		$this->seed( [ 'score' => 9.0 ] );
		$this->seed( [ 'score' => 5.0 ] );

		$this->assertEquals( 1.0, QbFixture::oldest( 'score' )->score );
	}

	/**
	 * Test latest() returns null for an unknown column.
	 */
	public function test_latest_returns_null_for_invalid_column(): void {
		$this->seed();

		$this->assertNull( QbFixture::latest( 'bogus_col' ) );
	}

	/**
	 * Test oldest() returns null for an unknown column.
	 */
	public function test_oldest_returns_null_for_invalid_column(): void {
		$this->seed();

		$this->assertNull( QbFixture::oldest( 'bogus_col' ) );
	}

	// =========================================================================
	// create / destroy / truncate
	// =========================================================================

	/**
	 * Test create() persists a row and returns the model.
	 */
	public function test_create_persists_and_returns_model(): void {
		$model = QbFixture::create(
			[
				'name'     => 'new',
				'category' => 'z',
				'value'    => 7,
				'score'    => 2.0,
				'label'    => 'l',
			]
		);

		$this->assertInstanceOf( QbFixture::class, $model );
		$this->assertNotEmpty( $model->id );
		$this->assertEquals( 'z', QbFixture::find( $model->id )->category );
	}

	/**
	 * Test destroy() deletes rows by IDs and returns the count.
	 */
	public function test_destroy_deletes_by_ids_returns_count(): void {
		$a = $this->seed();
		$b = $this->seed();
		$this->seed();

		$deleted = QbFixture::destroy( [ $a->id, $b->id ] );

		$this->assertEquals( 2, $deleted );
		$this->assertEquals( 1, QbFixture::count() );
	}

	/**
	 * Test truncate() empties the table.
	 */
	public function test_truncate_empties(): void {
		$this->seed();
		$this->seed();

		$this->assertTrue( QbFixture::truncate() );
		$this->assertEquals( 0, QbFixture::count() );
	}

	// =========================================================================
	// Starter forwarding (static → QueryBuilder)
	// =========================================================================

	/**
	 * Test where() as a static starter returns a builder and filters.
	 */
	public function test_where_starter_returns_builder_and_filters(): void {
		$this->seed( [ 'category' => 'a' ] );
		$this->seed( [ 'category' => 'b' ] );
		$this->seed( [ 'category' => 'a' ] );

		$rows = QbFixture::where( 'category', 'a' )->get();

		$this->assertCount( 2, $rows );
	}

	/**
	 * Test where() starter with a comparison operator.
	 */
	public function test_where_starter_with_operator(): void {
		$this->seed( [ 'value' => 10 ] );
		$this->seed( [ 'value' => 500 ] );
		$this->seed( [ 'value' => 900 ] );

		$rows = QbFixture::where( 'value', '>=', 500 )->get();

		$this->assertCount( 2, $rows );
	}

	/**
	 * Test order_by() as a static starter.
	 */
	public function test_order_by_starter(): void {
		$this->seed( [ 'score' => 3.0 ] );
		$this->seed( [ 'score' => 1.0 ] );
		$this->seed( [ 'score' => 2.0 ] );

		$scores = array_map( 'floatval', QbFixture::order_by( 'score', 'ASC' )->pluck( 'score' ) );

		$this->assertEquals( [ 1.0, 2.0, 3.0 ], $scores );
	}

	/**
	 * Test where_in() starter chained into delete().
	 */
	public function test_where_in_starter_into_delete(): void {
		$a = $this->seed();
		$b = $this->seed();
		$this->seed();

		$deleted = QbFixture::where_in( 'id', [ $a->id, $b->id ] )->delete();

		$this->assertEquals( 2, $deleted );
		$this->assertEquals( 1, QbFixture::count() );
	}

	// =========================================================================
	// first_or_create / update_or_create
	// =========================================================================

	/**
	 * Test first_or_create() creates when no match exists.
	 */
	public function test_first_or_create_creates_when_missing(): void {
		$model = QbFixture::first_or_create(
			[ 'category' => 'uniq' ],
			[
				'name'  => 'made',
				'value' => 1,
				'score' => 1.0,
				'label' => 'l',
			]
		);

		$this->assertEquals( 'uniq', $model->category );
		$this->assertEquals( 1, QbFixture::count() );
	}

	/**
	 * Test first_or_create() returns the existing row without creating.
	 */
	public function test_first_or_create_returns_existing(): void {
		$this->seed(
			[
				'category' => 'dup',
				'name'     => 'orig',
			]
		);

		$model = QbFixture::first_or_create( [ 'category' => 'dup' ], [ 'name' => 'made' ] );

		$this->assertEquals( 'orig', $model->name );
		$this->assertEquals( 1, QbFixture::count() );
	}

	/**
	 * Test update_or_create() updates an existing row.
	 */
	public function test_update_or_create_updates_existing(): void {
		$this->seed(
			[
				'category' => 'up',
				'name'     => 'old',
			]
		);

		$model = QbFixture::update_or_create( [ 'category' => 'up' ], [ 'name' => 'new' ] );

		$this->assertEquals( 'new', $model->name );
		$this->assertEquals( 1, QbFixture::count() );
	}

	/**
	 * Test update_or_create() creates when no match exists.
	 */
	public function test_update_or_create_creates_when_missing(): void {
		$model = QbFixture::update_or_create(
			[ 'category' => 'fresh' ],
			[
				'name'  => 'n',
				'value' => 1,
				'score' => 1.0,
				'label' => 'l',
			]
		);

		$this->assertEquals( 'fresh', $model->category );
		$this->assertEquals( 1, QbFixture::count() );
	}
}
