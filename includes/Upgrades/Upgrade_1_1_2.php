<?php
/**
 * Upgrade Routine for 1.1.2
 *
 * @package DebugSuite\Upgrades
 */

namespace DebugSuite\Upgrades;

use DebugSuite\Core\Activator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 1.1.2 upgrade class.
 * Resolves previous user notifications and forced updates by refreshing swagger rewrites silently.
 *
 * @since 1.1.2
 */
class Upgrade_1_1_2 extends AbstractUpgrader { // phpcs:ignore

	/**
	 * Run the upgrade routine.
	 *
	 * @return void
	 */
	public function run(): void {
		// Refresh rewrite rules automatically for this upgrade.
		Activator::refresh_swagger_rewrite_rules();
	}
}
