<?php
/**
 * Settings REST API controller for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\API;

use WP_Error;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * REST API controller for Debug Suite settings management.
 *
 * Handles REST API endpoints for managing Debug Suite settings including
 * updating wp-config.php constants for debug configuration.
 *
 * @since DEBUG_SUITE_SINCE
 */
class SettingsController extends RestController {
	/**
	 * Route base for settings endpoints.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var string
	 */
	protected $rest_base = 'settings';

	/**
	 * Register the routes for settings endpoints.
	 *
	 * Registers REST API routes for updating Debug Suite settings
	 * including debug, debug_log, and debug_display configuration.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_settings' ],
					'permission_callback' => [ $this, 'permissions_check' ],
					'args'                => [
						'debug'         => [
							'type'        => 'string',
							'default'     => 'false',
							'description' => __( 'Enable or disable debugging mode.', 'debug-suite' ),
							'enum'        => [ 'true', 'false' ],
						],
						'debug_log'     => [
							'type'        => 'string',
							'default'     => 'false',
							'description' => __( 'Enable or disable WP_DEBUG_LOG.', 'debug-suite' ),
							'enum'        => [ 'true', 'false' ],
						],
						'debug_display' => [
							'type'        => 'string',
							'default'     => 'false',
							'description' => __( 'Enable or disable WP_DEBUG_DISPLAY.', 'debug-suite' ),
							'enum'        => [ 'true', 'false' ],
						],
					],
				],
			]
		);
	}

	/**
	 * Update Debug Suite settings in wp-config.php.
	 *
	 * Updates debugging constants in the wp-config.php file based on the
	 * provided parameters. Handles WP_DEBUG, WP_DEBUG_LOG, and WP_DEBUG_DISPLAY
	 * constants with proper validation and error handling.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param WP_REST_Request $request The REST request object containing settings data.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, WP_Error on failure.
	 */
	public function update_settings( $request ): WP_REST_Response|WP_Error {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			return new WP_Error(
				'invalid_params',
				__( 'Invalid parameters provided.', 'debug-suite' ),
				[ 'status' => 400 ]
			);
		}
		$file_path = ABSPATH . 'wp-config.php';
		// Sanitize and validate settings here as needed.
		if ( ! file_exists( $file_path ) || ! is_writable( $file_path ) ) {
			return new WP_Error(
				'file_not_writable',
				__( 'The wp-config.php file is not writable or does not exist.', 'debug-suite' ),
				[ 'status' => 500 ]
			);
		}

		$is_debug         = $params['debug'] ?? 'false';
		$wp_debug_log     = $params['debug_log'] ?? 'false';
		$wp_debug_display = $params['debug_display'] ?? 'true';

		// Default debug settings
		$constants = [
			'WP_DEBUG'         => $is_debug,
			'WP_DEBUG_LOG'     => $wp_debug_log,
			'WP_DEBUG_DISPLAY' => $wp_debug_display,
		];

		$contents = file_get_contents( $file_path );
		foreach ( $constants as $constant => $value ) {
			// Regex to match existing define statements
			$pattern     = "/define\s*\(\s*['\"]{$constant}['\"]\s*,\s*.*?\);/";
			$replacement = "define('{$constant}', {$value});";

			if ( preg_match( $pattern, $contents ) ) {
				$contents = preg_replace( $pattern, $replacement, $contents );
			} else {
				// If not found, add it before the "stop editing" line
				$insertion_point = strpos( $contents, '/* That\'s all, stop editing!' );
				if ( $insertion_point !== false ) {
					$contents = substr_replace( $contents, $replacement . "\n", $insertion_point, 0 );
				}
			}
		}
		// Write the updated contents back to the file
		$is_update = file_put_contents( $file_path, $contents ) !== false;

		return rest_ensure_response( [ 'success' => $is_update ] );
	}
}
