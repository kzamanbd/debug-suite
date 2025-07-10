<?php
/**
 * Plugin deactivation functionality for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core;

/**
 * Plugin deactivation handler for Debug Suite.
 *
 * Handles plugin deactivation logic and cleanup procedures.
 *
 * @since 1.0.0
 */
class Deactivator {

	/**
	 * Plugin deactivation logic.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Clean up rewrite rules
		self::flush_rewrite_rules_on_deactivation();
	}

	/**
	 * Flush rewrite rules on plugin deactivation.
	 *
	 * @return void
	 */
	public static function flush_rewrite_rules_on_deactivation(): void {
		flush_rewrite_rules();
	}
}
