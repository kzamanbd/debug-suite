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
	 * @return mixed The resolved service instance.
	 * @throws Exception If the service cannot be resolved.
	 */
	function debug_suite_resolve( string $service ) {
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
	 * @return mixed Autowired definition for the container.
	 */
	function debug_suite_autowire( string $class_name ) {
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
	 * @return mixed Factory definition for the container.
	 */
	function debug_suite_factory( callable $factory ) {
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
	 * @return mixed Singleton factory definition for the container.
	 */
	function debug_suite_singleton( callable $factory ) {
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
	 * @return mixed Value definition for the container.
	 */
	function debug_suite_value( $value ) {
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
	 * @return mixed Autowired singleton definition for the container.
	 */
	function debug_suite_object( string $class_name ) {
		return new AutowiredDefinition( $class_name, true );
	}
}

if ( ! function_exists( 'debug_suite_container_builder' ) ) {
	/**
	 * Create a new container builder instance.
	 *
	 * @return ContainerBuilder
	 *@since DEBUG_SUITE_SINCE
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
	function debug_suite_date( $timestamp ): string {
		// Get a date format from WP settings (Settings > General)
		$date_format = get_option( 'date_format' );
		// Convert the timestamp to formatted date
		return date_i18n( $date_format, $timestamp );
	}
}
