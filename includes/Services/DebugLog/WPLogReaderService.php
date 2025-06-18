<?php
/**
 * Advanced Log Reader Service for Debug Suite.
 *
 * Provides advanced log reading, filtering, and exporting capabilities
 * with stack trace detection and parsing.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Services\DebugLog;

use DateTime;
use DebugSuite\Core\ServiceResponse;
use DebugSuite\Interfaces\ServiceInterface;
use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Advanced log reader service with stack trace support.
 *
 * @since DEBUG_SUITE_SINCE
 */
class WPLogReaderService implements ServiceInterface {

	/**
	 * Log levels mapping.
	 *
	 * @var array
	 */
	private array $log_levels = [
		'emergency' => 0,
		'alert'     => 1,
		'critical'  => 2,
		'error'     => 3,
		'warning'   => 4,
		'notice'    => 5,
		'info'      => 6,
		'debug'     => 7,
	];

	/**
	 * Default log file path.
	 *
	 * @var string
	 */
	private string $default_log_path;

	/**
	 * Constructor.
	 *
	 * @param string|null $log_path Optional custom log file path.
	 */
	public function __construct( ?string $log_path = null ) {
		$this->default_log_path = $log_path ?? WP_CONTENT_DIR . '/debug.log';
	}

	/**
	 * Read and parse log entries with advanced filtering.
	 *
	 * @param array $options {
	 *     Optional filtering and pagination options.
	 *     @type string $level      Log level filter.
	 *     @type string $search     Search term.
	 *     @type string $date_from  Start date filter (Y-m-d format).
	 *     @type string $date_to    End date filter (Y-m-d format).
	 *     @type int    $limit      Number of entries to return.
	 *     @type int    $offset     Offset for pagination.
	 *     @type string $log_file   Custom log file path.
	 * }
	 * @return ServiceResponse
	 */
	public function read_log_entries( array $options = [] ): ServiceResponse {
		$log_file = $options['log_file'] ?? $this->default_log_path;

		if ( ! file_exists( $log_file ) ) {
			return ServiceResponse::failure(
				__( 'Log file not found.', 'debug-suite' ),
				'file_not_found',
				[ 'path' => $log_file ]
			);
		}

		if ( ! is_readable( $log_file ) ) {
			return ServiceResponse::failure(
				__( 'Log file is not readable.', 'debug-suite' ),
				'file_not_readable',
				[ 'path' => $log_file ]
			);
		}

		try {
			$raw_content = file_get_contents( $log_file );
			if ( false === $raw_content ) {
				return ServiceResponse::failure(
					__( 'Failed to read log file.', 'debug-suite' ),
					'read_error'
				);
			}

			$entries = $this->parse_log_entries( $raw_content );
			$filtered_entries = $this->filter_entries( $entries, $options );
			$paginated_entries = $this->paginate_entries( $filtered_entries, $options );

			return ServiceResponse::success(
				[
					'entries' => $paginated_entries,
					'total'   => count( $filtered_entries ),
					'file'    => $log_file,
					'size'    => filesize( $log_file ),
				]
			);

		} catch ( Exception $e ) {
			return ServiceResponse::failure(
				// translators: %s is the error message.
				sprintf( __( 'Error reading log file: %s', 'debug-suite' ), $e->getMessage() ),
				'parse_error'
			);
		}
	}

	/**
	 * Parse raw log content into structured entries with stack trace detection.
	 *
	 * @param string $content Raw log content.
	 * @return array
	 */
	private function parse_log_entries( string $content ): array {
		$entries = [];
		$lines = explode( "\n", $content );
		$current_entry = null;
		$in_stack_trace = false;
		$stack_trace_lines = [];

		foreach ( $lines as $line_number => $line ) {
			$line = trim( $line );

			if ( empty( $line ) ) {
				continue;
			}

			// Check if this line starts a new log entry
			$parsed_entry = $this->parse_log_line( $line );

			if ( $parsed_entry ) {
				// Save previous entry if exists
				if ( $current_entry ) {
					if ( ! empty( $stack_trace_lines ) ) {
						$current_entry['stack_trace'] = $this->parse_stack_trace( $stack_trace_lines );
						$current_entry['has_stack_trace'] = true;
					}
					$entries[] = $current_entry;
				}

				// Start new entry
				$current_entry = $parsed_entry;
				$current_entry['line_number'] = $line_number + 1;
				$current_entry['has_stack_trace'] = false;
				$in_stack_trace = false;
				$stack_trace_lines = [];
			} else { // phpcs:ignore
				// This is a continuation line, check if it's part of a stack trace
				if ( $current_entry ) {
					if ( $this->is_stack_trace_line( $line ) ) {
						if ( ! $in_stack_trace ) {
							$in_stack_trace = true;
						}
						$stack_trace_lines[] = $line;
					} else if ( $in_stack_trace && $this->is_stack_trace_continuation( $line ) ) {
						// Continue collecting stack trace lines
						$stack_trace_lines[] = $line;
					} else {
						// Regular continuation of the message
						$current_entry['message'] .= "\n" . $line;
						$in_stack_trace = false;
					}
				}
			}
		}

		// Remember the last entry
		if ( $current_entry ) {
			if ( ! empty( $stack_trace_lines ) ) {
				$current_entry['stack_trace'] = $this->parse_stack_trace( $stack_trace_lines );
				$current_entry['has_stack_trace'] = true;
			}
			$entries[] = $current_entry;
		}

		return array_reverse( $entries ); // Most recent first
	}

	/**
	 * Parse a single log line into structured data.
	 *
	 * @param string $line Log line.
	 * @return array|null
	 */
	private function parse_log_line( string $line ): ?array {
		// WordPress debug.log format: [DD-MMM-YYYY HH:MM:SS UTC] PHP Error: Message
		$wordpress_pattern = '/^\[(\d{2}-[A-Za-z]{3}-\d{4}\s\d{2}:\d{2}:\d{2}\s[A-Z]{3})\]\s+(.*?):\s+(.*)$/';

		// Standard log format: YYYY-MM-DD HH:MM:SS [LEVEL] Message
		$standard_pattern = '/^(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2})\s+\[([^\]]+)\]\s+(.*)$/';

		// PHP error format: [DD-MMM-YYYY HH:MM:SS] PHP Error: Message
		$php_error_pattern = '/^\[(\d{2}-[A-Za-z]{3}-\d{4}\s\d{2}:\d{2}:\d{2})\]\s+(.*?):\s+(.*)$/';

		if ( preg_match( $wordpress_pattern, $line, $matches ) ) {
			return [
				'timestamp' => $this->parse_timestamp( $matches[1] ),
				'level'     => $this->normalize_log_level( $matches[2] ),
				'message'   => trim( $matches[3] ),
				'raw_line'  => $line,
			];
		}

		if ( preg_match( $standard_pattern, $line, $matches ) ) {
			return [
				'timestamp' => $matches[1],
				'level'     => $this->normalize_log_level( $matches[2] ),
				'message'   => trim( $matches[3] ),
				'raw_line'  => $line,
			];
		}

		if ( preg_match( $php_error_pattern, $line, $matches ) ) {
			return [
				'timestamp' => $this->parse_timestamp( $matches[1] ),
				'level'     => $this->normalize_log_level( $matches[2] ),
				'message'   => trim( $matches[3] ),
				'raw_line'  => $line,
			];
		}

		return null;
	}

	/**
	 * Check if a line is part of a stack trace.
	 *
	 * @param string $line Line to check.
	 * @return bool
	 */
	private function is_stack_trace_line( string $line ): bool {
		// Common stack trace patterns
		$patterns = [
			'/^#\d+\s+/',                           // #0 /path/to/file.php(123): function()
			'/^Stack trace:/',                      // Stack trace header
			'/^\s*at\s+/',                         // at ClassName->method()
			'/^\s*in\s+\/.*\.php:\d+/',            // in /path/file.php:123
			'/^\s*thrown in\s+.*\.php on line\s+\d+/', // thrown in /path/file.php on line 123
			'/^\s*Call to undefined/',             // Call to undefined function/method
			'/^\s*Fatal error:/',                  // Fatal error messages
			'/^\s*Warning:/',                      // Warning messages that might start traces
			'/^\s*Notice:/',                       // Notice messages
			'/^\s+thrown in\s+/',                  // Indented "thrown in" lines
		];

		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $line ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if a line is a continuation of a stack trace.
	 *
	 * @param string $line Line to check.
	 * @return bool
	 */
	private function is_stack_trace_continuation( string $line ): bool {
		// Lines that are likely continuation of stack traces
		$patterns = [
			'/^\s+/',                    // Indented lines
			'/^#\d+\s+/',               // Numbered stack trace entries
			'/^\s*\[internal function\]/', // Internal function calls
			'/^\s*{main}/',             // Main execution
		];

		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $line ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Parse stack trace lines into structured data.
	 *
	 * @param array $lines Stack trace lines.
	 * @return array
	 */
	private function parse_stack_trace( array $lines ): array {
		$stack_trace = [
			'raw_lines' => $lines,
			'frames'    => [],
			'summary'   => '',
		];

		$frames = [];
		$summary_parts = [];

		foreach ( $lines as $line ) {
			$line = trim( $line );

			// Parse numbered stack trace frames: #0 /path/file.php(123): function()
			if ( preg_match( '/^#(\d+)\s+(.+)$/', $line, $matches ) ) {
				$frame_number = (int) $matches[1];
				$frame_info = $matches[2];

				$frame = [
					'number' => $frame_number,
					'raw'    => $frame_info,
				];

				// Try to parse file, line, and function with improved patterns
				if ( preg_match( '/^(.*?)\((\d+)\):\s*(.*)$/', $frame_info, $frame_matches ) ) {
					$frame['file'] = $frame_matches[1];
					$frame['line'] = (int) $frame_matches[2];
					$frame['function'] = $frame_matches[3];
				} elseif ( preg_match( '/^\[internal function\]:\s*(.*)$/', $frame_info, $frame_matches ) ) {
					$frame['file'] = '[internal function]';
					$frame['line'] = null;
					$frame['function'] = $frame_matches[1];
				} elseif ( preg_match( '/^{main}$/', $frame_info ) ) {
					$frame['file'] = '{main}';
					$frame['line'] = null;
					$frame['function'] = '{main}';
				} else {
					// Fallback for any other format
					$frame['function'] = $frame_info;
					$frame['file'] = null;
					$frame['line'] = null;
				}

				$frames[] = $frame;
			} else {
				// Collect non-frame lines for summary
				$summary_parts[] = $line;
			}
		}

		$stack_trace['frames'] = $frames;
		$stack_trace['summary'] = implode( "\n", $summary_parts );
		$stack_trace['frame_count'] = count( $frames );

		return $stack_trace;
	}

	/**
	 * Parse WordPress timestamp format.
	 *
	 * @param string $timestamp WordPress timestamp.
	 * @return string
	 */
	private function parse_timestamp( string $timestamp ): string {
		// Convert WordPress format to standard format
		// From: 19-Jun-2025 01:30:45 UTC
		// To: 2025-06-19 01:30:45

		$date = DateTime::createFromFormat( 'd-M-Y H:i:s T', $timestamp );
		if ( $date ) {
			return $date->format( 'Y-m-d H:i:s' );
		}

		return $timestamp;
	}

	/**
	 * Normalize log level names.
	 *
	 * @param string $level Raw level.
	 * @return string
	 */
	private function normalize_log_level( string $level ): string {
		$level = strtolower( trim( $level ) );

		// Map common variations
		$level_map = [
			'php fatal error'    => 'critical',
			'php warning'        => 'warning',
			'php notice'         => 'notice',
			'php parse error'    => 'critical',
			'fatal error'        => 'critical',
			'err'               => 'error',
			'warn'              => 'warning',
			'inf'               => 'info',
		];

		return $level_map[ $level ] ?? $level;
	}

	/**
	 * Filter log entries based on criteria.
	 *
	 * @param array $entries Log entries.
	 * @param array $options Filter options.
	 * @return array
	 */
	private function filter_entries( array $entries, array $options ): array {
		$filtered = $entries;

		// Filter by level
		if ( ! empty( $options['level'] ) ) {
			$target_level = $this->log_levels[ $options['level'] ] ?? null;
			if ( null !== $target_level ) {
				$filtered = array_filter(
					$filtered,
					function ( $entry ) use ( $target_level ) {
						$entry_level = $this->log_levels[ $entry['level'] ] ?? 7;
						return $entry_level <= $target_level;
					}
				);
			}
		}

		// Filter by search term
		if ( ! empty( $options['search'] ) ) {
			$search_term = strtolower( $options['search'] );
			$filtered = array_filter(
				$filtered,
				function ( $entry ) use ( $search_term ) {
					return str_contains( strtolower( $entry['message'] ), $search_term ) ||
					   str_contains( strtolower( $entry['raw_line'] ), $search_term );
				}
			);
		}

		// Filter by date range
		if ( ! empty( $options['date_from'] ) || ! empty( $options['date_to'] ) ) {
			$date_from = $options['date_from'] ?? '1970-01-01';
			$date_to = $options['date_to'] ?? '2099-12-31';

			$filtered = array_filter(
				$filtered,
				function ( $entry ) use ( $date_from, $date_to ) {
					$entry_date = substr( $entry['timestamp'], 0, 10 );
					return $entry_date >= $date_from && $entry_date <= $date_to;
				}
			);
		}

		// Filter by stack trace presence
		if ( isset( $options['has_stack_trace'] ) ) {
			$filtered = array_filter(
				$filtered,
				function ( $entry ) use ( $options ) {
					return (bool) $entry['has_stack_trace'] === (bool) $options['has_stack_trace'];
				}
			);
		}

		return array_values( $filtered );
	}

	/**
	 * Paginate entries.
	 *
	 * @param array $entries Filtered entries.
	 * @param array $options Pagination options.
	 * @return array
	 */
	private function paginate_entries( array $entries, array $options ): array {
		$limit = $options['limit'] ?? 100;
		$offset = $options['offset'] ?? 0;

		return array_slice( $entries, $offset, $limit );
	}

	/**
	 * Export log entries to various formats.
	 *
	 * @param array $options Export options.
	 * @return ServiceResponse
	 */
	public function export_log_entries( array $options ): ServiceResponse {
		$format = $options['format'] ?? 'json';
		$log_result = $this->read_log_entries( $options );

		if ( $log_result->is_failure() ) {
			return $log_result;
		}

		$data = $log_result->get_data();
		$entries = $data['entries'];

		try {
			switch ( $format ) {
				case 'json':
					$content = wp_json_encode( $entries, JSON_PRETTY_PRINT );
					$mime_type = 'application/json';
					$extension = 'json';
					break;

				case 'csv':
					$content = $this->export_to_csv( $entries );
					$mime_type = 'text/csv';
					$extension = 'csv';
					break;

				case 'txt':
					$content = $this->export_to_text( $entries );
					$mime_type = 'text/plain';
					$extension = 'txt';
					break;

				default:
					return ServiceResponse::failure(
						// translators: %s is the unsupported format.
						sprintf( __( 'Unsupported export format: %s', 'debug-suite' ), $format ),
						'invalid_format'
					);
			}

			return ServiceResponse::success(
				[
					'content'   => $content,
					'mime_type' => $mime_type,
					'filename'  => 'debug-log-export-' . gmdate( 'Y-m-d-H-i-s' ) . '.' . $extension,
					'size'      => strlen( $content ),
					'entries'   => count( $entries ),
				]
			);

		} catch ( Exception $e ) {
			return ServiceResponse::failure(
				// translators: %s is the error message.
				sprintf( __( 'Export failed: %s', 'debug-suite' ), $e->getMessage() ),
				'export_error'
			);
		}
	}

	/**
	 * Export entries to CSV format.
	 *
	 * @param array $entries Log entries.
	 * @return string
	 */
	private function export_to_csv( array $entries ): string {
		$output = fopen( 'php://temp', 'r+' );

		// CSV headers
		fputcsv(
			$output,
			[
				'Timestamp',
				'Level',
				'Message',
				'Has Stack Trace',
				'Stack Trace Frames',
				'File',
				'Line Number',
			]
		);

		foreach ( $entries as $entry ) {
			fputcsv(
				$output,
				[
					$entry['timestamp'],
					$entry['level'],
					$entry['message'],
					$entry['has_stack_trace'] ? 'Yes' : 'No',
					$entry['has_stack_trace'] ? $entry['stack_trace']['frame_count'] : 0,
					$entry['raw_line'],
					$entry['line_number'],
				]
			);
		}

		rewind( $output );
		$csv_content = stream_get_contents( $output );
		fclose( $output );

		return $csv_content;
	}

	/**
	 * Export entries to plain text format.
	 *
	 * @param array $entries Log entries.
	 * @return string
	 */
	private function export_to_text( array $entries ): string {
		$lines = [];
		$lines[] = '# Debug Log Export';
		$lines[] = '# Generated: ' . gmdate( 'Y-m-d H:i:s' ) . ' UTC';
		$lines[] = '# Total Entries: ' . count( $entries );
		$lines[] = '';

		foreach ( $entries as $i => $entry ) {
			$lines[] = sprintf( '## Entry #%d', $i + 1 );
			$lines[] = sprintf( 'Timestamp: %s', $entry['timestamp'] );
			$lines[] = sprintf( 'Level: %s', strtoupper( $entry['level'] ) );
			$lines[] = sprintf( 'Line: %d', $entry['line_number'] );
			$lines[] = sprintf( 'Message: %s', $entry['message'] );

			if ( $entry['has_stack_trace'] ) {
				$lines[] = sprintf( 'Stack Trace: %d frames', $entry['stack_trace']['frame_count'] );
				$lines[] = '';
				$lines[] = '### Stack Trace Details:';
				foreach ( $entry['stack_trace']['frames'] as $frame ) {
					$lines[] = sprintf( '#%d %s', $frame['number'], $frame['raw'] );
				}
				if ( ! empty( $entry['stack_trace']['summary'] ) ) {
					$lines[] = '';
					$lines[] = 'Summary: ' . $entry['stack_trace']['summary'];
				}
			}

			$lines[] = '';
			$lines[] = str_repeat( '-', 80 );
			$lines[] = '';
		}

		return implode( "\n", $lines );
	}

	/**
	 * Get log file statistics.
	 *
	 * @param string|null $log_file Optional log file path.
	 * @return ServiceResponse
	 */
	public function get_log_statistics( ?string $log_file = null ): ServiceResponse {
		$log_file = $log_file ?? $this->default_log_path;

		if ( ! file_exists( $log_file ) ) {
			return ServiceResponse::failure(
				__( 'Log file not found.', 'debug-suite' ),
				'file_not_found',
				[ 'path' => $log_file ]
			);
		}

		$log_result = $this->read_log_entries( [ 'log_file' => $log_file ] );
		if ( $log_result->is_failure() ) {
			return $log_result;
		}

		$data = $log_result->get_data();
		$entries = $data['entries'];

		$file_size = filesize( $log_file );
		$stats = [
			'file_path'        => $log_file,
			'file_size'        => $file_size,
			'file_size_human'  => function_exists( 'size_format' ) ? size_format( $file_size ) : $this->format_bytes( $file_size ),
			'total_entries'    => count( $entries ),
			'entries_with_stack_traces' => 0,
			'levels'           => [],
			'recent_errors'    => 0,
			'last_modified'    => filemtime( $log_file ),
		];

		// Count entries by level and stack traces
		foreach ( $entries as $entry ) {
			$level = $entry['level'];
			$stats['levels'][ $level ] = ( $stats['levels'][ $level ] ?? 0 ) + 1;

			if ( $entry['has_stack_trace'] ) {
				$stats['entries_with_stack_traces']++;
			}

			// Count recent errors (last 24 hours)
			$entry_time = strtotime( $entry['timestamp'] );
			$day_in_seconds = defined( 'DAY_IN_SECONDS' ) ? DAY_IN_SECONDS : 86400;
			if ( $entry_time > ( time() - $day_in_seconds ) && in_array( $level, [ 'error', 'critical', 'emergency' ], true ) ) {
				$stats['recent_errors']++;
			}
		}

		return ServiceResponse::success( $stats );
	}

	/**
	 * Clear log file content.
	 *
	 * @param string|null $log_file Optional log file path.
	 * @return ServiceResponse
	 */
	public function clear_log_file( ?string $log_file = null ): ServiceResponse {
		$log_file = $log_file ?? $this->default_log_path;

		if ( ! file_exists( $log_file ) ) {
			return ServiceResponse::success(
				[
					'message' => __( 'Log file does not exist.', 'debug-suite' ),
					'path'    => $log_file,
				]
			);
		}

		if ( ! is_writable( $log_file ) ) {
			return ServiceResponse::failure(
				__( 'Log file is not writable.', 'debug-suite' ),
				'file_not_writable',
				[ 'path' => $log_file ]
			);
		}

		$result = file_put_contents( $log_file, '' );
		if ( false === $result ) {
			return ServiceResponse::failure(
				__( 'Failed to clear log file.', 'debug-suite' ),
				'clear_failed'
			);
		}

		return ServiceResponse::success(
			[
				'message' => __( 'Log file cleared successfully.', 'debug-suite' ),
				'path'    => $log_file,
			]
		);
	}

	/**
	 * Wrapper method for compatibility with FileLogsService.
	 * Delegates to read_log_entries.
	 *
	 * @param array $options Log reading options.
	 * @return ServiceResponse
	 */
	public function get_log_entries( array $options = [] ): ServiceResponse {
		return $this->read_log_entries( $options );
	}

	/**
	 * Wrapper method for compatibility with FileLogsService.
	 * Delegates to get_log_statistics.
	 *
	 * @param string|null $log_file Optional log file path.
	 * @return ServiceResponse
	 */
	public function get_log_file_stats( ?string $log_file = null ): ServiceResponse {
		return $this->get_log_statistics( $log_file );
	}

	/**
	 * Wrapper method for compatibility with FileLogsService.
	 * Delegates to export_log_entries.
	 *
	 * @param array $options Export options.
	 * @return ServiceResponse
	 */
	public function export_logs( array $options = [] ): ServiceResponse {
		return $this->export_log_entries( $options );
	}

	/**
	 * Format bytes into human-readable format.
	 * Fallback for when WordPress size_format() is not available.
	 *
	 * @param int $bytes File size in bytes.
	 * @return string
	 */
	private function format_bytes( int $bytes ): string {
		$units = [ 'B', 'KB', 'MB', 'GB', 'TB' ];
		$bytes = max( $bytes, 0 );
		$pow = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
		$pow = min( $pow, count( $units ) - 1 );
		$bytes /= pow( 1024, $pow );

		return round( $bytes, 2 ) . ' ' . $units[ $pow ];
	}
}
