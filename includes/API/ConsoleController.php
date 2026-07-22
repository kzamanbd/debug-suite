<?php
/**
 * Console REST API controller for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\API;

use DebugSuite\Services\Console\ConsoleService;
use DebugSuite\Services\Console\ConsoleSettingsService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exposes PHP evaluation and per-user console preferences.
 *
 * @since 1.0.0
 */
class ConsoleController extends RestController {

	protected $rest_base = 'console';

	private ConsoleService $console;
	private ConsoleSettingsService $settings;

	public function __construct( ConsoleService $console, ConsoleSettingsService $settings ) {
		$this->console  = $console;
		$this->settings = $settings;
	}

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/execute',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'execute' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'input' => [
						'required'          => true,
						'type'              => 'string',
						'description'       => __( 'PHP code to evaluate.', 'debug-suite' ),
						'validate_callback' => [ $this, 'validate_input' ],
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/settings',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_settings' ],
					'permission_callback' => [ $this, 'permissions_check' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'save_settings' ],
					'permission_callback' => [ $this, 'permissions_check' ],
				],
			]
		);
	}

	/**
	 * Reject empty input.
	 *
	 * @param string $value Raw input.
	 * @return bool|WP_Error
	 */
	public function validate_input( $value ) {
		if ( is_string( $value ) && '' !== trim( $value ) ) {
			return true;
		}

		return new WP_Error( 'rest_invalid_param', __( 'Input is empty.', 'debug-suite' ), [ 'status' => 400 ] );
	}

	/**
	 * Evaluate PHP.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function execute( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$result = $this->console->execute( (string) $request->get_param( 'input' ) );

		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	/**
	 * Get the current user's console settings.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_settings( WP_REST_Request $request ): WP_REST_Response {
		return rest_ensure_response( $this->settings->get( get_current_user_id() ) );
	}

	/**
	 * Save the current user's console settings.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function save_settings( WP_REST_Request $request ): WP_REST_Response {
		$payload = [];

		if ( null !== $request->get_param( 'window_split' ) ) {
			$payload['window_split'] = (string) $request->get_param( 'window_split' );
		}
		if ( null !== $request->get_param( 'snippets' ) ) {
			$payload['snippets'] = (array) $request->get_param( 'snippets' );
		}

		return rest_ensure_response( $this->settings->save( get_current_user_id(), $payload ) );
	}
}
