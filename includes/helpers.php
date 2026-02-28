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

use DebugSuite\Core\Container\ServiceManager;
use DebugSuite\Services\FeatureService;

if ( ! function_exists( 'debug_suite' ) ) {
	/**
	 * Get the main Debug Suite instance.
	 *
	 * @since 1.0.0
	 *
	 * @return DebugSuite The main plugin instance.
	 */
	function debug_suite(): DebugSuite {
		return DebugSuite::instance();
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

if ( ! function_exists( 'debug_suite_current_page' ) ) {
	/**
	 * Check if the current admin page is a Debug Suite page.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if it's a Debug Suite page, false otherwise.
	 */
	function debug_suite_current_page(): bool {
		$current_screen = get_current_screen();
		return $current_screen && strpos( $current_screen->id, 'debug-suite' ) !== false;
	}
}

if ( ! function_exists( 'debug_suite_is_feature_enabled' ) ) {
	/**
	 * Check whether a feature is enabled (from options, with defaults from FeatureService).
	 *
	 * Use this when deciding to load feature-specific code (e.g. service providers).
	 *
	 * @since 1.0.0
	 *
	 * @param string $feature_id Feature ID (e.g. 'email-log').
	 * @return bool True if the feature is enabled, false otherwise.
	 */
	function debug_suite_is_feature_enabled( string $feature_id ): bool {
		$saved = get_option( FeatureService::OPTION_NAME, [] );
		if ( ! is_array( $saved ) ) {
			$saved = [];
		}
		$defaults = FeatureService::get_default_features();
		return array_key_exists( $feature_id, $saved )
			? (bool) $saved[ $feature_id ]
			: ( isset( $defaults[ $feature_id ] ) ? $defaults[ $feature_id ] : false );
	}
}
