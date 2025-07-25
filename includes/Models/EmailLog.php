<?php
/**
 * EmailLog Model for Debug Suite.
 *
 * Handles all database operations for email logs including CRUD operations,
 * filtering, searching, and statistics.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * EmailLog model class.
 *
 * @since 1.0.0
 */
class EmailLog extends BaseModel {

	/**
	 * Table name without prefix.
	 *
	 * @var string
	 */
	protected static string $table = 'email_logs';

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
		'to_email',
		'subject',
		'message',
		'headers',
		'attachments',
		'status',
		'error_message',
		'sent_date',
	];

	/**
	 * Timestamps columns.
	 *
	 * @var array
	 */
	protected static array $timestamps = [ 'created_at', 'updated_at' ];

	/**
	 * Email status constants.
	 */
	const STATUS_PENDING = 'pending';
	const STATUS_SUCCESS = 'success';
	const STATUS_FAILED = 'failed';

	/**
	 * Get email logs with advanced filtering and pagination.
	 *
	 * @param array $options {
	 *     Optional filtering and pagination options.
	 *     @type string $receiver   Filter by receiver email.
	 *     @type string $status     Filter by status (success, failed, all).
	 *     @type string $search     Search term.
	 *     @type string $sort_by    Sort field.
	 *     @type string $sort_order Sort order (asc, desc).
	 *     @type int    $limit      Number of entries to return.
	 *     @type int    $offset     Offset for pagination.
	 *     @type string $date_from  Start date filter.
	 *     @type string $date_to    End date filter.
	 * }
	 * @return array
	 */
	public static function get_filtered_entries( array $options = [] ): array {
		$defaults = [
			'receiver'   => '',
			'status'     => 'all',
			'search'     => '',
			'sort_by'    => 'sent_date',
			'sort_order' => 'desc',
			'limit'      => 100,
			'offset'     => 0,
			'date_from'  => '',
			'date_to'    => '',
		];

		$options = wp_parse_args( $options, $defaults );

		$wpdb = static::get_wpdb();
		$table_name = static::get_table_name();

		// Build WHERE clause
		$where_conditions = [ '1=1' ];
		$where_values = [];

		// Filter by receiver
		if ( ! empty( $options['receiver'] ) ) {
			$where_conditions[] = 'to_email LIKE %s';
			$where_values[] = '%' . $wpdb->esc_like( $options['receiver'] ) . '%';
		}

		// Filter by status
		if ( $options['status'] !== 'all' ) {
			$where_conditions[] = 'status = %s';
			$where_values[] = $options['status'];
		}

		// Search functionality
		if ( ! empty( $options['search'] ) ) {
			$search_term = '%' . $wpdb->esc_like( $options['search'] ) . '%';
			$where_conditions[] = '(to_email LIKE %s OR subject LIKE %s OR message LIKE %s)';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		// Date range filtering
		if ( ! empty( $options['date_from'] ) ) {
			$where_conditions[] = 'sent_date >= %s';
			$where_values[] = $options['date_from'] . ' 00:00:00';
		}

		if ( ! empty( $options['date_to'] ) ) {
			$where_conditions[] = 'sent_date <= %s';
			$where_values[] = $options['date_to'] . ' 23:59:59';
		}

		$where_clause = implode( ' AND ', $where_conditions );

		// Build ORDER BY clause
		$allowed_sort_fields = [ 'sent_date', 'to_email', 'subject', 'status' ];
		$sort_by = in_array( $options['sort_by'], $allowed_sort_fields, true ) ? $options['sort_by'] : 'sent_date';
		$sort_order = strtoupper( $options['sort_order'] ) === 'ASC' ? 'ASC' : 'DESC';

		// Get entries
		$query = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY {$sort_by} {$sort_order} LIMIT %d OFFSET %d";
		$query_values = array_merge( $where_values, [ $options['limit'], $options['offset'] ] );

		$results = $wpdb->get_results( $wpdb->prepare( $query, $query_values ), ARRAY_A );

		return array_map( [ static::class, 'from_array' ], $results );
	}

	/**
	 * Count filtered entries.
	 *
	 * @param array $options Filter options.
	 * @return int
	 */
	public static function count_filtered_entries( array $options = [] ): int {
		$defaults = [
			'receiver'   => '',
			'status'     => 'all',
			'search'     => '',
			'date_from'  => '',
			'date_to'    => '',
		];

		$options = wp_parse_args( $options, $defaults );

		$wpdb = static::get_wpdb();
		$table_name = static::get_table_name();

		// Build WHERE clause
		$where_conditions = [ '1=1' ];
		$where_values = [];

		// Filter by receiver
		if ( ! empty( $options['receiver'] ) ) {
			$where_conditions[] = 'to_email LIKE %s';
			$where_values[] = '%' . $wpdb->esc_like( $options['receiver'] ) . '%';
		}

		// Filter by status
		if ( $options['status'] !== 'all' ) {
			$where_conditions[] = 'status = %s';
			$where_values[] = $options['status'];
		}

		// Search functionality
		if ( ! empty( $options['search'] ) ) {
			$search_term = '%' . $wpdb->esc_like( $options['search'] ) . '%';
			$where_conditions[] = '(to_email LIKE %s OR subject LIKE %s OR message LIKE %s)';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		// Date range filtering
		if ( ! empty( $options['date_from'] ) ) {
			$where_conditions[] = 'sent_date >= %s';
			$where_values[] = $options['date_from'] . ' 00:00:00';
		}

		if ( ! empty( $options['date_to'] ) ) {
			$where_conditions[] = 'sent_date <= %s';
			$where_values[] = $options['date_to'] . ' 23:59:59';
		}

		$where_clause = implode( ' AND ', $where_conditions );

		// Get count
		$count_query = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_clause}";

		if ( ! empty( $where_values ) ) {
			return (int) $wpdb->get_var( $wpdb->prepare( $count_query, $where_values ) );
		}

		return (int) $wpdb->get_var( $count_query );
	}

	/**
	 * Get email statistics.
	 *
	 * @return array
	 */
	public static function get_statistics(): array {
		$wpdb = static::get_wpdb();
		$table_name = static::get_table_name();

		$stats_query = "
			SELECT 
				COUNT(*) as total_emails,
				SUM(CASE WHEN status = %s THEN 1 ELSE 0 END) as successful,
				SUM(CASE WHEN status = %s THEN 1 ELSE 0 END) as failed
			FROM {$table_name}
		";

		$stats = $wpdb->get_row(
			$wpdb->prepare( $stats_query, self::STATUS_SUCCESS, self::STATUS_FAILED ),
			ARRAY_A
		);

		if ( ! $stats ) {
			return [
				'total_emails' => 0,
				'successful'   => 0,
				'failed'       => 0,
				'success_rate' => '0%',
			];
		}

		$total = (int) $stats['total_emails'];
		$successful = (int) $stats['successful'];
		$failed = (int) $stats['failed'];

		$success_rate = $total > 0 ? round( ( $successful / $total ) * 100, 2 ) : 0;

		return [
			'total_emails' => $total,
			'successful'   => $successful,
			'failed'       => $failed,
			'success_rate' => $success_rate . '%',
		];
	}

	/**
	 * Create email log from wp_mail data.
	 *
	 * @param array  $mail_data Mail data.
	 * @param string $status Email status.
	 * @param string $error_message Optional error message.
	 * @return static|null
	 */
	public static function create_from_mail_data( array $mail_data, string $status, string $error_message = '' ): ?static {
		$attributes = [
			'to_email'      => is_array( $mail_data['to'] ?? '' ) ? implode( ', ', $mail_data['to'] ) : ( $mail_data['to'] ?? '' ),
			'subject'       => $mail_data['subject'] ?? '',
			'message'       => $mail_data['message'] ?? '',
			'headers'       => is_array( $mail_data['headers'] ?? '' ) ? implode( "\n", $mail_data['headers'] ) : ( $mail_data['headers'] ?? '' ),
			'attachments'   => is_array( $mail_data['attachments'] ?? '' ) ? wp_json_encode( $mail_data['attachments'] ) : '',
			'status'        => $status,
			'error_message' => $error_message,
			'sent_date'     => current_time( 'mysql' ),
		];

		return static::create( $attributes );
	}

	/**
	 * Delete multiple email logs by IDs.
	 *
	 * @param array $ids Array of email log IDs.
	 * @return int Number of deleted rows.
	 */
	public static function delete_by_ids( array $ids ): int {
		if ( empty( $ids ) ) {
			return 0;
		}

		// Sanitize IDs
		$ids = array_map( 'absint', $ids );
		$ids = array_filter( $ids );

		if ( empty( $ids ) ) {
			return 0;
		}

		return static::delete_where( [ static::$primary_key => $ids ] );
	}

	/**
	 * Get emails by status.
	 *
	 * @param string $status Email status.
	 * @param array  $options Query options.
	 * @return array
	 */
	public static function get_by_status( string $status, array $options = [] ): array {
		$conditions = [ 'status' => $status ];
		return static::where( $conditions, $options );
	}

	/**
	 * Get recent emails.
	 *
	 * @param int $limit Number of emails to retrieve.
	 * @return array
	 */
	public static function get_recent( int $limit = 10 ): array {
		return static::all(
			[
				'limit'    => $limit,
				'order_by' => 'sent_date',
				'order'    => 'DESC',
			]
		);
	}

	/**
	 * Search emails by term.
	 *
	 * @param string $search_term Search term.
	 * @param array  $options Query options.
	 * @return array
	 */
	public static function search( string $search_term, array $options = [] ): array {
		if ( empty( $search_term ) ) {
			return [];
		}

		return static::get_filtered_entries( array_merge( $options, [ 'search' => $search_term ] ) );
	}

	/**
	 * Get emails sent to specific recipient.
	 *
	 * @param string $email Email address.
	 * @param array  $options Query options.
	 * @return array
	 */
	public static function get_by_recipient( string $email, array $options = [] ): array {
		return static::get_filtered_entries( array_merge( $options, [ 'receiver' => $email ] ) );
	}

	/**
	 * Get emails within date range.
	 *
	 * @param string $date_from Start date (Y-m-d format).
	 * @param string $date_to End date (Y-m-d format).
	 * @param array  $options Query options.
	 * @return array
	 */
	public static function get_by_date_range( string $date_from, string $date_to, array $options = [] ): array {
		return static::get_filtered_entries(
			array_merge(
				$options,
				[
					'date_from' => $date_from,
					'date_to'   => $date_to,
				]
			)
		);
	}

	/**
	 * Format email entry for API response.
	 *
	 * @return array
	 */
	public function to_api_array(): array {
		return [
			'id'         => (int) $this->id,
			'time'       => $this->sent_date,
			'receiver'   => $this->to_email,
			'subject'    => $this->subject,
			'message'    => $this->message,
			'headers'    => $this->headers,
			'attachments' => $this->attachments,
			'status'     => $this->status,
			'error'      => $this->error_message,
			'created_at' => $this->created_at,
		];
	}

	/**
	 * Get parsed attachments as array.
	 *
	 * @return array
	 */
	public function get_attachments(): array {
		if ( empty( $this->attachments ) ) {
			return [];
		}

		$decoded = json_decode( $this->attachments, true );
		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Get headers as array.
	 *
	 * @return array
	 */
	public function get_headers(): array {
		if ( empty( $this->headers ) ) {
			return [];
		}

		return explode( "\n", $this->headers );
	}

	/**
	 * Check if email was successful.
	 *
	 * @return bool
	 */
	public function is_successful(): bool {
		return $this->status === self::STATUS_SUCCESS;
	}

	/**
	 * Check if email failed.
	 *
	 * @return bool
	 */
	public function is_failed(): bool {
		return $this->status === self::STATUS_FAILED;
	}

	/**
	 * Check if email is pending.
	 *
	 * @return bool
	 */
	public function is_pending(): bool {
		return $this->status === self::STATUS_PENDING;
	}

	/**
	 * Mark email as successful.
	 *
	 * @return bool
	 */
	public function mark_as_successful(): bool {
		$this->status = self::STATUS_SUCCESS;
		$this->error_message = '';
		return $this->save();
	}

	/**
	 * Mark email as failed.
	 *
	 * @param string $error_message Error message.
	 * @return bool
	 */
	public function mark_as_failed( string $error_message = '' ): bool {
		$this->status = self::STATUS_FAILED;
		$this->error_message = $error_message;
		return $this->save();
	}
}
