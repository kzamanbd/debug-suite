<?php
/**
 * Service result class for consistent service layer responses.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Result class for service layer operations.
 *
 * Provides a consistent way to return success/failure states with data
 * and error messages from service layer operations.
 *
 * @since DEBUG_SUITE_SINCE
 *
 * @template T
 */
final class ServiceResult {
	/**
	 * Whether the operation was successful.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var bool
	 */
	private bool $success;

	/**
	 * The data returned by the operation.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var mixed
	 */
	private mixed $data;

	/**
	 * Error message if the operation failed.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var string|null
	 */
	private ?string $error_message;

	/**
	 * Error code if the operation failed.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var string|null
	 */
	private ?string $error_code;

	/**
	 * Additional context data for the result.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var array<string, mixed>
	 */
	private array $context;

	/**
	 * Constructor for ServiceResult.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param bool                 $success       Whether the operation was successful.
	 * @param mixed                $data          The data returned by the operation.
	 * @param string|null          $error_message Error message if the operation failed.
	 * @param string|null          $error_code    Error code if the operation failed.
	 * @param array<string, mixed> $context       Additional context data.
	 */
	private function __construct(
		bool $success,
		mixed $data = null,
		?string $error_message = null,
		?string $error_code = null,
		array $context = []
	) {
		$this->success       = $success;
		$this->data          = $data;
		$this->error_message = $error_message;
		$this->error_code    = $error_code;
		$this->context       = $context;
	}

	/**
	 * Create a successful result.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param mixed                $data    The data to return.
	 * @param array<string, mixed> $context Additional context data.
	 *
	 * @return self
	 */
	public static function success( mixed $data = null, array $context = [] ): self {
		return new self( true, $data, null, null, $context );
	}

	/**
	 * Create a failed result.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string               $error_message The error message.
	 * @param string|null          $error_code    The error code.
	 * @param array<string, mixed> $context       Additional context data.
	 *
	 * @return self
	 */
	public static function failure( string $error_message, ?string $error_code = null, array $context = [] ): self {
		return new self( false, null, $error_message, $error_code, $context );
	}

	/**
	 * Check if the operation was successful.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return bool
	 */
	public function is_success(): bool {
		return $this->success;
	}

	/**
	 * Check if the operation failed.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return bool
	 */
	public function is_failure(): bool {
		return ! $this->success;
	}

	/**
	 * Get the data from the result.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return mixed
	 */
	public function get_data(): mixed {
		return $this->data;
	}

	/**
	 * Get the error message.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return string|null
	 */
	public function get_error_message(): ?string {
		return $this->error_message;
	}

	/**
	 * Get the error code.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return string|null
	 */
	public function get_error_code(): ?string {
		return $this->error_code;
	}

	/**
	 * Get the context data.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return array<string, mixed>
	 */
	public function get_context(): array {
		return $this->context;
	}

	/**
	 * Get a specific context value.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $key     The context key.
	 * @param mixed  $default Default value if key doesn't exist.
	 *
	 * @return mixed
	 */
	public function get_context_value( string $key, mixed $default = null ): mixed {
		return $this->context[ $key ] ?? $default;
	}

	/**
	 * Get the context data for an error.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return array<string, mixed>
	 */
	public function get_error_context(): array {
		return $this->context;
	}

	/**
	 * Get the data or a default value if this is a failure.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param mixed $default The default value to return if this is a failure.
	 *
	 * @return mixed
	 */
	public function get_data_or( mixed $default ): mixed {
		return $this->is_success() ? $this->data : $default;
	}

	/**
	 * Convert the result to an array suitable for API responses.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		$result = [
			'success' => $this->success,
		];

		if ( $this->success ) {
			$result['data'] = $this->data;
		} else {
			$result['error'] = [
				'message' => $this->error_message,
				'code'    => $this->error_code,
				'context' => $this->context,
			];
		}

		if ( ! empty( $this->context ) && $this->success ) {
			$result['context'] = $this->context;
		}

		return $result;
	}
}
