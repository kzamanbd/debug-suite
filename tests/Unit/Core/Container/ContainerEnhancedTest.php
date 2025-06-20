<?php
/**
 * Enhanced container features test.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Tests\Unit\Core\Container;

use DebugSuite\Core\Container\Container;
use DebugSuite\Core\Container\Exceptions\ContainerException;
use DebugSuite\Core\Container\Exceptions\NotFoundException;
use DebugSuite\Tests\Helpers\TestCase;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Test enhanced container features.
 *
 * @covers \DebugSuite\Core\Container\Container
 * @group container
 * @group enhanced-features
 * @since DEBUG_SUITE_SINCE
 */
class ContainerEnhancedTest extends TestCase {

	/**
	 * Container instance for testing.
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * Set up test case.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->container = Container::get_instance();
		$this->container->clear_performance_data();
	}

	/**
	 * Test circular dependency detection.
	 *
	 * @return void
	 */
	public function test_circular_dependency_detection(): void {
		// Create classes with circular dependencies
		$this->container->bind( 'ServiceA', function ( $container ) {
			return new class( $container->get( 'ServiceB' ) ) {
				public function __construct( $dependency ) {}
			};
		} );

		$this->container->bind( 'ServiceB', function ( $container ) {
			return new class( $container->get( 'ServiceA' ) ) {
				public function __construct( $dependency ) {}
			};
		} );

		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( 'Circular dependency detected' );

		$this->container->get( 'ServiceA' );
	}

	/**
	 * Test interface binding functionality.
	 *
	 * @return void
	 */
	public function test_interface_binding(): void {
		// Define test interface and implementation
		if ( ! interface_exists( 'TestLoggerInterface' ) ) {
			eval( '
			interface TestLoggerInterface {
				public function log(string $message): void;
			}
			' );
		}

		if ( ! class_exists( 'TestFileLogger' ) ) {
			eval( '
			class TestFileLogger implements TestLoggerInterface {
				public function log(string $message): void {
					// Implementation
				}
			}
			' );
		}

		// Bind interface to implementation
		$this->container->bind_interface( 'TestLoggerInterface', 'TestFileLogger' );

		// Should resolve interface to implementation
		$logger = $this->container->get( 'TestLoggerInterface' );
		$this->assertInstanceOf( 'TestFileLogger', $logger );
	}

	/**
	 * Test service aliasing functionality.
	 *
	 * @return void
	 */
	public function test_service_aliasing(): void {
		$this->container->bind( 'original_service', function () {
			return new \stdClass();
		}, true ); // Make it a singleton

		$this->container->alias( 'alias_service', 'original_service' );

		$original = $this->container->get( 'original_service' );
		$aliased  = $this->container->get( 'alias_service' );

		$this->assertSame( $original, $aliased );
	}

	/**
	 * Test circular alias detection.
	 *
	 * @return void
	 */
	public function test_circular_alias_detection(): void {
		$this->container->alias( 'alias1', 'alias2' );
		$this->container->alias( 'alias2', 'alias1' );

		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( 'Circular alias reference detected' );

		$this->container->get( 'alias1' );
	}

	/**
	 * Test container compilation functionality.
	 *
	 * @return void
	 */
	public function test_container_compilation(): void {
		$this->assertFalse( $this->container->is_compiled() );

		// Add some services
		$this->container->bind( 'test_service', function () {
			return new \stdClass();
		} );

		// Compile container
		$this->container->compile();
		$this->assertTrue( $this->container->is_compiled() );

		// Should not allow modifications after compilation
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( 'Cannot modify compiled container' );

		$this->container->bind( 'another_service', function () {
			return new \stdClass();
		} );
	}

	/**
	 * Test compilation prevents definition setting.
	 *
	 * @return void
	 */
	public function test_compilation_prevents_definition_setting(): void {
		$this->container->compile();

		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( 'Cannot modify compiled container' );

		$this->container->set( 'test', $this->container->factory( fn() => new \stdClass() ) );
	}

	/**
	 * Test compilation prevents interface binding.
	 *
	 * @return void
	 */
	public function test_compilation_prevents_interface_binding(): void {
		$this->container->compile();

		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( 'Cannot modify compiled container' );

		$this->container->bind_interface( 'TestInterface', 'TestImplementation' );
	}

	/**
	 * Test compilation prevents aliasing.
	 *
	 * @return void
	 */
	public function test_compilation_prevents_aliasing(): void {
		$this->container->compile();

		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( 'Cannot modify compiled container' );

		$this->container->alias( 'test_alias', 'test_service' );
	}

	/**
	 * Test debug mode functionality.
	 *
	 * @return void
	 */
	public function test_debug_mode(): void {
		$this->assertFalse( $this->container->is_debug_mode() );

		$this->container->set_debug_mode( true );
		$this->assertTrue( $this->container->is_debug_mode() );

		$this->container->set_debug_mode( false );
		$this->assertFalse( $this->container->is_debug_mode() );
	}

	/**
	 * Test performance statistics collection.
	 *
	 * @return void
	 */
	public function test_performance_statistics(): void {
		$this->container->bind( 'test_service', function () {
			return new \stdClass();
		} );

		// Resolve service multiple times
		$this->container->get( 'test_service' );
		$this->container->get( 'test_service' );

		$stats = $this->container->get_performance_stats();

		$this->assertIsArray( $stats );
		$this->assertArrayHasKey( 'total_resolutions', $stats );
		$this->assertArrayHasKey( 'total_time', $stats );
		$this->assertArrayHasKey( 'average_time', $stats );
		$this->assertArrayHasKey( 'cache_hits', $stats );
		$this->assertArrayHasKey( 'services_resolved', $stats );
		$this->assertArrayHasKey( 'detailed_stats', $stats );

		$this->assertGreaterThan( 0, $stats['total_resolutions'] );
		$this->assertGreaterThanOrEqual( 0, $stats['total_time'] );
	}

	/**
	 * Test performance statistics clearing.
	 *
	 * @return void
	 */
	public function test_performance_statistics_clearing(): void {
		$this->container->bind( 'test_service', function () {
			return new \stdClass();
		} );

		$this->container->get( 'test_service' );

		$stats_before = $this->container->get_performance_stats();
		$this->assertGreaterThan( 0, $stats_before['total_resolutions'] );

		$this->container->clear_performance_data();

		$stats_after = $this->container->get_performance_stats();
		$this->assertEquals( 0, $stats_after['total_resolutions'] );
		$this->assertEquals( 0, $stats_after['total_time'] );
	}

	/**
	 * Test enhanced error messages.
	 *
	 * @return void
	 */
	public function test_enhanced_error_messages(): void {
		$this->expectException( NotFoundException::class );
		$this->expectExceptionMessage( 'Service [nonexistent_service] not found in container' );

		$this->container->get( 'nonexistent_service' );
	}

	/**
	 * Test reflection caching.
	 *
	 * @return void
	 */
	public function test_reflection_caching(): void {
		// Create a test class
		if ( ! class_exists( 'TestServiceForReflection' ) ) {
			eval( '
			class TestServiceForReflection {
				public function __construct() {}
			}
			' );
		}

		// Use autowiring instead of bind to avoid circular dependency
		$this->container->set( 'TestServiceForReflection', $this->container->autowire( 'TestServiceForReflection' ) );

		// First resolution should cache reflection
		$service1 = $this->container->get( 'TestServiceForReflection' );
		$this->assertInstanceOf( 'TestServiceForReflection', $service1 );
		
		// Second resolution should use cached reflection
		$service2 = $this->container->get( 'TestServiceForReflection' );
		$this->assertInstanceOf( 'TestServiceForReflection', $service2 );

		$stats = $this->container->get_performance_stats();
		$this->assertGreaterThan( 0, $stats['total_resolutions'] );
	}

	/**
	 * Test autowiring with constructor parameters.
	 *
	 * @return void
	 */
	public function test_autowiring_with_constructor_parameters(): void {
		// Create test classes
		if ( ! class_exists( 'TestDependency' ) ) {
			eval( '
			class TestDependency {
				public function getValue(): string {
					return "dependency_value";
				}
			}
			' );
		}

		if ( ! class_exists( 'TestServiceWithDependency' ) ) {
			eval( '
			class TestServiceWithDependency {
				private $dependency;
				
				public function __construct(TestDependency $dependency) {
					$this->dependency = $dependency;
				}
				
				public function getDependency(): TestDependency {
					return $this->dependency;
				}
			}
			' );
		}

		$service = $this->container->get( 'TestServiceWithDependency' );

		$this->assertInstanceOf( 'TestServiceWithDependency', $service );
		$this->assertInstanceOf( 'TestDependency', $service->getDependency() );
		$this->assertEquals( 'dependency_value', $service->getDependency()->getValue() );
	}

	/**
	 * Test multiple recompilation error.
	 *
	 * @return void
	 */
	public function test_multiple_compilation_error(): void {
		$this->container->compile();

		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( 'Container is already compiled' );

		$this->container->compile();
	}

	/**
	 * Test interface binding validation.
	 *
	 * @return void
	 */
	public function test_interface_binding_validation(): void {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( 'Interface NonExistentInterface does not exist' );

		$this->container->bind_interface( 'NonExistentInterface', 'SomeClass' );
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		// Clear container state for next test
		$this->container->clear_performance_data();
		parent::tear_down();
	}
}
