<?php
/**
 * Model interface for Debug Suite.
 *
 * Defines the contract for all database model instances (ORM-style).
 *
 * Query methods (find, where, all, create, destroy, truncate, etc.) are
 * accessed via __callStatic / __call and are NOT part of this interface.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Model interface — instance-level contract only.
 *
 * Static query methods are handled by BaseModel's __callStatic magic:
 *
 *     EmailLog::find( 1 );
 *     ApiLog::where( [ 'method' => 'GET' ] );
 *     EmailLog::create( [ ... ] );
 *     EmailLog::destroy( [ 1, 2, 3 ] );
 *
 * @since 1.0.0
 */
interface Model {

	/**
	 * Fill the model with attributes.
	 *
	 * @param array $attributes Attributes to fill.
	 * @return static
	 */
	public function fill( array $attributes ): static;

	/**
	 * Save the model to the database.
	 *
	 * @return bool
	 */
	public function save(): bool;

	/**
	 * Delete the model from the database.
	 *
	 * @return bool
	 */
	public function delete(): bool;

	/**
	 * Get attribute value.
	 *
	 * @param string $key Attribute key.
	 * @return mixed
	 */
	public function get_attribute( string $key ): mixed;

	/**
	 * Set attribute value.
	 *
	 * @param string $key   Attribute key.
	 * @param mixed  $value Attribute value.
	 * @return static
	 */
	public function set_attribute( string $key, mixed $value ): static;

	/**
	 * Get all attributes as an array.
	 *
	 * @return array
	 */
	public function to_array(): array;

	/**
	 * Determine if the model or given attribute(s) have been modified.
	 *
	 * @param string|array|null $attributes Attribute(s) to check, or null for any.
	 * @return bool
	 */
	public function is_dirty( string|array|null $attributes = null ): bool;

	/**
	 * Determine if the model or given attribute(s) are unchanged.
	 *
	 * @param string|array|null $attributes Attribute(s) to check, or null for any.
	 * @return bool
	 */
	public function is_clean( string|array|null $attributes = null ): bool;

	/**
	 * Get the attributes that have been changed since last sync.
	 *
	 * @return array
	 */
	public function get_dirty(): array;

	/**
	 * Get the original value(s) of the model.
	 *
	 * @param string|null $key Specific attribute key, or null for all.
	 * @return mixed
	 */
	public function get_original( ?string $key = null ): mixed;

	/**
	 * Determine if the model or given attribute(s) were changed on last save.
	 *
	 * @param string|array|null $attributes Attribute(s) to check, or null for any.
	 * @return bool
	 */
	public function was_changed( string|array|null $attributes = null ): bool;

	/**
	 * Get a fresh instance of this model from the database.
	 *
	 * @return static|null
	 */
	public function fresh(): ?static;

	/**
	 * Refresh the current model instance from the database.
	 *
	 * @return static
	 */
	public function refresh(): static;
}
