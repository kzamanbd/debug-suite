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
 * @property int   $id              Unique identifier for the email log.
 * @property string $to_email       Recipient email address.
 * @property string $subject        Email subject.
 * @property string $message        Email message body.
 * @property string $headers        Email headers.
 * @property string $attachments    JSON encoded attachments.
 * @property string $status         Email status (success/failed).
 * @property string $error_message  Error message if email sending failed.
 * @property string $sent_date      Date and time when the email was sent.
 * @property string $created_at     Timestamp when the log was created.
 * @property string $updated_at     Timestamp when the log was last updated.
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
		'id',
		'to_email',
		'subject',
		'message',
		'headers',
		'attachments',
		'status',
		'error_message',
		'sent_date',
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
	 * Email status constants.
	 */
	const STATUS_SUCCESS = 'success';
	const STATUS_FAILED = 'failed';



	/**
	 * Get email statistics.
	 *
	 * @return array
	 */
	public static function get_statistics(): array {
		$wpdb = static::get_wpdb();
		$table_name = static::get_table_name();

		$query = "
			SELECT 
				COUNT(*) as total_emails,
				SUM(CASE WHEN status = %s THEN 1 ELSE 0 END) as successful,
				SUM(CASE WHEN status = %s THEN 1 ELSE 0 END) as failed
			FROM {$table_name}
		";

		$stats = $wpdb->get_row(
			$wpdb->prepare( $query, self::STATUS_SUCCESS, self::STATUS_FAILED ),
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
			'to_email'      => self::format_mail_recipients( $mail_data['to'] ?? '' ),
			'subject'       => $mail_data['subject'] ?? '',
			'message'       => $mail_data['message'] ?? '',
			'headers'       => self::format_mail_headers( $mail_data['headers'] ?? '' ),
			'attachments'   => self::format_mail_attachments( $mail_data['attachments'] ?? '' ),
			'status'        => $status,
			'error_message' => $error_message,
			'sent_date'     => current_time( 'mysql' ),
		];

		return static::create( $attributes );
	}

	/**
	 * Format mail recipients for storage.
	 *
	 * @param mixed $recipients Recipients data.
	 * @return string
	 */
	private static function format_mail_recipients( mixed $recipients ): string {
		return is_array( $recipients ) ? implode( ', ', $recipients ) : (string) $recipients;
	}

	/**
	 * Format mail headers for storage.
	 *
	 * @param mixed $headers Headers data.
	 * @return string
	 */
	private static function format_mail_headers( mixed $headers ): string {
		return is_array( $headers ) ? implode( "\n", $headers ) : (string) $headers;
	}

	/**
	 * Format mail attachments for storage.
	 *
	 * @param mixed $attachments Attachments data.
	 * @return string
	 */
	private static function format_mail_attachments( mixed $attachments ): string {
		return is_array( $attachments ) ? wp_json_encode( $attachments ) : '';
	}

	/**
	 * Get filtered email logs with search and pagination.
	 *
	 * @param array $filters Filter options.
	 * @return array
	 */
	public static function get_filtered( array $filters = [] ): array {
		$wpdb = static::get_wpdb();
		$table_name = static::get_table_name();

		$defaults = [
			'search'     => '',
			'status'     => 'all',
			'receiver'   => '',
			'sort_by'    => 'sent_date',
			'sort_order' => 'DESC',
			'limit'      => 20,
			'offset'     => 0,
		];

		$filters = wp_parse_args( $filters, $defaults );

		// Build WHERE clause using helper method
		$where_data = self::build_where_clause( $filters );
		$where_clause = $where_data['where_clause'];
		$prepare_values = $where_data['prepare_values'];

		$sort_by = sanitize_sql_orderby( $filters['sort_by'] );
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
	 * Count filtered email logs.
	 *
	 * @param array $filters Filter options.
	 * @return int
	 */
	public static function count_filtered( array $filters = [] ): int {
		$wpdb = static::get_wpdb();
		$table_name = static::get_table_name();

		$defaults = [
			'search'   => '',
			'status'   => 'all',
			'receiver' => '',
		];

		$filters = wp_parse_args( $filters, $defaults );

		// Build WHERE clause using helper method
		$where_data = self::build_where_clause( $filters );
		$where_clause = $where_data['where_clause'];
		$prepare_values = $where_data['prepare_values'];

		$query = "SELECT COUNT(*) FROM $table_name $where_clause";

		return (int) $wpdb->get_var(
			empty( $prepare_values ) ? $query : $wpdb->prepare( $query, $prepare_values )
		);
	}

	/**
	 * Get unique receivers for filter dropdown.
	 *
	 * @return array
	 */
	public static function get_unique_receivers(): array {
		$wpdb       = static::get_wpdb();
		$table_name = static::get_table_name();

		// No dynamic values in query; safe to run directly without prepare().
		$query = "SELECT DISTINCT to_email FROM $table_name WHERE to_email != '' ORDER BY to_email ASC";
		return $wpdb->get_col( $query );
	}

	/**
	 * Delete multiple email logs by IDs.
	 *
	 * @param array $ids Array of email log IDs.
	 * @return int Number of deleted rows.
	 */
	public static function delete_by_ids( array $ids ): int {
		// Sanitize and filter IDs
		$sanitized_ids = array_filter( array_map( 'absint', $ids ) );

		if ( empty( $sanitized_ids ) ) {
			return 0;
		}

		return static::delete_where( [ static::$primary_key => $sanitized_ids ] );
	}



	/**
	 * Format email entry for API response.
	 *
	 * @return array
	 */
	public function to_array(): array {
		return [
			'id'            => $this->id,
			'sent_date'     => $this->sent_date,
			'receiver'      => $this->to_email,
			'subject'       => $this->subject,
			'message'       => $this->message,
			'headers'       => $this->headers,
			'attachments'   => $this->attachments,
			'status'        => $this->status,
			'error'         => $this->error_message,
			'created_at'    => $this->created_at,
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
		return empty( $this->headers ) ? [] : explode( "\n", $this->headers );
	}

	/**
	 * Build WHERE clause conditions for filters.
	 *
	 * @param array $filters Validated filter options.
	 * @return array Array containing where_clause string and prepare_values array.
	 */
	private static function build_where_clause( array $filters ): array {
		$wpdb = static::get_wpdb();
		$where_conditions = [];
		$prepare_values = [];

		// Search filter
		if ( ! empty( $filters['search'] ) ) {
			$search_term = '%' . $wpdb->esc_like( $filters['search'] ) . '%';
			$where_conditions[] = '(subject LIKE %s OR to_email LIKE %s OR message LIKE %s)';
			$prepare_values[] = $search_term;
			$prepare_values[] = $search_term;
			$prepare_values[] = $search_term;
		}

		// Status filter
		if ( $filters['status'] !== 'all' ) {
			$where_conditions[] = 'status = %s';
			$prepare_values[] = $filters['status'];
		}

		// Receiver filter
		if ( ! empty( $filters['receiver'] ) ) {
			$where_conditions[] = 'to_email LIKE %s';
			$prepare_values[] = '%' . $wpdb->esc_like( $filters['receiver'] ) . '%';
		}

		$where_clause = empty( $where_conditions ) ? '' : 'WHERE ' . implode( ' AND ', $where_conditions );

		return [
			'where_clause'    => $where_clause,
			'prepare_values'  => $prepare_values,
		];
	}
}
