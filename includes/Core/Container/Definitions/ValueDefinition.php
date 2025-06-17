<?php
/**
 * Value definition for dependency injection.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core\Container\Definitions;

/**
 * Value definition for dependency injection.
 *
 * Represents a static value that will be returned directly.
 * Follows PHP-DI's value pattern for simple value injection.
 *
 * @since DEBUG_SUITE_SINCE
 */
class ValueDefinition implements DefinitionInterface {

	/**
	 * The definition name.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * The value to return.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var mixed
	 */
	private $value;

	/**
	 * Constructor.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param mixed $value The value to return.
	 */
	public function __construct( $value ) {
		$this->value = $value;
		$this->name  = '';
	}

	/**
	 * Get the name of this definition.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Set the name of this definition.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $name The definition name.
	 *
	 * @return static
	 */
	public function set_name( string $name ): static {
		$this->name = $name;
		return $this;
	}

	/**
	 * Resolve this definition to an instance.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param callable $resolver Function to resolve dependencies.
	 *
	 * @return mixed
	 */
	public function resolve( callable $resolver ) {
		return $this->value;
	}

	/**
	 * Check if this definition is cacheable (singleton).
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return bool
	 */
	public function is_singleton(): bool {
		return true; // Values are always cached
	}
}
