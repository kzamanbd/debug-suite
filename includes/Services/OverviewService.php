<?php
/**
 * Overview service for Debug Suite dashboard functionality.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Services;

use DebugSuite\Core\ServiceResponse;
use DebugSuite\Interfaces\ServiceInterface;
use DebugSuite\Services\DebugLog\LogsService;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Overview service for dashboard and overview functionality.
 *
 * Aggregates data from multiple sources to provide comprehensive dashboard statistics.
 *
 * @since 1.0.0
 */
class OverviewService implements ServiceInterface {

	/**
	 * File logs service for log-related statistics.
	 *
	 * @var LogsService
	 */
	private LogsService $logs_service;

	/**
	 * Constructor.
	 *
	 * @param LogsService $logs_service Logs service.
	 */
	public function __construct( LogsService $logs_service ) {
		$this->logs_service = $logs_service;
	}

	/**
	 * Get dashboard statistics.
	 *
	 * Aggregates data from various sources to provide comprehensive dashboard overview.
	 *
	 * @return ServiceResponse Success with dashboard data or failure with error.
	 */
	public function get_dashboard_stats(): ServiceResponse {
		// Get log statistics
		$log_stats_result = $this->logs_service->get_log_file_stats();

		if ( $log_stats_result->is_failure() ) {
			return ServiceResponse::failure(
				__( 'Failed to retrieve log statistics.', 'debug-suite' ),
				'log_stats_error',
				[ 'original_error' => $log_stats_result->get_error_message() ]
			);
		}

		$log_stats = $log_stats_result->get_data();

		// Get system configuration
		$system_config = $this->get_system_configuration();

		// Aggregate dashboard data
		$dashboard_data = [
			'logs'   => $log_stats,
			'system' => $system_config,
		];

		return ServiceResponse::success( $dashboard_data );
	}

	/**
	 * Get system configuration information.
	 *
	 * @return array System configuration data.
	 */
	private function get_system_configuration(): array {
		return [
			'wp_debug'         => defined( 'WP_DEBUG' ) && WP_DEBUG,
			'wp_debug_log'     => defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG,
			'wp_debug_display' => defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY,
			'php_version'      => PHP_VERSION,
			'wp_version'       => get_bloginfo( 'version' ),
			'wp_memory_limit'  => $this->get_wp_memory_limit(),
			'php_memory_limit' => ini_get( 'memory_limit' ),
			'max_execution_time' => ini_get( 'max_execution_time' ),
		];
	}

	/**
	 * Get WordPress memory limit.
	 *
	 * @return string WordPress memory limit.
	 */
	private function get_wp_memory_limit(): string {
		if ( defined( 'WP_MEMORY_LIMIT' ) ) {
			return WP_MEMORY_LIMIT;
		}

		return __( 'Not defined', 'debug-suite' );
	}
}
