<?php
/**
 * Unit tests for AutowiredDefinition class.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Tests\Unit\Core\Container\Definitions;

use DebugSuite\Core\Container\Definitions\AutowiredDefinition;
use DebugSuite\Core\Container\Exceptions\ContainerException;
use DebugSuite\Tests\Helpers\TestCase;

/**
 * Test cases for AutowiredDefinition functionality.
 *
 * @covers \DebugSuite\Core\Container\Definitions\AutowiredDefinition
 * @group definitions
 * @group autowiring
 */
class AutowiredDefinitionTest extends TestCase {

	/**
	 * Test basic autowired definition creation.
	 */
	public function test_creates_basic_autowired_definition(): void {
		$definition = new AutowiredDefinition( SimpleClass::class );

		$this->assertEquals( SimpleClass::class, $definition->get_class_name() );
		$this->assertFalse( $definition->is_singleton() );
		$this->assertEquals( '', $definition->get_name() );
	}

	/**
	 * Test singleton autowired definition creation.
	 */
	public function test_creates_singleton_autowired_definition(): void {
		$definition = new AutowiredDefinition( SimpleClass::class, true );

		$this->assertEquals( SimpleClass::class, $definition->get_class_name() );
		$this->assertTrue( $definition->is_singleton() );
	}

	/**
	 * Test setting definition name.
	 */
	public function test_sets_definition_name(): void {
		$definition = new AutowiredDefinition( SimpleClass::class );
		$result = $definition->set_name( 'test.service' );

		$this->assertSame( $definition, $result );
		$this->assertEquals( 'test.service', $definition->get_name() );
	}

	/**
	 * Test resolving class without constructor.
	 */
	public function test_resolves_class_without_constructor(): void {
		$definition = new AutowiredDefinition( SimpleClass::class );
		$resolver = fn( $class ) => new $class();

		$instance = $definition->resolve( $resolver );

		$this->assertInstanceOf( SimpleClass::class, $instance );
	}

	/**
	 * Test resolving class with constructor but no parameters.
	 */
	public function test_resolves_class_with_empty_constructor(): void {
		$definition = new AutowiredDefinition( EmptyConstructorClass::class );
		$resolver = fn( $class ) => new $class();

		$instance = $definition->resolve( $resolver );

		$this->assertInstanceOf( EmptyConstructorClass::class, $instance );
	}

	/**
	 * Test resolving class with dependency injection.
	 */
	public function test_resolves_class_with_dependency_injection(): void {
		$definition = new AutowiredDefinition( ClassWithDependency::class );
		$resolver = function( $class ) {
			if ( $class === SimpleClass::class ) {
				return new SimpleClass();
			}
			return new $class();
		};

		$instance = $definition->resolve( $resolver );

		$this->assertInstanceOf( ClassWithDependency::class, $instance );
		$this->assertInstanceOf( SimpleClass::class, $instance->dependency );
	}

	/**
	 * Test static parameter override.
	 */
	public function test_constructor_parameter_override(): void {
		$definition = new AutowiredDefinition( ClassWithStaticParameter::class );
		$definition->constructor_parameter( 'value', 'overridden' );
		
		$resolver = fn( $class ) => new $class();
		$instance = $definition->resolve( $resolver );
		
		$this->assertInstanceOf( ClassWithStaticParameter::class, $instance );
		$this->assertEquals( 'overridden', $instance->value );
	}

	/**
	 * Test multiple static parameters override at once.
	 */
	public function test_constructor_parameters_bulk_override(): void {
		$definition = new AutowiredDefinition( ClassWithMultipleParameters::class );
		$definition->constructor_parameters([
			'param1' => 'value1',
			'param2' => 'value2',
			'param3' => 'value3'
		]);
		
		$resolver = fn( $class ) => new $class();
		$instance = $definition->resolve( $resolver );
		
		$this->assertInstanceOf( ClassWithMultipleParameters::class, $instance );
		$this->assertEquals( 'value1', $instance->param1 );
		$this->assertEquals( 'value2', $instance->param2 );
		$this->assertEquals( 'value3', $instance->param3 );
	}

	/**
	 * Test getting parameter overrides.
	 */
	public function test_get_parameter_overrides(): void {
		$definition = new AutowiredDefinition( SimpleClass::class );
		$parameters = ['param1' => 'value1', 'param2' => 'value2'];
		
		$definition->constructor_parameters( $parameters );
		
		$this->assertEquals( $parameters, $definition->get_parameter_overrides() );
	}
	public function test_static_parameter_override(): void {
		$definition = new AutowiredDefinition( ClassWithStringParameter::class );
		$definition->constructor_parameter( 'value', 'custom_value' );

		$resolver = fn( $class ) => new $class();
		$instance = $definition->resolve( $resolver );

		$this->assertInstanceOf( ClassWithStringParameter::class, $instance );
		$this->assertEquals( 'custom_value', $instance->value );
	}

	/**
	 * Test callback parameter injection.
	 */
	public function test_callback_parameter_injection(): void {
		$definition = new AutowiredDefinition( ClassWithStringParameter::class );
		$definition->constructor_parameter_callback( 'value', fn() => 'callback_value' );

		$resolver = fn( $class ) => new $class();
		$instance = $definition->resolve( $resolver );

		$this->assertInstanceOf( ClassWithStringParameter::class, $instance );
		$this->assertEquals( 'callback_value', $instance->value );
	}

	/**
	 * Test callback parameter injection with resolver access.
	 */
	public function test_callback_parameter_injection_with_resolver(): void {
		$definition = new AutowiredDefinition( ClassWithStringParameter::class );
		$definition->constructor_parameter_callback( 'value', function( $resolver ) {
			$dependency = $resolver( SimpleClass::class );
			return get_class( $dependency );
		});

		$resolver = fn( $class ) => new $class();
		$instance = $definition->resolve( $resolver );

		$this->assertEquals( SimpleClass::class, $instance->value );
	}

	/**
	 * Test environment-specific parameter injection.
	 */
	public function test_environment_specific_parameter_injection(): void {
		$definition = new AutowiredDefinition( ClassWithStringParameter::class );
		$definition->environment_parameters( 'development', [ 'value' => 'dev_value' ] );
		$definition->environment_parameters( 'production', [ 'value' => 'prod_value' ] );

		// Mock WP_DEBUG to simulate development environment
		if ( ! defined( 'WP_DEBUG' ) ) {
			define( 'WP_DEBUG', true );
		}

		$resolver = fn( $class ) => new $class();
		$instance = $definition->resolve( $resolver );

		$this->assertEquals( 'dev_value', $instance->value );
	}

	/**
	 * Test default parameter value fallback.
	 */
	public function test_default_parameter_value_fallback(): void {
		$definition = new AutowiredDefinition( ClassWithDefaultParameter::class );
		$resolver = fn( $class ) => new $class();

		$instance = $definition->resolve( $resolver );

		$this->assertEquals( 'default_value', $instance->value );
	}

	/**
	 * Test parameter resolution priority order.
	 */
	public function test_parameter_resolution_priority(): void {
		$definition = new AutowiredDefinition( ClassWithStringParameter::class );
		
		// Set all types of parameter injection
		$definition->environment_parameters( 'development', [ 'value' => 'env_value' ] );
		$definition->constructor_parameter_callback( 'value', fn() => 'callback_value' );
		$definition->constructor_parameter( 'value', 'static_value' );

		$resolver = fn( $class ) => new $class();
		$instance = $definition->resolve( $resolver );

		// Environment parameters should take highest priority
		$this->assertEquals( 'env_value', $instance->value );
	}

	/**
	 * Test parameter resolution priority without environment.
	 */
	public function test_parameter_resolution_priority_without_environment(): void {
		$definition = new AutowiredDefinition( ClassWithStringParameter::class );
		
		// Set callback and static parameters (no environment)
		$definition->constructor_parameter_callback( 'value', fn() => 'callback_value' );
		$definition->constructor_parameter( 'value', 'static_value' );

		$resolver = fn( $class ) => new $class();
		$instance = $definition->resolve( $resolver );

		// Callback should take priority over static
		$this->assertEquals( 'callback_value', $instance->value );
	}

	/**
	 * Test error handling for unresolvable parameters.
	 */
	public function test_throws_exception_for_unresolvable_parameter(): void {
		$definition = new AutowiredDefinition( ClassWithUnresolvableParameter::class );
		$resolver = fn( $class ) => new $class();

		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( 'Cannot resolve parameter [value]' );

		$definition->resolve( $resolver );
	}

	/**
	 * Test enhanced error message includes helpful suggestions.
	 */
	public function test_error_message_includes_suggestions(): void {
		$definition = new AutowiredDefinition( ClassWithUnresolvableParameter::class );
		$resolver = fn( $class ) => new $class();

		try {
			$definition->resolve( $resolver );
			$this->fail( 'Expected ContainerException was not thrown' );
		} catch ( ContainerException $e ) {
			$message = $e->getMessage();
			$this->assertStringContainsString( 'Suggestions:', $message );
			$this->assertStringContainsString( 'constructor_parameter', $message );
			$this->assertStringContainsString( 'constructor_parameter_callback', $message );
		}
	}

	/**
	 * Test error handling for non-existent class.
	 */
	public function test_throws_exception_for_non_existent_class(): void {
		$definition = new AutowiredDefinition( 'NonExistentClass' );
		$resolver = fn( $class ) => new $class();

		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( 'Cannot autowire class [NonExistentClass]' );

		$definition->resolve( $resolver );
	}

	/**
	 * Test fluent interface for parameter methods.
	 */
	public function test_fluent_interface(): void {
		$definition = new AutowiredDefinition( ClassWithStringParameter::class );

		$result = $definition
			->constructor_parameter( 'value', 'test' )
			->constructor_parameter_callback( 'other', fn() => 'test' )
			->environment_parameters( 'test', [] );

		$this->assertSame( $definition, $result );
	}
}

// Test helper classes

/**
 * Simple class without constructor for testing.
 */
class SimpleClass {
}

/**
 * Class with empty constructor for testing.
 */
class EmptyConstructorClass {
	public function __construct() {
	}
}

/**
 * Class with dependency for testing dependency injection.
 */
class ClassWithDependency {
	public SimpleClass $dependency;

	public function __construct( SimpleClass $dependency ) {
		$this->dependency = $dependency;
	}
}

/**
 * Class with string parameter for testing parameter injection.
 */
class ClassWithStringParameter {
	public string $value;

	public function __construct( string $value ) {
		$this->value = $value;
	}
}

/**
 * Class with default parameter for testing default values.
 */
class ClassWithDefaultParameter {
	public string $value;

	public function __construct( string $value = 'default_value' ) {
		$this->value = $value;
	}
}

/**
 * Class with unresolvable parameter for testing error handling.
 */
class ClassWithUnresolvableParameter {
	public function __construct( string $value ) {
		// No default value, no type hint that can be resolved
	}
}

/**
 * Class with static parameter for testing parameter override.
 */
class ClassWithStaticParameter {
	public string $value;

	public function __construct( string $value ) {
		$this->value = $value;
	}
}

/**
 * Class with multiple parameters for testing bulk parameter override.
 */
class ClassWithMultipleParameters {
	public string $param1;
	public string $param2;
	public string $param3;

	public function __construct( string $param1, string $param2, string $param3 ) {
		$this->param1 = $param1;
		$this->param2 = $param2;
		$this->param3 = $param3;
	}
}
