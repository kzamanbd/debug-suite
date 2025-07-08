<?php
/**
 * Value definition for dependency injection.
 *
 * @package DebugSuite
 *
 * @internal This class is for internal container use only.
 *           Use $container->object() and $container->autowire() instead.
 */

namespace DebugSuite\Core\Container\Definitions;

/**
 * Value definition for dependency injection.
 *
 * Represents a static value that will be returned directly.
 * Follow value pattern for simple value injection.
 *
 * @since 1.0.0
 *
 * @internal This class is for internal container use only.
 *           Use $container->object() and $container->autowire() in service providers instead.
 */
class ValueDefinition implements DefinitionInterface {

	/**
	 * The definition name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * The value to return.
	 *
	 * @since 1.0.0
	 *
	 * @var mixed
	 */
	private $value;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Set the name of this definition.
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_singleton(): bool {
		return true; // Values are always cached
	}
}
