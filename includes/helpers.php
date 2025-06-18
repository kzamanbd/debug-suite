<?php

/**
 * Global helper functions for Debug Suite with PSR-11 DI Container integration.
 *
 * This file contains all helper functions for the Debug Suite plugin including
 * PSR-11 compliant dependency injection helpers, container utilities, PHP-DI
 * style definition functions, and general utility functions for seamless
 * integration with the enhanced DI Container system.
 *
 * @since      1.0.0
 *
 * @package    DebugSuite
 *
 * @author     Kamruzzaman <kzamanbn@gmail.com>
 */

use DebugSuite\Core\Container\Container;
use DebugSuite\Core\Container\ContainerBuilder;
use DebugSuite\Core\Container\Definitions\AutowiredDefinition;
use DebugSuite\Core\Container\Definitions\FactoryDefinition;
use DebugSuite\Core\Container\Definitions\ValueDefinition;
use DebugSuite\Core\Container\Definitions\ConfigDefinition;
use DebugSuite\Core\Container\Definitions\DecoratorDefinition;
use DebugSuite\Core\Container\ServiceManager;

if ( ! function_exists( 'debug_suite' ) ) {
	/**
	 * Get the main Debug Suite instance.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return DebugSuite The main plugin instance.
	 */
	function debug_suite(): DebugSuite {
		return DebugSuite::init();
	}
}

if ( ! function_exists( 'debug_suite_container' ) ) {
	/**
	 * Get the Debug Suite container instance.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return Container The DI Container instance.
	 */
	function debug_suite_container(): Container {
		return debug_suite()->get_container();
	}
}

if ( ! function_exists( 'debug_suite_resolve' ) ) {
	/**
	 * Resolve a service from the Debug Suite container.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $service Service name or class name.
	 *
	 * @throws Exception If the service cannot be resolved.
	 */
	function debug_suite_resolve( string $service ): mixed {
		return debug_suite_container()->resolve( $service );
	}
}

if ( ! function_exists( 'debug_suite_service_manager' ) ) {
	/**
	 * Get the Debug Suite service manager instance.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return ServiceManager The service manager instance.
	 */
	function debug_suite_service_manager(): ServiceManager {
		return debug_suite()->get_service_manager();
	}
}

if ( ! function_exists( 'debug_suite_autowire' ) ) {
	/**
	 * Create an autowired service definition.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $class_name The class name to autowire.
	 *
	 * @return AutowiredDefinition Autowired definition for the container.
	 */
	function debug_suite_autowire( string $class_name ): AutowiredDefinition {
		return new AutowiredDefinition( $class_name );
	}
}

if ( ! function_exists( 'debug_suite_factory' ) ) {
	/**
	 * Create a factory service definition.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param callable $factory The factory callable.
	 *
	 * @return FactoryDefinition Factory definition for the container.
	 */
	function debug_suite_factory( callable $factory ): FactoryDefinition {
		return new FactoryDefinition( $factory );
	}
}

if ( ! function_exists( 'debug_suite_singleton' ) ) {
	/**
	 * Create a singleton factory service definition.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param callable $factory The factory callable.
	 *
	 * @return FactoryDefinition Singleton factory definition for the container.
	 */
	function debug_suite_singleton( callable $factory ): FactoryDefinition {
		return new FactoryDefinition( $factory, true );
	}
}

if ( ! function_exists( 'debug_suite_value' ) ) {
	/**
	 * Create a value service definition.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param mixed $value The value to inject.
	 *
	 * @return ValueDefinition Value definition for the container.
	 */
	function debug_suite_value( mixed $value ): ValueDefinition {
		return new ValueDefinition( $value );
	}
}

if ( ! function_exists( 'debug_suite_object' ) ) {
	/**
	 * Create an object service definition (autowired singleton).
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $class_name The class name to autowire as singleton.
	 *
	 * @return AutowiredDefinition Autowired singleton definition for the container.
	 */
	function debug_suite_object( string $class_name ): AutowiredDefinition {
		return new AutowiredDefinition( $class_name, true );
	}
}

if ( ! function_exists( 'debug_suite_container_builder' ) ) {
	/**
	 * Create a new container builder instance.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return ContainerBuilder
	 */
	function debug_suite_container_builder(): ContainerBuilder {
		return new ContainerBuilder();
	}
}

if ( ! function_exists( 'debug_suite_date' ) ) {
	/**
	 * Format a timestamp into a localized date string.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $timestamp The timestamp to format.
	 *
	 * @return string Formatted date string.
	 */
	function debug_suite_date( string $timestamp ): string {
		// Get a date format from WP settings (Settings > General)
		$date_format = get_option( 'date_format' );
		// Convert the timestamp to formatted date
		return date_i18n( $date_format, $timestamp );
	}
}

if ( ! function_exists( 'debug_suite_config' ) ) {
	/**
	 * Create a configuration service definition.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param array      $configurations Environment-specific configurations.
	 * @param mixed|null $default_config Default configuration.
	 * @param bool       $singleton      Whether this is a singleton.
	 *
	 * @return ConfigDefinition Configuration definition for the container.
	 */
	function debug_suite_config( array $configurations = [], mixed $default_config = null, bool $singleton = false ): ConfigDefinition {
		return new ConfigDefinition( $configurations, $default_config, $singleton );
	}
}

if ( ! function_exists( 'debug_suite_tagged' ) ) {
	/**
	 * Get all services tagged with a specific tag.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $tag The tag to search for.
	 *
	 * @return array Array of resolved service instances.
	 */
	function debug_suite_tagged( string $tag ): array {
		return debug_suite_container()->tagged( $tag );
	}
}

if ( ! function_exists( 'debug_suite_decorate' ) ) {
	/**
	 * Create a decorator service definition.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $decorator_class   The decorator class name.
	 * @param string $decorated_service The service to decorate.
	 * @param bool   $singleton         Whether this is a singleton.
	 *
	 * @return DecoratorDefinition Decorator definition for the container.
	 */
	function debug_suite_decorate( string $decorator_class, string $decorated_service, bool $singleton = false ): DecoratorDefinition {
		return new DecoratorDefinition( $decorator_class, $decorated_service, $singleton );
	}
}

if ( ! function_exists( 'debug_suite_autowire_with_params' ) ) {
	/**
	 * Create an autowired definition with parameter overrides.
	 *
	 * Convenience function to quickly create an autowired definition with
	 * static parameter overrides in a single call.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $class_name  The class name to autowire.
	 * @param array  $parameters  Associative array of parameter_name => value pairs.
	 * @param bool   $singleton   Whether this should be a singleton.
	 *
	 * @return AutowiredDefinition Configured autowired definition.
	 *
	 * @example
	 * ```php
	 * $definition = debug_suite_autowire_with_params(LoggerService::class, [
	 *     'log_level' => 'debug',
	 *     'log_file' => '/var/log/app.log'
	 * ], true);
	 * ```
	 */
	function debug_suite_autowire_with_params( string $class_name, array $parameters = [], bool $singleton = false ): AutowiredDefinition {
		return ( new AutowiredDefinition( $class_name, $singleton ) )
			->constructor_parameters( $parameters );
	}
}

if ( ! function_exists( 'debug_suite_autowire_env' ) ) {
	/**
	 * Create an autowired definition with environment-specific parameters.
	 *
	 * Convenience function for creating environment-aware service definitions
	 * with different configurations for development, production, etc.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $class_name             The class name to autowire.
	 * @param array  $environment_parameters Multi-dimensional array with environment => parameters.
	 * @param bool   $singleton              Whether this should be a singleton.
	 *
	 * @return AutowiredDefinition Configured autowired definition.
	 *
	 * @example
	 * ```php
	 * $definition = debug_suite_autowire_env(DatabaseService::class, [
	 *     'development' => ['host' => 'localhost', 'debug' => true],
	 *     'production' => ['host' => 'prod-db.com', 'debug' => false]
	 * ], true);
	 * ```
	 */
	function debug_suite_autowire_env( string $class_name, array $environment_parameters = [], bool $singleton = false ): AutowiredDefinition {
		$definition = new AutowiredDefinition( $class_name, $singleton );

		foreach ( $environment_parameters as $environment => $parameters ) {
			$definition->environment_parameters( $environment, $parameters );
		}

		return $definition;
	}
}
