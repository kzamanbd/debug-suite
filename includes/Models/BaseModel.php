<?php
/**
 * Base Model class for Debug Suite.
 *
 * Provides common database operations and utilities for all models.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Base model class with common database operations.
 *
 * @since 1.0.0
 */
abstract class BaseModel {

	/**
	 * Table name without prefix.
	 *
	 * @var string
	 */
	protected static string $table = '';

	/**
	 * Primary key column name.
	 *
	 * @var string
	 */
	protected static string $primary_key = 'id';

	/**
	 * Fillable columns for mass assignment.
	 *
	 * @var array
	 */
	protected static array $fillable = [];

	/**
	 * Timestamps columns.
	 *
	 * @var array
	 */
	protected static array $timestamps = [ 'created_at', 'updated_at' ];

	/**
	 * Model attributes.
	 *
	 * @var array
	 */
	protected array $attributes = [];

	/**
	 * Original attributes (for detecting changes).
	 *
	 * @var array
	 */
	protected array $original = [];

	/**
	 * Indicates if the model exists in the database.
	 *
	 * @var bool
	 */
	protected bool $exists = false;

	/**
	 * Constructor.
	 *
	 * @param array $attributes Initial attributes.
	 */
	public function __construct( array $attributes = [] ) {
		$this->fill( $attributes );
	}

	/**
	 * Get the full table name with prefix.
	 *
	 * @return string
	 */
	public static function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'debug_suite_' . static::$table;
	}

	/**
	 * Get WPDB instance.
	 *
	 * @return \wpdb
	 */
	protected static function get_wpdb(): \wpdb {
		global $wpdb;
		return $wpdb;
	}

	/**
	 * Find a record by primary key.
	 *
	 * @param mixed $id Primary key value.
	 * @return static|null
	 */
	public static function find( $id ): ?static {
		$wpdb = static::get_wpdb();
		$table_name = static::get_table_name();
		$primary_key = static::$primary_key;

		$result = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table_name} WHERE {$primary_key} = %s", $id ),
			ARRAY_A
		);

		return $result ? static::from_array( $result ) : null;
	}

	/**
	 * Find multiple records by IDs.
	 *
	 * @param array $ids Array of primary key values.
	 * @return array
	 */
	public static function find_many( array $ids ): array {
		if ( empty( $ids ) ) {
			return [];
		}

		$wpdb = static::get_wpdb();
		$table_name = static::get_table_name();
		$primary_key = static::$primary_key;

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%s' ) );
		$query = "SELECT * FROM {$table_name} WHERE {$primary_key} IN ({$placeholders})";

		$results = $wpdb->get_results( $wpdb->prepare( $query, $ids ), ARRAY_A );

		return array_map( [ static::class, 'from_array' ], $results );
	}

	/**
	 * Build LIMIT and OFFSET clause.
	 *
	 * @param array $options Query options with limit and offset.
	 * @return string
	 */
	protected static function build_limit_clause( array $options ): string {
		if ( ! $options['limit'] ) {
			return '';
		}

		$wpdb = static::get_wpdb();
		return $wpdb->prepare( ' LIMIT %d OFFSET %d', $options['limit'], $options['offset'] );
	}

	/**
	 * Get all records.
	 *
	 * @param array $options Query options.
	 * @return array
	 */
	public static function all( array $options = [] ): array {
		$options = wp_parse_args( $options, static::get_default_query_options() );

		$wpdb = static::get_wpdb();
		$table_name = static::get_table_name();

		$query = "SELECT * FROM {$table_name} ORDER BY {$options['order_by']} {$options['order']}";
		$query .= static::build_limit_clause( $options );

		$results = $wpdb->get_results( $query, ARRAY_A );

		return array_map( [ static::class, 'from_array' ], $results );
	}

	/**
	 * Build WHERE clause and values from conditions.
	 *
	 * @param array $conditions WHERE conditions.
	 * @return array {
	 *     WHERE clause data.
	 *     @type string $clause WHERE clause string.
	 *     @type array  $values Prepared statement values.
	 * }
	 */
	protected static function build_where_clause( array $conditions ): array {
		$where_conditions = [];
		$where_values = [];

		foreach ( $conditions as $column => $value ) {
			if ( is_array( $value ) ) {
				// Handle IN conditions
				$placeholders = implode( ',', array_fill( 0, count( $value ), '%s' ) );
				$where_conditions[] = "{$column} IN ({$placeholders})";
				$where_values = array_merge( $where_values, $value );
			} else {
				$where_conditions[] = "{$column} = %s";
				$where_values[] = $value;
			}
		}

		return [
			'clause' => implode( ' AND ', $where_conditions ),
			'values' => $where_values,
		];
	}

	/**
	 * Get default query options.
	 *
	 * @return array
	 */
	protected static function get_default_query_options(): array {
		return [
			'limit'      => null,
			'offset'     => 0,
			'order_by'   => static::$primary_key,
			'order'      => 'ASC',
		];
	}

	/**
	 * Build a query with WHERE conditions.
	 *
	 * @param array $conditions WHERE conditions.
	 * @param array $options Query options.
	 * @return array
	 */
	public static function where( array $conditions, array $options = [] ): array {
		$options = wp_parse_args( $options, static::get_default_query_options() );

		$wpdb = static::get_wpdb();
		$table_name = static::get_table_name();

		$where_data = static::build_where_clause( $conditions );

		$query = "SELECT * FROM {$table_name} WHERE {$where_data['clause']} ORDER BY {$options['order_by']} {$options['order']}";
		$query .= static::build_limit_clause( $options );

		$results = $wpdb->get_results( $wpdb->prepare( $query, $where_data['values'] ), ARRAY_A );

		return array_map( [ static::class, 'from_array' ], $results );
	}

	/**
	 * Count records with conditions.
	 *
	 * @param array $conditions WHERE conditions.
	 * @return int
	 */
	public static function count( array $conditions = [] ): int {
		$wpdb = static::get_wpdb();
		$table_name = static::get_table_name();

		if ( empty( $conditions ) ) {
			return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
		}

		$where_data = static::build_where_clause( $conditions );
		$query = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_data['clause']}";

		return (int) $wpdb->get_var( $wpdb->prepare( $query, $where_data['values'] ) );
	}

	/**
	 * Delete records by conditions.
	 *
	 * @param array $conditions WHERE conditions.
	 * @return int Number of deleted rows.
	 */
	public static function delete_where( array $conditions ): int {
		if ( empty( $conditions ) ) {
			return 0;
		}

		$wpdb = static::get_wpdb();
		$table_name = static::get_table_name();

		$where_data = static::build_where_clause( $conditions );
		$query = "DELETE FROM {$table_name} WHERE {$where_data['clause']}";

		return (int) $wpdb->query( $wpdb->prepare( $query, $where_data['values'] ) );
	}

	/**
	 * Truncate the table.
	 *
	 * @return bool
	 */
	public static function truncate(): bool {
		$wpdb = static::get_wpdb();
		$table_name = static::get_table_name();

		return false !== $wpdb->query( "TRUNCATE TABLE {$table_name}" );
	}

	/**
	 * Create a new model instance from array.
	 *
	 * @param array $attributes Attributes array.
	 * @return static
	 */
	public static function from_array( array $attributes ): static {
		$instance = new static( $attributes );
		$instance->exists = true;
		$instance->original = $attributes;
		return $instance;
	}

	/**
	 * Create a new record.
	 *
	 * @param array $attributes Attributes.
	 * @return static|null
	 */
	public static function create( array $attributes ): ?static {
		$instance = new static( $attributes );
		return $instance->save() ? $instance : null;
	}

	/**
	 * Check if an attribute is fillable.
	 *
	 * @param string $key Attribute key.
	 * @return bool
	 */
	protected static function is_fillable( string $key ): bool {
		return empty( static::$fillable ) || in_array( $key, static::$fillable, true );
	}

	/**
	 * Fill the model with attributes.
	 *
	 * @param array $attributes Attributes to fill.
	 * @return $this
	 */
	public function fill( array $attributes ): static {
		foreach ( $attributes as $key => $value ) {
			if ( static::is_fillable( $key ) ) {
				$this->attributes[ $key ] = $value;
			}
		}
		return $this;
	}

	/**
	 * Update timestamps before saving.
	 *
	 * @return void
	 */
	protected function touch_timestamps(): void {
		$current_time = current_time( 'mysql' );

		if ( in_array( 'updated_at', static::$timestamps, true ) ) {
			$this->attributes['updated_at'] = $current_time;
		}

		if ( ! $this->exists && in_array( 'created_at', static::$timestamps, true ) ) {
			$this->attributes['created_at'] = $current_time;
		}
	}

	/**
	 * Perform database update operation.
	 *
	 * @param \wpdb $wpdb WordPress database instance.
	 * @param string $table_name Table name.
	 * @return mixed
	 */
	protected function perform_update( \wpdb $wpdb, string $table_name ): mixed {
		$primary_key_value = $this->get_primary_key_value();
		if ( ! $primary_key_value ) {
			return false;
		}

		return $wpdb->update( $table_name, $this->attributes, [ static::$primary_key => $primary_key_value ] );
	}

	/**
	 * Perform database insert operation.
	 *
	 * @param \wpdb $wpdb WordPress database instance.
	 * @param string $table_name Table name.
	 * @return mixed
	 */
	protected function perform_insert( \wpdb $wpdb, string $table_name ): mixed {
		$result = $wpdb->insert( $table_name, $this->attributes );
		if ( $result && $wpdb->insert_id ) {
			$this->attributes[ static::$primary_key ] = $wpdb->insert_id;
			$this->exists = true;
		}
		return $result;
	}

	/**
	 * Save the model to the database.
	 *
	 * @return bool
	 */
	public function save(): bool {
		$wpdb = static::get_wpdb();
		$table_name = static::get_table_name();

		$this->touch_timestamps();

		$result = $this->exists 
			? $this->perform_update( $wpdb, $table_name )
			: $this->perform_insert( $wpdb, $table_name );

		if ( $result !== false ) {
			$this->original = $this->attributes;
			return true;
		}

		return false;
	}

	/**
	 * Get primary key value for the model.
	 *
	 * @return mixed
	 */
	protected function get_primary_key_value(): mixed {
		return $this->attributes[ static::$primary_key ] ?? null;
	}

	/**
	 * Delete the model from the database.
	 *
	 * @return bool
	 */
	public function delete(): bool {
		if ( ! $this->exists ) {
			return false;
		}

		$primary_key_value = $this->get_primary_key_value();
		if ( ! $primary_key_value ) {
			return false;
		}

		$wpdb = static::get_wpdb();
		$table_name = static::get_table_name();

		$result = $wpdb->delete( $table_name, [ static::$primary_key => $primary_key_value ] );

		if ( $result !== false ) {
			$this->exists = false;
			return true;
		}

		return false;
	}

	/**
	 * Get attribute value.
	 *
	 * @param string $key Attribute key.
	 * @return mixed
	 */
	public function get_attribute( string $key ): mixed {
		return $this->attributes[ $key ] ?? null;
	}

	/**
	 * Set attribute value.
	 *
	 * @param string $key Attribute key.
	 * @param mixed  $value Attribute value.
	 * @return $this
	 */
	public function set_attribute( string $key, mixed $value ): static {
		if ( static::is_fillable( $key ) ) {
			$this->attributes[ $key ] = $value;
		}
		return $this;
	}

	/**
	 * Get all attributes.
	 *
	 * @return array
	 */
	public function to_array(): array {
		return $this->attributes;
	}

	/**
	 * Magic getter.
	 *
	 * @param string $key Attribute key.
	 * @return mixed
	 */
	public function __get( string $key ): mixed {
		return $this->get_attribute( $key );
	}

	/**
	 * Magic setter.
	 *
	 * @param string $key Attribute key.
	 * @param mixed  $value Attribute value.
	 * @return void
	 */
	public function __set( string $key, mixed $value ): void {
		$this->set_attribute( $key, $value );
	}

	/**
	 * Magic isset.
	 *
	 * @param string $key Attribute key.
	 * @return bool
	 */
	public function __isset( string $key ): bool {
		return isset( $this->attributes[ $key ] );
	}

	/**
	 * Magic unset.
	 *
	 * @param string $key Attribute key.
	 * @return void
	 */
	public function __unset( string $key ): void {
		unset( $this->attributes[ $key ] );
	}
}
