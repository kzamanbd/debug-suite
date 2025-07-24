<?php
/**
 * Autowired definition for dependency injection with enhanced parameter injection.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core\Container\Definitions;

use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use ReflectionType;
use ReflectionNamedType;
use DebugSuite\Core\Container\Exceptions\ContainerException;

/**
 * Autowired definition for advanced dependency injection.
 *
 * Provides comprehensive autowiring capabilities with multiple parameter injection strategies.
 *
 * @since 1.0.0
 *
 * @internal This class is for internal container use only.
 *           Use $container->object() and $container->autowire() in service providers instead.
 */
class AutowiredDefinition implements DefinitionInterface {

	/**
	 * The definition name/identifier.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private string $name = '';

	/**
	 * The class name to autowire.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private string $class_name;

	/**
	 * Whether this definition should be cached as singleton.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	private bool $singleton;

	/**
	 * Static parameter value overrides by parameter name.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, mixed>
	 */
	private array $parameter_overrides = [];

	/**
	 * Dynamic parameter resolution callbacks by parameter name.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, callable>
	 */
	private array $parameter_callbacks = [];

	/**
	 * Environment-specific parameter configurations.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $environment_parameters = [];

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The class name to autowire.
	 * @param bool   $singleton  Whether this definition should be cached as singleton.
	 */
	public function __construct( string $class_name, bool $singleton = false ) {
		$this->class_name = $class_name;
		$this->singleton  = $singleton;
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
	 * @param string $name The definition name/identifier.
	 *
	 * @return static
	 */
	public function set_name( string $name ): static {
		$this->name = $name;
		return $this;
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

	/**
	 * Get the target class name for autowiring.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_class_name(): string {
		return $this->class_name;
	}

	/**
	 * Resolve this definition to an instance using reflection and dependency injection.
	 *
	 * @since 1.0.0
	 *
	 * @param callable $resolver Function to resolve dependencies.
	 *
	 * @return mixed The instantiated object with resolved dependencies.
	 * @throws ContainerException If the class cannot be autowired or dependencies are missing.
	 */
	public function resolve( callable $resolver ) {
		try {
			$reflection = new ReflectionClass( $this->class_name );

			// Handle classes without constructors
			if ( ! $reflection->hasMethod( '__construct' ) ) {
				return $reflection->newInstance();
			}

			$constructor = $reflection->getConstructor();
			$parameters  = $constructor->getParameters();

			// Handle constructors without parameters
			if ( empty( $parameters ) ) {
				return $reflection->newInstance();
			}

			// Resolve all constructor dependencies
			$dependencies = $this->resolve_constructor_dependencies( $parameters, $resolver );

			return $reflection->newInstanceArgs( $dependencies );

		} catch ( ReflectionException $e ) {
			throw new ContainerException(
				esc_html( "Cannot autowire class [{$this->class_name}]: " . $e->getMessage() ),
				0
			);
		}
	}

	/**
	 * Set a static parameter value override for constructor injection.
	 *
	 * This method allows you to manually specify values for constructor parameters,
	 * overriding the default autowiring behavior for specific arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param string $parameter_name The exact name of the constructor parameter.
	 * @param mixed  $value          The static value to inject for this parameter.
	 *
	 * @return static Fluent interface for method chaining.
	 *
	 * @example
	 * ```php
	 * $definition = $container->autowire(LoggerService::class)
	 *     ->constructor_parameter('log_level', 'debug')
	 *     ->constructor_parameter('log_file', '/var/log/app.log');
	 * ```
	 */
	public function constructor_parameter( string $parameter_name, $value ): static {
		$this->parameter_overrides[ $parameter_name ] = $value;
		return $this;
	}

	/**
	 * Set a dynamic parameter value using a callback function.
	 *
	 * This method enables runtime parameter resolution using callable functions.
	 * The callback receives the container resolver as its first argument, allowing
	 * access to other container services for complex parameter construction.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $parameter_name The exact name of the constructor parameter.
	 * @param callable $callback       Callable that returns the parameter value.
	 *                                 Receives container resolver as first argument.
	 *
	 * @return static Fluent interface for method chaining.
	 *
	 * @example
	 * ```php
	 * $definition = $container->autowire(EmailService::class)
	 *     ->constructor_parameter_callback('api_key', function($resolver) {
	 *         $config = $resolver(ConfigService::class);
	 *         return $config->get('email.api_key');
	 *     })
	 *     ->constructor_parameter_callback('timestamp', fn() => time());
	 * ```
	 */
	public function constructor_parameter_callback( string $parameter_name, callable $callback ): static {
		$this->parameter_callbacks[ $parameter_name ] = $callback;
		return $this;
	}

	/**
	 * Configure environment-specific parameter values.
	 *
	 * This method enables different parameter values based on the WordPress environment
	 * (development, production, staging, testing). Environment detection is automatic
	 * using WP_ENVIRONMENT_TYPE constant or WP_DEBUG fallback.
	 *
	 * @since 1.0.0
	 *
	 * @param string $environment The target environment name (development, production, staging, testing).
	 * @param array  $parameters  Associative array of parameter_name => value pairs for this environment.
	 *
	 * @return static Fluent interface for method chaining.
	 *
	 * @example
	 * ```php
	 * $definition = $container->autowire(DatabaseService::class)
	 *     ->environment_parameters('development', [
	 *         'host' => 'localhost',
	 *         'debug' => true,
	 *         'pool_size' => 5
	 *     ])
	 *     ->environment_parameters('production', [
	 *         'host' => 'prod-db.example.com',
	 *         'debug' => false,
	 *         'pool_size' => 20
	 *     ]);
	 * ```
	 */
	public function environment_parameters( string $environment, array $parameters ): static {
		$this->environment_parameters[ $environment ] = $parameters;
		return $this;
	}

	/**
	 * Set multiple static parameter overrides at once.
	 *
	 * Convenience method for setting multiple static parameter values without
	 * chaining multiple constructor_parameter() calls.
	 *
	 * @since 1.0.0
	 *
	 * @param array $parameters Associative array of parameter_name => value pairs.
	 *
	 * @return static Fluent interface for method chaining.
	 *
	 * @example
	 * ```php
	 * $definition = $container->autowire(LoggerService::class)
	 *     ->constructor_parameters([
	 *         'log_level' => 'debug',
	 *         'log_file' => '/var/log/app.log',
	 *         'max_size' => 1024 * 1024
	 *     ]);
	 * ```
	 */
	public function constructor_parameters( array $parameters ): static {
		$this->parameter_overrides = array_merge( $this->parameter_overrides, $parameters );
		return $this;
	}

	/**
	 * Get all configured parameter overrides.
	 *
	 * Returns an array of all currently configured static parameter overrides.
	 * Useful for debugging or introspection purposes.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Array of parameter_name => value pairs.
	 */
	public function get_parameter_overrides(): array {
		return $this->parameter_overrides;
	}

	/**
	 * Resolve constructor dependencies using various injection strategies.
	 *
	 * @since 1.0.0
	 *
	 * @param array    $parameters Array of ReflectionParameter objects.
	 * @param callable $resolver   Function to resolve dependencies.
	 *
	 * @return array<mixed> Array of resolved dependencies.
	 * @throws ContainerException If a required parameter cannot be resolved.
	 */
	private function resolve_constructor_dependencies( array $parameters, callable $resolver ): array {
		$dependencies = [];

		foreach ( $parameters as $parameter ) {
			$dependency = $this->resolve_single_parameter( $parameter, $resolver );
			$dependencies[] = $dependency;
		}

		return $dependencies;
	}

	/**
	 * Resolve a single constructor parameter using multiple injection strategies.
	 *
	 * @since 1.0.0
	 *
	 * @param ReflectionParameter $parameter The parameter to resolve.
	 * @param callable            $resolver  Function to resolve dependencies.
	 *
	 * @return mixed The resolved parameter value.
	 * @throws ContainerException If the parameter cannot be resolved.
	 */
	private function resolve_single_parameter( ReflectionParameter $parameter, callable $resolver ) {
		$param_name = $parameter->getName();

		// Strategy 1: Environment-specific parameter override
		$environment_value = $this->resolve_environment_parameter( $param_name );
		if ( $environment_value !== null ) {
			return $environment_value;
		}

		// Strategy 2: Dynamic parameter callback
		if ( isset( $this->parameter_callbacks[ $param_name ] ) ) {
			return $this->parameter_callbacks[ $param_name ]( $resolver );
		}

		// Strategy 3: Static parameter override
		if ( isset( $this->parameter_overrides[ $param_name ] ) ) {
			return $this->parameter_overrides[ $param_name ];
		}

		// Strategy 4: Type-based dependency injection
		$type = $parameter->getType();
		if ( $type && ! $this->is_builtin_type( $type ) ) {
			$dependency_class = $this->get_type_name( $type );
			return $resolver( $dependency_class );
		}

		// Strategy 5: Default parameter value
		if ( $parameter->isDefaultValueAvailable() ) {
			return $parameter->getDefaultValue();
		}

		// Strategy 6: Fail with a comprehensive error message
		$error_context = $this->build_parameter_error_context( $parameter );
		throw new ContainerException(
			esc_html( "Cannot resolve parameter [{$param_name}] for class [$this->class_name]. " ) .
			esc_html( "No type hint, default value, or explicit binding provided. $error_context" )
		);
	}

	/**
	 * Resolve environment-specific parameter value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $param_name The parameter name.
	 *
	 * @return mixed|null The environment-specific value or null if not found.
	 */
	private function resolve_environment_parameter( string $param_name ) {
		$current_env = $this->detect_current_environment();

		// Check for current environment configuration
		if ( isset( $this->environment_parameters[ $current_env ][ $param_name ] ) ) {
			return $this->environment_parameters[ $current_env ][ $param_name ];
		}

		return null;
	}

	/**
	 * Detect the current WordPress environment.
	 *
	 * @since 1.0.0
	 *
	 * @return string The detected environment (development, staging, production).
	 */
	private function detect_current_environment(): string {
		// Check for explicit environment type (WordPress 5.5+)
		if ( defined( 'WP_ENVIRONMENT_TYPE' ) ) {
			return constant( 'WP_ENVIRONMENT_TYPE' );
		}

		// Check for debug mode as fallback
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return 'development';
		}

		// Default to production
		return 'production';
	}

	/**
	 * Build detailed error context for parameter resolution failures.
	 *
	 * Creates a helpful error message with suggestions for resolving
	 * parameter injection issues.
	 *
	 * @since 1.0.0
	 *
	 * @param ReflectionParameter $parameter The failed parameter.
	 *
	 * @return string Formatted error context with suggestions.
	 */
	private function build_parameter_error_context( ReflectionParameter $parameter ): string {
		$suggestions = [];
		$param_name = $parameter->getName();

		// Suggest type hinting if missing
		if ( ! $parameter->getType() ) {
			$suggestions[] = "Add a type hint to parameter \${$param_name}";
		}

		// Suggest explicit parameter binding
		$suggestions[] = "Use ->constructor_parameter('{$param_name}', \$value)";

		// Suggest callback binding for dynamic values
		$suggestions[] = "Use ->constructor_parameter_callback('{$param_name}', \$callback)";

		// Suggest default value
		$suggestions[] = 'Add a default value to the parameter in the constructor';

		return 'Suggestions: ' . implode( ', ', $suggestions ) . '.';
	}

	/**
	 * Check if a reflection type is a builtin type (PHP 8.1 compatible).
	 *
	 * @since 1.0.0
	 *
	 * @param ReflectionType $type The reflection type to check.
	 * @return bool True if builtin type, false otherwise.
	 */
	private function is_builtin_type( ReflectionType $type ): bool {
		if ( method_exists( $type, 'isBuiltin' ) ) {
			return $type->isBuiltin();
		}

		// Fallback for older PHP versions
		if ( $type instanceof ReflectionNamedType ) {
			$type_name = $type->getName();
			return in_array(
				$type_name,
				[
					'string',
					'int',
					'float',
					'bool',
					'array',
					'object',
					'callable',
					'resource',
					'null',
					'mixed',
				],
				true
			);
		}

		return false;
	}

	/**
	 * Get the name of a reflection type (PHP 8.1 compatible).
	 *
	 * @since 1.0.0
	 *
	 * @param ReflectionType $type The reflection type.
	 * @return string The type name.
	 */
	private function get_type_name( ReflectionType $type ): string {
		if ( method_exists( $type, 'getName' ) ) {
			return $type->getName();
		}

		// Fallback for older PHP versions
		if ( $type instanceof ReflectionNamedType ) {
			return $type->getName();
		}

		return 'mixed';
	}
}
