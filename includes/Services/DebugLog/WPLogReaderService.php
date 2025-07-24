<?php
/**
 * Advanced Log Reader Service for Debug Suite.
 *
 * Provides advanced log reading and filtering capabilities
 * with stack trace detection and parsing.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Services\DebugLog;

use DateTime;
use DebugSuite\Core\FileSystem;
use DebugSuite\Core\ServiceResponse;
use DebugSuite\Interfaces\ServiceInterface;
use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Advanced log reader service with stack trace support.
 *
 * @since 1.0.0
 */
class WPLogReaderService implements ServiceInterface {

	/**
	 * Log levels mapping.
	 *
	 * @var array<string, int>
	 */
	protected array $log_levels = [
		'emergency' => 0,
		'alert'     => 1,
		'critical'  => 2,
		'error'     => 3,
		'warning'   => 4,
		'notice'    => 5,
		'info'      => 6,
		'debug'     => 7,
		'trace'     => 8,
	];

	/**
	 * Default log file path.
	 *
	 * @var string
	 */
	protected string $log_file_path;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->log_file_path = ini_get( 'error_log' );
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

		if ( ! FileSystem::exists( $log_file ) ) {
			return ServiceResponse::failure(
				__( 'Log file not found.', 'debug-suite' ),
				'file_not_found',
				[ 'path' => $log_file ]
			);
		}

		if ( ! FileSystem::is_readable( $log_file ) ) {
			return ServiceResponse::failure(
				__( 'Log file is not readable.', 'debug-suite' ),
				'file_not_readable',
				[ 'path' => $log_file ]
			);
		}

		try {
			$content = FileSystem::get_contents( $log_file );
			if ( false === $content ) {
				return ServiceResponse::failure(
					__( 'Failed to read log file.', 'debug-suite' ),
					'read_error',
					[ 'path' => $log_file ]
				);
			}

			// Convert content to lines for processing
			$lines = explode( "\n", $content );
			$lines = array_filter( $lines, 'trim' ); // Remove empty lines

			$entries = $this->parse_log_entries( $lines );
			$filtered_entries = $this->filter_entries( $entries, $options );
			$paginated_entries = $this->paginate_entries( $filtered_entries, $options );

			$file_size = FileSystem::size( $log_file );
			$last_modified = FileSystem::mtime( $log_file );

			$stats = [
				'total_entries'     => count( $entries ),
				'filtered_entries'  => count( $filtered_entries ),
				'returned_entries'  => count( $paginated_entries ),
				'file_size'        => $file_size,
				'file_size_human'  => FileSystem::format_size( $file_size ),
				'last_modified'    => $last_modified,
			];

			return ServiceResponse::success(
				[
					'entries' => $paginated_entries,
					'total'   => count( $filtered_entries ),
					'file_path'    => $log_file,
					'size'    => $file_size,
					'labels'  => $this->get_log_levels(),
					'stats'   => $stats,
				]
			);

		} catch ( Exception $e ) {
			return ServiceResponse::failure(
				// translators: %s is the error message.
				sprintf( __( 'Error reading log file: %s', 'debug-suite' ), $e->getMessage() ),
				'parse_error',
				[ 'error' => $e->getMessage() ]
			);
		}
	}

	/**
	 * Parse log lines into structured entries with improved efficiency.
	 *
	 * @param array $lines Array of log lines.
	 * @return array
	 */
	protected function parse_log_entries( array $lines ): array {
		$entries = [];
		$entry = null;

		foreach ( $lines as $line_number => $line ) {
			// Check if this line starts a new log entry
			// Enhanced regex to handle multiple log formats
			if ( preg_match( '/^\[(.*?)\]\s+(.*)$/', $line, $matches ) ) {
				// Save previous entry if exists
				if ( $entry ) {
					$entries[] = $entry;
				}

				$timestamp = $matches[1];
				$content = trim( $matches[2] );

				// Determine type and message based on content format
				$type = '';
				$message = '';
				$file = null;
				$line_info = null;

				// Pattern 1: Standard error format (TYPE: message)
				if ( preg_match( '/^([^:]+):\s+(.*)$/', $content, $type_matches ) ) {
					$potential_type = trim( $type_matches[1] );
					$potential_message = trim( $type_matches[2] );

					// Check if this looks like a real error type or just content with colons
					if ( $this->is_standard_error_type( $potential_type ) ) {
						$type = $potential_type;
						$message = $potential_message;
					} else {
						// This is content with colons, treat as a simple message
						$type = $this->determine_type_from_content( $content );
						$message = $content;
					}
				} else {
					// Pattern 2: Simple message without type (like "Array", "Automatic updates starting...")
					$type = $this->determine_type_from_content( $content );
					$message = $content;
				}

				// Extract file and line information from the message if present
				if ( preg_match( '/^(.*?)\s+in\s+(\/[^\s]+)\s+on\s+line\s+(\d+)$/', $message, $file_matches ) ) {
					$message = trim( $file_matches[1] );
					$file = $file_matches[2];
					$line_info = (int) $file_matches[3];
				}

				$level = $this->determine_log_level( $type );

				$entry = [
					'timestamp'        => $this->parse_timestamp( $timestamp ),
					'type'             => $type,
					'level'            => $level,
					'message'          => $message,
					'file_path'        => $file,
					'line'             => $line_info,
					'stack_trace'      => '',
					'has_stack_trace'  => false,
					'line_number'      => $line_number + 1,
					'raw_line'         => $line,
				];
			} elseif ( $entry ) {
				// This is a continuation line (could be multiline message, array dump, or stack trace)
				$trimmed_line = trim( $line );

				// Check if this line looks like a stack trace
				if ( preg_match( '/^#\d+/', $trimmed_line ) ||
						str_contains( $trimmed_line, 'Stack trace:' ) ||
						str_contains( $trimmed_line, 'thrown in' ) ) {
					$entry['stack_trace'] .= $line . "\n";
					$entry['has_stack_trace'] = true;
				} else {
					// This is likely a continuation of the message (array content, multiline text, etc.)
					if ( ! empty( $trimmed_line ) ) {
						// For array dumps and multiline content, preserve formatting
						if ( str_contains( $trimmed_line, '=>' ) ||
							 str_contains( $trimmed_line, '(' ) ||
							 str_contains( $trimmed_line, ')' ) ||
							 str_starts_with( $trimmed_line, '[' ) ||
							 str_starts_with( $trimmed_line, '#' ) ) {
							// Preserve array/object structure formatting
							$entry['message'] .= "\n" . $trimmed_line;
						} else {
							// Regular continuation text
							$entry['message'] .= ' ' . $trimmed_line;
						}
					}
					$entry['raw_line'] .= "\n" . $line;
				}
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
	 * Determine the type from content when no explicit type is provided.
	 *
	 * @param string $content The content to analyze.
	 *
	 * @return string The determined type.
	 */
	protected function determine_type_from_content( string $content ): string {
		$content_lower = strtolower( $content );

		// Check for specific patterns
		if ( str_starts_with( $content, 'Array' ) ) {
			return 'Array Dump';
		}

		if ( str_contains( $content_lower, 'automatic update' ) ) {
			return 'System Update';
		}

		if ( str_contains( $content_lower, 'cron' ) ) {
			return 'Cron Event';
		}

		if ( str_contains( $content_lower, 'exception' ) ) {
			return 'Exception';
		}

		if ( str_contains( $content_lower, 'scraping' ) ) {
			return 'Scraping Result';
		}

		if ( str_contains( $content_lower, 'error' ) ) {
			return 'Error';
		}

		if ( str_contains( $content_lower, 'warning' ) ) {
			return 'Warning';
		}

		if ( str_contains( $content_lower, 'notice' ) ) {
			return 'Notice';
		}

		// Default to 'Info' for unrecognized content
		return 'Info';
	}

	/**
	 * Determine log level from an error type.
	 *
	 * @param string $type Error type string.
	 *
	 * @return string
	 */
	protected function determine_log_level( string $type ): string {
		$type_lower = strtolower( $type );

		// Check for critical level errors first (fatal errors are critical)
		if ( str_contains( $type_lower, 'fatal' ) ||
			 str_contains( $type_lower, 'parse error' ) ) {
			return 'critical';
		}

		// Check for regular errors
		if ( str_contains( $type_lower, 'uncaught' ) ||
			 str_contains( $type_lower, 'error' ) ||
			 str_contains( $type_lower, 'exception' ) ) {
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

		// Special handling for our custom identified types
		if ( str_contains( $type_lower, 'cron' ) ) {
			return 'warning'; // Cron events are often warnings when they fail
		}

		if ( str_contains( $type_lower, 'array dump' ) ) {
			return 'debug'; // Array dumps are debug information
		}

		if ( str_contains( $type_lower, 'system update' ) ) {
			return 'info'; // System updates are informational
		}

		if ( str_contains( $type_lower, 'scraping' ) ) {
			return 'debug'; // Scraping results are debug information
		}

		return 'info';
	}

	/**
	 * Process stack trace for an entry.
	 *
	 * @param array $entry Log entry.
	 *
	 * @return array
	 */
	protected function process_stack_trace( array $entry ): array {
		if ( empty( $entry['stack_trace'] ) ) {
			return $entry;
		}

		$stack_trace_lines = array_filter(
			explode( "\n", trim( $entry['stack_trace'] ) ),
			fn( $line ) => ! empty( trim( $line ) )
		);

		$entry['stack_trace'] = [
			'stack_trace_lines'   => $stack_trace_lines,
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
	 *
	 * @return array
	 */
	protected function parse_stack_trace_frames( array $lines ): array {
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
					$frame['file_path'] = $frame_matches[1];
					$frame['line']     = (int) $frame_matches[2];
					$frame['function'] = $frame_matches[3];
				} else {
					$frame['function'] = $matches[2];
					$frame['file_path'] = null;
					$frame['line']     = null;
				}

				$frames[] = $frame;
			} else {
				// Generic frame for non-numbered lines
				$frames[] = [
					'number'   => $index,
					'raw'      => $line,
					'function' => $line,
					'file_path' => null,
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
	 *
	 * @return string
	 */
	protected function parse_timestamp( string $timestamp ): string {
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
	protected function filter_entries( array $entries, array $options ): array {
		$filtered = $entries;

		// Filter by level
		if ( ! empty( $options['level'] ) ) {
			$target_level = $this->log_levels[ $options['level'] ] ?? null;
			if ( null !== $target_level ) {
				$filtered = array_filter(
					$filtered,
					function ( $entry ) use ( $target_level ) {
						$entry_level = $this->log_levels[ $entry['level'] ] ?? 7; // Default to debug level
						return $entry_level <= $target_level;
					}
				);
			}
		}

		// Filter by search term (improved to search in more fields)
		if ( ! empty( $options['search'] ) ) {
			$search_term = strtolower( $options['search'] );
			$filtered = array_filter(
				$filtered,
				function ( $entry ) use ( $search_term ) {
					// Search in multiple fields
					$searchable_content = implode(
						' ',
						array_filter(
							[
								$entry['message'] ?? '',
								$entry['type'] ?? '',
								$entry['file_path'] ?? '',
								$entry['raw_line'] ?? '',
								$entry['stack_trace']['summary'] ?? '',
							]
						)
					);
					return str_contains( strtolower( $searchable_content ), $search_term );
				}
			);
		}

		// Filter by date range with improved validation
		if ( ! empty( $options['date_from'] ) || ! empty( $options['date_to'] ) ) {
			$date_from = $this->validate_date( $options['date_from'] ?? '1970-01-01' );
			$date_to = $this->validate_date( $options['date_to'] ?? '2099-12-31' );

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

		// Apply sorting if specified
		if ( ! empty( $options['sort_by'] ) ) {
			$sort_field = $options['sort_by'];
			$sort_order = $options['sort_order'] ?? 'desc';

			usort(
				$filtered,
				function ( $a, $b ) use ( $sort_field, $sort_order ) {
					$a_value = $this->get_sort_value( $a, $sort_field );
					$b_value = $this->get_sort_value( $b, $sort_field );

					$result = $a_value <=> $b_value;
					return $sort_order === 'desc' ? -$result : $result;
				}
			);
		}

		return array_values( $filtered );
	}

	/**
	 * Get sortable value from an entry.
	 *
	 * @param array  $entry Entry data.
	 * @param string $field Field to sort by.
	 * @return mixed
	 */
	protected function get_sort_value( array $entry, string $field ) {
		switch ( $field ) {
			case 'timestamp':
				return strtotime( $entry['timestamp'] );
			case 'level':
				return $this->log_levels[ $entry['level'] ] ?? 7;
			case 'type':
				return strtolower( $entry['type'] ?? '' );
			case 'message':
				return strtolower( $entry['message'] ?? '' );
			default:
				return $entry[ $field ] ?? '';
		}
	}

	/**
	 * Validate and normalize date string.
	 *
	 * @param string $date Date string.
	 * @return string
	 */
	protected function validate_date( string $date ): string {
		$timestamp = strtotime( $date );
		return $timestamp ? gmdate( 'Y-m-d', $timestamp ) : '1970-01-01';
	}

	/**
	 * Paginate entries.
	 *
	 * @param array $entries Filtered entries.
	 * @param array $options Pagination options.
	 * @return array
	 */
	protected function paginate_entries( array $entries, array $options ): array {
		$limit = max( 1, min( (int) ( $options['limit'] ?? 100 ), 1000 ) );
		$offset = max( 0, (int) ( $options['offset'] ?? 0 ) );

		return array_slice( $entries, $offset, $limit );
	}

	/**
	 * Get log file statistics.
	 *
	 * @param string|null $log_file Optional log file path.
	 * @return ServiceResponse
	 */
	public function get_log_statistics( ?string $log_file = null ): ServiceResponse {
		$log_file = $log_file ?? $this->log_file_path;

		if ( ! FileSystem::exists( $log_file ) ) {
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

		$file_size = FileSystem::size( $log_file );
		$last_modified = FileSystem::mtime( $log_file );

		$stats = [
			'file_path'        => $log_file,
			'file_size'        => $file_size,
			'file_size_human'  => FileSystem::format_size( $file_size ),
			'total_entries'    => $data['total'],
			'entries_with_stack_traces' => 0,
			'levels'           => [],
			'recent_errors'    => 0,
			'last_modified'    => $last_modified,
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

		if ( ! FileSystem::exists( $log_file ) ) {
			return ServiceResponse::success(
				[
					'message' => __( 'Log file does not exist.', 'debug-suite' ),
					'path'    => $log_file,
				]
			);
		}

		$result = FileSystem::put_contents( $log_file, '' );
		if ( ! $result ) {
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
	 * Wrapper method for compatibility with LogsService.
	 * Delegates to read_log_entries.
	 *
	 * @param array $options Log reading options.
	 * @return ServiceResponse
	 */
	public function get_log_entries( array $options = [] ): ServiceResponse {
		return $this->read_log_entries( $options );
	}

	/**
	 * Wrapper method for compatibility with LogsService.
	 * Delegates to get_log_statistics.
	 *
	 * @param string|null $log_file Optional log file path.
	 * @return ServiceResponse
	 */
	public function get_log_file_stats( ?string $log_file = null ): ServiceResponse {
		return $this->get_log_statistics( $log_file );
	}

	/**
	 * Check if a type string looks like a standard error type.
	 *
	 * @param string $type The type string to check.
	 * @return bool True if it looks like a standard error type, false otherwise.
	 */
	private function is_standard_error_type( string $type ): bool {
		$standard_types = [
			'PHP Fatal error',
			'PHP Parse error',
			'PHP Warning',
			'PHP Notice',
			'PHP Deprecated',
			'E_ERROR',
			'E_WARNING',
			'E_PARSE',
			'E_NOTICE',
			'E_CORE_ERROR',
			'E_CORE_WARNING',
			'E_COMPILE_ERROR',
			'E_COMPILE_WARNING',
			'E_USER_ERROR',
			'E_USER_WARNING',
			'E_USER_NOTICE',
			'E_USER_DEPRECATED',
			'E_STRICT',
			'E_RECOVERABLE_ERROR',
			'E_DEPRECATED',
			'EXCEPTION',
		];

		return in_array( $type, $standard_types, true );
	}
}
