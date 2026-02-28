<?php
/**
 * ApiLogService for Debug Suite.
 *
 * Captures REST API requests and responses, storing detailed logs
 * for debugging and monitoring purposes.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Services;

use DebugSuite\Core\ServiceResponse;
use DebugSuite\Interfaces\Hookable;
use DebugSuite\Interfaces\ServiceInterface;
use DebugSuite\Models\ApiLog;
use Exception;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API logging service.
 *
 * @since 1.2.0
 */
class ApiLogService implements ServiceInterface, Hookable {

	/**
	 * Request start time for duration calculation.
	 *
	 * @var float
	 */
	private float $start_time = 0;

	/**
	 * Captured request data.
	 *
	 * @var array
	 */
	private array $request_data = [];

	/**
	 * Whether we are currently logging (prevents recursion).
	 *
	 * @var bool
	 */
	private bool $is_logging = false;

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_filter( 'rest_pre_dispatch', [ $this, 'capture_request' ], 10, 3 );
		add_filter( 'rest_post_dispatch', [ $this, 'log_response' ], 999, 3 );
	}

	/**
	 * Capture REST API request data before dispatch.
	 *
	 * @param mixed           $result  Response to replace the requested version with. Can be anything
	 *                                 a normal endpoint can return, or null to not hijack the request.
	 * @param WP_REST_Server  $server  Server instance.
	 * @param WP_REST_Request $request Request used to generate the response.
	 * @return mixed Unchanged $result.
	 */
	public function capture_request( $result, WP_REST_Server $server, WP_REST_Request $request ) {
		// Skip if already logging (prevents recursion)
		if ( $this->is_logging ) {
			return $result;
		}

		$route = $request->get_route();

		// Skip our own debug-suite API calls to prevent infinite recursion
		if ( str_starts_with( $route, '/debug-suite/' ) ) {
			return $result;
		}

		$this->start_time = microtime( true );

		// Capture request body: JSON params take priority, then body params ($_POST), then raw body
		$body_params = $request->get_json_params();
		if ( empty( $body_params ) ) {
			$body_params = $request->get_body_params();
		}
		if ( empty( $body_params ) && $request->get_body() ) {
			$body_params = $request->get_body();
		}

		// Capture all parameters (query + body + URL params merged)
		$all_params = $request->get_params();

		$this->request_data = [
			'method'          => $request->get_method(),
			'route'           => $route,
			'url'             => rest_url( $route ),
			'request_headers' => $request->get_headers(),
			'request_body'    => $body_params ?? [],
			'request_params'  => ! empty( $all_params ) ? $all_params : $request->get_query_params(),
			'user_id'         => get_current_user_id(),
			'user_ip'         => self::get_client_ip(),
			'source'          => self::extract_namespace( $route ),
		];

		return $result;
	}

	/**
	 * Log REST API response after dispatch.
	 *
	 * @param WP_HTTP_Response $result  Result to send to the client. Usually a WP_REST_Response.
	 * @param WP_REST_Server   $server  Server instance.
	 * @param WP_REST_Request  $request Request used to generate the response.
	 * @return WP_HTTP_Response Unchanged $result.
	 */
	public function log_response( WP_HTTP_Response $result, WP_REST_Server $server, WP_REST_Request $request ) {
		// Skip if no request data was captured
		if ( empty( $this->request_data ) ) {
			return $result;
		}

		// Prevent recursion
		$this->is_logging = true;

		$duration = $this->start_time > 0
			? ( microtime( true ) - $this->start_time ) * 1000
			: 0;

		$response_body = '';
		if ( $result instanceof WP_REST_Response ) {
			$data = $result->get_data();
			$response_body = is_string( $data ) ? $data : wp_json_encode( $data );
		}

		$log_data = array_merge(
            $this->request_data,
            [
				'response_status'  => $result->get_status(),
				'response_headers' => $result->get_headers(),
				'response_body'    => $response_body ?? '',
				'duration'         => $duration,
			]
        );

		ApiLog::create_from_request( $log_data );

		// Reset state
		$this->request_data = [];
		$this->start_time   = 0;
		$this->is_logging   = false;

		return $result;
	}

	/**
	 * Get API log entries with filtering and pagination.
	 *
	 * @param array $options Query options.
	 * @return ServiceResponse
	 */
	public function get_api_log_entries( array $options = [] ): ServiceResponse {
		$defaults = [
			'search'     => '',
			'method'     => 'all',
			'status'     => 'all',
			'route'      => '',
			'sort_by'    => 'created_at',
			'sort_order' => 'desc',
			'per_page'   => 20,
			'page'       => 1,
		];

		$options = wp_parse_args( $options, $defaults );

		$options['per_page']   = max( 1, min( 100, (int) $options['per_page'] ) );
		$options['page']       = max( 1, (int) $options['page'] );
		$options['sort_order'] = strtolower( $options['sort_order'] ) === 'asc' ? 'asc' : 'desc';

		$offset = ( $options['page'] - 1 ) * $options['per_page'];

		$filters = [
			'search'     => sanitize_text_field( $options['search'] ),
			'method'     => in_array( $options['method'], [ 'all', 'GET', 'POST', 'PUT', 'DELETE', 'PATCH' ], true ) ? $options['method'] : 'all',
			'status'     => in_array( $options['status'], [ 'all', 'success', 'redirect', 'client_error', 'server_error' ], true ) ? $options['status'] : 'all',
			'route'      => sanitize_text_field( $options['route'] ),
			'sort_by'    => in_array( $options['sort_by'], [ 'created_at', 'method', 'route', 'response_status', 'duration' ], true ) ? $options['sort_by'] : 'created_at',
			'sort_order' => strtoupper( $options['sort_order'] ),
			'limit'      => $options['per_page'],
			'offset'     => $offset,
		];

		try {
			$entries     = ApiLog::get_filtered( $filters );
			$total_count = ApiLog::count_filtered( $filters );

			$formatted_entries = array_map(
				static function ( ApiLog $entry ) {
					return $entry->to_list_array();
				},
				$entries
			);

			$total_pages  = ceil( $total_count / $options['per_page'] );
			$current_page = $options['page'];

			return ServiceResponse::success(
				[
					'entries'    => $formatted_entries,
					'pagination' => [
						'current_page' => $current_page,
						'total_pages'  => $total_pages,
						'total_items'  => $total_count,
						'per_page'     => $options['per_page'],
						'from'         => $total_count > 0 ? $offset + 1 : 0,
						'to'           => min( $offset + $options['per_page'], $total_count ),
						'has_more'     => $current_page < $total_pages,
					],
					'total_count' => $total_count,
				]
			);
		} catch ( Exception $e ) {
			return ServiceResponse::failure(
				__( 'Failed to retrieve API logs.', 'debug-suite' ),
				'database_error',
				[ 'error' => $e->getMessage() ]
			);
		}
	}

	/**
	 * Get a single API log entry with full details.
	 *
	 * @param int $id Log ID.
	 * @return ServiceResponse
	 */
	public function get_api_log_detail( int $id ): ServiceResponse {
		$entry = ApiLog::find( $id );

		if ( ! $entry ) {
			return ServiceResponse::failure(
				__( 'API log not found.', 'debug-suite' ),
				'not_found'
			);
		}

		return ServiceResponse::success( $entry->to_array() );
	}

	/**
	 * Get filter options for dropdowns.
	 *
	 * @return ServiceResponse
	 */
	public function get_filter_options(): ServiceResponse {
		try {
			$routes = ApiLog::get_unique_routes();

			return ServiceResponse::success(
				[
					'routes'  => array_map(
						static function ( $route ) {
							return [
								'value' => $route,
								'label' => $route,
							];
						},
						$routes
					),
					'methods' => [
						[
							'value' => 'all',
							'label' => __( 'All Methods', 'debug-suite' ),
						],
						[
							'value' => 'GET',
							'label' => 'GET',
						],
						[
							'value' => 'POST',
							'label' => 'POST',
						],
						[
							'value' => 'PUT',
							'label' => 'PUT',
						],
						[
							'value' => 'DELETE',
							'label' => 'DELETE',
						],
						[
							'value' => 'PATCH',
							'label' => 'PATCH',
						],
					],
					'statuses' => [
						[
							'value' => 'all',
							'label' => __( 'All Statuses', 'debug-suite' ),
						],
						[
							'value' => 'success',
							'label' => __( '2xx Success', 'debug-suite' ),
						],
						[
							'value' => 'redirect',
							'label' => __( '3xx Redirect', 'debug-suite' ),
						],
						[
							'value' => 'client_error',
							'label' => __( '4xx Client Error', 'debug-suite' ),
						],
						[
							'value' => 'server_error',
							'label' => __( '5xx Server Error', 'debug-suite' ),
						],
					],
				]
			);
		} catch ( Exception $e ) {
			return ServiceResponse::failure(
				__( 'Failed to retrieve filter options.', 'debug-suite' ),
				'database_error',
				[ 'error' => $e->getMessage() ]
			);
		}
	}

	/**
	 * Get API statistics.
	 *
	 * @return ServiceResponse
	 */
	public function get_api_statistics(): ServiceResponse {
		$stats = ApiLog::get_statistics();
		return ServiceResponse::success( $stats );
	}

	/**
	 * Delete multiple API logs.
	 *
	 * @param array $log_ids Array of API log IDs.
	 * @return ServiceResponse
	 */
	public function bulk_delete_logs( array $log_ids ): ServiceResponse {
		if ( empty( $log_ids ) ) {
			return ServiceResponse::failure( __( 'No log IDs provided.', 'debug-suite' ) );
		}

		$deleted_count = ApiLog::delete_by_ids( $log_ids );

		return ServiceResponse::success(
			[
				'deleted_count' => $deleted_count,
				// translators: %d is the number of deleted logs.
				'message'       => sprintf( __( '%d API logs deleted.', 'debug-suite' ), $deleted_count ),
			]
		);
	}

	/**
	 * Clear all API logs.
	 *
	 * @return ServiceResponse
	 */
	public function clear_all_logs(): ServiceResponse {
		ApiLog::truncate();
		return ServiceResponse::success( [ 'message' => __( 'All API logs cleared.', 'debug-suite' ) ] );
	}

	/**
	 * Extract namespace from a REST route.
	 *
	 * @param string $route REST route.
	 * @return string
	 */
	private static function extract_namespace( string $route ): string {
		$parts = explode( '/', ltrim( $route, '/' ) );

		if ( count( $parts ) >= 2 ) {
			return $parts[0] . '/' . $parts[1];
		}

		return $parts[0] ?? '';
	}

	/**
	 * Get the client IP address.
	 *
	 * @return string
	 */
	private static function get_client_ip(): string {
		$ip = '';

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			$ip  = trim( $ips[0] );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}
}
