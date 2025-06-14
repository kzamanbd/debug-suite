<?php
/**
 * Settings REST API controller for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\API;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class SettingsController
 *
 * Handles REST API endpoints for Debug Suite settings.
 *
 * @since 1.0.0
 */
class SettingsController extends RestController {
	/**
	 * Route base for settings.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $rest_base = 'settings';

	/**
	 * Register the routes for settings.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_settings' ],
					'permission_callback' => [ $this, 'permissions_check' ],
				],
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'update_settings' ],
					'permission_callback' => [ $this, 'permissions_check' ],
				],
			]
		);
	}

	/**
	 * Get Debug Suite settings.
	 *
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @return WP_REST_Response
	 * @since 1.0.0
	 */
	public function get_settings( $request ): WP_REST_Response {
		$settings = get_option( 'debug_suite_settings', [] );
		return new WP_REST_Response( $settings, 200 );
	}

	/**
	 * Update Debug Suite settings.
	 *
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @return WP_REST_Response
	 * @since 1.0.0
	 */
	public function update_settings( $request ): WP_REST_Response {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			return new WP_REST_Response( [ 'error' => __( 'Invalid settings data.', 'debug-suite' ) ], 400 );
		}
		// Sanitize and validate settings here as needed.
		update_option( 'debug_suite_settings', $params );
		return new WP_REST_Response( [ 'success' => true ], 200 );
	}
}
