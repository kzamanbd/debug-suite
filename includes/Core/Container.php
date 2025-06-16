<?php
/**
 * Dependency Injection Container for managing class dependencies.
 */

namespace DebugSuite\Core;

use Exception;
use ReflectionClass;
use ReflectionException;

/**
 * Dependency Injection Container for managing class dependencies.
 *
 * Provides a singleton container implementation for dependency injection
 * with support for service binding, singleton management, and automatic
 * dependency resolution using reflection.
 *
 * @since DEBUG_SUITE_SINCE
 */
class Container {

	/**
	 * Container singleton instance.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var Container|null
	 */
	private static ?Container $instance = null;

	/**
	 * Registered services array.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var array<string, mixed>
	 */
	// @phpstan-ignore-next-line
	private array $services = [];

	/**
	 * Singleton instances cache.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var array<string, mixed>
	 */
	private array $instances = [];

	/**
	 * Service bindings configuration.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var array<string, array{resolver: mixed, singleton: bool}>
	 */
	private array $bindings = [];

	/**
	 * Private constructor to prevent direct instantiation.
	 *
	 * Implements singleton pattern by preventing external instantiation.
	 *
	 * @since DEBUG_SUITE_SINCE
	 */
	private function __construct() {
		// Private constructor
	}

	/**
	 * Get the container singleton instance.
	 *
	 * Returns the singleton instance of the container, creating it if
	 * it doesn't exist yet.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return Container The container instance.
	 */
	public static function get_instance(): Container {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Bind a service to the container.
	 *
	 * Registers a service resolver in the container with optional singleton behavior.
	 * The resolver can be a class name, closure, or any callable.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $name Service name/identifier.
	 * @param mixed  $resolver Resolver function or class name.
	 * @param bool   $singleton Whether to treat as singleton.
	 *
	 * @return void
	 */
	public function bind( string $name, $resolver, bool $singleton = false ): void {
		$this->bindings[ $name ] = [
			'resolver'  => $resolver,
			'singleton' => $singleton,
		];

		// Remove any existing instance if rebinding
		if ( isset( $this->instances[ $name ] ) ) {
			unset( $this->instances[ $name ] );
		}
	}

	/**
	 * Bind a singleton service to the container.
	 *
	 * Convenience method for binding a service as a singleton.
	 * Equivalent to calling bind() with singleton parameter set to true.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $name Service name/identifier.
	 * @param mixed  $resolver Resolver function or class name.
	 *
	 * @return void
	 */
	public function singleton( string $name, $resolver ): void {
		$this->bind( $name, $resolver, true );
	}

	/**
	 * Register an existing instance as a singleton.
	 *
	 * Stores an already instantiated object in the container as a singleton.
	 * Useful for registering objects that were created externally.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $name Service name/identifier.
	 * @param mixed  $instance Service instance.
	 *
	 * @return void
	 */
	public function instance( string $name, $instance ): void {
		$this->instances[ $name ] = $instance;
	}

	/**
	 * Resolve a service from the container.
	 *
	 * Retrieves a service instance from the container, creating it if necessary.
	 * Handles singleton caching and automatic dependency injection through reflection.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $name Service name/identifier.
	 *
	 * @return mixed The resolved service instance.
	 *
	 * @throws Exception If service not found or cannot be resolved.
	 */
	public function resolve( string $name ) {
		// Check if we have a cached singleton instance
		if ( isset( $this->instances[ $name ] ) ) {
			return $this->instances[ $name ];
		}

		// Check if we have a binding for this service
		if ( ! isset( $this->bindings[ $name ] ) ) {
			// Try to auto-resolve if it's a class name
			if ( class_exists( $name ) ) {
				return $this->auto_resolve( $name );
			}

			throw new Exception( "Service [$name] not found in container." ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$binding  = $this->bindings[ $name ];
		$resolver = $binding['resolver'];

		// Resolve the service
		if ( is_callable( $resolver ) ) {
			$instance = $resolver( $this );
		} elseif ( is_string( $resolver ) && class_exists( $resolver ) ) {
			$instance = $this->auto_resolve( $resolver );
		} else {
			$instance = $resolver;
		}

		// Cache singleton instances
		if ( $binding['singleton'] ) {
			$this->instances[ $name ] = $instance;
		}

		return $instance;
	}

	/**
	 * Auto-resolve a class using reflection.
	 *
	 * Uses reflection to automatically resolve class dependencies by analyzing
	 * constructor parameters and recursively resolving dependencies from the container.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $class_name Class name to resolve.
	 *
	 * @return mixed The instantiated class with resolved dependencies.
	 *
	 * @throws Exception If class cannot be resolved or dependencies are missing.
	 */
	private function auto_resolve( string $class_name ) {
		try {
			$reflection = new ReflectionClass( $class_name );

			// If no constructor, just instantiate
			if ( ! $reflection->hasMethod( '__construct' ) ) {
				return $reflection->newInstance();
			}

			$constructor = $reflection->getConstructor();
			$parameters  = $constructor->getParameters();

			// If no parameters, just instantiate
			if ( empty( $parameters ) ) {
				return $reflection->newInstance();
			}

			// Resolve constructor dependencies
			$dependencies = [];
			foreach ( $parameters as $parameter ) {
				$type = $parameter->getType();
				// @phpstan-ignore-next-line
				if ( $type && ! $type->isBuiltin() ) {
					// @phpstan-ignore-next-line
					$dependency_class = $type->getName();
					$dependencies[]   = $this->resolve( $dependency_class );
				} elseif ( $parameter->isDefaultValueAvailable() ) {
					$dependencies[] = $parameter->getDefaultValue();
				} else {
					throw new Exception( "Cannot resolve parameter [{$parameter->getName()}] for class [$class_name]." );
				}
			}

			return $reflection->newInstanceArgs( $dependencies );
		} catch ( ReflectionException $e ) {
			throw new Exception( "Cannot auto-resolve class [$class_name]: " . $e->getMessage() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
	}

	/**
	 * Check if a service is bound in the container.
	 *
	 * Verifies whether a service is registered in the bindings or
	 * exists as a cached instance.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $name Service name/identifier.
	 *
	 * @return bool True if service exists, false otherwise.
	 */
	public function has( string $name ): bool {
		return isset( $this->bindings[ $name ] ) || isset( $this->instances[ $name ] );
	}

	/**
	 * Get all registered service names.
	 *
	 * Returns an array of all service names that are either bound
	 * in the container or exist as cached instances.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return array<string> Array of service names.
	 */
	public function get_services(): array {
		return array_merge( array_keys( $this->bindings ), array_keys( $this->instances ) );
	}

	/**
	 * Magic method to resolve services using property syntax.
	 *
	 * Allows accessing services using property syntax: $container->service_name
	 * instead of $container->resolve('service_name').
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $name Service name.
	 *
	 * @return mixed The resolved service instance.
	 *
	 * @throws Exception If service cannot be resolved.
	 */
	public function __get( string $name ) {
		return $this->resolve( $name );
	}

	/**
	 * Magic method to check if service exists using isset().
	 *
	 * Allows checking service existence using isset($container->service_name)
	 * syntax instead of $container->has('service_name').
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $name Service name.
	 *
	 * @return bool True if service exists, false otherwise.
	 */
	public function __isset( string $name ): bool {
		return $this->has( $name );
	}
}
