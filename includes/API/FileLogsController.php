<?php
/**
 * File logs REST API controller for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\API;

use DebugSuite\Services\DebugLog\FileLogsService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simple logs controller for Debug Suite.
 *
 * @since DEBUG_SUITE_SINCE
 */
class FileLogsController extends RestController {

	private FileLogsService $service;
	protected $rest_base = 'logs';

	public function __construct( FileLogsService $service ) {
		$this->service = $service;
	}

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_logs' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'page' => [
						'type'              => 'integer',
						'default'           => 1,
						'minimum'           => 1,
						'sanitize_callback' => 'absint',
					],
					'per_page' => [
						'type'              => 'integer',
						'default'           => 25,
						'minimum'           => 1,
						'maximum'           => 100,
						'sanitize_callback' => 'absint',
					],
					'level_filter' => [
						'type'              => 'string',
						'enum'              => [ 'critical', 'error', 'warning', 'notice', 'info', 'debug' ],
						'sanitize_callback' => 'sanitize_text_field',
					],
					'search' => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'date_from' => [
						'type'              => 'string',
						'format'            => 'date',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'date_to' => [
						'type'              => 'string',
						'format'            => 'date',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'sort_by' => [
						'type'              => 'string',
						'default'           => 'timestamp',
						'enum'              => [ 'timestamp', 'level', 'message' ],
						'sanitize_callback' => 'sanitize_text_field',
					],
					'sort_order' => [
						'type'              => 'string',
						'default'           => 'desc',
						'enum'              => [ 'asc', 'desc' ],
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/files',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_log_files' ],
				'permission_callback' => [ $this, 'permissions_check' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/clear',
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'clear_log_file' ],
				'permission_callback' => [ $this, 'permissions_check' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/stats',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_log_stats' ],
				'permission_callback' => [ $this, 'permissions_check' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/export',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'export_logs' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'format' => [
						'type'              => 'string',
						'default'           => 'json',
						'enum'              => [ 'json', 'csv', 'txt' ],
						'sanitize_callback' => 'sanitize_text_field',
					],
					'limit' => [
						'type'              => 'integer',
						'default'           => 1000,
						'minimum'           => 1,
						'maximum'           => 10000,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/advanced',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_advanced_logs' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'limit' => [
						'type'              => 'integer',
						'default'           => 1000,
						'minimum'           => 1,
						'maximum'           => 10000,
						'sanitize_callback' => 'absint',
					],
					'level_filter' => [
						'type'              => 'string',
						'enum'              => [ 'critical', 'error', 'warning', 'notice', 'info', 'debug' ],
						'sanitize_callback' => 'sanitize_text_field',
					],
					'search' => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'date_from' => [
						'type'              => 'string',
						'format'            => 'date',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'date_to' => [
						'type'              => 'string',
						'format'            => 'date',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);
	}

	public function get_logs( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$page = (int) ( $request->get_param( 'page' ) ?? 1 );
		$per_page = (int) ( $request->get_param( 'per_page' ) ?? 25 );
		$offset = ( $page - 1 ) * $per_page;

		$options = [
			'limit'        => $per_page,
			'offset'       => $offset,
			'level_filter' => $request->get_param( 'level_filter' ),
			'search'       => $request->get_param( 'search' ),
			'date_from'    => $request->get_param( 'date_from' ),
			'date_to'      => $request->get_param( 'date_to' ),
			'sort_by'      => $request->get_param( 'sort_by' ) ?? 'timestamp',
			'sort_order'   => $request->get_param( 'sort_order' ) ?? 'desc',
		];

		$result = $this->service->get_log_entries( $options );

		if ( $result->is_failure() ) {
			return new WP_Error( $result->get_error_code(), $result->get_error_message(), [ 'status' => 500 ] );
		}

		$data = $result->get_data();
		$total = $data['total'] ?? 0;
		$total_pages = ceil( $total / $per_page );

		return rest_ensure_response(
			[
				'entries'      => $data['entries'] ?? [],
				'total'        => $total,
				'total_pages'  => $total_pages,
				'current_page' => $page,
				'per_page'     => $per_page,
				'has_more'     => $page < $total_pages,
			]
		);
	}

	public function clear_log_file( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$result = $this->service->clear_log_file();

		return $result->is_failure()
			? new WP_Error( $result->get_error_code(), $result->get_error_message(), [ 'status' => 500 ] )
			: rest_ensure_response(
				[
					'success' => true,
					'message' => __( 'Log file cleared successfully.', 'debug-suite' ),
				]
			);
	}

	public function get_log_stats( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$result = $this->service->get_log_file_stats();

		return $result->is_failure()
			? new WP_Error( $result->get_error_code(), $result->get_error_message(), [ 'status' => 500 ] )
			: rest_ensure_response( $result->get_data() );
	}

	/**
	 * Export logs in various formats.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function export_logs( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$options = [
			'format' => $request->get_param( 'format' ) ?? 'json',
			'limit'  => $request->get_param( 'limit' ) ?? 1000,
		];

		$result = $this->service->export_logs( $options );

		if ( $result->is_failure() ) {
			return new WP_Error(
				$result->get_error_code(),
				$result->get_error_message(),
				[ 'status' => 500 ]
			);
		}

		$data = $result->get_data();

		// Set appropriate headers for download
		$filename = 'debug-logs-' . gmdate( 'Y-m-d-H-i-s' ) . '.' . $data['format'];

		return rest_ensure_response(
			[
				'success'  => true,
				'data'     => $data['data'],
				'format'   => $data['format'],
				'count'    => $data['count'],
				'filename' => $filename,
			]
		);
	}

	/**
	 * Get advanced log entries with enhanced filtering.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_advanced_logs( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$options = [
			'limit'        => $request->get_param( 'limit' ) ?? 1000,
			'level_filter' => $request->get_param( 'level_filter' ),
			'search'       => $request->get_param( 'search' ),
			'date_from'    => $request->get_param( 'date_from' ),
			'date_to'      => $request->get_param( 'date_to' ),
		];

		$result = $this->service->get_advanced_log_entries( $options );

		return $result->is_failure()
			? new WP_Error( $result->get_error_code(), $result->get_error_message(), [ 'status' => 500 ] )
			: rest_ensure_response( $result->get_data() );
	}

	/**
	 * Get available log files for sidebar navigation.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_log_files( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		// Get common log file locations
		$log_files = [];

		// Main debug.log
		$debug_log = WP_CONTENT_DIR . '/debug.log';
		if ( file_exists( $debug_log ) ) {
			$log_files[] = [
				'name' => 'debug.log',
				'path' => $debug_log,
				'size' => size_format( filesize( $debug_log ) ),
				'size_bytes' => filesize( $debug_log ),
				'modified' => gmdate( 'Y-m-d H:i:s', filemtime( $debug_log ) ),
				'type' => 'WordPress Debug',
				'is_current' => true,
			];
		}

		// Check for other common log files
		$other_logs = [
			WP_CONTENT_DIR . '/error.log' => 'PHP Error Log',
			WP_CONTENT_DIR . '/access.log' => 'Access Log',
			ABSPATH . 'error_log' => 'Root Error Log',
		];

		foreach ( $other_logs as $path => $type ) {
			if ( file_exists( $path ) ) {
				$log_files[] = [
					'name' => basename( $path ),
					'path' => $path,
					'size' => size_format( filesize( $path ) ),
					'size_bytes' => filesize( $path ),
					'modified' => gmdate( 'Y-m-d H:i:s', filemtime( $path ) ),
					'type' => $type,
					'is_current' => false,
				];
			}
		}

		return rest_ensure_response(
			[
				'files' => $log_files,
				'current_file' => $debug_log,
			]
		);
	}
}
