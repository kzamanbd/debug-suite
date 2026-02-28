<?php
/**
 * Base Model class for Debug Suite.
 *
 * Provides common database operations and utilities for all models.
 * Supports Eloquent-style static calls via __callStatic proxy.
 *
 * Usage:
 *     EmailLog::find( 1 );
 *     EmailLog::where( [ 'status' => 'success' ] );
 *     EmailLog::create( [ 'subject' => 'Test' ] );
 *     EmailLog::all();
 *     EmailLog::count();
 *     EmailLog::destroy( [ 1, 2, 3 ] );
 *     EmailLog::truncate();
 *     EmailLog::first( [ 'status' => 'success' ] );
 *     EmailLog::latest();
 *     EmailLog::first_or_create( [ 'status' => 'success' ], [ 'subject' => 'Test' ] );
 *
 * @package DebugSuite
 */

namespace DebugSuite\Models;

use DebugSuite\Interfaces\Model;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Base model class with common database operations (ORM-style).
 *
 * Query methods are protected and accessed via __callStatic / __call.
 *
 * @since 1.0.0
 *
 * @method static string      get_table_name()
 * @method static static|null find( mixed $id )
 * @method static array       find_many( array $ids )
 * @method static array       all( array $options = [] )
 * @method static array       where( array $conditions, array $options = [] )
 * @method static int         count( array $conditions = [] )
 * @method static int         destroy( array $ids )
 * @method static bool        truncate()
 * @method static static|null create( array $attributes )
 * @method static static      from_array( array $attributes )
 * @method static static|null first( array $conditions = [] )
 * @method static static|null latest( string $column = 'created_at' )
 * @method static static|null oldest( string $column = 'created_at' )
 * @method static static      first_or_create( array $conditions, array $attributes = [] )
 * @method static static      update_or_create( array $conditions, array $attributes )
 * @method static QueryBuilder query()
 */
abstract class BaseModel implements Model {

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
	 * Attribute casting definitions.
	 *
	 * Maps attribute names to their cast types.
	 * Supported types: 'int', 'integer', 'float', 'double', 'bool', 'boolean',
	 *                  'string', 'array', 'json', 'datetime'.
	 *
	 * @var array<string, string>
	 */
	protected static array $casts = [];

	/**
	 * Attributes that should be hidden from array/JSON output.
	 *
	 * @var array<int, string>
	 */
	protected static array $hidden = [];

	/**
	 * Computed accessor attributes to append to array/JSON output.
	 *
	 * @var array<int, string>
	 */
	protected static array $appends = [];

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
	 * Attributes that were changed during the last save.
	 *
	 * @var array
	 */
	protected array $changes = [];

	/**
	 * Constructor.
	 *
	 * @param array $attributes Initial attributes.
	 */
	public function __construct( array $attributes = [] ) {
		$this->fill( $attributes );
	}

	// =========================================================================
	// Magic Methods
	// =========================================================================

	/**
	 * Handle static calls by proxying to instance methods.
	 *
	 * This enables Eloquent-style static usage:
	 *     EmailLog::find( 1 );
	 *     ApiLog::where( [ 'method' => 'GET' ] );
	 *
	 * @param string $method     Method name.
	 * @param array  $parameters Method parameters.
	 * @return mixed
	 */
	public static function __callStatic( string $method, array $parameters ): mixed {
		return ( new static() )->$method( ...$parameters );
	}

	/**
	 * Handle instance calls to protected query methods.
	 *
	 * Allows calling protected methods from outside via object instance:
	 *     $model->find( 1 );
	 *
	 * @param string $method     Method name.
	 * @param array  $parameters Method parameters.
	 * @return mixed
	 *
	 * @throws \BadMethodCallException When method does not exist.
	 */
	public function __call( string $method, array $parameters ): mixed {
		if ( method_exists( $this, $method ) ) {
			return $this->$method( ...$parameters );
		}

		throw new \BadMethodCallException(
			sprintf( 'Method %s::%s does not exist.', static::class, esc_html( $method ) )
		);
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
	 * @param string $key   Attribute key.
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
		if ( isset( $this->attributes[ $key ] ) ) {
			return true;
		}

		// Check if an accessor exists for this key.
		return method_exists( $this, "get_{$key}_attribute" );
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

	// =========================================================================
	// Table & Database Helpers
	// =========================================================================

	/**
	 * Get the full table name with prefix.
	 *
	 * @return string
	 */
	public function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'debug_suite_' . static::$table;
	}

	/**
	 * Get WPDB instance.
	 *
	 * @return \wpdb
	 */
	public function get_wpdb() {
		global $wpdb;
		return $wpdb;
	}

	/**
	 * Create a new query builder instance for this model.
	 *
	 * @return QueryBuilder
	 */
	protected function query(): QueryBuilder {
		return new QueryBuilder( $this );
	}

	// =========================================================================
	// Attribute Casting
	// =========================================================================

	/**
	 * Cast an attribute value for reading (get).
	 *
	 * @param string $key   Attribute key.
	 * @param mixed  $value Raw stored value.
	 * @return mixed Cast value.
	 */
	protected function cast_attribute( string $key, mixed $value ): mixed {
		$cast_type = static::$casts[ $key ] ?? null;

		if ( $cast_type === null || $value === null ) {
			return $value;
		}

		return match ( $cast_type ) {
			'int', 'integer'  => (int) $value,
			'float', 'double' => (float) $value,
			'bool', 'boolean' => (bool) $value,
			'string'          => (string) $value,
			'array', 'json'   => $this->cast_as_json( $value ),
			'datetime'        => $value,
			default           => $value,
		};
	}

	/**
	 * Cast an attribute value for writing (set).
	 *
	 * Ensures the value is in its DB-storable form.
	 *
	 * @param string $key   Attribute key.
	 * @param mixed  $value Value to store.
	 * @return mixed Value ready for DB storage.
	 */
	protected function set_cast_attribute( string $key, mixed $value ): mixed {
		$cast_type = static::$casts[ $key ] ?? null;

		if ( $cast_type === null ) {
			return $value;
		}

		return match ( $cast_type ) {
			'int', 'integer'  => (int) $value,
			'float', 'double' => (float) $value,
			'bool', 'boolean' => (bool) $value,
			'string'          => (string) $value,
			'array', 'json'   => $this->cast_to_json( $value ),
			'datetime'        => $value,
			default           => $value,
		};
	}

	/**
	 * Decode a JSON string to an array.
	 *
	 * @param mixed $value Value to decode.
	 * @return mixed Decoded array or original value.
	 */
	private function cast_as_json( mixed $value ): mixed {
		if ( is_array( $value ) || is_object( $value ) ) {
			return $value;
		}

		if ( ! is_string( $value ) || $value === '' ) {
			return [];
		}

		$decoded = json_decode( $value, true );

		return is_array( $decoded ) ? $decoded : $value;
	}

	/**
	 * Encode a value to a JSON string for DB storage.
	 *
	 * @param mixed $value Value to encode.
	 * @return string JSON encoded string.
	 */
	private function cast_to_json( mixed $value ): string {
		if ( is_string( $value ) ) {
			// Already a JSON string; validate it.
			json_decode( $value, true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				return $value;
			}
		}

		$encoded = wp_json_encode( $value );

		return $encoded ? $encoded : '{}';
	}

	/**
	 * Check if an attribute has a cast defined.
	 *
	 * @param string $key Attribute key.
	 * @return bool
	 */
	protected function has_cast( string $key ): bool {
		return array_key_exists( $key, static::$casts );
	}

	// =========================================================================
	// Accessors, Mutators & Attribute Access
	// =========================================================================

	/**
	 * Get attribute value with accessor and cast support.
	 *
	 * Checks for accessor method get_{key}_attribute() first,
	 * then applies $casts if defined.
	 *
	 * @param string $key Attribute key.
	 * @return mixed
	 */
	public function get_attribute( string $key ): mixed {
		$value = $this->attributes[ $key ] ?? null;

		// Check for accessor method: get_{key}_attribute().
		$accessor = "get_{$key}_attribute";
		if ( method_exists( $this, $accessor ) ) {
			return $this->$accessor( $value );
		}

		// Apply cast if defined.
		if ( $this->has_cast( $key ) ) {
			return $this->cast_attribute( $key, $value );
		}

		return $value;
	}

	/**
	 * Set attribute value with mutator and cast support.
	 *
	 * Checks for mutator method set_{key}_attribute() first,
	 * then applies $casts if defined.
	 *
	 * @param string $key   Attribute key.
	 * @param mixed  $value Attribute value.
	 * @return $this
	 */
	public function set_attribute( string $key, mixed $value ): static {
		if ( ! $this->is_fillable( $key ) ) {
			return $this;
		}

		// Check for mutator method: set_{key}_attribute().
		$mutator = "set_{$key}_attribute";
		if ( method_exists( $this, $mutator ) ) {
			$value = $this->$mutator( $value );
		} elseif ( $this->has_cast( $key ) ) {
			$value = $this->set_cast_attribute( $key, $value );
		}

		$this->attributes[ $key ] = $value;

		return $this;
	}

	/**
	 * Check if an attribute is fillable.
	 *
	 * @param string $key Attribute key.
	 * @return bool
	 */
	protected function is_fillable( string $key ): bool {
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
			$this->set_attribute( $key, $value );
		}

		return $this;
	}

	/**
	 * Get all attributes as an array.
	 *
	 * Applies casts and accessors, respects $hidden and $appends.
	 *
	 * @return array
	 */
	public function to_array(): array {
		$attributes = [];

		// Include all non-hidden attributes with casts applied.
		foreach ( $this->attributes as $key => $value ) {
			if ( in_array( $key, static::$hidden, true ) ) {
				continue;
			}

			$attributes[ $key ] = $this->get_attribute( $key );
		}

		// Append computed accessor attributes.
		foreach ( static::$appends as $key ) {
			$attributes[ $key ] = $this->get_attribute( $key );
		}

		return $attributes;
	}

	// =========================================================================
	// Dirty Tracking
	// =========================================================================

	/**
	 * Determine if the model or given attribute(s) have been modified.
	 *
	 * @param string|array|null $attributes Attribute(s) to check, or null for any.
	 * @return bool
	 */
	public function is_dirty( string|array|null $attributes = null ): bool {
		$dirty = $this->get_dirty();

		if ( $attributes === null ) {
			return count( $dirty ) > 0;
		}

		foreach ( (array) $attributes as $attribute ) {
			if ( array_key_exists( $attribute, $dirty ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine if the model or given attribute(s) are unchanged.
	 *
	 * @param string|array|null $attributes Attribute(s) to check, or null for any.
	 * @return bool
	 */
	public function is_clean( string|array|null $attributes = null ): bool {
		return ! $this->is_dirty( $attributes );
	}

	/**
	 * Get the attributes that have been changed since last sync.
	 *
	 * @return array
	 */
	public function get_dirty(): array {
		$dirty = [];

		foreach ( $this->attributes as $key => $value ) {
			if ( ! array_key_exists( $key, $this->original ) || $value !== $this->original[ $key ] ) {
				$dirty[ $key ] = $value;
			}
		}

		return $dirty;
	}

	/**
	 * Get the original value(s) of the model.
	 *
	 * @param string|null $key Specific attribute key, or null for all.
	 * @return mixed
	 */
	public function get_original( ?string $key = null ): mixed {
		if ( $key === null ) {
			return $this->original;
		}

		return $this->original[ $key ] ?? null;
	}

	/**
	 * Determine if the model or given attribute(s) were changed on last save.
	 *
	 * @param string|array|null $attributes Attribute(s) to check, or null for any.
	 * @return bool
	 */
	public function was_changed( string|array|null $attributes = null ): bool {
		if ( $attributes === null ) {
			return count( $this->changes ) > 0;
		}

		foreach ( (array) $attributes as $attribute ) {
			if ( array_key_exists( $attribute, $this->changes ) ) {
				return true;
			}
		}

		return false;
	}

	// =========================================================================
	// CRUD Operations
	// =========================================================================

	/**
	 * Find a record by primary key.
	 *
	 * @param mixed $id Primary key value.
	 * @return static|null
	 */
	protected function find( $id ): ?static {
		$wpdb        = $this->get_wpdb();
		$table_name  = $this->get_table_name();
		$primary_key = static::$primary_key;

		$query  = "SELECT * FROM $table_name WHERE $primary_key = %s";
		$result = $wpdb->get_row(
			$wpdb->prepare( $query, $id ),
			ARRAY_A
		);

		return $result ? $this->from_array( $result ) : null;
	}

	/**
	 * Find multiple records by IDs.
	 *
	 * @param array $ids Array of primary key values.
	 * @return array
	 */
	protected function find_many( array $ids ): array {
		if ( empty( $ids ) ) {
			return [];
		}

		$wpdb        = $this->get_wpdb();
		$table_name  = $this->get_table_name();
		$primary_key = static::$primary_key;

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%s' ) );
		$query        = "SELECT * FROM $table_name WHERE $primary_key IN ($placeholders)";

		$results = $wpdb->get_results( $wpdb->prepare( $query, $ids ), ARRAY_A );

		return array_map( [ $this, 'from_array' ], $results );
	}

	/**
	 * Get all records.
	 *
	 * @param array $options Query options.
	 * @return array
	 */
	protected function all( array $options = [] ): array {
		$wpdb        = $this->get_wpdb();
		$table_name  = $this->get_table_name();
		$primary_key = static::$primary_key;

		$limit  = isset( $options['limit'] ) ? (int) $options['limit'] : 100;
		$offset = isset( $options['offset'] ) ? (int) $options['offset'] : 0;

		$query   = "SELECT * FROM $table_name ORDER BY $primary_key DESC LIMIT %d OFFSET %d";
		$results = $wpdb->get_results( $wpdb->prepare( $query, $limit, $offset ), ARRAY_A );

		return array_map( [ $this, 'from_array' ], $results );
	}

	/**
	 * Find records by simple conditions.
	 *
	 * @param array $conditions Simple WHERE conditions (column => value).
	 * @param array $options    Query options.
	 * @return array
	 */
	protected function where( array $conditions, array $options = [] ): array {
		if ( empty( $conditions ) ) {
			return $this->all( $options );
		}

		$wpdb        = $this->get_wpdb();
		$table_name  = $this->get_table_name();
		$primary_key = static::$primary_key;

		$limit  = isset( $options['limit'] ) ? (int) $options['limit'] : 100;
		$offset = isset( $options['offset'] ) ? (int) $options['offset'] : 0;

		// Build simple WHERE clause.
		$where_parts = [];
		$values      = [];
		foreach ( $conditions as $column => $value ) {
			$where_parts[] = "$column = %s";
			$values[]      = $value;
		}

		$where_clause = implode( ' AND ', $where_parts );
		$query        = "SELECT * FROM $table_name WHERE $where_clause ORDER BY $primary_key DESC LIMIT %d OFFSET %d";

		$values[] = $limit;
		$values[] = $offset;

		$results = $wpdb->get_results( $wpdb->prepare( $query, $values ), ARRAY_A );

		return array_map( [ $this, 'from_array' ], $results );
	}

	/**
	 * Get the first record matching the conditions.
	 *
	 * @param array $conditions Simple WHERE conditions (column => value).
	 * @return static|null
	 */
	protected function first( array $conditions = [] ): ?static {
		$results = $this->where( $conditions, [ 'limit' => 1 ] );

		return ! empty( $results ) ? $results[0] : null;
	}

	/**
	 * Get the latest record by a given column.
	 *
	 * @param string $column Column to order by.
	 * @return static|null
	 */
	protected function latest( string $column = 'created_at' ): ?static {
		$wpdb       = $this->get_wpdb();
		$table_name = $this->get_table_name();

		// Validate column name against fillable + primary key.
		$allowed = array_merge( static::$fillable, [ static::$primary_key ] );
		if ( ! in_array( $column, $allowed, true ) ) {
			return null;
		}

		$query  = "SELECT * FROM $table_name ORDER BY `$column` DESC LIMIT 1";
		$result = $wpdb->get_row( $query, ARRAY_A );

		return $result ? $this->from_array( $result ) : null;
	}

	/**
	 * Get the oldest record by a given column.
	 *
	 * @param string $column Column to order by.
	 * @return static|null
	 */
	protected function oldest( string $column = 'created_at' ): ?static {
		$wpdb       = $this->get_wpdb();
		$table_name = $this->get_table_name();

		// Validate column name against fillable + primary key.
		$allowed = array_merge( static::$fillable, [ static::$primary_key ] );
		if ( ! in_array( $column, $allowed, true ) ) {
			return null;
		}

		$query  = "SELECT * FROM $table_name ORDER BY `$column` ASC LIMIT 1";
		$result = $wpdb->get_row( $query, ARRAY_A );

		return $result ? $this->from_array( $result ) : null;
	}

	/**
	 * Count records.
	 *
	 * @param array $conditions Simple WHERE conditions.
	 * @return int
	 */
	protected function count( array $conditions = [] ): int {
		$wpdb       = $this->get_wpdb();
		$table_name = $this->get_table_name();
		$query      = "SELECT COUNT(*) FROM $table_name";

		if ( empty( $conditions ) ) {
			// No dynamic values; run raw query safely.
			return (int) $wpdb->get_var( $query );
		}

		// Validate column names to prevent SQL injection.
		$allowed_columns = array_merge( static::$fillable, [ static::$primary_key ] );

		// Build simple WHERE clause.
		$where_parts = [];
		$values      = [];
		foreach ( $conditions as $column => $value ) {
			// Validate column name.
			if ( ! in_array( $column, $allowed_columns, true ) ) {
				continue; // Skip invalid columns.
			}
			$where_parts[] = "`$column` = %s";
			$values[]      = $value;
		}

		if ( empty( $where_parts ) ) {
			return 0;
		}

		$where_clause = implode( ' AND ', $where_parts );
		$query        = "SELECT COUNT(*) FROM $table_name WHERE $where_clause";

		return (int) $wpdb->get_var( $wpdb->prepare( $query, $values ) );
	}

	/**
	 * Create a new record.
	 *
	 * @param array $attributes Attributes.
	 * @return static|null
	 */
	protected function create( array $attributes ): ?static {
		$instance = new static( $attributes );

		return $instance->save() ? $instance : null;
	}

	/**
	 * Find the first record matching conditions or create it.
	 *
	 * @param array $conditions Conditions to search by.
	 * @param array $attributes Additional attributes for creation.
	 * @return static
	 */
	protected function first_or_create( array $conditions, array $attributes = [] ): static {
		$existing = $this->first( $conditions );

		if ( $existing ) {
			return $existing;
		}

		return $this->create( array_merge( $conditions, $attributes ) );
	}

	/**
	 * Find a record matching conditions and update it, or create it.
	 *
	 * @param array $conditions Conditions to search by.
	 * @param array $attributes Attributes to update or set on creation.
	 * @return static
	 */
	protected function update_or_create( array $conditions, array $attributes ): static {
		$existing = $this->first( $conditions );

		if ( $existing ) {
			$existing->fill( $attributes );
			$existing->save();

			return $existing;
		}

		return $this->create( array_merge( $conditions, $attributes ) );
	}

	/**
	 * Create a new model instance from a database row.
	 *
	 * @param array $attributes Attributes array.
	 * @return static
	 */
	protected function from_array( array $attributes ): static {
		$instance           = new static( $attributes );
		$instance->exists   = true;
		$instance->original = $instance->attributes;

		return $instance;
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
		$wpdb       = $this->get_wpdb();
		$table_name = $this->get_table_name();

		$this->touch_timestamps();

		// Capture dirty attributes before save for was_changed() tracking.
		$dirty = $this->get_dirty();

		if ( $this->exists ) {
			// Update existing record.
			$primary_key_value = $this->attributes[ static::$primary_key ] ?? null;
			if ( ! $primary_key_value ) {
				return false;
			}

			$result = $wpdb->update( $table_name, $this->attributes, [ static::$primary_key => $primary_key_value ] );
		} else {
			// Insert new record.
			$result = $wpdb->insert( $table_name, $this->attributes );
			if ( $result && $wpdb->insert_id ) {
				$this->attributes[ static::$primary_key ] = $wpdb->insert_id;
				$this->exists = true;
			}
		}

		if ( $result !== false ) {
			$this->changes  = $dirty;
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

		$wpdb       = $this->get_wpdb();
		$table_name = $this->get_table_name();

		$result = $wpdb->delete( $table_name, [ static::$primary_key => $primary_key_value ] );

		if ( $result !== false ) {
			$this->exists = false;
			return true;
		}

		return false;
	}

	/**
	 * Delete records by primary key IDs.
	 *
	 * Uses a single DELETE ... WHERE IN query for efficiency.
	 *
	 * @param array $ids Array of primary key values.
	 * @return int Number of deleted records.
	 */
	protected function destroy( array $ids ): int {
		if ( empty( $ids ) ) {
			return 0;
		}

		$wpdb        = $this->get_wpdb();
		$table_name  = $this->get_table_name();
		$primary_key = static::$primary_key;

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$query        = "DELETE FROM $table_name WHERE `$primary_key` IN ($placeholders)";

		$result = $wpdb->query( $wpdb->prepare( $query, $ids ) );

		return $result !== false ? (int) $result : 0;
	}

	/**
	 * Truncate the table.
	 *
	 * @return bool
	 */
	protected function truncate(): bool {
		$wpdb       = $this->get_wpdb();
		$table_name = $this->get_table_name();
		$query      = "TRUNCATE TABLE $table_name";

		// No placeholders; execute directly.
		return false !== $wpdb->query( $query );
	}

	// =========================================================================
	// Instance Refresh
	// =========================================================================

	/**
	 * Get a fresh instance of this model from the database.
	 *
	 * @return static|null
	 */
	public function fresh(): ?static {
		if ( ! $this->exists ) {
			return null;
		}

		$primary_key_value = $this->get_primary_key_value();
		if ( ! $primary_key_value ) {
			return null;
		}

		return static::find( $primary_key_value );
	}

	/**
	 * Refresh the current model instance from the database.
	 *
	 * @return static
	 */
	public function refresh(): static {
		if ( ! $this->exists ) {
			return $this;
		}

		$fresh = $this->fresh();

		if ( $fresh ) {
			$this->attributes = $fresh->attributes;
			$this->original   = $fresh->original;
			$this->changes    = [];
		}

		return $this;
	}
}
