<?php
/**
 * Autowired definition for dependency injection.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core\DI\Definitions;

use ReflectionClass;
use ReflectionException;
use DebugSuite\Core\DI\Exceptions\ContainerException;

/**
 * Autowired definition for dependency injection.
 *
 * Represents a class that should be autowired using reflection.
 * Follows PHP-DI's autowiring pattern for automatic dependency resolution.
 *
 * @since DEBUG_SUITE_SINCE
 */
class AutowiredDefinition implements DefinitionInterface {

	/**
	 * The definition name.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * The class name to autowire.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var string
	 */
	private string $class_name;

	/**
	 * Whether this is a singleton.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var bool
	 */
	private bool $singleton;

	/**
	 * Constructor parameter overrides.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var array<string, mixed>
	 */
	private array $constructor_parameters = [];

	/**
	 * Constructor.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $class_name The class name to autowire.
	 * @param bool   $singleton  Whether this is a singleton.
	 */
	public function __construct( string $class_name, bool $singleton = false ) {
		$this->class_name = $class_name;
		$this->singleton  = $singleton;
		$this->name       = '';
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
		try {
			$reflection = new ReflectionClass( $this->class_name );

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
				$param_name = $parameter->getName();

				// Check for parameter override
				if ( isset( $this->constructor_parameters[ $param_name ] ) ) {
					$dependencies[] = $this->constructor_parameters[ $param_name ];
					continue;
				}

				$type = $parameter->getType();
				if ( $type && ! $type->isBuiltin() ) {
					// @phpstan-ignore-next-line
					$dependency_class = $type->getName();
					$dependencies[]   = $resolver( $dependency_class );
				} elseif ( $parameter->isDefaultValueAvailable() ) {
					$dependencies[] = $parameter->getDefaultValue();
				} else {
					throw new ContainerException( "Cannot resolve parameter [{$param_name}] for class [{$this->class_name}]." );
				}
			}

			return $reflection->newInstanceArgs( $dependencies );
		} catch ( ReflectionException $e ) {
			throw new ContainerException( "Cannot autowire class [{$this->class_name}]: " . $e->getMessage(), 0, $e );
		}
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
	 * Set constructor parameter value.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $parameter_name The parameter name.
	 * @param mixed  $value          The parameter value.
	 *
	 * @return static
	 */
	public function constructor_parameter( string $parameter_name, $value ): static {
		$this->constructor_parameters[ $parameter_name ] = $value;
		return $this;
	}

	/**
	 * Get the class name.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return string
	 */
	public function get_class_name(): string {
		return $this->class_name;
	}
}
