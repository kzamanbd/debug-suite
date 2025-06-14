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
class FileLogsController extends RestController {
	/**
	 * Route base for settings.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $rest_base = 'logs';

	/**
	 * Register the routes for settings.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_file_logs' ],
					'permission_callback' => [ $this, 'permissions_check' ],
				],
			]
		);
	}

	/**
	 * Get Debug Suite File Logs.
	 *
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @return WP_REST_Response
	 * @since 1.0.0
	 */
	public function get_file_logs( $request ): WP_REST_Response {
		$log_file = WP_CONTENT_DIR . '/debug.log';

		if ( ! file_exists( $log_file ) ) {
			return rest_ensure_response(
				[
					'success' => false,
					'message' => 'Log file not found.',
				]
			);
		}

		$lines   = file( $log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		$entries = [];

		$entry = null;
		foreach ( $lines as $line ) {
			if ( preg_match( '/^\[(.*?)] (.*?): (.*?)( in (.*?) on line (\d+))?$/', $line, $matches ) ) {
				if ( $entry ) {
					$entries[] = $entry;
				}

				$type  = trim( $matches[2] );
				$level = 'info';

				if ( stripos( $type, 'fatal' ) !== false || stripos( $type, 'parse error' ) !== false || stripos( $type, 'uncaught' ) !== false ) {
					$level = 'error';
				} elseif ( stripos( $type, 'warning' ) !== false ) {
					$level = 'warning';
				} elseif ( stripos( $type, 'notice' ) !== false ) {
					$level = 'notice';
				} elseif ( stripos( $type, 'deprecated' ) !== false ) {
					$level = 'debug';
				}

				$entry = [
					'timestamp' => $matches[1],
					'type'      => $type,
					'level'     => $level,
					'message'   => trim( $matches[3] ),
					'file'      => $matches[5] ?? null,
					'line'      => $matches[6] ?? null,
					'trace'     => '',
				];
			} elseif ( $entry ) {
				$entry['trace'] .= $line . "\n";
			}
		}

		if ( $entry ) {
			$entries[] = $entry;
		}

		return rest_ensure_response(
			[
				'success' => true,
				'entries' => array_reverse( $entries ), // newest first
			]
		);
	}
}
