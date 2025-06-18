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
	 * Constructor for FileLogsService.
	 */
	public function __construct() {
		$this->log_reader = new WPLogReaderService();
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
		// Set default limit for backward compatibility
		$options['limit'] = $options['limit'] ?? 100;

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
	 * Get advanced log entries with enhanced filtering (new method).
	 *
	 * @param array $options Advanced filtering options.
	 * @return ServiceResponse
	 */
	public function get_advanced_log_entries( array $options = [] ): ServiceResponse {
		// Use higher default limit for advanced features
		$options['limit'] = $options['limit'] ?? 1000;

		return $this->log_reader->get_log_entries( $options );
	}
}
