<?php
/**
 * REST API base controller for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\API;

use WP_REST_Controller;
use WP_Error;
use DebugSuite\Interfaces\Hookable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simple base REST controller for Debug Suite.
 *
 * @since 1.0.0
 */
class RestController extends WP_REST_Controller implements Hookable {

	protected $namespace = 'debug-suite/v1';

	/**
	 * Register hooks for WordPress.
	 *
	 * Automatically registers REST API routes when the controller
	 * is resolved from the container.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function permissions_check( $request ): bool|WP_Error {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'rest_not_logged_in', __( 'You are not currently logged in.', 'debug-suite' ), [ 'status' => 401 ] );
		}

		return current_user_can( 'manage_options' )
			? true
			: new WP_Error( 'rest_forbidden', __( 'You do not have permission to access this endpoint.', 'debug-suite' ), [ 'status' => 403 ] );
	}
}
