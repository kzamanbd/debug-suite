<?php
/**
 * Abstract Upgrader.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Upgrades;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract base class for version upgrades.
 *
 * @since 1.1.2
 */
abstract class AbstractUpgrader {

	/**
	 * Run the upgrade routine.
	 *
	 * @return void
	 */
	abstract public function run(): void;
}
