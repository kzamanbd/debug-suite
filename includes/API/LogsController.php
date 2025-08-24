<?php
/**
 * File logs REST API controller for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\API;

use DebugSuite\Services\DebugLog\LogsService;
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
 * @since 1.0.0
 */
class LogsController extends RestController {

	private LogsService $service;
	protected $rest_base = 'logs';

	public function __construct( LogsService $service ) {
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
			'/' . $this->rest_base . '/clear',
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'clear_log_file' ],
				'permission_callback' => [ $this, 'permissions_check' ],
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
		$file_path = $request->get_param( 'file' );
		$result = $this->service->clear_log_file( $file_path );

		return $result->is_failure()
			? new WP_Error( $result->get_error_code(), $result->get_error_message(), [ 'status' => 500 ] )
			: rest_ensure_response(
				[
					'success' => true,
					'message' => __( 'Log file cleared successfully.', 'debug-suite' ),
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

		// Delegate to service
		$result = $this->service->get_raw_file_content( $file_path );

		// Transform service result to HTTP response
		if ( $result->is_failure() ) {
			$status_code = match ( $result->get_error_code() ) {
				'file_access_denied' => 403,
				'file_not_found'     => 404,
				'no_file_path'       => 400,
				default              => 500
			};

			return new WP_Error(
				$result->get_error_code(),
				$result->get_error_message(),
				[ 'status' => $status_code ]
			);
		}

		return rest_ensure_response( $result->get_data() );
	}
}
