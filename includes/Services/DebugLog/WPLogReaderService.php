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
	private string $log_file_path;

	/**
	 * Constructor.
	 *
	 * @param string $log_path Optional custom log file path.
	 */
	public function __construct( string $log_path ) {
		$this->log_file_path = $log_path;
	}

	/**
	 * Get the list of log levels with their labels.
	 *
	 * @return array
	 */
	public function get_log_levels(): array {
		$levels = [];
		foreach ( $this->log_levels as $level => $value ) {
			$levels[ $level ] = [
				'label' => ucfirst( $level ),
				'value' => $value,
			];
		}
		return $levels;
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
		$log_file = $options['log_file'] ?? $this->log_file_path;

		if ( ! file_exists( $log_file ) ) {
			return ServiceResponse::failure(
				__( 'Log file not found.', 'debug-suite' ),
				'file_not_found',
				[ 'path' => $log_file ]
			);
		}

		try {
			$lines = file( $log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
			if ( false === $lines ) {
				return ServiceResponse::failure(
					__( 'Failed to read log file.', 'debug-suite' ),
					'read_error'
				);
			}

			$entries = $this->parse_log_entries( $lines );
			$filtered_entries = $this->filter_entries( $entries, $options );
			$paginated_entries = $this->paginate_entries( $filtered_entries, $options );

			return ServiceResponse::success(
				[
					'entries' => $paginated_entries,
					'total'   => count( $filtered_entries ),
					'file'    => $log_file,
					'size'    => filesize( $log_file ),
					'labels'  => $this->get_log_levels(),
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
	 * Parse log lines into structured entries with improved efficiency.
	 *
	 * @param array $lines Array of log lines.
	 * @return array
	 */
	private function parse_log_entries( array $lines ): array {
		$entries = [];
		$entry = null;

		foreach ( $lines as $line_number => $line ) {
			// Check if this line starts a new log entry
			if ( preg_match( '/^\[(.*?)] (.*?): (.*?)( in (.*?) on line (\d+))?$/', $line, $matches ) ) {
				// Save previous entry if exists
				if ( $entry ) {
					$entries[] = $entry;
				}

				$type = trim( $matches[2] );
				$level = $this->determine_log_level( $type );

				$entry = [
					'timestamp'        => $this->parse_timestamp( $matches[1] ),
					'type'             => $type,
					'level'            => $level,
					'message'          => trim( $matches[3] ),
					'file'             => $matches[5] ?? null,
					'line'             => isset( $matches[6] ) ? (int) $matches[6] : null,
					'stack_trace'      => '',
					'has_stack_trace'  => false,
					'line_number'      => $line_number + 1,
					'raw_line'         => $line,
				];
			} elseif ( $entry ) {
				// This is a continuation line (stack trace)
				$entry['stack_trace'] .= $line . "\n";
				$entry['has_stack_trace'] = true;
			}
		}

		// Add the last entry if exists
		if ( $entry ) {
			$entries[] = $entry;
		}

		// Process stack traces and return most recent first
		return array_reverse( array_map( [ $this, 'process_stack_trace' ], $entries ) );
	}

	/**
	 * Determine log level from error type.
	 *
	 * @param string $type Error type string.
	 * @return string
	 */
	private function determine_log_level( string $type ): string {
		$type_lower = strtolower( $type );

		// Check for critical level errors first (fatal errors are critical)
		if ( str_contains( $type_lower, 'fatal' ) ||
			 str_contains( $type_lower, 'parse error' ) ) {
			return 'critical';
		}

		// Check for regular errors
		if ( str_contains( $type_lower, 'uncaught' ) ||
			 str_contains( $type_lower, 'error' ) ) {
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
	 * Process stack trace for an entry.
	 *
	 * @param array $entry Log entry.
	 * @return array
	 */
	private function process_stack_trace( array $entry ): array {
		if ( empty( $entry['stack_trace'] ) ) {
			return $entry;
		}

		$stack_trace_lines = array_filter(
			explode( "\n", trim( $entry['stack_trace'] ) ),
			fn( $line ) => ! empty( trim( $line ) )
		);

		$entry['stack_trace'] = [
			'raw_lines'   => $stack_trace_lines,
			'frames'      => $this->parse_stack_trace_frames( $stack_trace_lines ),
			'summary'     => implode( "\n", $stack_trace_lines ),
			'frame_count' => count( $stack_trace_lines ),
		];

		return $entry;
	}

	/**
	 * Parse stack trace frames from lines.
	 *
	 * @param array $lines Stack trace lines.
	 * @return array
	 */
	private function parse_stack_trace_frames( array $lines ): array {
		$frames = [];

		foreach ( $lines as $index => $line ) {
			$line = trim( $line );

			// Try to parse numbered stack frames
			if ( preg_match( '/^#(\d+)\s+(.+)$/', $line, $matches ) ) {
				$frame = [
					'number' => (int) $matches[1],
					'raw'    => $matches[2],
				];

				// Parse file, line, and function
				if ( preg_match( '/^(.*?)\((\d+)\):\s*(.*)$/', $matches[2], $frame_matches ) ) {
					$frame['file']     = $frame_matches[1];
					$frame['line']     = (int) $frame_matches[2];
					$frame['function'] = $frame_matches[3];
				} else {
					$frame['function'] = $matches[2];
					$frame['file']     = null;
					$frame['line']     = null;
				}

				$frames[] = $frame;
			} else {
				// Generic frame for non-numbered lines
				$frames[] = [
					'number'   => $index,
					'raw'      => $line,
					'function' => $line,
					'file'     => null,
					'line'     => null,
				];
			}
		}

		return $frames;
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
		$log_file = $log_file ?? $this->log_file_path;

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
		$log_file = $log_file ?? $this->log_file_path;

		if ( ! file_exists( $log_file ) ) {
			return ServiceResponse::success(
				[
					'message' => __( 'Log file does not exist.', 'debug-suite' ),
					'path'    => $log_file,
				]
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
