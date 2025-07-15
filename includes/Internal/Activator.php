<?php
/**
 * Plugin activation functionality for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Internal;

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

		// Flush rewrite rules for frontend routing
		self::flush_rewrite_rules_on_activation();
	}

	/**
	 * Flush rewrite rules on plugin activation.
	 *
	 * @return void
	 */
	public static function flush_rewrite_rules_on_activation(): void {
		// Add rewrite rules for frontend routing
		add_rewrite_rule( '^debug-suite/?$', 'index.php?debug_suite_page=main', 'top' );
		add_rewrite_rule( '^debug-suite/([^/]+)/?$', 'index.php?debug_suite_page=$matches[1]', 'top' );

		// Flush rewrite rules
		flush_rewrite_rules();
	}
}
