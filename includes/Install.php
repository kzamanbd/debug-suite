<?php
/**
 * Database installation and schema management for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite;

use DebugSuite\Core\DatabaseManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Database installation and schema management class.
 *
 * Handles database table creation, updates, and schema management
 * for the Debug Suite plugin.
 *
 * @since 1.0.0
 */
class Install {

	/**
	 * Install database tables and setup initial schema.
	 *
	 * @return void
	 */
	public static function do_install(): void {
		DatabaseManager::create_tables();
	}
}
