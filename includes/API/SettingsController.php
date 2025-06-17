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
 * @since DEBUG_SUITE_SINCE
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
	}

	public function get_settings( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$result = $this->service->get_current_debug_settings();

		return $result->is_failure()
			? new WP_Error( $result->get_error_code(), $result->get_error_message(), [ 'status' => 500 ] )
			: rest_ensure_response(
				[
					'success' => true,
					'settings' => $result->get_data(),
				]
			);
	}

	public function update_settings( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			return new WP_Error( 'invalid_params', __( 'Invalid parameters provided.', 'debug-suite' ), [ 'status' => 400 ] );
		}

		$settings = [];
		if ( isset( $params['debug'] ) ) {
			$settings['WP_DEBUG'] = $params['debug'];
		}
		if ( isset( $params['debug_log'] ) ) {
			$settings['WP_DEBUG_LOG'] = $params['debug_log'];
		}
		if ( isset( $params['debug_display'] ) ) {
			$settings['WP_DEBUG_DISPLAY'] = $params['debug_display'];
		}

		$result = $this->service->update_debug_settings( $settings );

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
}
