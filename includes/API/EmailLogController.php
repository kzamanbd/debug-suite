<?php
/**
 * Email Log REST API controller for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\API;

use DebugSuite\Core\ServiceResponse;
use DebugSuite\Services\EmailLog\EmailLogService;
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
		// Get email logs with filtering
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

		// Get email stats
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/stats',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_email_stats' ],
				'permission_callback' => [ $this, 'permissions_check' ],
			]
		);

		// Bulk delete emails
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/bulk-delete',
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'bulk_delete_emails' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'ids' => [
						'type'     => 'array',
						'items'    => [ 'type' => 'integer' ],
						'required' => true,
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
						'type'              => 'integer',
						'required'          => true,
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
			'limit'      => $request->get_param( 'per_page' ) ?? 100,
			'offset'     => ( ( $request->get_param( 'page' ) ?? 1 ) - 1 ) * ( $request->get_param( 'per_page' ) ?? 100 ),
			'receiver'   => $request->get_param( 'receiver' ),
			'status'     => $request->get_param( 'status' ) ?? 'all',
			'search'     => $request->get_param( 'search' ),
			'date_from'  => $request->get_param( 'date_from' ),
			'date_to'    => $request->get_param( 'date_to' ),
			'sort_by'    => $request->get_param( 'sort_by' ) ?? 'sent_date',
			'sort_order' => $request->get_param( 'sort_order' ) ?? 'desc',
		];

		$result = $this->service->get_email_log_entries( $options );

		if ( $result->is_failure() ) {
			return $this->convert_service_failure_to_error( $result );
		}

		return rest_ensure_response( $result->get_data() );
	}

	/**
	 * Get email statistics.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_email_stats( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$result = $this->service->get_email_statistics();

		if ( $result->is_failure() ) {
			return $this->convert_service_failure_to_error( $result );
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
				__( 'Email IDs are required.', 'debug-suite' ),
				[ 'status' => 400 ]
			);
		}

		$result = $this->service->bulk_delete_emails( $ids );

		if ( $result->is_failure() ) {
			return $this->convert_service_failure_to_error( $result );
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

		if ( $result->is_failure() ) {
			return $this->convert_service_failure_to_error( $result );
		}

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

		if ( $email_id <= 0 ) {
			return new WP_Error(
				'invalid_email_id',
				__( 'Invalid email ID.', 'debug-suite' ),
				[ 'status' => 400 ]
			);
		}

		$result = $this->service->resend_email( $email_id );

		if ( $result->is_failure() ) {
			return $this->convert_service_failure_to_error( $result );
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
			'receiver' => [
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_email',
			],
			'status' => [
				'type'              => 'string',
				'enum'              => [ 'all', 'success', 'failed' ],
				'default'           => 'all',
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
				'default'           => 'sent_date',
				'enum'              => [ 'sent_date', 'to_email', 'subject', 'status' ],
				'sanitize_callback' => 'sanitize_text_field',
			],
			'sort_order' => [
				'type'              => 'string',
				'default'           => 'desc',
				'enum'              => [ 'asc', 'desc' ],
				'sanitize_callback' => 'sanitize_text_field',
			],
		];
	}

	/**
	 * Convert ServiceResponse failure to WP_Error with simplified mapping.
	 *
	 * @param ServiceResponse $result Failed service result.
	 * @return WP_Error
	 */
	private function convert_service_failure_to_error( ServiceResponse $result ): WP_Error {
		$status_map = [
			'invalid_input' => 400,
			'validation_error' => 400,
			'not_found' => 404,
			'permission_denied' => 403,
		];

		$status = $status_map[ $result->get_error_code() ] ?? 500;

		return new WP_Error(
			$result->get_error_code(),
			$result->get_error_message(),
			[ 'status' => $status ]
		);
	}
}
