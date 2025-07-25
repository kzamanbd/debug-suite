<?php
/**
 * EmailLogService for Debug Suite.
 *
 * Provides email logging functionality similar to wp-email-log-db.
 * Captures wp_mail data and stores detailed email logs using the EmailLog model.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Services\EmailLog;

use DebugSuite\Core\ServiceResponse;
use DebugSuite\Interfaces\Hookable;
use DebugSuite\Interfaces\ServiceInterface;
use DebugSuite\Models\EmailLog;
use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Email logging service.
 *
 * @since 1.0.0
 */
class EmailLogService implements ServiceInterface, Hookable {

	/**
	 * Email data captured from wp_mail.
	 *
	 * @var array
	 */
	private array $email_data = [];

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'wp_mail', [ $this, 'capture_email_data' ] );
		add_action( 'wp_mail_succeeded', [ $this, 'log_email_success' ] );
		add_action( 'wp_mail_failed', [ $this, 'log_email_failure' ] );
	}

	/**
	 * Capture email data from wp_mail.
	 *
	 * @param array $mail_data Mail data from wp_mail.
	 * @return array
	 */
	public function capture_email_data( array $mail_data ): array {
		$this->email_data = $mail_data;
		return $mail_data;
	}

	/**
	 * Log successful email.
	 *
	 * @param object $mail_info PHPMailer instance or mail info.
	 * @return void
	 */
	public function log_email_success( $mail_info ): void {
		if ( empty( $this->email_data ) ) {
			return;
		}

		// Try to get additional data from PHPMailer if available
		$enhanced_data = $this->enhance_mail_data( $mail_info );

		EmailLog::create_from_mail_data(
			$enhanced_data,
			EmailLog::STATUS_SUCCESS
		);

		// Clear captured data
		$this->email_data = [];
	}

	/**
	 * Log failed email.
	 *
	 * @param object $wp_error WP_Error instance.
	 * @return void
	 */
	public function log_email_failure( $wp_error ): void {
		if ( empty( $this->email_data ) ) {
			return;
		}

		$error_message = '';
		if ( is_wp_error( $wp_error ) ) {
			$error_message = $wp_error->get_error_message();
		} elseif ( property_exists( $wp_error, 'ErrorInfo' ) ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$error_message = $wp_error->ErrorInfo;
		}

		EmailLog::create_from_mail_data(
			$this->email_data,
			EmailLog::STATUS_FAILED,
			$error_message
		);

		// Clear captured data
		$this->email_data = [];
	}

	/**
	 * Get email log entries with filtering and pagination.
	 *
	 * @param array $options Query options.
	 * @return ServiceResponse
	 */
	public function get_email_log_entries( array $options = [] ): ServiceResponse {
		try {
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

			// Validate options
			$validation_result = $this->validate_get_entries_options( $options );
			if ( $validation_result->is_failure() ) {
				return $validation_result;
			}

			// Get entries using model
			$entries = EmailLog::get_filtered_entries( $options );
			$total_count = EmailLog::count_filtered_entries( $options );

			// Convert to API format
			$formatted_entries = array_map(
				static function ( EmailLog $entry ) {
					return $entry->to_array();
				},
				$entries
			);

			return ServiceResponse::success(
				[
					'entries'     => $formatted_entries,
					'total_count' => $total_count,
					'has_more'    => (int) ( $options['offset'] + $options['limit'] ) < $total_count,
					'pagination'  => [
						'limit'  => $options['limit'],
						'offset' => $options['offset'],
						'total'  => $total_count,
					],
				]
			);

		} catch ( Exception $e ) {
			return ServiceResponse::failure(
				__( 'Failed to retrieve email logs.', 'debug-suite' ),
				'database_error',
				[ 'error' => $e->getMessage() ]
			);
		}
	}

	/**
	 * Get email statistics.
	 *
	 * @return ServiceResponse
	 */
	public function get_email_statistics(): ServiceResponse {
		try {
			$stats = EmailLog::get_statistics();

			return ServiceResponse::success( $stats );

		} catch ( Exception $e ) {
			return ServiceResponse::failure(
				__( 'Failed to retrieve email statistics.', 'debug-suite' ),
				'database_error',
				[ 'error' => $e->getMessage() ]
			);
		}
	}

	/**
	 * Delete multiple email logs.
	 *
	 * @param array $email_ids Array of email log IDs.
	 * @return ServiceResponse
	 */
	public function bulk_delete_emails( array $email_ids ): ServiceResponse {
		if ( empty( $email_ids ) ) {
			return ServiceResponse::failure(
				__( 'No email IDs provided for deletion.', 'debug-suite' ),
				'invalid_input'
			);
		}

		try {
			$deleted_count = EmailLog::delete_by_ids( $email_ids );

			if ( $deleted_count === 0 ) {
				return ServiceResponse::failure(
					__( 'No emails found to delete.', 'debug-suite' ),
					'not_found'
				);
			}

			return ServiceResponse::success(
				[
					'deleted_count' => $deleted_count,
					'message'       => sprintf(
						 /* translators: %d: number of deleted emails */
						_n(
							'%d email log deleted successfully.',
							'%d email logs deleted successfully.',
							$deleted_count,
							'debug-suite'
						),
						$deleted_count
					),
				]
			);

		} catch ( Exception $e ) {
			return ServiceResponse::failure(
				__( 'Failed to delete email logs.', 'debug-suite' ),
				'database_error',
				[ 'error' => $e->getMessage() ]
			);
		}
	}

	/**
	 * Clear all email logs.
	 *
	 * @return ServiceResponse
	 */
	public function clear_all_emails(): ServiceResponse {
		try {
			$deleted_count = EmailLog::truncate();

			return ServiceResponse::success(
				[
					'deleted_count' => $deleted_count,
					'message'       => __( 'All email logs cleared successfully.', 'debug-suite' ),
				]
			);

		} catch ( Exception $e ) {
			return ServiceResponse::failure(
				__( 'Failed to clear email logs.', 'debug-suite' ),
				'database_error',
				[ 'error' => $e->getMessage() ]
			);
		}
	}

	/**
	 * Get specific email log by ID.
	 *
	 * @param int $email_id Email log ID.
	 * @return ServiceResponse
	 */
	public function get_email_by_id( int $email_id ): ServiceResponse {
		if ( $email_id <= 0 ) {
			return ServiceResponse::failure(
				__( 'Invalid email ID provided.', 'debug-suite' ),
				'invalid_input'
			);
		}

		try {
			$email = EmailLog::find( $email_id );

			if ( ! $email ) {
				return ServiceResponse::failure(
					__( 'Email log not found.', 'debug-suite' ),
					'not_found'
				);
			}

			return ServiceResponse::success( $email->to_array() );

		} catch ( Exception $e ) {
			return ServiceResponse::failure(
				__( 'Failed to retrieve email log.', 'debug-suite' ),
				'database_error',
				[ 'error' => $e->getMessage() ]
			);
		}
	}

	/**
	 * Resend email by ID.
	 *
	 * @param int $email_id Email log ID.
	 * @return ServiceResponse
	 */
	public function resend_email( int $email_id ): ServiceResponse {
		if ( $email_id <= 0 ) {
			return ServiceResponse::failure(
				__( 'Invalid email ID provided.', 'debug-suite' ),
				'invalid_input'
			);
		}

		try {
			$email = EmailLog::find( $email_id );

			if ( ! $email ) {
				return ServiceResponse::failure(
					__( 'Email log not found.', 'debug-suite' ),
					'not_found'
				);
			}

			// Prepare email data for resending
			$to = $email->to_email;
			$subject = $email->subject;
			$message = $email->message;
			$headers = $email->get_headers();
			$attachments = $email->get_attachments();

			// Attempt to resend
			$sent = wp_mail( $to, $subject, $message, $headers, $attachments );

			if ( $sent ) {
				return ServiceResponse::success(
					[
						'message' => __( 'Email resent successfully.', 'debug-suite' ),
						'email_id' => $email_id,
					]
				);
			} else {
				return ServiceResponse::failure(
					__( 'Failed to resend email.', 'debug-suite' ),
					'send_failed'
				);
			}
		} catch ( Exception $e ) {
			return ServiceResponse::failure(
				__( 'Failed to resend email.', 'debug-suite' ),
				'send_error',
				[ 'error' => $e->getMessage() ]
			);
		}
	}

	/**
	 * Enhance mail data with additional information from PHPMailer.
	 *
	 * @param object $mail_info PHPMailer instance or mail info.
	 * @return array
	 */
	private function enhance_mail_data( $mail_info ): array {
		$enhanced_data = $this->email_data;

		// Try to extract additional data from PHPMailer if available
		if ( is_object( $mail_info ) ) {
			// Check if it's a PHPMailer instance
			if ( property_exists( $mail_info, 'getAllRecipientAddresses' ) && method_exists( $mail_info, 'getAllRecipientAddresses' ) ) {
				$recipients = $mail_info->getAllRecipientAddresses();
				if ( ! empty( $recipients ) ) {
					$enhanced_data['to'] = implode( ', ', array_keys( $recipients ) );
				}
			}

			// Get subject if available
			if ( property_exists( $mail_info, 'Subject' ) ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$enhanced_data['subject'] = $mail_info->Subject;
			}

			// Get body if available
			if ( property_exists( $mail_info, 'Body' ) ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$enhanced_data['message'] = $mail_info->Body;
			}
		}

		return $enhanced_data;
	}

	/**
	 * Validate options for get_email_log_entries.
	 *
	 * @param array $options Query options.
	 * @return ServiceResponse
	 */
	private function validate_get_entries_options( array $options ): ServiceResponse {
		$validation_rules = [
			'status' => [
				'values' => [ 'all', 'success', 'failed', 'pending' ],
				'message' => __( 'Invalid status filter. Use: all, success, failed, or pending.', 'debug-suite' ),
				'code' => 'invalid_status',
			],
			'sort_by' => [
				'values' => [ 'sent_date', 'to_email', 'subject', 'status' ],
				/* translators: %s: comma-separated list of allowed sort fields */
				'message' => __( 'Invalid sort field. Allowed: %s', 'debug-suite' ),
				'code' => 'invalid_sort_field',
			],
			'sort_order' => [
				'values' => [ 'asc', 'desc' ],
				'message' => __( 'Invalid sort order. Use: asc or desc.', 'debug-suite' ),
				'code' => 'invalid_sort_order',
				'transform' => 'strtolower',
			],
		];

		// Validate enum fields
		foreach ( $validation_rules as $field => $rule ) {
			if ( empty( $options[ $field ] ) ) {
				continue;
			}

			$value = $options[ $field ];
			if ( isset( $rule['transform'] ) ) {
				$value = call_user_func( $rule['transform'], $value );
			}

			if ( ! in_array( $value, $rule['values'], true ) ) {
				$message = str_contains( $rule['message'], '%s' )
					? sprintf( $rule['message'], implode( ', ', $rule['values'] ) )
					: $rule['message'];

				return ServiceResponse::failure( $message, $rule['code'] );
			}
		}

		// Validate numeric ranges
		if ( isset( $options['limit'] ) ) {
			$limit = (int) $options['limit'];
			if ( $limit < 1 || $limit > 1000 ) {
				return ServiceResponse::failure(
					__( 'Limit must be between 1 and 1000.', 'debug-suite' ),
					'invalid_limit'
				);
			}
		}

		if ( isset( $options['offset'] ) && (int) $options['offset'] < 0 ) {
			return ServiceResponse::failure(
				__( 'Offset must be 0 or greater.', 'debug-suite' ),
				'invalid_offset'
			);
		}

		// Validate dates
		foreach ( [ 'date_from', 'date_to' ] as $date_field ) {
			if ( ! empty( $options[ $date_field ] ) && ! $this->is_valid_date( $options[ $date_field ] ) ) {
				return ServiceResponse::failure(
					/* translators: %s: the date field name */
					sprintf( __( 'Invalid %s format. Use Y-m-d format.', 'debug-suite' ), $date_field ),
					'invalid_' . $date_field
				);
			}
		}

		return ServiceResponse::success();
	}

	/**
	 * Check if a date string is valid.
	 *
	 * @param string $date Date string.
	 * @return bool
	 */
	private function is_valid_date( string $date ): bool {
		$d = \DateTime::createFromFormat( 'Y-m-d', $date );
		return $d && $d->format( 'Y-m-d' ) === $date;
	}
}
