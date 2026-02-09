<?php
/**
 * Feature REST API controller for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\API;

use DebugSuite\Services\FeatureService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST controller for feature toggles (get/save to options).
 *
 * @since 1.0.0
 */
class FeatureController extends RestController {

	/**
	 * @var FeatureService
	 */
	private FeatureService $service;

	/**
	 * REST API base path.
	 *
	 * @var string
	 */
	protected $rest_base = 'features';

	/**
	 * Constructor.
	 *
	 * @param FeatureService $service Feature service.
	 */
	public function __construct( FeatureService $service ) {
		$this->service = $service;
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_features' ],
					'permission_callback' => [ $this, 'permissions_check' ],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_features' ],
					'permission_callback' => [ $this, 'permissions_check' ],
					'args'                => [
						'features' => [
							'type'        => 'object',
							'description' => __( 'Map of feature_id to enabled (boolean).', 'debug-suite' ),
							'required'    => false,
						],
						'feature_id' => [
							'type'        => 'string',
							'description' => __( 'Single feature ID to update (use with enabled).', 'debug-suite' ),
							'required'    => false,
						],
						'enabled' => [
							'type'        => 'boolean',
							'description' => __( 'Enabled state for the feature (use with feature_id).', 'debug-suite' ),
							'required'    => false,
						],
					],
				],
			]
		);
	}

	/**
	 * GET /features – return current feature states.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_features( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$features = $this->service->get_features();
		return rest_ensure_response( [ 'features' => $features ] );
	}

	/**
	 * PATCH/POST /features – update one or more feature states.
	 *
	 * Body: { feature_id: string, enabled: boolean } OR { features: { [id]: boolean } }
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_features( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			$params = [];
		}

		if ( ! empty( $params['feature_id'] ) && isset( $params['enabled'] ) ) {
			$updated = $this->service->update_feature(
				sanitize_key( $params['feature_id'] ),
				(bool) $params['enabled']
			);
		} elseif ( ! empty( $params['features'] ) && is_array( $params['features'] ) ) {
			$updated = $this->service->update_features( $params['features'] );
		} else {
			return new WP_Error(
				'missing_params',
				__( 'Provide feature_id and enabled, or features object.', 'debug-suite' ),
				[ 'status' => 400 ]
			);
		}

		if ( ! $updated ) {
			return new WP_Error(
				'update_failed',
				__( 'Failed to update feature(s).', 'debug-suite' ),
				[ 'status' => 500 ]
			);
		}

		return rest_ensure_response(
			[
				'success'  => true,
				'features' => $this->service->get_features(),
				'message'  => __( 'Features saved.', 'debug-suite' ),
			]
		);
	}
}
