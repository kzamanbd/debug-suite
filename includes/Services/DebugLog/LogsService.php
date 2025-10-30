<?php
/**
 * File logs service for Debug Suite business logic.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Services\DebugLog;

use DebugSuite\Core\FileSystem;
use DebugSuite\Core\ServiceResponse;
use DebugSuite\Interfaces\ServiceInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Enhanced file logs service using advanced log reader for WordPress debug log operations.
 *
 * @since 1.0.0
 */
class LogsService implements ServiceInterface {

	/**
	 * Constructor with dependency injection.
	 *
	 * @param WPLogReaderService  $log_reader    Advanced log reader service.
	 * @param LogDiscoveryService $log_discovery Log file discovery service.
	 */
	public function __construct(
		private readonly WPLogReaderService $log_reader,
		private readonly LogDiscoveryService $log_discovery
	) {}

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
	 * @param string $file_path Path to the log file to clear. If not provided, uses the default debug log.
	 *
	 * @return ServiceResponse
	 */
	public function clear_log_file( string $file_path = '' ): ServiceResponse {
		return $this->log_reader->clear_log_file( $file_path );
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
	 * Get paths to supported log files.
	 *
	 * @return array
	 */
	public function supported_log_files(): array {
		return $this->log_discovery->get_supported_log_files();
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
		if ( ! FileSystem::exists( $file_path ) ) {
			return ServiceResponse::failure(
				__( 'The requested log file was not found.', 'debug-suite' ),
				'file_not_found',
				[ 'path' => $file_path ]
			);
		}

		// Check if file is readable
		if ( ! FileSystem::is_readable( $file_path ) ) {
			return ServiceResponse::failure(
				__( 'Access to this file is not allowed.', 'debug-suite' ),
				'file_access_denied',
				[ 'path' => $file_path ]
			);
		}

		// Get file information
		$file_size = FileSystem::size( $file_path );
		$max_size = 50 * 1024 * 1024; // 50MB limit

		// For large files, limit the content to avoid memory issues
		if ( $file_size > $max_size ) {
			// Read only the last portion of the file
			$content = FileSystem::read_tail( $file_path, $max_size );
			$truncated = true;
		} else {
			$content = FileSystem::get_contents( $file_path );
			$truncated = false;
		}

		if ( $content === false ) {
			return ServiceResponse::failure(
				__( 'Failed to read the log file.', 'debug-suite' ),
				'file_read_error',
				[ 'path' => $file_path ]
			);
		}

		return ServiceResponse::success(
			[
				'content'           => $content,
				'filename'          => basename( $file_path ),
				'size'              => FileSystem::format_size( $file_size ),
				'size_bytes'        => $file_size,
				'last_modified'     => gmdate( 'Y-m-d H:i:s', FileSystem::mtime( $file_path ) ),
				'truncated'         => $truncated,
				'max_size_reached'  => $file_size > $max_size,
				'max_size_limit'    => $max_size,
			]
		);
	}
}
