<?php
/**
 * ApiLog Model for Debug Suite.
 *
 * Handles all database operations for REST API logs including CRUD operations,
 * filtering, searching, and statistics.
 *
 * @package DebugSuite
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
 * @property string $request_headers  JSON encoded request headers.
 * @property string $request_body     JSON encoded request body.
 * @property string $request_params   JSON encoded request parameters.
 * @property int    $response_status  HTTP response status code.
 * @property string $response_headers JSON encoded response headers.
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
	 * @param array $data Request and response data.
	 * @return static|null
	 */
	public static function create_from_request( array $data ): ?static {
		$attributes = [
			'method'           => strtoupper( $data['method'] ?? '' ),
			'route'            => $data['route'] ?? '',
			'url'              => $data['url'] ?? '',
			'request_headers'  => self::encode_json( $data['request_headers'] ?? [] ),
			'request_body'     => self::encode_json( $data['request_body'] ?? [] ),
			'request_params'   => self::encode_json( $data['request_params'] ?? [] ),
			'response_status'  => (int) ( $data['response_status'] ?? 0 ),
			'response_headers' => self::encode_json( $data['response_headers'] ?? [] ),
			'response_body'    => self::truncate_body( $data['response_body'] ?? '' ),
			'duration'         => round( (float) ( $data['duration'] ?? 0 ), 2 ),
			'user_id'          => (int) ( $data['user_id'] ?? 0 ),
			'user_ip'          => $data['user_ip'] ?? '',
			'source'           => $data['source'] ?? '',
		];

		return static::create( $attributes );
	}

	/**
	 * Get API log statistics.
	 *
	 * @return array
	 */
	public static function get_statistics(): array {
		$wpdb       = static::get_wpdb();
		$table_name = static::get_table_name();

		$query = "
			SELECT
				COUNT(*) as total_requests,
				SUM(CASE WHEN response_status >= 200 AND response_status < 300 THEN 1 ELSE 0 END) as successful,
				SUM(CASE WHEN response_status >= 400 THEN 1 ELSE 0 END) as failed,
				ROUND(AVG(duration), 2) as avg_duration
			FROM {$table_name}
		";

		// No dynamic values; safe to run directly.
		$stats = $wpdb->get_row( $query, ARRAY_A );

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
	public static function get_filtered( array $filters = [] ): array {
		$wpdb       = static::get_wpdb();
		$table_name = static::get_table_name();

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

		$where_data      = self::build_where_clause( $filters );
		$where_clause    = $where_data['where_clause'];
		$prepare_values  = $where_data['prepare_values'];

		$sort_by    = sanitize_sql_orderby( $filters['sort_by'] );
		$sort_order = strtoupper( $filters['sort_order'] ) === 'ASC' ? 'ASC' : 'DESC';

		$query = "SELECT * FROM $table_name $where_clause ORDER BY $sort_by $sort_order LIMIT %d OFFSET %d";

		$prepare_values[] = (int) $filters['limit'];
		$prepare_values[] = (int) $filters['offset'];

		$results = $wpdb->get_results(
			$wpdb->prepare( $query, $prepare_values ),
			ARRAY_A
		);

		return array_map( [ static::class, 'from_array' ], $results ?? [] );
	}

	/**
	 * Count filtered API logs.
	 *
	 * @param array $filters Filter options.
	 * @return int
	 */
	public static function count_filtered( array $filters = [] ): int {
		$wpdb       = static::get_wpdb();
		$table_name = static::get_table_name();

		$defaults = [
			'search' => '',
			'method' => 'all',
			'status' => 'all',
			'route'  => '',
		];

		$filters = wp_parse_args( $filters, $defaults );

		$where_data     = self::build_where_clause( $filters );
		$where_clause   = $where_data['where_clause'];
		$prepare_values = $where_data['prepare_values'];

		$query = "SELECT COUNT(*) FROM $table_name $where_clause";

		return (int) $wpdb->get_var(
			empty( $prepare_values ) ? $query : $wpdb->prepare( $query, $prepare_values )
		);
	}

	/**
	 * Get unique routes for filter dropdown.
	 *
	 * @return array
	 */
	public static function get_unique_routes(): array {
		$wpdb       = static::get_wpdb();
		$table_name = static::get_table_name();

		// No dynamic values; safe to run directly.
		$query = "SELECT DISTINCT route FROM $table_name WHERE route != '' ORDER BY route ASC LIMIT 100";
		return $wpdb->get_col( $query );
	}

	/**
	 * Delete multiple API logs by IDs.
	 *
	 * @param array $ids Array of API log IDs.
	 * @return int Number of deleted rows.
	 */
	public static function delete_by_ids( array $ids ): int {
		$sanitized_ids = array_filter( array_map( 'absint', $ids ) );

		if ( empty( $sanitized_ids ) ) {
			return 0;
		}

		return static::delete_where( [ static::$primary_key => $sanitized_ids ] );
	}

	/**
	 * Format API log entry for API response.
	 *
	 * @return array
	 */
	public function to_array(): array {
		return [
			'id'               => (int) $this->id,
			'method'           => $this->method,
			'route'            => $this->route,
			'url'              => $this->url,
			'request_headers'  => $this->get_decoded( 'request_headers' ),
			'request_body'     => $this->get_decoded( 'request_body' ),
			'request_params'   => $this->get_decoded( 'request_params' ),
			'response_status'  => (int) $this->response_status,
			'response_headers' => $this->get_decoded( 'response_headers' ),
			'response_body'    => $this->response_body,
			'duration'         => (float) $this->duration,
			'user_id'          => (int) $this->user_id,
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
			'id'              => (int) $this->id,
			'method'          => $this->method,
			'route'           => $this->route,
			'response_status' => (int) $this->response_status,
			'duration'        => (float) $this->duration,
			'user_id'         => (int) $this->user_id,
			'source'          => $this->source,
			'created_at'      => $this->created_at,
		];
	}

	/**
	 * Decode a JSON-encoded attribute.
	 *
	 * @param string $key Attribute key.
	 * @return mixed
	 */
	private function get_decoded( string $key ): mixed {
		$value = $this->get_attribute( $key );
		if ( empty( $value ) ) {
			return [];
		}

		$decoded = json_decode( $value, true );
		return is_array( $decoded ) ? $decoded : $value;
	}

	/**
	 * JSON encode data safely.
	 *
	 * @param mixed $data Data to encode.
	 * @return string
	 */
	private static function encode_json( mixed $data ): string {
		if ( is_string( $data ) ) {
			return $data;
		}

		return wp_json_encode( $data ) ?? '{}';
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
	 * Build WHERE clause conditions for filters.
	 *
	 * @param array $filters Validated filter options.
	 * @return array Array containing where_clause string and prepare_values array.
	 */
	private static function build_where_clause( array $filters ): array {
		$wpdb             = static::get_wpdb();
		$where_conditions = [];
		$prepare_values   = [];

		// Search filter
		if ( ! empty( $filters['search'] ) ) {
			$search_term      = '%' . $wpdb->esc_like( $filters['search'] ) . '%';
			$where_conditions[] = '(route LIKE %s OR url LIKE %s OR source LIKE %s)';
			$prepare_values[]   = $search_term;
			$prepare_values[]   = $search_term;
			$prepare_values[]   = $search_term;
		}

		// Method filter
		if ( $filters['method'] !== 'all' ) {
			$where_conditions[] = 'method = %s';
			$prepare_values[]   = strtoupper( $filters['method'] );
		}

		// Status filter
		if ( $filters['status'] !== 'all' ) {
			switch ( $filters['status'] ) {
				case 'success':
					$where_conditions[] = '(response_status >= 200 AND response_status < 300)';
					break;
				case 'redirect':
					$where_conditions[] = '(response_status >= 300 AND response_status < 400)';
					break;
				case 'client_error':
					$where_conditions[] = '(response_status >= 400 AND response_status < 500)';
					break;
				case 'server_error':
					$where_conditions[] = '(response_status >= 500)';
					break;
			}
		}

		// Route filter
		if ( ! empty( $filters['route'] ) ) {
			$where_conditions[] = 'route LIKE %s';
			$prepare_values[]   = '%' . $wpdb->esc_like( $filters['route'] ) . '%';
		}

		$where_clause = empty( $where_conditions ) ? '' : 'WHERE ' . implode( ' AND ', $where_conditions );

		return [
			'where_clause'   => $where_clause,
			'prepare_values' => $prepare_values,
		];
	}
}
