<?php
/**
 * Mock factory for creating test mocks and stubs.
 *
 * @package DebugSuite\Tests
 */

namespace DebugSuite\Tests\Helpers;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use DebugSuite\Core\ServiceResponse;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Factory class for creating common test mocks and stubs.
 *
 * @since 1.0.0
 */
class MockFactory {

	/**
	 * Test case instance for creating mocks.
	 *
	 * @var TestCase
	 */
	private TestCase $test_case;

	/**
	 * Constructor.
	 *
	 * @param TestCase $test_case Test case instance.
	 */
	public function __construct( TestCase $test_case ) {
		$this->test_case = $test_case;
	}

	/**
	 * Create a ServiceResponse that returns success.
	 *
	 * @param array $data Optional data to return.
	 * @return ServiceResponse
	 */
	public function create_successful_service_result( array $data = [] ): ServiceResponse {
		return ServiceResponse::success( $data );
	}

	/**
	 * Create a ServiceResponse that returns failure.
	 *
	 * @param string $error_message Error message.
	 * @param string $error_code    Error code.
	 * @param array  $context       Additional context.
	 * @return ServiceResponse
	 */
	public function create_failed_service_result( 
		string $error_message = 'Test error', 
		string $error_code = 'test_error',
		array $context = []
	): ServiceResponse {
		return ServiceResponse::failure( $error_message, $error_code, $context );
	}

	/**
	 * Create a file logs service.
	 *
	 * @return mixed
	 */
	public function create_file_logs_service() {
		// Create a minimal implementation using anonymous class
		return new class {
			public function get_log_entries( array $options = [] ) {
				return ServiceResponse::success([
					'entries' => [],
					'total' => 0,
				]);
			}
			
			public function get_log_file_stats() {
				return ServiceResponse::success([
					'size' => 0,
					'modified' => current_time('mysql'),
					'exists' => true,
				]);
			}
			
			public function clear_log_file() {
				return ServiceResponse::success();
			}
		};
	}

	/**
	 * Create a file manager service.
	 *
	 * @return mixed
	 */
	public function create_file_manager_service() {
		// Create a minimal implementation using anonymous class
		return new class {
			public function get_directory_tree( string $path = '', array $options = [] ) {
				return ServiceResponse::success([
					'path' => $path,
					'files' => [],
				]);
			}
			
			public function get_file_contents( string $path ) {
				return ServiceResponse::success([
					'content' => '',
					'path' => $path,
				]);
			}
			
			public function save_file_contents( string $path, string $content, array $options = [] ) {
				return ServiceResponse::success();
			}
		};
	}

	/**
	 * Create a settings service.
	 *
	 * @return mixed
	 */
	public function create_settings_service() {
		// Create a minimal implementation using anonymous class
		return new class {
			public function get_debug_settings() {
				return ServiceResponse::success([
					'WP_DEBUG' => true,
					'WP_DEBUG_LOG' => true,
					'WP_DEBUG_DISPLAY' => false,
				]);
			}
			
			public function update_settings( array $settings ) {
				return ServiceResponse::success();
			}
			
			public function reset_debug_settings() {
				return ServiceResponse::success();
			}
		};
	}

	/**
	 * Create a WP_REST_Request.
	 *
	 * @param array $params Request parameters.
	 * @return mixed
	 */
	public function create_wp_rest_request( array $params = [] ) {
		// Create a minimal implementation using anonymous class
		return new class($params) {
			private $params;
			
			public function __construct( $params ) {
				$this->params = $params;
			}
			
			public function get_param( $key ) {
				return $this->params[$key] ?? null;
			}
			
			public function get_params() {
				return $this->params;
			}
		};
	}

	/**
	 * Create a WP_REST_Response.
	 *
	 * @param array $data Response data.
	 * @param int   $status HTTP status code.
	 * @return mixed
	 */
	public function create_wp_rest_response( array $data = [], int $status = 200 ) {
		// Create a minimal implementation using anonymous class
		return new class($data, $status) {
			private $data;
			private $status;
			
			public function __construct( $data, $status ) {
				$this->data = $data;
				$this->status = $status;
			}
			
			public function get_data() {
				return $this->data;
			}
			
			public function get_status() {
				return $this->status;
			}
		};
	}

	/**
	 * Create a WP_Error.
	 *
	 * @param string $code Error code.
	 * @param string $message Error message.
	 * @param array  $data Additional data.
	 * @return mixed
	 */
	public function create_wp_error( 
		string $code = 'test_error', 
		string $message = 'Test error message',
		array $data = []
	) {
		// Create a minimal implementation using anonymous class
		return new class($code, $message, $data) {
			private $code;
			private $message;
			private $data;
			
			public function __construct( $code, $message, $data ) {
				$this->code = $code;
				$this->message = $message;
				$this->data = $data;
			}
			
			public function get_error_code() {
				return $this->code;
			}
			
			public function get_error_message() {
				return $this->message;
			}
			
			public function get_error_data() {
				return $this->data;
			}
		};
	}

	/**
	 * Create a simple object with properties.
	 *
	 * @param array $properties Object properties.
	 * @return object
	 */
	public function create_simple_object( array $properties = [] ): object {
		$object = new \stdClass();
		
		foreach ( $properties as $key => $value ) {
			$object->$key = $value;
		}
		
		return $object;
	}

	/**
	 * Create a test log entry.
	 *
	 * @param array $overrides Property overrides.
	 * @return array
	 */
	public function create_log_entry( array $overrides = [] ): array {
		$defaults = [
			'timestamp' => '2025-06-18 10:30:00',
			'level'     => 'ERROR',
			'message'   => 'Test error message',
			'file'      => '/path/to/test-file.php',
			'line'      => 42,
			'context'   => [],
		];
		
		return array_merge( $defaults, $overrides );
	}

	/**
	 * Create test file metadata.
	 *
	 * @param array $overrides Property overrides.
	 * @return array
	 */
	public function create_file_metadata( array $overrides = [] ): array {
		$defaults = [
			'name'           => 'test-file.php',
			'path'           => '/path/to/test-file.php',
			'type'           => 'file',
			'size'           => 1024,
			'modified'       => '2025-06-18 10:30:00',
			'permissions'    => '0644',
			'is_readable'    => true,
			'is_writable'    => true,
		];
		
		return array_merge( $defaults, $overrides );
	}

	/**
	 * Create test directory tree structure.
	 *
	 * @param array $overrides Structure overrides.
	 * @return array
	 */
	public function create_directory_tree( array $overrides = [] ): array {
		$defaults = [
			'name'     => 'test-directory',
			'path'     => '/path/to/test-directory',
			'type'     => 'directory',
			'children' => [
				[
					'name' => 'file1.php',
					'path' => '/path/to/test-directory/file1.php',
					'type' => 'file',
					'size' => 512,
				],
				[
					'name' => 'file2.txt',
					'path' => '/path/to/test-directory/file2.txt',
					'type' => 'file',
					'size' => 256,
				],
			],
		];
		
		return array_merge( $defaults, $overrides );
	}
}
