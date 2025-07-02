<?php
/**
 * File logs service for Debug Suite business logic.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Services\DebugLog;

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
	 * Log file discovery service.
	 *
	 * @var LogFileDiscoveryService
	 */
	private LogFileDiscoveryService $file_discovery;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->log_reader = new WPLogReaderService();
		$this->file_discovery = new LogFileDiscoveryService();
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
		return $this->file_discovery->get_supported_log_files();
	}

	/**
	 * Get raw file content for viewing.
	 *
	 * @param string|null $file_path Optional file path. If null, uses current debug log.
	 * @return ServiceResponse
	 */
	public function get_raw_file_content( ?string $file_path = null ): ServiceResponse {
		// Default to main debug log if no file specified
		if ( empty( $file_path ) ) {
			$file_path = ini_get( 'error_log' );
		}

		// Validate file path
		if ( empty( $file_path ) ) {
			return ServiceResponse::failure(
				__( 'No log file path provided or configured.', 'debug-suite' ),
				'no_file_path'
			);
		}

		// Check if file exists first
		if ( ! file_exists( $file_path ) ) {
			return ServiceResponse::failure(
				__( 'The requested log file was not found.', 'debug-suite' ),
				'file_not_found',
				[ 'path' => $file_path ]
			);
		}

		// Get file information
		$file_size = filesize( $file_path );
		$max_size = 50 * 1024 * 1024; // 50MB limit

		// For large files, limit the content to avoid memory issues
		if ( $file_size > $max_size ) {
			// Read only the last portion of the file
			$content = $this->read_file_tail( $file_path, $max_size );
			$truncated = true;
		} else {
			$content = file_get_contents( $file_path );
			$truncated = false;
		}

		if ( $content === false ) {
			return ServiceResponse::failure(
				__( 'Failed to read the log file.', 'debug-suite' ),
				'file_read_error',
				[ 'path' => $file_path ]
			);
		}

		return ServiceResponse::success([
			'content'           => $content,
			'filename'          => basename( $file_path ),
			'size'              => size_format( $file_size ),
			'size_bytes'        => $file_size,
			'last_modified'     => gmdate( 'Y-m-d H:i:s', filemtime( $file_path ) ),
			'truncated'         => $truncated,
			'max_size_reached'  => $file_size > $max_size,
			'max_size_limit'    => $max_size,
		]);
	}

	/**
	 * Read the tail of a large file efficiently.
	 *
	 * @param string $file_path The file path.
	 * @param int    $bytes     Number of bytes to read from the end.
	 * @return string|false
	 */
	private function read_file_tail( string $file_path, int $bytes ): false|string {
		$handle = fopen( $file_path, 'rb' );
		if ( ! $handle ) {
			return false;
		}

		// Seek to the position we want to start reading from
		fseek( $handle, -$bytes, SEEK_END );
		$content = fread( $handle, $bytes );
		fclose( $handle );

		return $content;
	}
}
