<?php
/**
 * File manager REST API controller for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\API;

use DebugSuite\Admin\FileManager\FileManager;
use Exception;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * REST API controller for Debug Suite file management.
 *
 * Handles REST API endpoints for browsing files and directories, retrieving
 * file contents, and managing file system operations within the WordPress
 * installation directory.
 *
 * @since DEBUG_SUITE_SINCE
 */
class FileManagerController extends RestController {

	/**
	 * File manager instance for handling file operations.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var FileManager
	 */
	private FileManager $file_manager;

	/**
	 * Route base for files endpoints.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var string
	 */
	protected $rest_base = 'files';

	/**
	 * Constructor for FileManagerController.
	 *
	 * Initializes the file manager instance for handling file operations.
	 *
	 * @since DEBUG_SUITE_SINCE
	 */
	public function __construct() {
		$this->file_manager = new FileManager();
	}

	/**
	 * Register the routes for file management endpoints.
	 *
	 * Registers REST API routes for browsing directories and retrieving
	 * file contents with proper validation and authentication.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_files' ],
					'permission_callback' => [ $this, 'permissions_check' ],
					'args'                => [
						'path' => [
							'description'       => __( 'The path to the directory to retrieve files from.', 'debug-suite' ),
							'type'              => 'string',
							'required'          => false,
							'default'           => '',
							'validate_callback' => function ( $param ) {
								// Validate that the path is a valid string.
								return is_string( $param );
							},
						],
					],
				],
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
					'args'                => [
						'path' => [
							'description'       => __( 'The path to the directory to retrieve files from.', 'debug-suite' ),
							'type'              => 'string',
							'required'          => true,
							'default'           => '',
							'validate_callback' => function ( $param ) {
								// Validate that the path is a valid string.
								return is_string( $param );
							},
						],
					],
				],
			]
		);
	}

	/**
	 * Get files and directories from specified path.
	 *
	 * Retrieves the directory tree structure from the specified path
	 * relative to the WordPress installation directory. Handles errors
	 * gracefully with proper exception handling.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param WP_REST_Request $request The REST request object containing path parameter.
	 *
	 * @return WP_REST_Response|WP_Error Response with directory tree or error on failure.
	 *
	 * @throws Exception When directory operations fail.
	 */
	public function get_files( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		try {
			$path      = $request->get_param( 'path' ) ?? '';
			$full_path = ABSPATH . $path;
			$files     = $this->file_manager->get_directory_tree( $full_path );

			return rest_ensure_response(
				[
					'current_path' => $path,
					'files'        => $files,
				]
			);
		} catch ( Exception $e ) {
			$message = $e->getMessage();
			if ( $e instanceof DirectoryNotFoundException ) {
				$message = sprintf(
				// translators: %s is the directory path that was not found.
					__( 'Directory not found: %s', 'debug-suite' ),
					$request->get_param( 'path' )
				);
			}

			return new WP_Error(
				'debug_suite_file_manager_error',
				$message,
				[
					'status' => 500,
				]
			);
		}
	}

	/**
	 * Get the contents of a specified file.
	 *
	 * Retrieves and returns the raw contents of a file along with
	 * metadata such as file extension and path information.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param WP_REST_Request $request The REST request object containing file path.
	 *
	 * @return WP_REST_Response|WP_Error Response with file contents or error on failure.
	 *
	 * @throws Exception When file reading operations fail.
	 */

	public function get_file_contents( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$path = $request->get_param( 'path' );
		try {
			$contents = $this->file_manager->get_file_contents( $path );

			return rest_ensure_response(
				[
					'path'      => $path,
					'extension' => pathinfo( $path, PATHINFO_EXTENSION ),
					'contents'  => $contents,
				]
			);
		} catch ( Exception $e ) {
			return new WP_Error(
				'debug_suite_file_manager_error_contents',
				$e->getMessage(),
				[
					'path'   => $path,
					'status' => 500,
				]
			);
		}
	}
}
