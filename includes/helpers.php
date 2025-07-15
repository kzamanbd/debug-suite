<?php

/**
 * Global helper functions for Debug Suite with DI Container integration.
 *
 * This file contains all helper functions for the Debug Suite plugin including
 * dependency injection helpers, container utilities, and general utility
 * functions for seamless integration with the container system.
 *
 * @since      1.0.0
 *
 * @package    DebugSuite
 *
 * @author     Kamruzzaman <kzamanbn@gmail.com>
 */

use DebugSuite\Core\Container\Container;
use DebugSuite\Core\Container\ServiceManager;

if ( ! function_exists( 'debug_suite' ) ) {
	/**
	 * Get the main Debug Suite instance.
	 *
	 * @since 1.0.0
	 *
	 * @return DebugSuite The main plugin instance.
	 */
	function debug_suite(): DebugSuite {
		return DebugSuite::init();
	}
}

if ( ! function_exists( 'debug_suite_service_manager' ) ) {
	/**
	 * Get the Debug Suite service manager instance.
	 *
	 * @since 1.0.0
	 *
	 * @return ServiceManager The service manager instance.
	 */
	function debug_suite_service_manager(): ServiceManager {
		return debug_suite()->get_service_manager();
	}
}

if ( ! function_exists( 'debug_suite_date' ) ) {
	/**
	 * Format a timestamp into a localized date string.
	 *
	 * @since 1.0.0
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
