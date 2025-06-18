<?php
/**
 * PSR-11 compliant dependency injection container with PHP-DI features.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core\Container;

use Psr\Container\ContainerInterface;
use DebugSuite\Core\Container\Exceptions\ContainerException;
use DebugSuite\Core\Container\Exceptions\NotFoundException;
use DebugSuite\Core\Container\Definitions\DefinitionInterface;
use DebugSuite\Core\Container\Definitions\FactoryDefinition;
use DebugSuite\Core\Container\Definitions\AutowiredDefinition;
use DebugSuite\Core\Container\Definitions\ValueDefinition;
use DebugSuite\Core\Container\Definitions\ConfigDefinition;
use DebugSuite\Core\Container\Definitions\DecoratorDefinition;
use ReflectionClass;
use ReflectionException;

/**
 * PSR-11 compliant dependency injection container with PHP-DI features.
 *
 * Provides a singleton container implementation for dependency injection
 * with support for service binding, singleton management, automatic
 * dependency resolution using reflection, and PHP-DI style definitions.
 *
 * @since DEBUG_SUITE_SINCE
 */
class Container implements ContainerInterface {

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
	 * Service definitions configuration.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var array<string, DefinitionInterface>
	 */
	private array $definitions = [];

	/**
	 * Legacy service bindings configuration (for backward compatibility).
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var array<string, array{resolver: mixed, singleton: bool}>
	 */
	private array $bindings = [];

	/**
	 * Autowiring enabled flag.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var bool
	 */
	private bool $autowiring_enabled = true;

	/**
	 * Tagged services registry.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var array<string, array<string>>
	 */
	private array $tagged_services = [];

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
	 * PSR-11: Finds an entry of the container by its identifier and returns it.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $id Identifier of the entry to look for.
	 *
	 * @return mixed Entry.
	 *
	 * @throws NotFoundException  No entry was found for this identifier.
	 * @throws ContainerException Error while retrieving the entry.
	 */
	public function get( string $id ) {
		return $this->resolve( $id );
	}

	/**
	 * PSR-11: Returns true if the container can return an entry for the given identifier.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $id Identifier of the entry to look for.
	 *
	 * @return bool
	 */
	public function has( string $id ): bool {
		return isset( $this->definitions[ $id ] ) ||
			   isset( $this->bindings[ $id ] ) ||
			   isset( $this->instances[ $id ] ) ||
			   ( $this->autowiring_enabled && class_exists( $id ) );
	}

	/**
	 * Set a definition for a service.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string              $id         Service identifier.
	 * @param DefinitionInterface $definition Service definition.
	 *
	 * @return void
	 */
	public function set( string $id, DefinitionInterface $definition ): void {
		$definition->set_name( $id );
		$this->definitions[ $id ] = $definition;

		// Remove any existing instance if rebinding
		if ( isset( $this->instances[ $id ] ) ) {
			unset( $this->instances[ $id ] );
		}
	}

	/**
	 * Create a factory definition.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param callable $factory The factory callable.
	 *
	 * @return FactoryDefinition
	 */
	public function factory( callable $factory ): FactoryDefinition {
		return new FactoryDefinition( $factory );
	}

	/**
	 * Create an autowired definition.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $class_name The class name to autowire.
	 *
	 * @return AutowiredDefinition
	 */
	public function autowire( string $class_name ): AutowiredDefinition {
		return new AutowiredDefinition( $class_name );
	}

	/**
	 * Create a value definition.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param mixed $value The value to return.
	 *
	 * @return ValueDefinition
	 */
	public function value( $value ): ValueDefinition {
		return new ValueDefinition( $value );
	}

	/**
	 * Create an object definition (autowired singleton).
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $class_name The class name to autowire.
	 *
	 * @return AutowiredDefinition
	 */
	public function object( string $class_name ): AutowiredDefinition {
		return new AutowiredDefinition( $class_name, true );
	}

	/**
	 * Create a configuration definition.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param array $configurations Environment-specific configurations.
	 * @param mixed $default_config Default configuration.
	 * @param bool  $singleton      Whether this is a singleton.
	 *
	 * @return ConfigDefinition
	 */
	public function config( array $configurations = [], $default_config = null, bool $singleton = false ): ConfigDefinition {
		return new ConfigDefinition( $configurations, $default_config, $singleton );
	}

	/**
	 * Create a decorator definition.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $decorator_class   The decorator class name.
	 * @param string $decorated_service The service to decorate.
	 * @param bool   $singleton         Whether this is a singleton.
	 *
	 * @return DecoratorDefinition
	 */
	public function decorate( string $decorator_class, string $decorated_service, bool $singleton = false ): DecoratorDefinition {
		return new DecoratorDefinition( $decorator_class, $decorated_service, $singleton );
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
	 * @throws NotFoundException  If service not found.
	 * @throws ContainerException If service cannot be resolved.
	 */
	public function resolve( string $name ) {
		// Check if we have a cached singleton instance
		if ( isset( $this->instances[ $name ] ) ) {
			return $this->instances[ $name ];
		}

		// Check for definition first
		if ( isset( $this->definitions[ $name ] ) ) {
			$definition = $this->definitions[ $name ];
			$instance   = $definition->resolve( [ $this, 'resolve' ] );

			// Cache singleton instances
			if ( $definition->is_singleton() ) {
				$this->instances[ $name ] = $instance;
			}

			return $instance;
		}

		// Check for legacy binding
		if ( isset( $this->bindings[ $name ] ) ) {
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

		// Try to auto-resolve if enabled and it's a class name
		if ( $this->autowiring_enabled && class_exists( $name ) ) {
			return $this->auto_resolve( $name );
		}

		throw NotFoundException::for_identifier( $name ); // phpcs:ignore
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
	 * @throws ContainerException If class cannot be resolved or dependencies are missing.
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
					throw new ContainerException( "Cannot resolve parameter [{$parameter->getName()}] for class [$class_name]." );
				}
			}

			return $reflection->newInstanceArgs( $dependencies );
		} catch ( ReflectionException $e ) {
			throw new ContainerException( "Cannot auto-resolve class [$class_name]: " . $e->getMessage(), 0, $e ); // phpcs:ignore
		}
	}

	/**
	 * Enable or disable autowiring.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param bool $enabled Whether autowiring is enabled.
	 *
	 * @return void
	 */
	public function set_autowiring( bool $enabled ): void {
		$this->autowiring_enabled = $enabled;
	}

	/**
	 * Check if autowiring is enabled.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return bool
	 */
	public function is_autowiring_enabled(): bool {
		return $this->autowiring_enabled;
	}

	/**
	 * Tag a service with a specific tag.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $service_id The service identifier.
	 * @param string $tag        The tag to apply.
	 *
	 * @return void
	 */
	public function tag( string $service_id, string $tag ): void {
		if ( ! isset( $this->tagged_services[ $tag ] ) ) {
			$this->tagged_services[ $tag ] = [];
		}

		if ( ! in_array( $service_id, $this->tagged_services[ $tag ], true ) ) {
			$this->tagged_services[ $tag ][] = $service_id;
		}
	}

	/**
	 * Get all services tagged with a specific tag.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $tag The tag to search for.
	 *
	 * @return array<mixed> Array of resolved service instances.
	 */
	public function tagged( string $tag ): array {
		if ( ! isset( $this->tagged_services[ $tag ] ) ) {
			return [];
		}

		$services = [];
		foreach ( $this->tagged_services[ $tag ] as $service_id ) {
			if ( $this->has( $service_id ) ) {
				$services[] = $this->resolve( $service_id );
			}
		}

		return $services;
	}

	/**
	 * Get all tags for a service.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $service_id The service identifier.
	 *
	 * @return array<string> Array of tags.
	 */
	public function get_tags( string $service_id ): array {
		$tags = [];
		foreach ( $this->tagged_services as $tag => $services ) {
			if ( in_array( $service_id, $services, true ) ) {
				$tags[] = $tag;
			}
		}

		return $tags;
	}

	/**
	 * Add multiple definitions from an array (PHP-DI style).
	 *
	 * Supports PHP-DI compatible definition arrays for bulk service configuration.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param array $definitions Associative array of service_id => definition pairs.
	 *
	 * @return void
	 *
	 * @example
	 * ```php
	 * $container->add_definitions([
	 *     MyService::class => DI::autowire(),
	 *     'config.debug' => DI::value(true),
	 *     LoggerInterface::class => DI::factory(function() {
	 *         return new FileLogger();
	 *     })
	 * ]);
	 * ```
	 */
	public function add_definitions( array $definitions ): void {
		foreach ( $definitions as $id => $definition ) {
			if ( $definition instanceof DefinitionInterface ) {
				$this->set( $id, $definition );
			} elseif ( is_callable( $definition ) ) {
				$this->set( $id, new FactoryDefinition( $definition ) );
			} elseif ( is_string( $definition ) && class_exists( $definition ) ) {
				$this->set( $id, new AutowiredDefinition( $definition ) );
			} else {
				$this->set( $id, new ValueDefinition( $definition ) );
			}
		}
	}

	/**
	 * Get all registered service names.
	 *
	 * Returns an array of all service names that are either defined,
	 * bound in the container, or exist as cached instances.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return array<string> Array of service names.
	 */
	public function get_services(): array {
		return array_merge(
			array_keys( $this->definitions ),
			array_keys( $this->bindings ),
			array_keys( $this->instances )
		);
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
	 * @throws NotFoundException  If service not found.
	 * @throws ContainerException If service cannot be resolved.
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
