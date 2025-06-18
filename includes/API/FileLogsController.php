<?php
/**
 * File logs REST API controller for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\API;

use DebugSuite\Services\FileLogsService;
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
				'callback'            => [ $this, 'get_file_logs' ],
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
	}

	public function get_file_logs( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$options = [
			'limit' => $request->get_param( 'limit' ) ?? 100,
			'level_filter' => $request->get_param( 'level' ),
		];

		$result = $this->service->get_log_entries( $options );

		return $result->is_failure()
			? new WP_Error( $result->get_error_code(), $result->get_error_message(), [ 'status' => 500 ] )
			: rest_ensure_response( $result->get_data() );
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
}
