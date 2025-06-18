<?php
/**
 * Decorator definition for dependency injection.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core\Container\Definitions;

/**
 * Decorator definition for dependency injection.
 *
 * Allows wrapping existing services with decorators to add functionality
 * without modifying the original service implementation.
 *
 * @since DEBUG_SUITE_SINCE
 */
class DecoratorDefinition implements DefinitionInterface {

	/**
	 * The definition name.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * The decorator class name.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var string
	 */
	private string $decorator_class;

	/**
	 * The original service identifier to decorate.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var string
	 */
	private string $decorated_service;

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
	 * @param string $decorator_class   The decorator class name.
	 * @param string $decorated_service The service to decorate.
	 * @param bool   $singleton         Whether this is a singleton.
	 */
	public function __construct( string $decorator_class, string $decorated_service, bool $singleton = false ) {
		$this->decorator_class   = $decorator_class;
		$this->decorated_service = $decorated_service;
		$this->singleton         = $singleton;
		$this->name              = '';
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
	 * Resolve this definition to a decorated service instance.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param callable $resolver Function to resolve dependencies.
	 *
	 * @return mixed
	 */
	public function resolve( callable $resolver ): mixed {
		// Resolve the original service first
		$original_service = $resolver( $this->decorated_service );

		// Create the decorator with the original service
		return new $this->decorator_class( $original_service );
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

	/**
	 * Get the decorator class name.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return string
	 */
	public function get_decorator_class(): string {
		return $this->decorator_class;
	}

	/**
	 * Get the decorated service identifier.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return string
	 */
	public function get_decorated_service(): string {
		return $this->decorated_service;
	}
}
