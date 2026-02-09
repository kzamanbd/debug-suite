<?php
/**
 * EmailLogService for Debug Suite.
 *
 * Provides email logging functionality similar to wp-email-log-db.
 * Captures wp_mail data and stores detailed email logs using the EmailLog model.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Services;

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
		$defaults = [
			'search'     => '',
			'status'     => 'all',
			'receiver'   => '',
			'sort_by'    => 'sent_date',
			'sort_order' => 'desc',
			'per_page'   => 20,
			'page'       => 1,
		];

		$options = wp_parse_args( $options, $defaults );

		// Sanitize and validate options
		$options['per_page'] = max( 1, min( 100, (int) $options['per_page'] ) );
		$options['page'] = max( 1, (int) $options['page'] );
		$options['sort_order'] = strtolower( $options['sort_order'] ) === 'asc' ? 'asc' : 'desc';

		// Calculate offset
		$offset = ( $options['page'] - 1 ) * $options['per_page'];

		// Prepare filters for model
		$filters = [
			'search'     => sanitize_text_field( $options['search'] ),
			'status'     => in_array( $options['status'], [ 'all', 'success', 'failed' ], true ) ? $options['status'] : 'all',
			'receiver'   => sanitize_email( $options['receiver'] ),
			'sort_by'    => in_array( $options['sort_by'], [ 'sent_date', 'subject', 'to_email', 'status' ], true ) ? $options['sort_by'] : 'sent_date',
			'sort_order' => strtoupper( $options['sort_order'] ),
			'limit'      => $options['per_page'],
			'offset'     => $offset,
		];

		try {
			// Get filtered entries
			$entries = EmailLog::get_filtered( $filters );
			$total_count = EmailLog::count_filtered( $filters );

			// Format entries for API response
			$formatted_entries = array_map(
				static function ( EmailLog $entry ) {
					return $entry->to_array();
				},
				$entries
			);

			// Calculate pagination info
			$total_pages = ceil( $total_count / $options['per_page'] );
			$current_page = $options['page'];

			return ServiceResponse::success(
				[
					'entries'      => $formatted_entries,
					'pagination'   => [
						'current_page' => $current_page,
						'total_pages'  => $total_pages,
						'total_items'  => $total_count,
						'per_page'     => $options['per_page'],
						'from'         => $total_count > 0 ? $offset + 1 : 0,
						'to'           => min( $offset + $options['per_page'], $total_count ),
						'has_more'     => $current_page < $total_pages,
					],
					'total_count'  => $total_count,
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
	 * Get filter options for dropdowns.
	 *
	 * @return ServiceResponse
	 */
	public function get_filter_options(): ServiceResponse {
		try {
			$receivers = EmailLog::get_unique_receivers();

			return ServiceResponse::success(
				[
					'receivers' => array_map(
						static function ( $receiver ) {
							return [
								'value' => $receiver,
								'label' => $receiver,
							];
						},
						$receivers
					),
					'statuses'  => [
						[
							'value' => 'all',
							'label' => __( 'All Statuses', 'debug-suite' ),
						],
						[
							'value' => 'success',
							'label' => __( 'Successful', 'debug-suite' ),
						],
						[
							'value' => 'failed',
							'label' => __( 'Failed', 'debug-suite' ),
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
	 * Get email statistics.
	 *
	 * @return ServiceResponse
	 */
	public function get_email_statistics(): ServiceResponse {
		$stats = EmailLog::get_statistics();
		return ServiceResponse::success( $stats );
	}

	/**
	 * Delete multiple email logs.
	 *
	 * @param array $email_ids Array of email log IDs.
	 * @return ServiceResponse
	 */
	public function bulk_delete_emails( array $email_ids ): ServiceResponse {
		if ( empty( $email_ids ) ) {
			return ServiceResponse::failure( __( 'No email IDs provided.', 'debug-suite' ) );
		}

		$deleted_count = EmailLog::delete_by_ids( $email_ids );

		return ServiceResponse::success(
			[
				'deleted_count' => $deleted_count,
				// translators: %d is the number of deleted emails.
				'message'       => sprintf( __( '%d emails deleted.', 'debug-suite' ), $deleted_count ),
			]
		);
	}

	/**
	 * Clear all email logs.
	 *
	 * @return ServiceResponse
	 */
	public function clear_all_emails(): ServiceResponse {
		EmailLog::truncate();
		return ServiceResponse::success( [ 'message' => __( 'All emails cleared.', 'debug-suite' ) ] );
	}

	/**
	 * Resend email by ID.
	 *
	 * @param int $email_id Email log ID.
	 * @return ServiceResponse
	 */
	public function resend_email( int $email_id ): ServiceResponse {
		$email = EmailLog::find( $email_id );

		if ( ! $email ) {
			return ServiceResponse::failure(
				__( 'Email not found.', 'debug-suite' ),
				'email_not_found'
			);
		}

		$sent = wp_mail(
			$email->to_email,
			$email->subject,
			$email->message,
			$email->get_headers(),
			$email->get_attachments()
		);

		if ( $sent ) {
			return ServiceResponse::success( [ 'message' => __( 'Email resent.', 'debug-suite' ) ] );
		}

		return ServiceResponse::failure(
			__( 'Failed to resend email.', 'debug-suite' ),
			'send_failed'
		);
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
}
