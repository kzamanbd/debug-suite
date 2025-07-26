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
			// phpcs:ignore
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
			'to_email'      => static::format_mail_recipients( $mail_data['to'] ?? '' ),
			'subject'       => $mail_data['subject'] ?? '',
			'message'       => $mail_data['message'] ?? '',
			'headers'       => static::format_mail_headers( $mail_data['headers'] ?? '' ),
			'attachments'   => static::format_mail_attachments( $mail_data['attachments'] ?? '' ),
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
		return empty( $this->headers ) ? [] : explode( "\n", $this->headers );
	}
}
