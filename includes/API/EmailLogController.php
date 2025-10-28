<?php
/**
 * Email Log REST API controller for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\API;

use DebugSuite\Services\EmailLogService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email log controller for Debug Suite.
 *
 * @since 1.0.0
 */
class EmailLogController extends RestController {

	/**
	 * Email log service instance.
	 *
	 * @var EmailLogService
	 */
	private EmailLogService $service;

	/**
	 * Route base for endpoints.
	 *
	 * @var string
	 */
	protected $rest_base = 'email-logs';

	/**
	 * Constructor.
	 *
	 * @param EmailLogService $service Email log service instance.
	 */
	public function __construct( EmailLogService $service ) {
		$this->service = $service;
	}

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// Get email logs
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_email_logs' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => $this->get_collection_params(),
			]
		);

		// Removed dedicated /filters and /stats endpoints. Their data is merged into get_email_logs response.

		// Bulk delete emails
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/bulk',
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'bulk_delete_emails' ],
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

		// Clear all emails
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/clear',
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'clear_all_emails' ],
				'permission_callback' => [ $this, 'permissions_check' ],
			]
		);

		// Resend email
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>\d+)/resend',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'resend_email' ],
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
	}

	/**
	 * Get email logs.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_email_logs( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$options = [
			'search'     => $request->get_param( 'search' ) ?? '',
			'status'     => $request->get_param( 'status' ) ?? 'all',
			'receiver'   => $request->get_param( 'receiver' ) ?? '',
			'sort_by'    => $request->get_param( 'sort_by' ) ?? 'sent_date',
			'sort_order' => $request->get_param( 'sort_order' ) ?? 'desc',
			'per_page'   => $request->get_param( 'per_page' ) ?? 20,
			'page'       => $request->get_param( 'page' ) ?? 1,
		];

		$logs_result    = $this->service->get_email_log_entries( $options );
		$filters_result = $this->service->get_filter_options();
		$stats_result   = $this->service->get_email_statistics();

		if ( $logs_result->is_failure() ) {
			$status_code = match ( $logs_result->get_error_code() ) {
				'database_error'    => 500,
				'validation_error'  => 400,
				default             => 500
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

		$data = $logs_result->get_data();
		$data['filter_options'] = $filters_result->get_data();
		$data['stats']          = $stats_result->get_data();

		return rest_ensure_response( $data );
	}

	/**
	 * Get filter options.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_filter_options( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$result = $this->service->get_filter_options();

		if ( $result->is_failure() ) {
			return new WP_Error( 'error', $result->get_error_message(), [ 'status' => 500 ] );
		}

		return rest_ensure_response( $result->get_data() );
	}


	/**
	 * Bulk delete emails.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function bulk_delete_emails( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$ids = $request->get_param( 'ids' );

		if ( empty( $ids ) || ! is_array( $ids ) ) {
			return new WP_Error(
				'invalid_ids',
				__( 'Invalid or missing email IDs.', 'debug-suite' ),
				[ 'status' => 400 ]
			);
		}

		$result = $this->service->bulk_delete_emails( $ids );

		if ( $result->is_failure() ) {
			return new WP_Error( 'error', $result->get_error_message(), [ 'status' => 500 ] );
		}

		return rest_ensure_response( $result->get_data() );
	}

	/**
	 * Clear all emails.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function clear_all_emails( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$result = $this->service->clear_all_emails();
		return rest_ensure_response( $result->get_data() );
	}

	/**
	 * Resend email by ID.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function resend_email( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$email_id = (int) $request->get_param( 'id' );
		$result = $this->service->resend_email( $email_id );

		if ( $result->is_failure() ) {
			$status_code = match ( $result->get_error_code() ) {
				'email_not_found'   => 404,
				'send_failed'       => 500,
				default             => 500
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
			'status'     => [
				'description' => __( 'Limit results to emails with a specific status.', 'debug-suite' ),
				'type'        => 'string',
				'default'     => 'all',
				'enum'        => [ 'all', 'success', 'failed' ],
			],
			'receiver'   => [
				'description'       => __( 'Limit results to a specific receiver email.', 'debug-suite' ),
				'type'              => 'string',
				'format'            => 'email',
				'sanitize_callback' => 'sanitize_email',
			],
			'sort_by'    => [
				'description' => __( 'Sort collection by email attribute.', 'debug-suite' ),
				'type'        => 'string',
				'default'     => 'sent_date',
				'enum'        => [ 'sent_date', 'subject', 'to_email', 'status' ],
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
