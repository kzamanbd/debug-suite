<?php
/**
 * Settings REST API controller for Debug Suite.
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
 * Class SettingsController
 *
 * Handles REST API endpoints for Debug Suite settings.
 *
 * @since 1.0.0
 */
class FileManagerController extends RestController {

	private FileManager $file_manager;
	/**
	 * Route base for settings.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $rest_base = 'files';

	public function __construct() {
		$this->file_manager = new FileManager();
	}

	/**
	 * Register the routes for settings.
	 *
	 * @return void
	 * @since 1.0.0
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
	 * Get Debug Suite File Logs.
	 *
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 1.0.0
	 */
	public function get_files( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		try {
			$path         = $request->get_param( 'path' ) ?? '';
			$full_path    = ABSPATH . $path;
			$files        = $this->file_manager->get_directory_tree( $full_path );

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
	 * Get the contents of a file.
	 *
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 1.0.0
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
