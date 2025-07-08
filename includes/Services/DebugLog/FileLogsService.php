<?php
/**
 * File logs service for Debug Suite business logic.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Services\DebugLog;

use Automattic\WooCommerce\Utilities\LoggingUtil;
use DebugSuite\Core\ServiceResponse;
use DebugSuite\Interfaces\ServiceInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Enhanced file logs service using advanced log reader for WordPress debug log operations.
 *
 * @since DEBUG_SUITE_SINCE
 */
class FileLogsService implements ServiceInterface {

	/**
	 * Advanced log reader service.
	 *
	 * @var WPLogReaderService
	 */
	private WPLogReaderService $log_reader;

	/**
	 * Constructor for FileLogsService.
	 *
	 * @param WPLogReaderService $log_reader The log reader service.
	 */
	public function __construct( WPLogReaderService $log_reader ) {
		$this->log_reader = $log_reader;
	}

	/**
	 * Get log entries from the debug log file.
	 *
	 * @param array $options {
	 *     Parsing options.
	 *     @type int    $limit        Maximum number of entries to return. Default 100.
	 *     @type string $level_filter Filter by log level (error, warning, notice, info, debug).
	 *     @type string $search       Search term to filter messages.
	 *     @type string $date_from    Start date filter (Y-m-d format).
	 *     @type string $date_to      End date filter (Y-m-d format).
	 * }
	 * @return ServiceResponse
	 */
	public function get_log_entries( array $options = [] ): ServiceResponse {
		return $this->log_reader->get_log_entries( $options );
	}

	/**
	 * Clear the debug log file.
	 *
	 * @return ServiceResponse
	 */
	public function clear_log_file(): ServiceResponse {
		return $this->log_reader->clear_log_file();
	}

	/**
	 * Get log file statistics.
	 *
	 * @return ServiceResponse
	 */
	public function get_log_file_stats(): ServiceResponse {
		return $this->log_reader->get_log_file_stats();
	}

	/**
	 * Export log entries to various formats.
	 *
	 * @param array $options Export options including format (json, csv, txt).
	 * @return ServiceResponse
	 */
	public function export_logs( array $options = [] ): ServiceResponse {
		return $this->log_reader->export_logs( $options );
	}

	/**
	 * Get paths to supported log files.
	 *
	 * @return array
	 */

	public function supported_log_files(): array {
		$paths = [
			WP_CONTENT_DIR,         // WordPress content (for debug.log)
			'/var/log/apache2',     // Apache (Debian/Ubuntu)
			'/var/log/httpd',       // Apache (CentOS/RedHat)
			'/var/log/nginx',       // Nginx
			'/var/log/php',         // PHP logs
			'/var/log/redis',       // Redis logs
		];

		// Add custom paths for WooCommerce logs if available.
		if ( class_exists( '\Automattic\WooCommerce\Utilities\LoggingUtil' ) ) {
			$paths[] = LoggingUtil::get_log_directory();
		}
		$files = [];
		foreach ( $paths as $path ) {
			if ( is_dir( $path ) ) {
				$pattern = rtrim( $path, '/' ) . '/*.log';
				$found = glob( $pattern );
				if ( $found ) {
					$files = array_merge( $files, $found );
				}
			}
		}
		// Merge with other log file paths.
		$files = array_merge(
			$files,
			[
				$this->find_apache_log_file(),
				$this->find_nginx_log_file(),
				$this->find_redis_log_file(),
				$this->find_php_fpm_error_log(),
			]
		);

		// Filter only valid log file paths that exist.
		$files = array_filter(
			$files,
			( function ( $path ) {
				return ! empty( $path ) && is_file( $path );
			} )
		);

		// Build detailed info only for existing files.
		$log_files = array_filter(
			array_map(
				function ( $path ) {
					return [
						'name'        => basename( $path ),
						'path'        => $path,
						'size'        => size_format( filesize( $path ) ),
						'size_bytes'  => filesize( $path ),
						'modified'    => gmdate( 'Y-m-d H:i:s', filemtime( $path ) ),
						'type'        => $this->detect_log_type( $path ),
					];
				},
				$files
			)
		);

		return array_values( $log_files );
	}

	/**
	 * Detect a log type based on a file path.
	 */
	private function detect_log_type( $path ): string {
		if ( str_contains( $path, 'debug.log' ) ) {
			return 'WordPress Debug';
		}
		if ( str_contains( $path, 'wc' ) ) {
			return 'WooCommerce';
		}
		if ( str_contains( $path, 'apache' ) ) {
			return 'Apache';
		}
		if ( str_contains( $path, 'nginx' ) ) {
			return 'Nginx';
		}
		if ( str_contains( $path, 'redis' ) ) {
			return 'Redis';
		}
		if ( str_contains( $path, 'php' ) ) {
			return 'PHP-FPM';
		}
		return 'Unknown';
	}

	/**
	 * Find the PHP-FPM error log file.
	 *
	 * @return string|null
	 */
	public function find_php_fpm_error_log(): ?string {
		$candidates = [
			'/var/log/php-fpm.log',
			'/var/log/php/php-fpm.log',
			'/opt/bitnami/php/var/log/php-fpm.log',
		];

		// Check common PHP-FPM log locations
		$candidates = array_merge( $candidates, glob( '/var/log/php-fpm*.log' ) );
		$candidates = array_merge( $candidates, glob( '/var/log/php*.log' ) );

		foreach ( $candidates as $path ) {
			if ( is_readable( $path ) ) {
				return $path;
			}
		}

		return null;
	}

	/**
	 * Find the Apache error log file.
	 *
	 * @return string|null
	 */
	public function find_apache_log_file(): ?string {
		$candidates = [
			'/var/log/apache2/error.log',
			'/var/log/httpd/error_log',
			'/usr/local/apache/logs/error_log',
			'/opt/bitnami/apache2/logs/error_log',
		];
		foreach ( $candidates as $path ) {
			if ( is_readable( $path ) ) {
				return $path;
			}
		}
		return null;
	}

	/**
	 * Find the Nginx error log file.
	 *
	 * @return string|null
	 */
	public function find_nginx_log_file(): ?string {
		$candidates = [
			'/var/log/nginx/error.log',
			'/usr/local/nginx/logs/error.log',
			'/opt/bitnami/nginx/logs/error.log',
		];
		foreach ( $candidates as $path ) {
			if ( is_readable( $path ) ) {
				return $path;
			}
		}
		return null;
	}

	/**
	 * Find the Redis log file.
	 *
	 * @return string|null
	 */
	public function find_redis_log_file(): ?string {
		$candidates = [
			'/var/log/redis/redis-server.log',
			'/var/log/redis.log',
		];
		foreach ( $candidates as $path ) {
			if ( is_readable( $path ) ) {
				return $path;
			}
		}

		// Try parsing redis.conf if found
		$config_paths = [
			'/etc/redis/redis.conf',
			'/usr/local/etc/redis/redis.conf',
		];
		foreach ( $config_paths as $conf ) {
			if ( is_readable( $conf ) ) {
				$lines = file( $conf );
				foreach ( $lines as $line ) {
					if ( preg_match( '/^logfile\s+(.+)$/', trim( $line ), $match ) ) {
						$logfile = trim( $match[1] );
						if ( is_readable( $logfile ) ) {
							return $logfile;
						}
					}
				}
			}
		}

		return null;
	}
}
