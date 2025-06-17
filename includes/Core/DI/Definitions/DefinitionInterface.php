<?php
/**
 * Base definition interface for dependency injection definitions.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core\DI\Definitions;

/**
 * Base definition interface for dependency injection definitions.
 *
 * Defines the contract for all dependency injection definition types.
 * This follows PHP-DI's definition pattern for flexible service configuration.
 *
 * @since DEBUG_SUITE_SINCE
 */
interface DefinitionInterface {

	/**
	 * Get the name of this definition.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Set the name of this definition.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $name The definition name.
	 *
	 * @return static
	 */
	public function set_name( string $name ): static;

	/**
	 * Resolve this definition to an instance.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param callable $resolver Function to resolve dependencies.
	 *
	 * @return mixed
	 */
	public function resolve( callable $resolver );

	/**
	 * Check if this definition is cacheable (singleton).
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return bool
	 */
	public function is_singleton(): bool;
}
