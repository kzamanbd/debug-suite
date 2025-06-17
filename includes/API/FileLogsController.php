<?php
/**
 * File logs REST API controller for Debug Suite with PSR-11 DI integration.
 *
 * @package DebugSuite
 */

namespace DebugSuite\API;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * File logs management REST API controller for Debug Suite.
 *
 * Handles REST API endpoints for retrieving and processing WordPress debug logs.
 *
 * @since DEBUG_SUITE_SINCE
 */
class FileLogsController extends RestController {
	/**
	 * Route base for logs endpoints.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var string
	 */
	protected $rest_base = 'logs';

	/**
	 * Register the routes for file logs endpoints.
	 *
	 * Registers REST API routes for retrieving WordPress debug log entries
	 * with proper authentication and permission checking.
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
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_file_logs' ],
					'permission_callback' => [ $this, 'permissions_check' ],
				],
			]
		);
	}

	/**
	 * Get Debug Suite file logs from debug.log.
	 *
	 * Reads and parses the WordPress debug.log file, extracting log entries
	 * with timestamps, types, messages, and stack traces. Categorizes entries
	 * by severity level (error, warning, notice, debug).
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response Response object containing parsed log entries.
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
