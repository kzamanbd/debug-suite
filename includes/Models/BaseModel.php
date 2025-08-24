<?php
/**
 * Base Model class for Debug Suite.
 *
 * Provides common database operations and utilities for all models.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Models;

use wpdb;

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
	 * @return wpdb
	 */
	protected static function get_wpdb(): wpdb {
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

		$query = "SELECT * FROM $table_name WHERE $primary_key = %s";
		$result = $wpdb->get_row(
			$wpdb->prepare( $query, $id ),
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
		$query = "SELECT * FROM $table_name WHERE $primary_key IN ($placeholders)";

		$results = $wpdb->get_results( $wpdb->prepare( $query, $ids ), ARRAY_A );

		return array_map( [ static::class, 'from_array' ], $results );
	}



	/**
	 * Get all records.
	 *
	 * @param array $options Query options.
	 * @return array
	 */
	public static function all( array $options = [] ): array {
		$wpdb = static::get_wpdb();
		$table_name = static::get_table_name();
		$primary_key = static::$primary_key;

		$limit = isset( $options['limit'] ) ? (int) $options['limit'] : 100;
		$offset = isset( $options['offset'] ) ? (int) $options['offset'] : 0;

		$query = "SELECT * FROM $table_name ORDER BY $primary_key DESC LIMIT %d OFFSET %d";
		$results = $wpdb->get_results( $wpdb->prepare( $query, $limit, $offset ), ARRAY_A );

		return array_map( [ static::class, 'from_array' ], $results );
	}

	/**
	 * Find records by simple conditions.
	 *
	 * @param array $conditions Simple WHERE conditions (column => value).
	 * @param array $options Query options.
	 * @return array
	 */
	public static function where( array $conditions, array $options = [] ): array {
		if ( empty( $conditions ) ) {
			return static::all( $options );
		}

		$wpdb = static::get_wpdb();
		$table_name = static::get_table_name();
		$primary_key = static::$primary_key;

		$limit = isset( $options['limit'] ) ? (int) $options['limit'] : 100;
		$offset = isset( $options['offset'] ) ? (int) $options['offset'] : 0;

		// Build simple WHERE clause
		$where_parts = [];
		$values = [];
		foreach ( $conditions as $column => $value ) {
			$where_parts[] = "$column = %s";
			$values[] = $value;
		}

		$where_clause = implode( ' AND ', $where_parts );
		$query = "SELECT * FROM $table_name WHERE $where_clause ORDER BY $primary_key DESC LIMIT %d OFFSET %d";

		$values[] = $limit;
		$values[] = $offset;

		$results = $wpdb->get_results( $wpdb->prepare( $query, $values ), ARRAY_A );

		return array_map( [ static::class, 'from_array' ], $results );
	}

	/**
	 * Count records.
	 *
	 * @param array $conditions Simple WHERE conditions.
	 * @return int
	 */
	public static function count( array $conditions = [] ): int {
		$wpdb = static::get_wpdb();
		$table_name = static::get_table_name();
		$query = "SELECT COUNT(*) FROM $table_name";
		if ( empty( $conditions ) ) {
			// No dynamic values; run raw query safely (table name already validated via class property).
			return (int) $wpdb->get_var( $query );
		}

		// Validate column names to prevent SQL injection
		$allowed_columns = array_merge( static::$fillable, [ static::$primary_key ] );

		// Build simple WHERE clause
		$where_parts = [];
		$values = [];
		foreach ( $conditions as $column => $value ) {
			// Validate column name
			if ( ! in_array( $column, $allowed_columns, true ) ) {
				continue; // Skip invalid columns
			}
			$where_parts[] = "`$column` = %s";
			$values[] = $value;
		}

		if ( empty( $where_parts ) ) {
			return 0;
		}

		$where_clause = implode( ' AND ', $where_parts );
		$query = "SELECT COUNT(*) FROM $table_name WHERE $where_clause";

		return (int) $wpdb->get_var( $wpdb->prepare( $query, $values ) );
	}

	/**
	 * Delete records by conditions.
	 *
	 * @param array $conditions Simple WHERE conditions.
	 * @return int Number of deleted rows.
	 */
	public static function delete_where( array $conditions ): int {
		if ( empty( $conditions ) ) {
			return 0;
		}

		$wpdb = static::get_wpdb();
		$table_name = static::get_table_name();

		// Validate column names to prevent SQL injection
		$allowed_columns = array_merge( static::$fillable, [ static::$primary_key ] );

		// Build simple WHERE clause
		$where_parts = [];
		$values = [];
		foreach ( $conditions as $column => $value ) {
			// Validate column name
			if ( ! in_array( $column, $allowed_columns, true ) ) {
				continue; // Skip invalid columns
			}

			if ( is_array( $value ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $value ), '%s' ) );
				$where_parts[] = "`$column` IN ($placeholders)";
				$values = array_merge( $values, $value );
			} else {
				$where_parts[] = "`$column` = %s";
				$values[] = $value;
			}
		}

		if ( empty( $where_parts ) ) {
			return 0;
		}

		$where_clause = implode( ' AND ', $where_parts );
		$query = "DELETE FROM $table_name WHERE $where_clause";

		return (int) $wpdb->query( $wpdb->prepare( $query, $values ) );
	}

	/**
	 * Truncate the table.
	 *
	 * @return bool
	 */
	public static function truncate(): bool {
		$wpdb = static::get_wpdb();
		$table_name = static::get_table_name();
		$query = "TRUNCATE TABLE $table_name";
		// No placeholders; execute directly.
		return false !== $wpdb->query( $query );
	}

	/**
	 * Create a new model instance from an array.
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
	 * Save the model to the database.
	 *
	 * @return bool
	 */
	public function save(): bool {
		$wpdb = static::get_wpdb();
		$table_name = static::get_table_name();

		$this->touch_timestamps();

		if ( $this->exists ) {
			// Update existing record
			$primary_key_value = $this->attributes[ static::$primary_key ] ?? null;
			if ( ! $primary_key_value ) {
				return false;
			}

			$result = $wpdb->update( $table_name, $this->attributes, [ static::$primary_key => $primary_key_value ] );
		} else {
			// Insert new record
			$result = $wpdb->insert( $table_name, $this->attributes );
			if ( $result && $wpdb->insert_id ) {
				$this->attributes[ static::$primary_key ] = $wpdb->insert_id;
				$this->exists = true;
			}
		}

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
