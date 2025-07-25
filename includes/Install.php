<?php
/**
 * Database installation and schema management for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite;

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
	 * Current database version.
	 *
	 * @var string
	 */
	const DB_VERSION = '1.0.0';

	/**
	 * Install database tables and setup initial schema.
	 *
	 * @return void
	 */
	public static function install(): void {
		self::create_tables();
		self::update_db_version();
	}

	/**
	 * Create all required database tables.
	 *
	 * @return void
	 */
	public static function create_tables(): void {
		self::create_email_logs_table();
		// Add more table creation methods here as needed
	}

	/**
	 * Create email logs table.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function create_email_logs_table(): bool {
		global $wpdb;

		$table_name = $wpdb->prefix . 'debug_suite_email_logs';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			to_email varchar(200) NOT NULL,
			subject text NOT NULL,
			message longtext NOT NULL,
			headers text DEFAULT NULL,
			attachments text DEFAULT NULL,
			status varchar(20) DEFAULT 'pending',
			error_message text DEFAULT NULL,
			sent_date datetime NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_status (status),
			KEY idx_sent_date (sent_date),
			KEY idx_to_email (to_email)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$result = dbDelta( $sql );

		// Check if table was created successfully
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name;
	}

	/**
	 * Update database version option.
	 *
	 * @return void
	 */
	private static function update_db_version(): void {
		update_option( 'debug_suite_db_version', self::DB_VERSION );
	}

	/**
	 * Get current database version.
	 *
	 * @return string
	 */
	public static function get_db_version(): string {
		return get_option( 'debug_suite_db_version', '0.0.0' );
	}

	/**
	 * Check if database needs upgrade.
	 *
	 * @return bool
	 */
	public static function needs_upgrade(): bool {
		return version_compare( self::get_db_version(), self::DB_VERSION, '<' );
	}

	/**
	 * Drop all plugin tables (used during uninstall).
	 *
	 * @return void
	 */
	public static function drop_tables(): void {
		global $wpdb;

		$tables = [
			$wpdb->prefix . 'debug_suite_email_logs',
			// Add more tables here as needed
		];

		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		}

		// Remove database version option
		delete_option( 'debug_suite_db_version' );
	}

	/**
	 * Check if email logs table exists.
	 *
	 * @return bool
	 */
	public static function email_logs_table_exists(): bool {
		global $wpdb;
		$table_name = $wpdb->prefix . 'debug_suite_email_logs';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name;
	}

	/**
	 * Get table schema information.
	 *
	 * @param string $table_name Table name without prefix.
	 * @return array|null
	 */
	public static function get_table_schema( string $table_name ): ?array {
		global $wpdb;

		$full_table_name = $wpdb->prefix . 'debug_suite_' . $table_name;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$full_table_name}'" ) !== $full_table_name ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$columns = $wpdb->get_results( "DESCRIBE {$full_table_name}", ARRAY_A );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$indexes = $wpdb->get_results( "SHOW INDEX FROM {$full_table_name}", ARRAY_A );

		return [
			'table_name' => $full_table_name,
			'columns'    => $columns,
			'indexes'    => $indexes,
		];
	}

	/**
	 * Repair tables if corrupted.
	 *
	 * @return array Results of repair operations.
	 */
	public static function repair_tables(): array {
		global $wpdb;

		$tables = [
			$wpdb->prefix . 'debug_suite_email_logs',
		];

		$results = [];

		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$result = $wpdb->get_row( "REPAIR TABLE {$table}", ARRAY_A );
				$results[ $table ] = $result;
			}
		}

		return $results;
	}

	/**
	 * Optimize tables for better performance.
	 *
	 * @return array Results of optimization operations.
	 */
	public static function optimize_tables(): array {
		global $wpdb;

		$tables = [
			$wpdb->prefix . 'debug_suite_email_logs',
		];

		$results = [];

		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$result = $wpdb->get_row( "OPTIMIZE TABLE {$table}", ARRAY_A );
				$results[ $table ] = $result;
			}
		}

		return $results;
	}

	/**
	 * Get database size information.
	 *
	 * @return array Database size information.
	 */
	public static function get_database_info(): array {
		global $wpdb;

		$tables = [
			$wpdb->prefix . 'debug_suite_email_logs',
		];

		$total_size = 0;
		$table_info = [];

		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
				$size_result = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT 
							table_name as 'table_name',
							round(((data_length + index_length) / 1024 / 1024), 2) as 'size_mb',
							table_rows as 'rows'
						FROM information_schema.TABLES 
						WHERE table_schema = %s AND table_name = %s",
						DB_NAME,
						$table
					),
					ARRAY_A
				);

				if ( $size_result ) {
					$table_info[ $table ] = $size_result;
					$total_size += (float) $size_result['size_mb'];
				}
			}
		}

		return [
			'total_size_mb' => round( $total_size, 2 ),
			'tables'        => $table_info,
		];
	}
}
