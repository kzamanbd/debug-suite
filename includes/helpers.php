<?php
/**
 * Global helper functions for the Debug Suite plugin.
 *
 * @since      1.0.0
 *
 * @package    DebugSuite
 *
 * @author     Kamruzzaman <kzamanbn@gmail.com>
 */

use DebugSuite\Core\Container;
use DebugSuite\Core\ServiceManager;

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
	 * @return Container
	 */
	function debug_suite_container(): Container {
		return debug_suite()->get_container();
	}
}

if ( ! function_exists( 'debug_suite_resolve' ) ) {
	/**
	 * Resolve a service from the Debug Suite container.
	 *
	 * @param string $service Service name or class name.
	 *
	 * @return mixed
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
	 * @return ServiceManager
	 */
	function debug_suite_service_manager(): ServiceManager {
		return debug_suite()->get_service_manager();
	}
}

if ( ! function_exists( 'debug_suite_date' ) ) {
	/**
	 * Register a service with the Debug Suite service manager.
	 *
	 * @param string $timestamp
	 *
	 * @return string
	 */
	function debug_suite_date( $timestamp ): string {
		// Get a date format from WP settings (Settings > General)
		$date_format = get_option( 'date_format' );
		// Convert the timestamp to formatted date
		return date_i18n( $date_format, $timestamp );
	}
}
