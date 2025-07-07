<?php
/**
 * REST API controller for onboarding functionality.
 *
 * @package DebugSuite
 */

namespace DebugSuite\API;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use DebugSuite\Services\OnboardingService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST controller for onboarding endpoints.
 *
 * @since 1.0.0
 */
class OnboardingController extends RestController {

	/**
	 * Route base for endpoints.
	 *
	 * @var string
	 */
	protected $rest_base = 'onboarding';

	/**
	 * Constructor.
	 *
	 * @param OnboardingService $service Onboarding service instance.
	 */
	public function __construct(
		private OnboardingService $service
	) {}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/status',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_status' ],
				'permission_callback' => [ $this, 'permissions_check' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/settings',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'save_settings' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'debug_mode' => [
						'required'          => true,
						'type'              => 'boolean',
						'description'       => __( 'Whether to enable debug mode', 'debug-suite' ),
					],
					'debug_log' => [
						'required'          => true,
						'type'              => 'boolean',
						'description'       => __( 'Whether to enable debug logging', 'debug-suite' ),
					],
					'debug_display' => [
						'required'          => true,
						'type'              => 'boolean',
						'description'       => __( 'Whether to enable debug display', 'debug-suite' ),
					],
				],
			]
		);
	}

	/**
	 * Get onboarding status.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_status( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$result = $this->service->get_status();

		if ( $result->is_failure() ) {
			return new WP_Error(
				$result->get_error_code(),
				$result->get_error_message(),
				[ 'status' => 400 ]
			);
		}

		return rest_ensure_response( $result->get_data() );
	}

	/**
	 * Save onboarding settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function save_settings( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$settings = [
			'debug_mode' => $request->get_param( 'debug_mode' ),
			'debug_log'  => $request->get_param( 'debug_log' ),
			'debug_display' => $request->get_param( 'debug_display' ),
		];

		$result = $this->service->save_settings( $settings );

		if ( $result->is_failure() ) {
			return new WP_Error(
				$result->get_error_code(),
				$result->get_error_message(),
				[ 'status' => 400 ]
			);
		}

		return rest_ensure_response( $result->get_data() );
	}
}
