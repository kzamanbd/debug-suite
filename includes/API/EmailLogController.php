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
		// Get email logs
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_email_logs' ],
				'permission_callback' => [ $this, 'permissions_check' ],
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
			'/' . $this->rest_base . '/bulk',
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'bulk_delete_emails' ],
				'permission_callback' => [ $this, 'permissions_check' ],
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
			'limit'  => $request->get_param( 'per_page' ) ?? 100,
			'offset' => ( ( $request->get_param( 'page' ) ?? 1 ) - 1 ) * ( $request->get_param( 'per_page' ) ?? 100 ),
		];

		$result = $this->service->get_email_log_entries( $options );

		if ( $result->is_failure() ) {
			return new WP_Error( 'error', $result->get_error_message(), [ 'status' => 500 ] );
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
		$result = $this->service->bulk_delete_emails( $ids );
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
				'type'    => 'integer',
				'default' => 1,
			],
			'per_page' => [
				'type'    => 'integer',
				'default' => 100,
			],
		];
	}
}
