<?php
/**
 * Dependency injection container with advanced features.
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
use ReflectionParameter;
use Throwable;

/**
 * Dependency injection container with advanced features.
 *
 * Provides a singleton container implementation for dependency injection
 * with support for service binding, singleton management, automatic
 * dependency resolution using reflection, and service definitions.
 *
 * @since 1.0.0
 */
class Container implements ContainerInterface {

	/**
	 * Container singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @var Container|null
	 */
	private static ?Container $instance = null;

	/**
	 * Registered services array.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, mixed>
	 */
	// @phpstan-ignore-next-line
	private array $services = [];

	/**
	 * Singleton instances cache.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, mixed>
	 */
	private array $instances = [];

	/**
	 * Service definitions configuration.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, DefinitionInterface>
	 */
	private array $definitions = [];

	/**
	 * Legacy service bindings configuration (for backward compatibility).
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, array{resolver: mixed, singleton: bool}>
	 */
	private array $bindings = [];

	/**
	 * Autowiring enabled flag.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	private bool $autowiring_enabled = true;

	/**
	 * Tagged services registry.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, array<string>>
	 */
	private array $tagged_services = [];

	/**
	 * Reflection cache for performance optimization.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, ReflectionClass>
	 */
	private array $reflection_cache = [];

	/**
	 * Constructor parameter cache for performance optimization.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, array<ReflectionParameter>>
	 */
	private array $constructor_cache = [];

	/**
	 * Service resolution stack for circular dependency detection.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string>
	 */
	private array $resolving_stack = [];

	/**
	 * Service resolution performance statistics.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, array{success: array<float>, failure: array<float>}>
	 */
	private array $resolution_stats = [];

	/**
	 * Service aliases for interface binding and aliasing.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, string>
	 */
	private array $aliases = [];

	/**
	 * Interface to implementation bindings.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, string>
	 */
	private array $interface_bindings = [];

	/**
	 * Debug mode flag for enhanced logging.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	private bool $debug_mode = false;

	/**
	 * Container compilation state for performance optimization.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	private bool $compiled = false;

	/**
	 * Private constructor to prevent direct instantiation.
	 *
	 * Implements singleton pattern by preventing external instantiation.
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * Finds an entry of the container by its identifier and returns it.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id Identifier of the entry to look for.
	 *
	 * @return mixed Entry.
	 *
	 * @throws NotFoundException  No entry was found for this identifier.
	 * @throws ContainerException|Throwable Error while retrieving the entry.
	 */
	public function get( string $id ): mixed {
		return $this->resolve( $id );
	}

	/**
	 * Returns true if the container can return an entry for the given identifier.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id Identifier of the entry to look for.
	 *
	 * @return bool
	 */
	public function has( string $id ): bool {
		// Try to resolve aliases, but don't throw on circular references
		try {
			$resolved_id = $this->resolve_alias( $id );
		} catch ( ContainerException $e ) {
			// If there's a circular alias, treat as not found
			return false;
		}

		return isset( $this->definitions[ $resolved_id ] ) ||
				isset( $this->bindings[ $resolved_id ] ) ||
				isset( $this->instances[ $resolved_id ] ) ||
				isset( $this->aliases[ $id ] ) ||
				isset( $this->interface_bindings[ $id ] ) ||
				( $this->autowiring_enabled && class_exists( $resolved_id ) );
	}

	/**
	 * Set a definition for a service.
	 *
	 * @since 1.0.0
	 *
	 * @param string              $id         Service identifier.
	 * @param DefinitionInterface $definition Service definition.
	 *
	 * @return void
	 *
	 * @throws ContainerException If container is compiled.
	 */
	public function set( string $id, DefinitionInterface $definition ): void {
		$this->ensure_not_compiled();

		$definition->set_name( $id );
		$this->definitions[ $id ] = $definition;

		// Remove any existing instance if rebinding
		if ( isset( $this->instances[ $id ] ) ) {
			unset( $this->instances[ $id ] );
		}

		if ( $this->debug_mode ) {
			error_log( "Debug Suite Container: Set definition for service [$id]." );
		}
	}

	/**
	 * Create a factory definition.
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
	 *
	 * @param mixed $value The value to return.
	 *
	 * @return ValueDefinition
	 */
	public function value( mixed $value ): ValueDefinition {
		return new ValueDefinition( $value );
	}

	/**
	 * Create an object definition (autowired singleton).
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
	 *
	 * @param string $name Service name/identifier.
	 * @param mixed  $resolver Resolver function or class name.
	 * @param bool   $singleton Whether to treat as singleton.
	 *
	 * @return void
	 *
	 * @throws ContainerException If container is compiled.
	 */
	public function bind( string $name, $resolver, bool $singleton = false ): void {
		$this->ensure_not_compiled();

		$this->bindings[ $name ] = [
			'resolver'  => $resolver,
			'singleton' => $singleton,
		];

		// Remove any existing instance if rebinding
		if ( isset( $this->instances[ $name ] ) ) {
			unset( $this->instances[ $name ] );
		}

		if ( $this->debug_mode ) {
			$type = $singleton ? 'singleton' : 'transient';
			error_log( "Debug Suite Container: Bound [$name] as [$type] service." );
		}
	}

	/**
	 * Bind a singleton service to the container.
	 *
	 * Convenience method for binding a service as a singleton.
	 * Equivalent to calling bind() with singleton parameter set to true.
	 *
	 * @param string $name Service name/identifier.
	 * @param mixed $resolver Resolver function or class name.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 * @throws ContainerException If container is compiled.
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
	 * @since 1.0.0
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
	 * Get cached reflection class for performance optimization.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The class name to get reflection for.
	 *
	 * @return ReflectionClass
	 *
	 * @throws ContainerException If reflection fails.
	 */
	private function get_cached_reflection( string $class_name ): ReflectionClass {
		if ( ! isset( $this->reflection_cache[ $class_name ] ) ) {
			try {
				$this->reflection_cache[ $class_name ] = new ReflectionClass( $class_name );
			} catch ( ReflectionException $e ) {
				throw new ContainerException( esc_html( "Cannot create reflection for class [$class_name]: " . $e->getMessage() ), 0 );
			}
		}

		return $this->reflection_cache[ $class_name ];
	}

	/**
	 * Get cached constructor parameters for performance optimization.
	 *
	 * @since 1.0.0
	 *
	 * @param string          $class_name The class name to get constructor parameters for.
	 * @param ReflectionClass $reflection Optional reflection class to use.
	 *
	 * @return array<ReflectionParameter>
	 *
	 * @throws ContainerException If reflection fails.
	 */
	private function get_cached_constructor_parameters( string $class_name, ?ReflectionClass $reflection = null ): array {
		if ( ! isset( $this->constructor_cache[ $class_name ] ) ) {
			$reflection = $reflection ?? $this->get_cached_reflection( $class_name );

			if ( ! $reflection->hasMethod( '__construct' ) ) {
				$this->constructor_cache[ $class_name ] = [];
			} else {
				$constructor                            = $reflection->getConstructor();
				$this->constructor_cache[ $class_name ] = $constructor ? $constructor->getParameters() : [];
			}
		}

		return $this->constructor_cache[ $class_name ];
	}

	/**
	 * Check for circular dependency in service resolution.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The class name being resolved.
	 *
	 * @throws ContainerException If circular dependency is detected.
	 *
	 * @return void
	 */
	private function check_circular_dependency( string $class_name ): void {
		if ( in_array( $class_name, $this->resolving_stack, true ) ) {
			$chain = implode( ' -> ', [ ...$this->resolving_stack, $class_name ] );
			throw new ContainerException( esc_html( "Circular dependency detected: $chain" ) );
		}
	}

	/**
	 * Record resolution time for performance monitoring.
	 *
	 * @since 1.0.0
	 *
	 * @param string $service    The service identifier.
	 * @param float  $start_time The start time of resolution.
	 * @param bool   $success    Whether resolution was successful.
	 *
	 * @return void
	 */
	private function record_resolution_time( string $service, float $start_time, bool $success ): void {
		$time = microtime( true ) - $start_time;

		if ( ! isset( $this->resolution_stats[ $service ] ) ) {
			$this->resolution_stats[ $service ] = [
				'success' => [],
				'failure' => [],
			];
		}

		$key = $success ? 'success' : 'failure';
		$this->resolution_stats[ $service ][ $key ][] = $time;

		if ( $this->debug_mode ) {
			$status = $success ? 'successfully resolved' : 'failed to resolve';
			error_log( "Container: $status '$service' in " . ( $time * 1000 ) . 'ms' );
		}
	}

	/**
	 * Build enhanced error message with context.
	 *
	 * @since 1.0.0
	 *
	 * @param string $service The service identifier.
	 * @param string $message Base error message.
	 * @param array  $context Additional context for debugging.
	 *
	 * @return string Enhanced error message.
	 */
	private function build_enhanced_error_message( string $service, string $message, array $context = [] ): string {
		$enhanced_message = "Service resolution failed for [$service]: $message";

		if ( ! empty( $context ) ) {
			$enhanced_message .= "\nContext:";
			foreach ( $context as $key => $value ) {
				if ( is_array( $value ) ) {
					$value = implode( ', ', $value );
				} elseif ( is_bool( $value ) ) {
					$value = $value ? 'true' : 'false';
				}
				$enhanced_message .= "\n  - $key: $value";
			}
		}

		if ( ! empty( $this->resolving_stack ) ) {
			$enhanced_message .= "\nResolution stack: " . implode( ' -> ', $this->resolving_stack );
		}

		return $enhanced_message;
	}

	/**
	 * Profile service resolution for performance monitoring.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $service  The service identifier.
	 * @param callable $resolver The resolver callable.
	 *
	 * @return mixed The resolved service.
	 *
	 * @throws Throwable If service resolution fails.
	 */
	private function profile_resolution( string $service, callable $resolver ) {
		$start = microtime( true );

		try {
			$result = $resolver();
			$time   = microtime( true ) - $start;

			if ( ! isset( $this->resolution_stats[ $service ] ) ) {
				$this->resolution_stats[ $service ] = [
					'success' => [],
					'failure' => [],
				];
			}

			$this->resolution_stats[ $service ]['success'][] = $time;

			if ( $this->debug_mode ) {
				error_log( "Container: Successfully resolved '$service' in " . ( $time * 1000 ) . 'ms' );
			}

			return $result;
		} catch ( Throwable $e ) {
			$time = microtime( true ) - $start;

			if ( ! isset( $this->resolution_stats[ $service ] ) ) {
				$this->resolution_stats[ $service ] = [
					'success' => [],
					'failure' => [],
				];
			}

			$this->resolution_stats[ $service ]['failure'][] = $time;

			if ( $this->debug_mode ) {
				error_log( "Container: Failed to resolve '$service' in " . ( $time * 1000 ) . 'ms: ' . $e->getMessage() );
			}

			throw $e;
		}
	}

	/**
	 * Find similar service names for better error messages.
	 *
	 * @since 1.0.0
	 *
	 * @param string $service The service name to find suggestions for.
	 *
	 * @return array<string> Array of similar service names.
	 */
	private function find_similar_services( string $service ): array {
		$available   = array_keys( $this->definitions );
		$suggestions = [];

		foreach ( $available as $available_service ) {
			$similarity = similar_text( $service, $available_service, $percent );
			if ( $percent > 70 ) {
				$suggestions[] = $available_service;
			}
		}

		// Also check levenshtein distance for shorter strings
		foreach ( $available as $available_service ) {
			if ( levenshtein( $service, $available_service ) <= 3 && strlen( $service ) > 3 ) {
				$suggestions[] = $available_service;
			}
		}

		return array_unique( $suggestions );
	}

	/**
	 * Generate enhanced error message with context and suggestions.
	 *
	 * @since 1.0.0
	 *
	 * @param string          $service  The service that failed to resolve.
	 * @param Throwable|null $previous Previous exception if any.
	 *
	 * @return string Enhanced error message.
	 */
	private function generate_enhanced_error_message( string $service, ?Throwable $previous = null ): string {
		$message = "Failed to resolve service: $service";

		if ( ! empty( $this->resolving_stack ) ) {
			$message .= "\nDependency chain: " . implode( ' -> ', $this->resolving_stack );
		}

		$suggestions = $this->find_similar_services( $service );
		if ( ! empty( $suggestions ) ) {
			$message .= "\nDid you mean one of these?\n";
			foreach ( $suggestions as $suggestion ) {
				$message .= "  - $suggestion\n";
			}
		}

		$available_services = array_keys( $this->definitions );
		if ( ! empty( $available_services ) ) {
			$message .= "\nAvailable services: " . implode( ', ', array_slice( $available_services, 0, 10 ) );
			if ( count( $available_services ) > 10 ) {
				$message .= '... and ' . ( count( $available_services ) - 10 ) . ' more';
			}
		}

		if ( $previous ) {
			$message .= "\nCaused by: " . $previous->getMessage();
		}

		return $message;
	}

	/**
	 * Resolve a service from the container with enhanced features.
	 *
	 * Retrieves a service instance from the container, creating it if necessary.
	 * Handles singleton caching and automatic dependency injection through reflection.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Service name/identifier.
	 *
	 * @return mixed The resolved service instance.
	 *
	 * @throws NotFoundException  If service not found.
	 * @throws ContainerException If service cannot be resolved.
	 */
	/**
	 * Resolve a service from the container with enhanced features.
	 *
	 * Retrieves a service instance from the container, creating it if necessary.
	 * Handles singleton caching, automatic dependency injection through reflection,
	 * circular dependency detection, and performance profiling.
	 *
	 * @param string $name Service name/identifier.
	 *
	 * @return mixed The resolved service instance.
	 *
	 * @since 1.0.0
	 *
	 * @throws NotFoundException  If service not found.
	 * @throws ContainerException|Throwable If service cannot be resolved.
	 */
	public function resolve( string $name ) {
		return $this->profile_resolution(
			$name,
			function () use ( $name ) {
				// Check for circular dependencies early
				$this->check_circular_dependency( $name );

				// Add to resolution stack
				$this->resolving_stack[] = $name;

				try {
					// Check interface bindings first
					if ( isset( $this->interface_bindings[ $name ] ) ) {
						$name = $this->interface_bindings[ $name ];
					}

					// Resolve aliases (with circular detection)
					$name = $this->resolve_alias( $name );

					// Check if we have a cached singleton instance
					if ( isset( $this->instances[ $name ] ) ) {
						array_pop( $this->resolving_stack );
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

						array_pop( $this->resolving_stack );
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

						array_pop( $this->resolving_stack );
						return $instance;
					}

					// Try to auto-resolve if enabled and it's a class name
					if ( $this->autowiring_enabled && class_exists( $name ) ) {
						$result = $this->auto_resolve_internal( $name );
						array_pop( $this->resolving_stack );
						return $result;
					}

					// Generate enhanced error message
					array_pop( $this->resolving_stack );
					$error_message = $this->generate_enhanced_error_message( $name );
					throw NotFoundException::for_identifier_with_message( esc_html( $name ), esc_html( $error_message ) );

				} catch ( \Exception $e ) {
					array_pop( $this->resolving_stack );
					throw $e;
				}
			}
		);
	}

	/**
	 * Auto-resolve a class using reflection.
	 *
	 * Uses reflection to automatically resolve class dependencies by analyzing
	 * constructor parameters and recursively resolving dependencies from the container.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name Class name to resolve.
	 *
	 * @return mixed The instantiated class with resolved dependencies.
	 *
	 * @throws ContainerException If class cannot be resolved or dependencies are missing.
	 */
	/**
	 * Auto-resolve a class with dependency injection.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The class name to auto-resolve.
	 *
	 * @return mixed The resolved instance.
	 *
	 * @throws ContainerException If the class cannot be auto-resolved.
	 */
	private function auto_resolve( string $class_name ) {
		$start_time = microtime( true );

		try {
			// Check for circular dependencies
			$this->check_circular_dependency( $class_name );

			// Add to resolution stack
			$this->resolving_stack[] = $class_name;

			// Get reflection from cache or create new
			$reflection = $this->get_cached_reflection( $class_name );

			// If no constructor, just instantiate
			if ( ! $reflection->hasMethod( '__construct' ) ) {
				$instance = $reflection->newInstance();
				$this->record_resolution_time( $class_name, $start_time, true );
				array_pop( $this->resolving_stack );
				return $instance;
			}
			// Get constructor parameters from cache or reflection
			$parameters = $this->get_cached_constructor_parameters( $class_name, $reflection );

			// If no parameters, just instantiate
			if ( empty( $parameters ) ) {
				$instance = $reflection->newInstance();
				$this->record_resolution_time( $class_name, $start_time, true );
				array_pop( $this->resolving_stack );
				return $instance;
			}

			// Resolve constructor dependencies
			$dependencies = $this->resolve_constructor_dependencies( $class_name, $parameters );

			$instance = $reflection->newInstanceArgs( $dependencies );
			$this->record_resolution_time( $class_name, $start_time, true );
			array_pop( $this->resolving_stack );

			return $instance;

		} catch ( ReflectionException $e ) {
			$this->record_resolution_time( $class_name, $start_time, false );
			array_pop( $this->resolving_stack );

			$error_message = $this->build_enhanced_error_message(
				$class_name,
				'Cannot auto-resolve class: ' . $e->getMessage(),
				[
					'reflection_error' => $e->getMessage(),
					'class_exists'     => class_exists( $class_name ),
					'resolution_stack' => $this->resolving_stack,
				]
			);
			throw new ContainerException( esc_html( $error_message ), 0 );
		} catch ( ContainerException $e ) {
			$this->record_resolution_time( $class_name, $start_time, false );
			array_pop( $this->resolving_stack );
			throw $e;
		}
	}

	/**
	 * Auto-resolve a class with dependency injection without managing resolution stack.
	 * This is used internally when the stack is already managed by the caller.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The class name to auto-resolve.
	 *
	 * @return mixed The resolved instance.
	 *
	 * @throws ContainerException If the class cannot be auto-resolved.
	 */
	private function auto_resolve_internal( string $class_name ) {
		$start_time = microtime( true );

		try {
			// Get reflection from cache or create new
			$reflection = $this->get_cached_reflection( $class_name );

			// If no constructor, just instantiate
			if ( ! $reflection->hasMethod( '__construct' ) ) {
				$instance = $reflection->newInstance();
				$this->record_resolution_time( $class_name, $start_time, true );
				return $instance;
			}

			// Get constructor parameters from cache or reflection
			$parameters = $this->get_cached_constructor_parameters( $class_name, $reflection );

			// If no parameters, just instantiate
			if ( empty( $parameters ) ) {
				$instance = $reflection->newInstance();
				$this->record_resolution_time( $class_name, $start_time, true );
				return $instance;
			}

			// Resolve constructor dependencies
			$dependencies = $this->resolve_constructor_dependencies( $class_name, $parameters );

			$instance = $reflection->newInstanceArgs( $dependencies );
			$this->record_resolution_time( $class_name, $start_time, true );

			return $instance;

		} catch ( ReflectionException $e ) {
			$this->record_resolution_time( $class_name, $start_time, false );

			$error_message = $this->build_enhanced_error_message(
				$class_name,
				'Cannot auto-resolve class: ' . $e->getMessage(),
				[
					'reflection_error' => $e->getMessage(),
					'class_exists'     => class_exists( $class_name ),
					'resolution_stack' => $this->resolving_stack,
				]
			);
			throw new ContainerException( esc_html( $error_message ), 0 );
		} catch ( ContainerException $e ) {
			$this->record_resolution_time( $class_name, $start_time, false );
			throw $e;
		}
	}

	/**
	 * Enable or disable autowiring.
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_autowiring_enabled(): bool {
		return $this->autowiring_enabled;
	}

	/**
	 * Tag a service with a specific tag.
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * Add multiple definitions from an array.
	 *
	 * Supports compatible definition arrays for bulk service configuration.
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
	 *
	 * @param string $name Service name.
	 *
	 * @return bool True if service exists, false otherwise.
	 */
	public function __isset( string $name ): bool {
		return $this->has( $name );
	}

	/**
	 * Compile the container for performance optimization.
	 *
	 * When compiled, the container becomes immutable and optimizes
	 * performance by pre-caching reflection data and definitions.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 *
	 * @throws ContainerException If container is already compiled.
	 */
	public function compile(): void {
		if ( $this->compiled ) {
			throw new ContainerException( 'Container is already compiled and cannot be modified.' );
		}

		// Pre-cache reflection data for all registered classes
		foreach ( array_merge( array_keys( $this->definitions ), array_keys( $this->bindings ) ) as $id ) {
			if ( class_exists( $id ) ) {
				try {
					$this->get_cached_reflection( $id );
				} catch ( ReflectionException $e ) {
					// Skip classes that can't be reflected
					continue;
				}
			}
		}

		// Mark as compiled
		$this->compiled = true;

		if ( $this->debug_mode ) {
			error_log( 'Debug Suite Container: Compiled with ' . count( $this->reflection_cache ) . ' cached reflections.' );
		}
	}

	/**
	 * Check if the container is compiled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if compiled, false otherwise.
	 */
	public function is_compiled(): bool {
		return $this->compiled;
	}

	/**
	 * Enable or disable debug mode.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $enabled Whether debug mode is enabled.
	 *
	 * @return void
	 */
	public function set_debug_mode( bool $enabled ): void {
		$this->debug_mode = $enabled;
	}

	/**
	 * Check if debug mode is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if debug mode is enabled, false otherwise.
	 */
	public function is_debug_mode(): bool {
		return $this->debug_mode;
	}

	/**
	 * Bind an interface to an implementation.
	 *
	 * @since 1.0.0
	 *
	 * @param string $b_interface      The interface name.
	 * @param string $implementation The implementation class name.
	 *
	 * @return void
	 *
	 * @throws ContainerException If container is compiled or interface doesn't exist.
	 */
	public function bind_interface( string $b_interface, string $implementation ): void {
		$this->ensure_not_compiled();

		// Validate that the interface exists
		if ( ! interface_exists( $b_interface ) ) {
			throw new ContainerException( esc_html( "Interface $b_interface does not exist" ) );
		}

		$this->interface_bindings[ $b_interface ] = $implementation;

		if ( $this->debug_mode ) {
			error_log( "Debug Suite Container: Bound interface [$b_interface] to implementation [$implementation]." );
		}
	}

	/**
	 * Create an alias for a service.
	 *
	 * @since 1.0.0
	 *
	 * @param string $alias   The alias name.
	 * @param string $service The original service name.
	 *
	 * @return void
	 *
	 * @throws ContainerException If container is compiled.
	 */
	public function alias( string $alias, string $service ): void {
		$this->ensure_not_compiled();
		$this->aliases[ $alias ] = $service;

		if ( $this->debug_mode ) {
			error_log( "Debug Suite Container: Created alias [$alias] for service [$service]." );
		}
	}

	/**
	 * Resolve an alias to the actual service name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id The service identifier or alias.
	 *
	 * @return string The actual service name.
	 */
	private function resolve_alias( string $id ): string {
		$original_id = $id;
		$seen        = [];

		// Follow alias chain while preventing infinite loops
		while ( isset( $this->aliases[ $id ] ) ) {
			if ( isset( $seen[ $id ] ) ) {
				throw new ContainerException( esc_html( "Circular alias reference detected for [$original_id]." ) );
			}
			$seen[ $id ] = true;
			$id          = $this->aliases[ $id ];
		}

		return $id;
	}

	/**
	 * Resolve interface binding to implementation.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id The interface name.
	 *
	 * @return string The implementation class name or original id.
	 */
	private function resolve_interface_binding( string $id ): string {
		return $this->interface_bindings[ $id ] ?? $id;
	}

	/**
	 * Ensure container is not compiled before modification.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 *
	 * @throws ContainerException If container is compiled.
	 */
	private function ensure_not_compiled(): void {
		if ( $this->compiled ) {
			throw new ContainerException( 'Cannot modify compiled container. Create a new container instance if modifications are needed.' );
		}
	}

	/**
	 * Get container performance statistics.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Performance statistics including resolution times and cache hits.
	 */
	public function get_performance_stats(): array {
		$total_resolutions = 0;
		$total_time        = 0.0;
		$cache_hits        = count( $this->reflection_cache );

		foreach ( $this->resolution_stats as $service => $stats ) {
			$resolutions        = count( $stats['success'] ) + count( $stats['failure'] );
			$total_resolutions += $resolutions;
			$total_time        += array_sum( $stats['success'] ) + array_sum( $stats['failure'] );
		}

		return [
			'total_resolutions'  => $total_resolutions,
			'total_time'         => $total_time,
			'average_time'       => $total_resolutions > 0 ? $total_time / $total_resolutions : 0,
			'cache_hits'         => $cache_hits,
			'services_resolved'  => count( $this->resolution_stats ),
			'is_compiled'        => $this->compiled,
			'debug_mode'         => $this->debug_mode,
			'detailed_stats'     => $this->resolution_stats,
		];
	}

	/**
	 * Clear performance statistics and caches.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function clear_performance_data(): void {
		$this->resolution_stats  = [];
		$this->reflection_cache  = [];
		$this->constructor_cache = [];

		if ( $this->debug_mode ) {
			error_log( 'Debug Suite Container: Performance data cleared.' );
		}
	}

	/**
	 * Resolve constructor dependencies for a class.
	 *
	 * @since 1.0.0
	 *
	 * @param string                      $class_name The class name being resolved.
	 * @param array<ReflectionParameter> $parameters The constructor parameters.
	 *
	 * @return array<mixed> The resolved dependencies.
	 *
	 * @throws ContainerException If a parameter cannot be resolved.
	 */
	private function resolve_constructor_dependencies( string $class_name, array $parameters ): array {
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
				$error_message = $this->build_enhanced_error_message(
					$class_name,
					"Cannot resolve parameter [{$parameter->getName()}]",
					[
						'parameter_name' => $parameter->getName(),
						'parameter_type' => $type ? $type->getName() : 'mixed',
						'has_default'    => $parameter->isDefaultValueAvailable(),
						'is_optional'    => $parameter->isOptional(),
						'resolution_stack' => $this->resolving_stack,
					]
				);
				throw new ContainerException( esc_html( $error_message ) );
			}
		}

		return $dependencies;
	}
}
