<?php
/**
 * Overview REST API controller for Debug Suite dashboard functionality.
 *
 * @package DebugSuite
 */

namespace DebugSuite\API;

use DebugSuite\Services\OverviewService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Overview controller for Debug Suite dashboard functionality.
 *
 * Handles REST API endpoints for dashboard and overview data.
 *
 * @since 1.0.0
 */
class OverviewController extends RestController {

	/**
	 * Overview service for business logic.
	 *
	 * @var OverviewService
	 */
	private OverviewService $service;

	/**
	 * REST API base path.
	 *
	 * @var string
	 */
	protected $rest_base = 'overview';

	/**
	 * Constructor.
	 *
	 * @param OverviewService $service Overview service.
	 */
	public function __construct( OverviewService $service ) {
		$this->service = $service;
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// Dashboard statistics endpoint
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/stats',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_dashboard_stats' ],
				'permission_callback' => [ $this, 'permissions_check' ],
			]
		);
	}

	/**
	 * Get dashboard statistics.
	 *
	 * Aggregates various statistics for the dashboard overview.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_dashboard_stats( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		// Delegate to service
		$result = $this->service->get_dashboard_stats();

		// Transform service result to HTTP response
		if ( $result->is_failure() ) {
			return new WP_Error(
				$result->get_error_code(),
				$result->get_error_message(),
				[ 'status' => 500 ]
			);
		}

		return rest_ensure_response( $result->get_data() );
	}
}
