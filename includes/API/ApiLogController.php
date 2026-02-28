<?php
/**
 * API Log REST API controller for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\API;

use DebugSuite\Services\ApiLogService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API log controller for Debug Suite.
 *
 * @since 1.2.0
 */
class ApiLogController extends RestController {

	/**
	 * API log service instance.
	 *
	 * @var ApiLogService
	 */
	private ApiLogService $service;

	/**
	 * Route base for endpoints.
	 *
	 * @var string
	 */
	protected $rest_base = 'api-logs';

	/**
	 * Constructor.
	 *
	 * @param ApiLogService $service API log service instance.
	 */
	public function __construct( ApiLogService $service ) {
		$this->service = $service;
	}

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// Get API logs
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_api_logs' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => $this->get_collection_params(),
			]
		);

		// Get single API log detail
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_api_log_detail' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'integer',
						'minimum'           => 1,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		// Bulk delete API logs
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/bulk',
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'bulk_delete_logs' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'ids' => [
						'required' => true,
						'type'     => 'array',
						'items'    => [
							'type'    => 'integer',
							'minimum' => 1,
						],
					],
				],
			]
		);

		// Clear all API logs
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/clear',
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'clear_all_logs' ],
				'permission_callback' => [ $this, 'permissions_check' ],
			]
		);
	}

	/**
	 * Get API logs.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_api_logs( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$options = [
			'search'     => $request->get_param( 'search' ) ?? '',
			'method'     => $request->get_param( 'method' ) ?? 'all',
			'status'     => $request->get_param( 'status' ) ?? 'all',
			'route'      => $request->get_param( 'route' ) ?? '',
			'sort_by'    => $request->get_param( 'sort_by' ) ?? 'created_at',
			'sort_order' => $request->get_param( 'sort_order' ) ?? 'desc',
			'per_page'   => $request->get_param( 'per_page' ) ?? 20,
			'page'       => $request->get_param( 'page' ) ?? 1,
		];

		$logs_result    = $this->service->get_api_log_entries( $options );
		$filters_result = $this->service->get_filter_options();
		$stats_result   = $this->service->get_api_statistics();

		if ( $logs_result->is_failure() ) {
			$status_code = match ( $logs_result->get_error_code() ) {
				'database_error'   => 500,
				'validation_error' => 400,
				default            => 500
			};

			return new WP_Error(
				$logs_result->get_error_code(),
				$logs_result->get_error_message(),
				[ 'status' => $status_code ]
			);
		}

		if ( $filters_result->is_failure() ) {
			return new WP_Error( 'filters_error', $filters_result->get_error_message(), [ 'status' => 500 ] );
		}

		if ( $stats_result->is_failure() ) {
			return new WP_Error( 'stats_error', $stats_result->get_error_message(), [ 'status' => 500 ] );
		}

		$data                   = $logs_result->get_data();
		$data['filter_options'] = $filters_result->get_data();
		$data['stats']          = $stats_result->get_data();

		return rest_ensure_response( $data );
	}

	/**
	 * Get single API log detail.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_api_log_detail( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$id     = (int) $request->get_param( 'id' );
		$result = $this->service->get_api_log_detail( $id );

		if ( $result->is_failure() ) {
			$status_code = match ( $result->get_error_code() ) {
				'not_found' => 404,
				default     => 500
			};

			return new WP_Error(
				$result->get_error_code(),
				$result->get_error_message(),
				[ 'status' => $status_code ]
			);
		}

		return rest_ensure_response( $result->get_data() );
	}

	/**
	 * Bulk delete API logs.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function bulk_delete_logs( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$ids = $request->get_param( 'ids' );

		if ( empty( $ids ) || ! is_array( $ids ) ) {
			return new WP_Error(
				'invalid_ids',
				__( 'Invalid or missing log IDs.', 'debug-suite' ),
				[ 'status' => 400 ]
			);
		}

		$result = $this->service->bulk_delete_logs( $ids );

		if ( $result->is_failure() ) {
			return new WP_Error( 'error', $result->get_error_message(), [ 'status' => 500 ] );
		}

		return rest_ensure_response( $result->get_data() );
	}

	/**
	 * Clear all API logs.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function clear_all_logs( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$result = $this->service->clear_all_logs();
		return rest_ensure_response( $result->get_data() );
	}

	/**
	 * Get collection parameters for the route.
	 *
	 * @return array
	 */
	public function get_collection_params(): array {
		return [
			'page'       => [
				'description'       => __( 'Current page of the collection.', 'debug-suite' ),
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			],
			'per_page'   => [
				'description'       => __( 'Maximum number of items to be returned in result set.', 'debug-suite' ),
				'type'              => 'integer',
				'default'           => 20,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
			],
			'search'     => [
				'description'       => __( 'Limit results to those matching a string.', 'debug-suite' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'method'     => [
				'description' => __( 'Filter by HTTP method.', 'debug-suite' ),
				'type'        => 'string',
				'default'     => 'all',
				'enum'        => [ 'all', 'GET', 'POST', 'PUT', 'DELETE', 'PATCH' ],
			],
			'status'     => [
				'description' => __( 'Filter by response status category.', 'debug-suite' ),
				'type'        => 'string',
				'default'     => 'all',
				'enum'        => [ 'all', 'success', 'redirect', 'client_error', 'server_error' ],
			],
			'route'      => [
				'description'       => __( 'Filter by route.', 'debug-suite' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'sort_by'    => [
				'description' => __( 'Sort collection by attribute.', 'debug-suite' ),
				'type'        => 'string',
				'default'     => 'created_at',
				'enum'        => [ 'created_at', 'method', 'route', 'response_status', 'duration' ],
			],
			'sort_order' => [
				'description' => __( 'Order sort attribute ascending or descending.', 'debug-suite' ),
				'type'        => 'string',
				'default'     => 'desc',
				'enum'        => [ 'asc', 'desc' ],
			],
		];
	}
}
