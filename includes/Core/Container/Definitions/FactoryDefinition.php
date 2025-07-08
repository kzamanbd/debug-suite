<?php
/**
 * Factory definition for dependency injection.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core\Container\Definitions;

/**
 * Factory definition for dependency injection.
 *
 * Represents a factory callable that will be invoked to create an instance.
 * Follow factory pattern for flexible object creation.
 *
 * @since 1.0.0
 */
class FactoryDefinition implements DefinitionInterface {

	/**
	 * The definition name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * The factory callable.
	 *
	 * @since 1.0.0
	 *
	 * @var callable
	 */
	private $factory;

	/**
	 * Whether this is a singleton.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	private bool $singleton;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param callable $factory   Factory callable.
	 * @param bool     $singleton Whether this is a singleton.
	 */
	public function __construct( callable $factory, bool $singleton = false ) {
		$this->factory   = $factory;
		$this->singleton = $singleton;
		$this->name      = '';
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
		return ( $this->factory )( $resolver );
	}

	/**
	 * Check if this definition is cacheable (singleton).
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_singleton(): bool {
		return $this->singleton;
	}
}
