<?php
/**
 * ApiLog Model for Debug Suite.
 *
 * Handles all database operations for REST API logs including CRUD operations,
 * filtering, searching, and statistics.
 *
 * @package DebugSuite
 *
 * @method static static|null create_from_request( array $data )
 * @method static array       get_statistics()
 * @method static array       get_filtered( array $filters = [] )
 * @method static int         count_filtered( array $filters = [] )
 * @method static array       get_unique_routes()
 * @method static int         delete_by_ids( array $ids )
 */

namespace DebugSuite\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ApiLog model class.
 *
 * @property int    $id               Unique identifier for the API log.
 * @property string $method           HTTP method (GET, POST, PUT, DELETE, PATCH).
 * @property string $route            REST API route.
 * @property string $url              Full request URL.
 * @property array  $request_headers  Request headers (auto-cast from JSON).
 * @property array  $request_body     Request body (auto-cast from JSON).
 * @property array  $request_params   Request parameters (auto-cast from JSON).
 * @property int    $response_status  HTTP response status code.
 * @property array  $response_headers Response headers (auto-cast from JSON).
 * @property string $response_body    Response body (truncated).
 * @property float  $duration         Request duration in milliseconds.
 * @property int    $user_id          WordPress user ID who made the request.
 * @property string $user_ip          IP address of the requester.
 * @property string $source           Source/namespace of the REST route.
 * @property string $created_at       Timestamp when the log was created.
 * @property string $updated_at       Timestamp when the log was last updated.
 *
 * @since 1.2.0
 */
class ApiLog extends BaseModel {

	/**
	 * Table name without prefix.
	 *
	 * @var string
	 */
	protected static string $table = 'api_logs';

	/**
	 * Primary key column name.
	 *
	 * @var string
	 */
	protected static string $primary_key = 'id';

	/**
	 * Fillable columns for mass assignment.
	 *
	 * @var array
	 */
	protected static array $fillable = [
		'id',
		'method',
		'route',
		'url',
		'request_headers',
		'request_body',
		'request_params',
		'response_status',
		'response_headers',
		'response_body',
		'duration',
		'user_id',
		'user_ip',
		'source',
		'created_at',
		'updated_at',
	];

	/**
	 * Timestamps columns.
	 *
	 * @var array
	 */
	protected static array $timestamps = [ 'created_at', 'updated_at' ];

	/**
	 * Attribute casting definitions.
	 *
	 * @var array<string, string>
	 */
	protected static array $casts = [
		'id'               => 'integer',
		'request_headers'  => 'json',
		'request_body'     => 'json',
		'request_params'   => 'json',
		'response_headers' => 'json',
		'response_status'  => 'integer',
		'duration'         => 'float',
		'user_id'          => 'integer',
	];

	/**
	 * HTTP method constants.
	 */
	const METHOD_GET    = 'GET';
	const METHOD_POST   = 'POST';
	const METHOD_PUT    = 'PUT';
	const METHOD_DELETE = 'DELETE';
	const METHOD_PATCH  = 'PATCH';

	/**
	 * Max response body size to store (64KB).
	 */
	const MAX_BODY_SIZE = 65536;

	/**
	 * Create API log from request/response data.
	 *
	 * JSON fields (request_headers, request_body, etc.) are automatically
	 * encoded via $casts when set through fill()/set_attribute().
	 *
	 * @param array $data Request and response data.
	 * @return static|null
	 */
	protected function create_from_request( array $data ): ?static {
		$attributes = [
			'method'           => strtoupper( $data['method'] ?? '' ),
			'route'            => $data['route'] ?? '',
			'url'              => $data['url'] ?? '',
			'request_headers'  => $data['request_headers'] ?? [],
			'request_body'     => $data['request_body'] ?? [],
			'request_params'   => $data['request_params'] ?? [],
			'response_status'  => $data['response_status'] ?? 0,
			'response_headers' => $data['response_headers'] ?? [],
			'response_body'    => self::truncate_body( $data['response_body'] ?? '' ),
			'duration'         => round( (float) ( $data['duration'] ?? 0 ), 2 ),
			'user_id'          => $data['user_id'] ?? 0,
			'user_ip'          => $data['user_ip'] ?? '',
			'source'           => $data['source'] ?? '',
		];

		return $this->create( $attributes );
	}

	/**
	 * Get API log statistics.
	 *
	 * @return array
	 */
	protected function get_statistics(): array {
		$stats = $this->query()
			->select_raw(
				'COUNT(*) as total_requests, '
				. 'SUM(CASE WHEN response_status >= 200 AND response_status < 300 THEN 1 ELSE 0 END) as successful, '
				. 'SUM(CASE WHEN response_status >= 400 THEN 1 ELSE 0 END) as failed, '
				. 'ROUND(AVG(duration), 2) as avg_duration'
			)
			->get_row();

		if ( ! $stats ) {
			return [
				'total_requests' => 0,
				'successful'     => 0,
				'failed'         => 0,
				'avg_duration'   => 0,
			];
		}

		return [
			'total_requests' => (int) $stats['total_requests'],
			'successful'     => (int) $stats['successful'],
			'failed'         => (int) $stats['failed'],
			'avg_duration'   => (float) $stats['avg_duration'],
		];
	}

	/**
	 * Get filtered API logs with search and pagination.
	 *
	 * @param array $filters Filter options.
	 * @return array
	 */
	protected function get_filtered( array $filters = [] ): array {
		$defaults = [
			'search'     => '',
			'method'     => 'all',
			'status'     => 'all',
			'route'      => '',
			'sort_by'    => 'created_at',
			'sort_order' => 'DESC',
			'limit'      => 20,
			'offset'     => 0,
		];

		$filters = wp_parse_args( $filters, $defaults );

		$query = $this->query();
		$this->apply_filters( $query, $filters );

		$sort_by = sanitize_sql_orderby( $filters['sort_by'] );

		return $query
			->order_by( $sort_by, $filters['sort_order'] )
			->limit( (int) $filters['limit'] )
			->offset( (int) $filters['offset'] )
			->get();
	}

	/**
	 * Count filtered API logs.
	 *
	 * @param array $filters Filter options.
	 * @return int
	 */
	protected function count_filtered( array $filters = [] ): int {
		$defaults = [
			'search' => '',
			'method' => 'all',
			'status' => 'all',
			'route'  => '',
		];

		$filters = wp_parse_args( $filters, $defaults );

		$query = $this->query();
		$this->apply_filters( $query, $filters );

		return $query->count();
	}

	/**
	 * Get unique routes for filter dropdown.
	 *
	 * @return array
	 */
	protected function get_unique_routes(): array {
		return $this->query()
			->distinct()
			->where_not_empty( 'route' )
			->order_by( 'route', 'ASC' )
			->limit( 100 )
			->pluck( 'route' );
	}

	/**
	 * Delete multiple API logs by IDs.
	 *
	 * @param array $ids Array of API log IDs.
	 * @return int Number of deleted rows.
	 */
	protected function delete_by_ids( array $ids ): int {
		$sanitized_ids = array_filter( array_map( 'absint', $ids ) );

		if ( empty( $sanitized_ids ) ) {
			return 0;
		}

		return $this->destroy( $sanitized_ids );
	}

	/**
	 * Format API log entry for API response.
	 *
	 * Casts are applied automatically via magic __get -> get_attribute():
	 * - JSON fields return decoded arrays.
	 * - Integer/float fields return proper types.
	 *
	 * @return array
	 */
	public function to_array(): array {
		return [
			'id'               => $this->id,
			'method'           => $this->method,
			'route'            => $this->route,
			'url'              => $this->url,
			'request_headers'  => $this->request_headers,
			'request_body'     => $this->request_body,
			'request_params'   => $this->request_params,
			'response_status'  => $this->response_status,
			'response_headers' => $this->response_headers,
			'response_body'    => $this->response_body,
			'duration'         => $this->duration,
			'user_id'          => $this->user_id,
			'user_ip'          => $this->user_ip,
			'source'           => $this->source,
			'created_at'       => $this->created_at,
		];
	}

	/**
	 * Format API log entry for list response (without large bodies).
	 *
	 * @return array
	 */
	public function to_list_array(): array {
		return [
			'id'              => $this->id,
			'method'          => $this->method,
			'route'           => $this->route,
			'response_status' => $this->response_status,
			'duration'        => $this->duration,
			'user_id'         => $this->user_id,
			'source'          => $this->source,
			'created_at'      => $this->created_at,
		];
	}

	/**
	 * Truncate body to max size.
	 *
	 * @param string $body Response body.
	 * @return string
	 */
	private static function truncate_body( string $body ): string {
		if ( strlen( $body ) > self::MAX_BODY_SIZE ) {
			return substr( $body, 0, self::MAX_BODY_SIZE ) . '... [truncated]';
		}
		return $body;
	}

	/**
	 * Apply filter conditions to a query builder.
	 *
	 * @param QueryBuilder $query   The query builder instance.
	 * @param array        $filters Validated filter options.
	 * @return void
	 */
	private function apply_filters( QueryBuilder $query, array $filters ): void {
		// Search filter.
		if ( ! empty( $filters['search'] ) ) {
			$query->where_any( [ 'route', 'url', 'source' ], 'LIKE', $filters['search'] );
		}

		// Method filter.
		if ( $filters['method'] !== 'all' ) {
			$query->where( 'method', strtoupper( $filters['method'] ) );
		}

		// Status filter.
		if ( $filters['status'] !== 'all' ) {
			$this->apply_status_filter( $query, $filters['status'] );
		}

		// Route filter.
		if ( ! empty( $filters['route'] ) ) {
			$query->where( 'route', 'LIKE', $filters['route'] );
		}
	}

	/**
	 * Apply HTTP status range filter to a query builder.
	 *
	 * @param QueryBuilder $query  The query builder instance.
	 * @param string       $status Status category (success, redirect, client_error, server_error).
	 * @return void
	 */
	private function apply_status_filter( QueryBuilder $query, string $status ): void {
		switch ( $status ) {
			case 'success':
				$query->where_raw( '(response_status >= %d AND response_status < %d)', [ 200, 300 ] );
				break;
			case 'redirect':
				$query->where_raw( '(response_status >= %d AND response_status < %d)', [ 300, 400 ] );
				break;
			case 'client_error':
				$query->where_raw( '(response_status >= %d AND response_status < %d)', [ 400, 500 ] );
				break;
			case 'server_error':
				$query->where_raw( '(response_status >= %d)', [ 500 ] );
				break;
		}
	}
}
