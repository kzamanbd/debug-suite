<?php
/**
 * Fluent Query Builder for Debug Suite models.
 *
 * Provides a chainable, Eloquent-style interface for building database queries
 * on top of WordPress $wpdb, eliminating raw SQL in child models.
 *
 * Usage:
 *     EmailLog::query()
 *         ->where( 'status', 'success' )
 *         ->where( 'subject', 'LIKE', 'invoice' )
 *         ->order_by( 'created_at', 'DESC' )
 *         ->limit( 20 )
 *         ->get();
 *
 *     EmailLog::query()
 *         ->distinct()
 *         ->where_not_empty( 'to_email' )
 *         ->pluck( 'to_email' );
 *
 *     EmailLog::query()
 *         ->select_raw( 'COUNT(*) as total, AVG(id) as avg' )
 *         ->get_row();
 *
 * @package DebugSuite
 */

namespace DebugSuite\Models;

use RuntimeException;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Fluent query builder for BaseModel subclasses.
 *
 * @since 1.3.0
 */
class QueryBuilder {

	/**
	 * The model instance that created this builder.
	 *
	 * @var BaseModel
	 */
	protected BaseModel $model;

	/**
	 * SELECT columns or expression.
	 *
	 * @var string
	 */
	protected string $select = '*';

	/**
	 * Whether to use DISTINCT.
	 *
	 * @var bool
	 */
	protected bool $is_distinct = false;

	/**
	 * WHERE clause parts with their prepare values.
	 *
	 * Each entry: [ 'clause' => string, 'values' => array ].
	 *
	 * @var array
	 */
	protected array $wheres = [];

	/**
	 * ORDER BY column.
	 *
	 * @var string|null
	 */
	protected ?string $order_by_column = null;

	/**
	 * ORDER BY direction (ASC or DESC).
	 *
	 * @var string
	 */
	protected string $order_direction = 'DESC';

	/**
	 * LIMIT value.
	 *
	 * @var int|null
	 */
	protected ?int $limit_value = null;

	/**
	 * OFFSET value.
	 *
	 * @var int
	 */
	protected int $offset_value = 0;

	/**
	 * GROUP BY column.
	 *
	 * @var string|null
	 */
	protected ?string $group_by_column = null;

	/**
	 * Constructor.
	 *
	 * @param BaseModel $model The model instance.
	 */
	public function __construct( BaseModel $model ) {
		$this->model = $model;
	}

	// =========================================================================
	// SELECT Clauses
	// =========================================================================

	/**
	 * Set the columns to select.
	 *
	 * @param string $columns Column list (e.g. 'id, name' or '*').
	 * @return $this
	 */
	public function select( string $columns ): static {
		$this->select = $columns;
		return $this;
	}

	/**
	 * Set a raw SELECT expression (for aggregates).
	 *
	 * @param string $expression Raw SQL expression (e.g. 'COUNT(*) as total').
	 * @return $this
	 */
	public function select_raw( string $expression ): static {
		$this->select = $expression;
		return $this;
	}

	/**
	 * Add DISTINCT to the query.
	 *
	 * @return $this
	 */
	public function distinct(): static {
		$this->is_distinct = true;
		return $this;
	}

	// =========================================================================
	// WHERE Clauses
	// =========================================================================

	/**
	 * Allowed comparison operators.
	 *
	 * @var array
	 */
	protected const OPERATORS = [ '=', '!=', '<>', '>', '<', '>=', '<=', 'LIKE', 'NOT LIKE' ];

	/**
	 * Add a WHERE condition with optional operator.
	 *
	 * Supports two calling conventions:
	 *   - where( 'column', 'value' )            → equality (column = value)
	 *   - where( 'column', 'operator', 'value' ) → column operator value
	 *
	 * LIKE / NOT LIKE auto-wraps the value with % wildcards and uses esc_like().
	 *
	 * @param string $column           Column name.
	 * @param mixed  $operator_or_value Operator (when 3 args) or value (when 2 args).
	 * @param mixed  $value            Value when using 3-arg form.
	 * @return $this
	 */
	public function where( string $column, mixed $operator_or_value = null, mixed $value = null ): static {
		// Two-argument form: where( 'column', 'value' ) → equality.
		if ( func_num_args() === 2 || $value === null ) {
			$this->wheres[] = [
				'clause' => "`$column` = %s",
				'values' => [ $operator_or_value ],
			];
			return $this;
		}

		$operator = strtoupper( trim( $operator_or_value ) );

		if ( ! in_array( $operator, self::OPERATORS, true ) ) {
			$operator = '=';
		}

		// LIKE / NOT LIKE: auto-wrap with wildcards and escape.
		if ( $operator === 'LIKE' || $operator === 'NOT LIKE' ) {
			$wpdb  = $this->model->get_wpdb();
			$value = '%' . $wpdb->esc_like( $value ) . '%';
		}

		$this->wheres[] = [
			'clause' => "`$column` $operator %s",
			'values' => [ $value ],
		];
		return $this;
	}

	/**
	 * Add an OR condition across multiple columns.
	 *
	 * Supports two calling conventions:
	 *   - where_any( ['col1', 'col2'], 'value' )            → equality OR
	 *   - where_any( ['col1', 'col2'], 'operator', 'value' ) → operator OR
	 *
	 * Generates: (col1 {operator} %s OR col2 {operator} %s OR ...)
	 * LIKE / NOT LIKE auto-wraps the value with % wildcards and uses esc_like().
	 *
	 * @param array $columns           Array of column names.
	 * @param mixed $operator_or_value Operator (when 3 args) or value (when 2 args).
	 * @param mixed $value             Value when using 3-arg form.
	 * @return $this
	 */
	public function where_any( array $columns, mixed $operator_or_value = null, mixed $value = null ): static {
		// Two-argument form: where_any( ['col1', 'col2'], 'value' ) → equality.
		if ( $value === null ) {
			$operator = '=';
			$value    = $operator_or_value;
		} else {
			$operator = strtoupper( trim( $operator_or_value ) );

			if ( ! in_array( $operator, self::OPERATORS, true ) ) {
				$operator = '=';
			}
		}

		// LIKE / NOT LIKE: auto-wrap with wildcards and escape.
		if ( $operator === 'LIKE' || $operator === 'NOT LIKE' ) {
			$wpdb  = $this->model->get_wpdb();
			$value = '%' . $wpdb->esc_like( $value ) . '%';
		}

		$parts  = [];
		$values = [];
		foreach ( $columns as $col ) {
			$parts[]  = "`$col` $operator %s";
			$values[] = $value;
		}

		$this->wheres[] = [
			'clause' => '(' . implode( ' OR ', $parts ) . ')',
			'values' => $values,
		];
		return $this;
	}

	/**
	 * Add a WHERE IN condition.
	 *
	 * @param string $column Column name.
	 * @param array  $values Array of values.
	 * @return $this
	 */
	public function where_in( string $column, array $values ): static {
		if ( empty( $values ) ) {
			// Empty IN clause should match nothing.
			$this->wheres[] = [
				'clause' => '1 = 0',
				'values' => [],
			];
			return $this;
		}

		$placeholders = implode( ',', array_fill( 0, count( $values ), '%s' ) );

		$this->wheres[] = [
			'clause' => "`$column` IN ($placeholders)",
			'values' => array_values( $values ),
		];
		return $this;
	}

	/**
	 * Add a raw WHERE clause with prepare values.
	 *
	 * @param string $clause Raw SQL clause (e.g. 'status >= %d AND status < %d').
	 * @param array  $values Values for prepare placeholders.
	 * @return $this
	 */
	public function where_raw( string $clause, array $values = [] ): static {
		$this->wheres[] = [
			'clause' => $clause,
			'values' => $values,
		];
		return $this;
	}

	/**
	 * Add a condition that the column is not empty.
	 *
	 * @param string $column Column name.
	 * @return $this
	 */
	public function where_not_empty( string $column ): static {
		$this->wheres[] = [
			'clause' => "`$column` != ''",
			'values' => [],
		];
		return $this;
	}

	// =========================================================================
	// ORDER, LIMIT, OFFSET, GROUP
	// =========================================================================

	/**
	 * Set the ORDER BY clause.
	 *
	 * @param string $column    Column to sort by.
	 * @param string $direction Sort direction (ASC or DESC).
	 * @return $this
	 */
	public function order_by( string $column, string $direction = 'DESC' ): static {
		$this->order_by_column = $column;
		$this->order_direction = strtoupper( $direction ) === 'ASC' ? 'ASC' : 'DESC';
		return $this;
	}

	/**
	 * Set the LIMIT.
	 *
	 * @param int $limit Number of rows to return.
	 * @return $this
	 */
	public function limit( int $limit ): static {
		$this->limit_value = $limit;
		return $this;
	}

	/**
	 * Set the OFFSET.
	 *
	 * @param int $offset Number of rows to skip.
	 * @return $this
	 */
	public function offset( int $offset ): static {
		$this->offset_value = $offset;
		return $this;
	}

	/**
	 * Set the GROUP BY clause.
	 *
	 * @param string $column Column to group by.
	 * @return $this
	 */
	public function group_by( string $column ): static {
		$this->group_by_column = $column;
		return $this;
	}

	// =========================================================================
	// Execution Methods
	// =========================================================================

	/**
	 * Execute the query and return an array of model instances.
	 *
	 * @return array Array of BaseModel instances.
	 */
	public function get(): array {
		$built   = $this->build_query();
		$wpdb    = $this->model->get_wpdb();
		$query   = $built['query'];
		$values  = $built['values'];

		$results = empty( $values )
			? $wpdb->get_results( $query, ARRAY_A )
			: $wpdb->get_results( $wpdb->prepare( $query, $values ), ARRAY_A );

		if ( ! is_array( $results ) ) {
			return [];
		}

		return array_map( [ $this->model, 'from_array' ], $results );
	}

	/**
	 * Execute the query and return the first model instance.
	 *
	 * @return BaseModel|null
	 */
	public function first(): ?BaseModel {
		$this->limit_value = 1;
		$results           = $this->get();

		return ! empty( $results ) ? $results[0] : null;
	}

	/**
	 * Execute a COUNT(*) query and return the count.
	 *
	 * @return int
	 */
	public function count(): int {
		$built  = $this->build_query( 'COUNT(*)' );
		$wpdb   = $this->model->get_wpdb();
		$query  = $built['query'];
		$values = $built['values'];

		$result = empty( $values )
			? $wpdb->get_var( $query )
			: $wpdb->get_var( $wpdb->prepare( $query, $values ) );

		return (int) $result;
	}

	/**
	 * Execute the query and return values from a single column.
	 *
	 * @param string $column Column to pluck.
	 * @return array Array of column values.
	 */
	public function pluck( string $column ): array {
		$built  = $this->build_query( $column );
		$wpdb   = $this->model->get_wpdb();
		$query  = $built['query'];
		$values = $built['values'];

		$results = empty( $values )
			? $wpdb->get_col( $query )
			: $wpdb->get_col( $wpdb->prepare( $query, $values ) );

		return is_array( $results ) ? $results : [];
	}

	/**
	 * Execute the query and return a single raw row as an associative array.
	 *
	 * Useful for aggregate queries with select_raw().
	 *
	 * @return array|null
	 */
	public function get_row(): ?array {
		$built  = $this->build_query();
		$wpdb   = $this->model->get_wpdb();
		$query  = $built['query'];
		$values = $built['values'];

		$result = empty( $values )
			? $wpdb->get_row( $query, ARRAY_A )
			: $wpdb->get_row( $wpdb->prepare( $query, $values ), ARRAY_A );

		return is_array( $result ) ? $result : null;
	}

	/**
	 * Execute the query and return raw associative arrays (no model hydration).
	 *
	 * @return array Array of associative arrays.
	 */
	public function get_results(): array {
		$built  = $this->build_query();
		$wpdb   = $this->model->get_wpdb();
		$query  = $built['query'];
		$values = $built['values'];

		$results = empty( $values )
			? $wpdb->get_results( $query, ARRAY_A )
			: $wpdb->get_results( $wpdb->prepare( $query, $values ), ARRAY_A );

		return is_array( $results ) ? $results : [];
	}

	/**
	 * Execute a DELETE for the current WHERE constraints.
	 *
	 * Requires at least one WHERE clause; call truncate() to intentionally
	 * empty a table.
	 *
	 * @return int Number of rows deleted.
	 *
	 * @throws RuntimeException When no WHERE clause has been set.
	 */
	public function delete(): int {
		if ( empty( $this->wheres ) ) {
			throw new RuntimeException( 'Refusing to run an unbounded DELETE; use truncate() to empty the table.' );
		}

		$wpdb       = $this->model->get_wpdb();
		$table_name = $this->model->get_table_name();

		$clauses = [];
		$values  = [];
		foreach ( $this->wheres as $where ) {
			$clauses[] = $where['clause'];
			foreach ( $where['values'] as $val ) {
				$values[] = $val;
			}
		}

		$query = "DELETE FROM {$table_name} WHERE " . implode( ' AND ', $clauses );

		$result = empty( $values )
			? $wpdb->query( $query )
			: $wpdb->query( $wpdb->prepare( $query, $values ) );

		return $result !== false ? (int) $result : 0;
	}

	/**
	 * Truncate the model's table.
	 *
	 * @return bool
	 */
	public function truncate(): bool {
		$wpdb       = $this->model->get_wpdb();
		$table_name = $this->model->get_table_name();

		return false !== $wpdb->query( $wpdb->prepare( 'TRUNCATE TABLE %i', $table_name ) );
	}

	// =========================================================================
	// Query Building
	// =========================================================================

	/**
	 * Build the complete SQL query and prepare values.
	 *
	 * @param string|null $select_override Override the SELECT clause (e.g. 'COUNT(*)').
	 * @return array{ query: string, values: array }
	 */
	protected function build_query( ?string $select_override = null ): array {
		$table_name = $this->model->get_table_name();
		$select     = $select_override ?? $this->select;
		$values     = [];

		// SELECT clause.
		$distinct_keyword = $this->is_distinct ? 'DISTINCT ' : '';
		$query            = "SELECT {$distinct_keyword}{$select} FROM {$table_name}";

		// WHERE clause.
		if ( ! empty( $this->wheres ) ) {
			$clauses = [];
			foreach ( $this->wheres as $where ) {
				$clauses[] = $where['clause'];
				foreach ( $where['values'] as $val ) {
					$values[] = $val;
				}
			}
			$query .= ' WHERE ' . implode( ' AND ', $clauses );
		}

		// GROUP BY clause.
		if ( $this->group_by_column !== null ) {
			$query .= " GROUP BY `{$this->group_by_column}`";
		}

		// ORDER BY clause (skip for COUNT queries).
		if ( $this->order_by_column !== null && $select_override !== 'COUNT(*)' ) {
			$query .= " ORDER BY {$this->order_by_column} {$this->order_direction}";
		}

		// LIMIT / OFFSET (skip for COUNT queries).
		if ( $this->limit_value !== null && $select_override !== 'COUNT(*)' ) {
			$query   .= ' LIMIT %d OFFSET %d';
			$values[] = $this->limit_value;
			$values[] = $this->offset_value;
		}

		return [
			'query'  => $query,
			'values' => $values,
		];
	}
}
