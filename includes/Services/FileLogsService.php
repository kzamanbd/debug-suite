<?php
/**
 * File logs service for Debug Suite business logic.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Services;

use DebugSuite\Core\ServiceResult;
use DebugSuite\Interfaces\ServiceInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Simple file logs service for managing WordPress debug log operations.
 *
 * @since DEBUG_SUITE_SINCE
 */
class FileLogsService implements ServiceInterface {

	/**
	 * Path to the WordPress debug log file.
	 *
	 * @var string
	 */
	private string $log_file_path;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->log_file_path = WP_CONTENT_DIR . '/debug.log';
	}

	/**
	 * Get log entries from the debug log file.
	 *
	 * @param array $options Parsing options (limit, level_filter).
	 * @return ServiceResult
	 */
	public function get_log_entries( array $options = [] ): ServiceResult {
		if ( ! file_exists( $this->log_file_path ) ) {
			return ServiceResult::failure( __( 'Log file not found.', 'debug-suite' ), 'log_file_not_found' );
		}

		if ( ! is_readable( $this->log_file_path ) ) {
			return ServiceResult::failure( __( 'Log file is not readable.', 'debug-suite' ), 'log_file_not_readable' );
		}

		$entries = $this->parse_log_file( $options );

		return ServiceResult::success(
			[
				'entries' => $entries,
				'total' => count( $entries ),
				'log_file' => $this->log_file_path,
			]
		);
	}

	/**
	 * Clear the debug log file.
	 *
	 * @return ServiceResult
	 */
	public function clear_log_file(): ServiceResult {
		if ( ! file_exists( $this->log_file_path ) ) {
			return ServiceResult::failure( __( 'Log file not found.', 'debug-suite' ), 'log_file_not_found' );
		}

		if ( ! is_writable( $this->log_file_path ) ) {
			return ServiceResult::failure( __( 'Log file is not writable.', 'debug-suite' ), 'log_file_not_writable' );
		}

		$result = file_put_contents( $this->log_file_path, '' );

		if ( $result === false ) {
			return ServiceResult::failure( __( 'Failed to clear log file.', 'debug-suite' ), 'log_clear_failed' );
		}

		return ServiceResult::success( true );
	}

	/**
	 * Get log file statistics.
	 *
	 * @return ServiceResult
	 */
	public function get_log_file_stats(): ServiceResult {
		if ( ! file_exists( $this->log_file_path ) ) {
			return ServiceResult::failure( __( 'Log file not found.', 'debug-suite' ), 'log_file_not_found' );
		}

		$file_size = filesize( $this->log_file_path );
		$entries = $this->parse_log_file();
		$stats = $this->calculate_stats( $entries );

		return ServiceResult::success(
			[
				'file_size' => $file_size,
				'file_size_mb' => round( $file_size / 1024 / 1024, 2 ),
				'total_entries' => count( $entries ),
				'last_modified' => filemtime( $this->log_file_path ),
				'stats_by_level' => $stats,
			]
		);
	}

	/**
	 * Parse log file into entries.
	 *
	 * @param array $options Parsing options.
	 * @return array
	 */
	private function parse_log_file( array $options = [] ): array {
		$content = file_get_contents( $this->log_file_path );
		if ( $content === false ) {
			return [];
		}

		$lines = explode( "\n", trim( $content ) );
		$entries = [];
		$limit = $options['limit'] ?? 1000;
		$level_filter = $options['level_filter'] ?? null;

		foreach ( $lines as $line ) {
			if ( empty( trim( $line ) ) ) {
				continue;
			}

			$entry = $this->parse_log_line( $line );
			if ( ! $entry ) {
				continue;
			}

			// Apply level filter
			if ( $level_filter && $entry['level'] !== $level_filter ) {
				continue;
			}

			$entries[] = $entry;

			// Apply limit
			if ( count( $entries ) >= $limit ) {
				break;
			}
		}

		return array_reverse( $entries ); // Newest first
	}

	/**
	 * Parse a single log line.
	 *
	 * @param string $line Log line to parse.
	 * @return array|null
	 */
	private function parse_log_line( string $line ): ?array {
		// Match WordPress log format: [timestamp] type: message
		if ( ! preg_match( '/^\[(.*?)\]\s+(.*?):\s+(.*)$/', $line, $matches ) ) {
			return null;
		}

		$timestamp = $matches[1];
		$type = trim( $matches[2] );
		$message = trim( $matches[3] );

		return [
			'timestamp' => $timestamp,
			'type' => $type,
			'level' => $this->determine_level( $type ),
			'message' => $message,
		];
	}

	/**
	 * Determine log level from type.
	 *
	 * @param string $type Log type.
	 * @return string
	 */
	private function determine_level( string $type ): string {
		$type_lower = strtolower( $type );

		if ( str_contains( $type_lower, 'fatal' ) || strpos( $type_lower, 'error' ) !== false ) {
			return 'error';
		}
		if ( str_contains( $type_lower, 'warning' ) ) {
			return 'warning';
		}
		if ( str_contains( $type_lower, 'notice' ) ) {
			return 'notice';
		}
		if ( str_contains( $type_lower, 'deprecated' ) ) {
			return 'debug';
		}

		return 'info';
	}

	/**
	 * Calculate statistics by level.
	 *
	 * @param array $entries Log entries.
	 * @return array
	 */
	private function calculate_stats( array $entries ): array {
		$stats = [
			'error' => 0,
			'warning' => 0,
			'notice' => 0,
			'debug' => 0,
			'info' => 0,
		];

		foreach ( $entries as $entry ) {
			$level = $entry['level'] ?? 'info';
			if ( isset( $stats[ $level ] ) ) {
				$stats[ $level ]++;
			}
		}

		return $stats;
	}
}
