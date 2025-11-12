<?php
/**
 * Plugin activation functionality for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core;

use DebugSuite\Install;

/**
 * Plugin activation handler for Debug Suite.
 *
 * Handles plugin activation logic and setup procedures.
 *
 * @since 1.0.0
 */
class Activator {

	/**
	 * Activate the plugin.
	 *
	 * @return void
	 */
	public static function activate(): void {
		// Set default options
		if ( ! get_option( 'debug_suite_version' ) ) {
			update_option( 'debug_suite_version', DEBUG_SUITE_VERSION );
		}

		// Set onboarding flag
		update_option( 'debug_suite_needs_onboarding', true );

		// Add activation redirect transient
		set_transient( 'debug_suite_activation_redirect', true, 30 );

		// Install database tables
		Install::do_install();
	}
}
