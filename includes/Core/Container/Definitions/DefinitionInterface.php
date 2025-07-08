<?php
/**
 * Base definition interface for dependency injection definitions.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core\Container\Definitions;

/**
 * Base definition interface for dependency injection definitions.
 *
 * Defines the contract for all dependency injection definition types.
 * This follows a definition pattern for flexible service configuration.
 *
 * @since 1.0.0
 */
interface DefinitionInterface {

	/**
	 * Get the name of this definition.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Set the name of this definition.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name The definition name.
	 *
	 * @return static
	 */
	public function set_name( string $name ): static;

	/**
	 * Resolve this definition to an instance.
	 *
	 * @since 1.0.0
	 *
	 * @param callable $resolver Function to resolve dependencies.
	 *
	 * @return mixed
	 */
	public function resolve( callable $resolver );

	/**
	 * Check if this definition is cacheable (singleton).
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_singleton(): bool;
}
