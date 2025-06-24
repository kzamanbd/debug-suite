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
						'default'           => 100,
						'minimum'           => 1,
						'maximum'           => 1000,
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
			'/' . $this->rest_base . '/supported-files',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_supported_log_files' ],
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
			'/' . $this->rest_base . '/raw',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_raw_file_content' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'file' => [
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
						'description'       => __( 'Log file path. If not provided, uses the current debug log file.', 'debug-suite' ),
					],
				],
			]
		);
	}

	public function get_logs( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$page = (int) ( $request->get_param( 'page' ) ?? 1 );
		$per_page = (int) ( $request->get_param( 'per_page' ) ?? 100 );
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
	 * Get available log files for sidebar navigation.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_supported_log_files( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$supported_log_files = [];
		// Main debug.log
		$debug_log = WP_CONTENT_DIR . '/debug.log';
		if ( file_exists( $debug_log ) ) {
			$supported_log_files[] = [
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
		$log_files = array_filter(
			$this->service->supported_log_files(),
			function ( $file ) {
				return ! empty( $file['path'] );
			}
		);

		return rest_ensure_response(
			[
				'files' => array_merge( $supported_log_files, $log_files ),
				'current_file' => $debug_log,
			]
		);
	}

	/**
	 * Get raw file content for the file viewer.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_raw_file_content( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$file_path = $request->get_param( 'file' );

		// Default to main debug log if no file specified
		if ( empty( $file_path ) ) {
			$file_path = WP_CONTENT_DIR . '/debug.log';
		}

		// Security check: only allow reading from allowed directories
		$allowed_paths = [
			WP_CONTENT_DIR,
			ABSPATH,
		];

		$real_path = realpath( $file_path );
		$is_allowed = false;

		foreach ( $allowed_paths as $allowed_path ) {
			if ( str_starts_with( $real_path, realpath( $allowed_path ) ) ) {
				$is_allowed = true;
				break;
			}
		}

		if ( ! $is_allowed ) {
			return new WP_Error(
				'file_access_denied',
				__( 'Access to this file is not allowed for security reasons.', 'debug-suite' ),
				[ 'status' => 403 ]
			);
		}

		// Check if file exists
		if ( ! file_exists( $file_path ) ) {
			return new WP_Error(
				'file_not_found',
				__( 'The requested log file was not found.', 'debug-suite' ),
				[ 'status' => 404 ]
			);
		}

		// For large files, limit the content to avoid memory issues
		$file_size = filesize( $file_path );
		$max_size = 50 * 1024 * 1024; // 50MB limit

		if ( $file_size > $max_size ) {
			// Read only the last 10MB of the file
			$content = $this->read_file_tail( $file_path, $max_size );
			$truncated = true;
		} else {
			$content = file_get_contents( $file_path );
			$truncated = false;
		}

		if ( $content === false ) {
			return new WP_Error(
				'file_read_error',
				__( 'Failed to read the log file.', 'debug-suite' ),
				[ 'status' => 500 ]
			);
		}

		return rest_ensure_response(
			[
				'content' => $content,
				'filename' => basename( $file_path ),
				'size' => size_format( $file_size ),
				'size_bytes' => $file_size,
				'last_modified' => gmdate( 'Y-m-d H:i:s', filemtime( $file_path ) ),
				'truncated' => $truncated,
				'max_size_reached' => $file_size > $max_size,
			]
		);
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
