<?php
/**
 * File manager REST API controller for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\API;

use DebugSuite\Services\FileManagerService;
use DebugSuite\Core\ServiceResponse;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Exception;
use PhpParser\ParserFactory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simple file manager controller for Debug Suite.
 *
 * @since DEBUG_SUITE_SINCE
 */
class FileManagerController extends RestController {

	private FileManagerService $service;
	protected $rest_base = 'files';

	public function __construct( FileManagerService $service ) {
		$this->service = $service;
	}

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_files' ],
				'permission_callback' => [ $this, 'permissions_check' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/content',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_file_contents' ],
					'permission_callback' => [ $this, 'permissions_check' ],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'save_file_contents' ],
					'permission_callback' => [ $this, 'permissions_check' ],
				],
			]
		);
	}

	public function get_files( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$path = $request->get_param( 'path' ) ?? '';
		$result = $this->service->get_directory_tree( $path );

		return $result->is_failure()
			? new WP_Error( $result->get_error_code(), $result->get_error_message(), [ 'status' => 500 ] )
			: rest_ensure_response( $result->get_data() );
	}

	public function get_file_contents( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$path = $request->get_param( 'path' );
		$result = $this->service->get_file_contents( $path );

		if ( $result->is_failure() ) {
			$status_code = match ( $result->get_error_code() ) {
				'file_not_found' => 404,
				'file_not_readable' => 403,
				'invalid_path' => 400,
				default => 500
			};
			return new WP_Error( $result->get_error_code(), $result->get_error_message(), [ 'status' => $status_code ] );
		}

		return rest_ensure_response( $result->get_data() );
	}

	public function save_file_contents( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$path = $request->get_param( 'path' );
		$contents = $request->get_param( 'contents' );
		$create_backup = $request->get_param( 'create_backup' ) ?? false;

		// Validate PHP syntax if it's a PHP file
		if ( pathinfo( $path, PATHINFO_EXTENSION ) === 'php' ) {
			$validation_result = $this->validate_php_syntax( $contents );
			if ( $validation_result->is_failure() ) {
				return new WP_Error(
					'php_syntax_error',
					$validation_result->get_error_message(),
					[ 'status' => 400 ]
				);
			}
		}

		$options = [ 'create_backup' => $create_backup ];
		$result = $this->service->save_file_contents( $path, $contents, $options );

		return $result->is_failure()
			? new WP_Error( $result->get_error_code(), $result->get_error_message(), [ 'status' => 500 ] )
			: rest_ensure_response(
				[
					'success' => true,
					'message' => __( 'File saved successfully.', 'debug-suite' ),
				]
			);
	}

	/**
	 * Validates PHP syntax using PHP-Parser.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $contents PHP code to validate.
	 * @return ServiceResponse Success if syntax is valid, failure with error message if not.
	 */
	private function validate_php_syntax( string $contents ): ServiceResponse {

		try {
			$parser = ( new ParserFactory() )->create( 1 ); // 1 is PHP7
			$parser->parse( $contents );
			return ServiceResponse::success();
		} catch ( Exception $e ) {
			return ServiceResponse::failure(
				$e->getMessage(),
				'php_syntax_error'
			);
		}
	}
}
