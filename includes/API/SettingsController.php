<?php
/**
 * Settings REST API controller for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\API;

use DebugSuite\Services\SettingsService;
use WP_Error;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simple settings controller for Debug Suite.
 *
 * @since 1.0.0
 */
class SettingsController extends RestController {

	private SettingsService $service;
	protected $rest_base = 'settings';

	public function __construct( SettingsService $service ) {
		$this->service = $service;
	}

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_settings' ],
					'permission_callback' => [ $this, 'permissions_check' ],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_settings' ],
					'permission_callback' => [ $this, 'permissions_check' ],
					'args'                => [
						'debug'          => [
							'type'        => 'boolean',
							'required'    => false,
							'description' => __( 'Enable or disable WP_DEBUG.', 'debug-suite' ),
						],
						'debug_log'     => [
							'type'        => 'boolean',
							'required'    => false,
							'description' => __( 'Enable or disable WP_DEBUG_LOG.', 'debug-suite' ),
						],
						'debug_display' => [
							'type'        => 'boolean',
							'required'    => false,
							'description' => __( 'Enable or disable WP_DEBUG_DISPLAY.', 'debug-suite' ),
						],
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/reset',
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'reset_settings' ],
				'permission_callback' => [ $this, 'permissions_check' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/full-view',
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'full_view' ],
				'permission_callback' => [ $this, 'permissions_check' ],
			]
		);
	}

	public function get_settings( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$result = $this->service->get_settings();

		return $result->is_failure()
			? new WP_Error( $result->get_error_code(), $result->get_error_message(), [ 'status' => 500 ] )
			: rest_ensure_response( $result->get_data() );
	}

	public function update_settings( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			return new WP_Error( 'invalid_params', __( 'Invalid parameters provided.', 'debug-suite' ), [ 'status' => 400 ] );
		}

		$settings = [];
		// Map request parameters to settings
		$values = [
			'debug' => 'WP_DEBUG',
			'debug_log' => 'WP_DEBUG_LOG',
			'debug_display' => 'WP_DEBUG_DISPLAY',
		];

		foreach ( $values as $paramKey => $settingKey ) {
			if ( isset( $params[ $paramKey ] ) ) {
				$settings[ $settingKey ] = $params[ $paramKey ] ? 'true' : 'false';
			}
		}

		$result = $this->service->update_settings( $settings );

		return $result->is_failure()
			? new WP_Error( $result->get_error_code(), $result->get_error_message(), [ 'status' => 500 ] )
			: rest_ensure_response(
				[
					'success' => true,
					'message' => __( 'Settings updated successfully.', 'debug-suite' ),
				]
			);
	}

	public function reset_settings( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$result = $this->service->reset_debug_settings();

		return $result->is_failure()
			? new WP_Error( $result->get_error_code(), $result->get_error_message(), [ 'status' => 500 ] )
			: rest_ensure_response(
				[
					'success' => true,
					'message' => __( 'Settings reset to defaults successfully.', 'debug-suite' ),
				]
			);
	}

	public function full_view( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$user_meta = get_user_meta( get_current_user_id(), 'debug_suite_full_view', true );
		$updated = update_user_meta( get_current_user_id(), 'debug_suite_full_view', ! $user_meta );

		return rest_ensure_response(
			[
				'success' => $updated,
				'data' => ! $user_meta,
				'message' => __( 'Full view request processed successfully.', 'debug-suite' ),
			]
		);
	}
}
