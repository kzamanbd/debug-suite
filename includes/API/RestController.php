<?php
/**
 * REST API base controller for Debug Suite with PSR-11 DI container integration.
 *
 * @package DebugSuite
 */

namespace DebugSuite\API;

use WP_REST_Controller;
use WP_REST_Request;
use WP_Error;
/**
 * Base REST API controller for Debug Suite.
 *
 * Provides common functionality for all Debug Suite REST API controllers
 * including namespace definition and permission checking.
 *
 * @since DEBUG_SUITE_SINCE
 */
class RestController extends WP_REST_Controller {

	/**
	 * Namespace for the REST API routes.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var string
	 */
	protected $namespace = 'debug-suite/v1';

	/**
	 * Checks if the current user has permission for the endpoint.
	 *
	 * Verifies that the current user has 'manage_options' capability,
	 * which is appropriate for Debug Suite's administrative functionality.
	 * Override in child classes for custom permission logic specific to
	 * individual endpoints.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param WP_REST_Request|null $request Optional. The REST request object.
	 *
	 * @return bool|WP_Error True if the request has access, WP_Error otherwise.
	 */
	public function permissions_check( $request ): bool|WP_Error {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to access this endpoint.', 'debug-suite' ),
				[ 'status' => 403 ]
			);
		}
		return true;
	}
}
