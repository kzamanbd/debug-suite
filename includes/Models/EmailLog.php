<?php
/**
 * EmailLog Model for Debug Suite.
 *
 * Handles all database operations for email logs including CRUD operations,
 * filtering, searching, and statistics.
 *
 * @package DebugSuite
 *
 * @method static static|null create_from_mail_data( array $mail_data, string $status, string $error_message = '' )
 * @method static array       get_statistics()
 * @method static array       get_filtered( array $filters = [] )
 * @method static int         count_filtered( array $filters = [] )
 * @method static array       get_unique_receivers()
 * @method static int         delete_by_ids( array $ids )
 */

namespace DebugSuite\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * EmailLog model class.
 *
 * @property int    $id              Unique identifier for the email log.
 * @property string $to_email        Recipient email address.
 * @property string $subject         Email subject.
 * @property string $message         Email message body.
 * @property string $headers         Email headers.
 * @property array  $attachments     Attachments (auto-cast from JSON).
 * @property string $status          Email status (success/failed).
 * @property string $error_message   Error message if email sending failed.
 * @property string $sent_date       Date and time when the email was sent.
 * @property string $created_at      Timestamp when the log was created.
 * @property string $updated_at      Timestamp when the log was last updated.
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
	 * Attribute casting definitions.
	 *
	 * @var array<string, string>
	 */
	protected static array $casts = [
		'id'          => 'integer',
		'attachments' => 'json',
	];

	/**
	 * Email status constants.
	 */
	const STATUS_SUCCESS = 'success';
	const STATUS_FAILED  = 'failed';

	/**
	 * Get email statistics.
	 *
	 * @return array
	 */
	protected function get_statistics(): array {
		$stats = $this->query()
			->select_raw(
				'COUNT(*) as total_emails, '
				. 'SUM(CASE WHEN status = %s THEN 1 ELSE 0 END) as successful, '
				. 'SUM(CASE WHEN status = %s THEN 1 ELSE 0 END) as failed'
			)
			->where_raw( '1 = 1', [ self::STATUS_SUCCESS, self::STATUS_FAILED ] )
			->get_row();

		if ( ! $stats ) {
			return [
				'total_emails' => 0,
				'successful'   => 0,
				'failed'       => 0,
				'success_rate' => '0%',
			];
		}

		$total      = (int) $stats['total_emails'];
		$successful = (int) $stats['successful'];
		$failed     = (int) $stats['failed'];

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
	 * The attachments field is automatically JSON-encoded via $casts
	 * when passed as an array through fill()/set_attribute().
	 *
	 * @param array  $mail_data     Mail data.
	 * @param string $status        Email status.
	 * @param string $error_message Optional error message.
	 * @return static|null
	 */
	protected function create_from_mail_data( array $mail_data, string $status, string $error_message = '' ): ?static {
		$attributes = [
			'to_email'      => self::format_mail_recipients( $mail_data['to'] ?? '' ),
			'subject'       => $mail_data['subject'] ?? '',
			'message'       => $mail_data['message'] ?? '',
			'headers'       => self::format_mail_headers( $mail_data['headers'] ?? '' ),
			'attachments'   => self::format_mail_attachments( $mail_data['attachments'] ?? [] ),
			'status'        => $status,
			'error_message' => $error_message,
			'sent_date'     => current_time( 'mysql' ),
		];

		return $this->create( $attributes );
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
	 * Returns an array which will be auto-encoded to JSON via $casts.
	 *
	 * @param mixed $attachments Attachments data.
	 * @return array
	 */
	private static function format_mail_attachments( mixed $attachments ): array {
		return is_array( $attachments ) ? $attachments : [];
	}

	/**
	 * Get filtered email logs with search and pagination.
	 *
	 * @param array $filters Filter options.
	 * @return array
	 */
	protected function get_filtered( array $filters = [] ): array {
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
	 * Count filtered email logs.
	 *
	 * @param array $filters Filter options.
	 * @return int
	 */
	protected function count_filtered( array $filters = [] ): int {
		$defaults = [
			'search'   => '',
			'status'   => 'all',
			'receiver' => '',
		];

		$filters = wp_parse_args( $filters, $defaults );

		$query = $this->query();
		$this->apply_filters( $query, $filters );

		return $query->count();
	}

	/**
	 * Get unique receivers for filter dropdown.
	 *
	 * @return array
	 */
	protected function get_unique_receivers(): array {
		return $this->query()
			->distinct()
			->where_not_empty( 'to_email' )
			->order_by( 'to_email', 'ASC' )
			->pluck( 'to_email' );
	}

	/**
	 * Delete multiple email logs by IDs.
	 *
	 * @param array $ids Array of email log IDs.
	 * @return int Number of deleted rows.
	 */
	protected function delete_by_ids( array $ids ): int {
		// Sanitize and filter IDs.
		$sanitized_ids = array_filter( array_map( 'absint', $ids ) );

		if ( empty( $sanitized_ids ) ) {
			return 0;
		}

		return $this->destroy( $sanitized_ids );
	}

	/**
	 * Format email entry for API response.
	 *
	 * Casts are applied automatically via magic __get -> get_attribute():
	 * - id returns integer, attachments returns decoded array.
	 *
	 * @return array
	 */
	public function to_array(): array {
		return [
			'id'          => $this->id,
			'sent_date'   => $this->sent_date,
			'receiver'    => $this->to_email,
			'subject'     => $this->subject,
			'message'     => $this->message,
			'headers'     => $this->headers,
			'attachments' => $this->attachments,
			'status'      => $this->status,
			'error'       => $this->error_message,
			'created_at'  => $this->created_at,
		];
	}

	/**
	 * Get parsed attachments as array.
	 *
	 * Leverages the 'json' cast on attachments attribute.
	 *
	 * @return array
	 */
	public function get_attachments(): array {
		$attachments = $this->attachments; // JSON cast handles decode.

		return is_array( $attachments ) ? $attachments : [];
	}

	/**
	 * Get headers as array.
	 *
	 * @return array
	 */
	public function get_headers(): array {
		$headers = $this->attributes['headers'] ?? '';

		return empty( $headers ) ? [] : explode( "\n", $headers );
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
			$query->where_any( [ 'subject', 'to_email', 'message' ], 'LIKE', $filters['search'] );
		}

		// Status filter.
		if ( $filters['status'] !== 'all' ) {
			$query->where( 'status', $filters['status'] );
		}

		// Receiver filter.
		if ( ! empty( $filters['receiver'] ) ) {
			$query->where( 'to_email', 'LIKE', $filters['receiver'] );
		}
	}
}
