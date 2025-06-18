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
 * Follows PHP-DI's factory pattern for flexible object creation.
 *
 * @since DEBUG_SUITE_SINCE
 */
class FactoryDefinition implements DefinitionInterface {

	/**
	 * The definition name.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * The factory callable.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var callable
	 */
	private $factory;

	/**
	 * Whether this is a singleton.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var bool
	 */
	private bool $singleton;

	/**
	 * Constructor.
	 *
	 * @since DEBUG_SUITE_SINCE
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
		return ( $this->factory )( $resolver );
	}

	/**
	 * Check if this definition is cacheable (singleton).
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return bool
	 */
	public function is_singleton(): bool {
		return $this->singleton;
	}
}
