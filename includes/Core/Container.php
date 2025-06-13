<?php
/**
 * Dependency Injection Container for managing class dependencies.
 *
 * @since      1.0.0
 * @package    DebugSuite
 * @subpackage DebugSuite/Core
 * @author     Kamruzzaman <kzamanbn@gmail.com>
 */

namespace DebugSuite\Core;

/**
 * Dependency Injection Container for managing class dependencies.
 *
 * @since      1.0.0
 * @package    DebugSuite
 * @subpackage DebugSuite/Core
 * @author     Kamruzzaman <kzamanbn@gmail.com>
 */
class Container {

	/**
	 * Container instance.
	 *
	 * @var Container
	 */
	private static $instance;

	/**
	 * Registered services.
	 *
	 * @var array
	 */
	private $services = array();

	/**
	 * Singleton instances.
	 *
	 * @var array
	 */
	private $instances = array();

	/**
	 * Service bindings.
	 *
	 * @var array
	 */
	private $bindings = array();

	/**
	 * Private constructor to prevent direct instantiation.
	 */
	private function __construct() {
		// Private constructor
	}

	/**
	 * Get the container instance.
	 *
	 * @return Container
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
	 * @param string   $name     Service name/identifier.
	 * @param callable $resolver Resolver function or class name.
	 * @param bool     $singleton Whether to treat as singleton.
	 * @return void
	 */
	public function bind( string $name, $resolver, bool $singleton = false ): void {
		$this->bindings[ $name ] = array(
			'resolver'  => $resolver,
			'singleton' => $singleton,
		);

		// Remove any existing instance if rebinding
		if ( isset( $this->instances[ $name ] ) ) {
			unset( $this->instances[ $name ] );
		}
	}

	/**
	 * Bind a singleton service to the container.
	 *
	 * @param string   $name     Service name/identifier.
	 * @param callable $resolver Resolver function or class name.
	 * @return void
	 */
	public function singleton( string $name, $resolver ): void {
		$this->bind( $name, $resolver, true );
	}

	/**
	 * Register an existing instance as a singleton.
	 *
	 * @param string $name     Service name/identifier.
	 * @param mixed  $instance Service instance.
	 * @return void
	 */
	public function instance( string $name, $instance ): void {
		$this->instances[ $name ] = $instance;
	}

	/**
	 * Resolve a service from the container.
	 *
	 * @param string $name Service name/identifier.
	 * @return mixed
	 * @throws \Exception If service not found.
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

			throw new \Exception( "Service [{$name}] not found in container." );
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
	 * @param string $class_name Class name to resolve.
	 * @return mixed
	 * @throws \Exception If class cannot be resolved.
	 */
	private function auto_resolve( string $class_name ) {
		try {
			$reflection = new \ReflectionClass( $class_name );

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
			$dependencies = array();
			foreach ( $parameters as $parameter ) {
				$type = $parameter->getType();

				if ( $type && ! $type->isBuiltin() ) {
					$dependency_class = $type->getName();
					$dependencies[]   = $this->resolve( $dependency_class );
				} elseif ( $parameter->isDefaultValueAvailable() ) {
					$dependencies[] = $parameter->getDefaultValue();
				} else {
					throw new \Exception( "Cannot resolve parameter [{$parameter->getName()}] for class [{$class_name}]." );
				}
			}

			return $reflection->newInstanceArgs( $dependencies );
		} catch ( \ReflectionException $e ) {
			throw new \Exception( "Cannot auto-resolve class [{$class_name}]: " . $e->getMessage() );
		}
	}

	/**
	 * Check if a service is bound.
	 *
	 * @param string $name Service name/identifier.
	 * @return bool
	 */
	public function has( string $name ): bool {
		return isset( $this->bindings[ $name ] ) || isset( $this->instances[ $name ] );
	}

	/**
	 * Get all registered services.
	 *
	 * @return array
	 */
	public function get_services(): array {
		return array_merge( array_keys( $this->bindings ), array_keys( $this->instances ) );
	}

	/**
	 * Magic method to resolve services using property syntax.
	 *
	 * @param string $name Service name.
	 * @return mixed
	 */
	public function __get( string $name ) {
		return $this->resolve( $name );
	}

	/**
	 * Magic method to check if service exists.
	 *
	 * @param string $name Service name.
	 * @return bool
	 */
	public function __isset( string $name ): bool {
		return $this->has( $name );
	}
}
