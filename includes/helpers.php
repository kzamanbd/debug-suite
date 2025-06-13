<?php
/**
 * Global helper functions for the Debug Suite plugin.
 *
 * @since      1.0.0
 * @package    DebugSuite
 * @author     Kamruzzaman <kzamanbn@gmail.com>
 */

if ( ! function_exists( 'debug_suite' ) ) {
	/**
	 * Get the main Debug Suite instance.
	 *
	 * @return DebugSuite
	 */
	function debug_suite(): DebugSuite {
		return DebugSuite::init();
	}
}

if ( ! function_exists( 'debug_suite_container' ) ) {
	/**
	 * Get the Debug Suite container instance.
	 *
	 * @return \DebugSuite\Core\Container
	 */
	function debug_suite_container(): \DebugSuite\Core\Container {
		return debug_suite()->get_container();
	}
}

if ( ! function_exists( 'debug_suite_resolve' ) ) {
	/**
	 * Resolve a service from the Debug Suite container.
	 *
	 * @param string $service Service name or class name.
	 * @return mixed
	 */
	function debug_suite_resolve( string $service ) {
		return debug_suite_container()->resolve( $service );
	}
}

if ( ! function_exists( 'debug_suite_service_manager' ) ) {
	/**
	 * Get the Debug Suite service manager instance.
	 *
	 * @return \DebugSuite\Core\ServiceManager
	 */
	function debug_suite_service_manager(): \DebugSuite\Core\ServiceManager {
		return debug_suite()->get_service_manager();
	}
}
