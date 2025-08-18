<?php
/**
 * Log file discovery service for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Services\DebugLog;

use DebugSuite\Core\FileSystem;
use DebugSuite\Interfaces\ServiceInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Service for discovering and managing log file paths.
 *
 * @since 1.0.0
 */
class LogDiscoveryService implements ServiceInterface {

	/**
	 * Get paths to supported log files.
	 *
	 * @return array
	 */
	public function get_supported_log_files(): array {
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
			$paths[] = \Automattic\WooCommerce\Utilities\LoggingUtil::get_log_directory();
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
					$file_size = FileSystem::size( $path );
					$file_mtime = FileSystem::mtime( $path );

					return [
						'name'        => basename( $path ),
						'path'        => $path,
						'size'        => FileSystem::format_size( $file_size ),
						'size_bytes'  => $file_size,
						'modified'    => gmdate( 'Y-m-d H:i:s', $file_mtime ),
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
	 *
	 * @param string $path File path.
	 * @return string
	 */
	private function detect_log_type( string $path ): string {
		// Check for WooCommerce logs first (more specific)
		if ( str_contains( $path, 'wc-' ) || str_contains( $path, 'woocommerce' ) ) {
			return 'WooCommerce';
		}
		if ( str_contains( $path, 'debug' ) ) {
			return 'WordPress Debug';
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
	private function find_php_fpm_error_log(): ?string {
		$candidates = [
			sys_get_temp_dir() . '/php-fpm.log',  // For testing
			'/var/log/php-fpm.log',
			'/var/log/php/php-fpm.log',
			'/opt/bitnami/php/var/log/php-fpm.log',
		];

		// Check common PHP-FPM log locations
		$candidates = array_merge( $candidates, glob( '/var/log/php-fpm*.log' ) );
		$candidates = array_merge( $candidates, glob( '/var/log/php*.log' ) );

		foreach ( $candidates as $path ) {
			if ( FileSystem::is_readable( $path ) ) {
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
	private function find_apache_log_file(): ?string {
		$candidates = [
			sys_get_temp_dir() . '/apache2/error.log',  // For testing
			'/var/log/apache2/error.log',
			'/var/log/httpd/error_log',
			'/usr/local/apache/logs/error_log',
			'/opt/bitnami/apache2/logs/error_log',
		];
		foreach ( $candidates as $path ) {
			if ( FileSystem::is_readable( $path ) ) {
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
	private function find_nginx_log_file(): ?string {
		$candidates = [
			sys_get_temp_dir() . '/nginx/error.log',  // For testing
			'/var/log/nginx/error.log',
			'/usr/local/nginx/logs/error.log',
			'/opt/bitnami/nginx/logs/error.log',
		];
		foreach ( $candidates as $path ) {
			if ( FileSystem::is_readable( $path ) ) {
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
	private function find_redis_log_file(): ?string {
		$candidates = [
			sys_get_temp_dir() . '/redis/redis-server.log',  // For testing
			'/var/log/redis/redis-server.log',
			'/var/log/redis.log',
		];
		foreach ( $candidates as $path ) {
			if ( FileSystem::is_readable( $path ) ) {
				return $path;
			}
		}
		return null;
	}
}
