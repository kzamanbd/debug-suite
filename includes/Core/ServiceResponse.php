<?php
/**
 * Service response class for consistent service layer responses.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Response class for service layer operations.
 *
 * Provides a consistent way to return success/failure states with data
 * and error messages from service layer operations.
 *
 * @since 1.0.0
 *
 * @template T
 */
final class ServiceResponse {
	/**
	 * Whether the operation was successful.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	private bool $success;

	/**
	 * The data returned by the operation.
	 *
	 * @since 1.0.0
	 *
	 * @var mixed
	 */
	private mixed $data;

	/**
	 * Error message if the operation failed.
	 *
	 * @since 1.0.0
	 *
	 * @var string|null
	 */
	private ?string $error_message;

	/**
	 * Error code if the operation failed.
	 *
	 * @since 1.0.0
	 *
	 * @var string|null
	 */
	private ?string $error_code;

	/**
	 * Additional context data for the result.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, mixed>
	 */
	private array $context;

	/**
	 * Constructor for ServiceResponse.
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_success(): bool {
		return $this->success;
	}

	/**
	 * Check if the operation failed.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_failure(): bool {
		return ! $this->success;
	}

	/**
	 * Get the data from the result.
	 *
	 * @since 1.0.0
	 *
	 * @return mixed
	 */
	public function get_data(): mixed {
		return $this->data;
	}

	/**
	 * Get the error message.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function get_error_message(): ?string {
		return $this->error_message;
	}

	/**
	 * Get the error code.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function get_error_code(): ?string {
		return $this->error_code;
	}

	/**
	 * Get the context data.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_context(): array {
		return $this->context;
	}

	/**
	 * Get a specific context value.
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_error_context(): array {
		return $this->context;
	}

	/**
	 * Get the data or a default value if this is a failure.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $default The default value to return if this is a failure.
	 *
	 * @return mixed
	 */
	public function get_data_or( mixed $default ): mixed {
		return $this->is_success() ? $this->data : $default;
	}

	/**
	 * Convert the response to an array suitable for API responses.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		$response = [
			'success' => $this->success,
		];

		if ( $this->success ) {
			$response['data'] = $this->data;
		} else {
			$response['error'] = [
				'message' => $this->error_message,
				'code'    => $this->error_code,
				'context' => $this->context,
			];
		}

		if ( ! empty( $this->context ) && $this->success ) {
			$response['context'] = $this->context;
		}

		return $response;
	}
}
