<?php
/**
 * Database Tables Manager for Debug Suite.
 *
 * Handles creation and management of custom database tables.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core;

use DebugSuite\Interfaces\Hookable;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Database tables manager class.
 *
 * @since 1.0.0
 */
class DatabaseManager implements Hookable {
	public function register_hooks(): void {
		add_action( 'init', [ $this, 'wpdb_table_shortcuts' ], 1 );
		add_action( 'debug_suite_email-log_activated', [ $this, 'create_email_logs_table' ] );
	}

	public function wpdb_table_shortcuts(): void {
		global $wpdb;
		// Define custom table names for the plugin
		$wpdb->debug_suite_email_logs = $wpdb->prefix . 'debug_suite_email_logs';
	}
	/**
	 * Get the current database version.
	 *
	 * @return string
	 */
	public static function get_db_version(): string {
		return get_option( 'debug_suite_db_version', '0.0.0' );
	}

	/**
	 * Update the database version.
	 *
	 * @param string $version Database version.
	 * @return bool
	 */
	public static function update_db_version( string $version ): bool {
		return update_option( 'debug_suite_db_version', $version );
	}

	/**
	 * Create all required database tables.
	 *
	 * @return void
	 */
	public static function create_tables(): void {
		// Update database version
		static::update_db_version( DEBUG_SUITE_VERSION );
	}

	/**
	 * Create the email logs table.
	 *
	 * @return void
	 */
	public static function create_email_logs_table( $feature ): void {
		if ( $feature !== 'email-log' ) {
			return;
		}

		global $wpdb;

		$table_name = $wpdb->prefix . 'debug_suite_email_logs';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			to_email varchar(200) NOT NULL DEFAULT '',
			subject text NOT NULL,
			message longtext NOT NULL,
			headers text NOT NULL DEFAULT '',
			attachments text NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'pending',
			error_message text NOT NULL DEFAULT '',
			sent_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY status (status),
			KEY to_email (to_email),
			KEY sent_date (sent_date),
			KEY created_at (created_at)
		) $charset_collate;";

		// Include WordPress upgrade functions if not available
		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		dbDelta( $sql );
	}

	/**
	 * Drop all plugin tables.
	 *
	 * @return void
	 */
	public static function drop_tables(): void {
		global $wpdb;

		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}debug_suite_email_logs" );

		// Remove a database version option
		delete_option( 'debug_suite_db_version' );
	}

	/**
	 * Check if the database needs update.
	 *
	 * @return bool
	 */
	public static function needs_update(): bool {
		$current_version = static::get_db_version();
		return version_compare( $current_version, DEBUG_SUITE_VERSION, '<' );
	}

	/**
	 * Run database updates if needed.
	 *
	 * @return void
	 */
	public static function maybe_update(): void {
		if ( static::needs_update() ) {
			static::create_tables();
		}
	}
}
