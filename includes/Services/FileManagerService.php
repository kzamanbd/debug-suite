<?php
/**
 * File manager service for Debug Suite business logic.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Services;

use DebugSuite\Core\FileSystem;
use DebugSuite\Core\ServiceResponse;
use DebugSuite\Interfaces\ServiceInterface;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Simple file manager service for handling file operations.
 *
 * @since 1.0.0
 */
class FileManagerService implements ServiceInterface {

	/**
	 * Base path for operations.
	 *
	 * @var string
	 */
	private string $base_path;

	/**
	 * Load directory size.
	 *
	 * @var bool
	 */
	private bool $load_directory_size = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->base_path = get_option( 'debug_suite_file_manager_base_path', WP_CONTENT_DIR );
		$this->load_directory_size = get_option( 'debug_suite_load_directory_size', false );
	}

	/**
	 * Helper function to format file sizes into readable format
	 *
	 * @param $bytes int
	 *
	 * @return string
	 */
	private function format_size_units( int $bytes ): string {
		return FileSystem::format_size( $bytes );
	}

	/**
	 * Helper function to get the file or directory info
	 *
	 * @param $item SplFileInfo object
	 *
	 * @return object
	 */

	private function get_file_info( SplFileInfo $item ): object {
		$modified_item = [
			'type'        => $item->getType(),
			'name'        => $item->getFilename(),
			'path'        => $item->getPathname(),
			'size'        => $this->format_size_units( $item->getSize() ),
			'modified_at' => debug_suite_date( $item->getMTime() ),
		];

		if ( $item->getType() === 'dir' ) {
			$modified_item['type']     = 'directory';
			$modified_item['size']     = $this->format_size_units( $this->git_directory_size( $item->getPathname() ) );
			$modified_item['expanded'] = false;
			$modified_item['children'] = []; // Recursive call to get the children
		}

		// trim the root path
		$modified_item['path'] = str_replace( ABSPATH, '', $modified_item['path'] );

		return (object) $modified_item;
	}

	/**
	 * Helper function to get the size of a directory
	 *
	 * @param $path
	 *
	 * @return int
	 */

	private function git_directory_size( $path ): int {
		if ( ! $this->load_directory_size ) {
			return 0;
		}

		if ( ! is_dir( $path ) ) {
			return filesize( $path );
		}

		// Use platform-specific du command
		if ( PHP_OS_FAMILY === 'Darwin' ) {
			// macOS doesn't support -b, use -k and convert to bytes
			$size_kb = (int) shell_exec( "du -sk $path | awk '{print $1}'" );
			return $size_kb * 1024;
		} elseif ( PHP_OS_FAMILY === 'Linux' ) {
			// Linux supports -b for bytes
			return (int) shell_exec( "du -sb $path | awk '{print $1}'" );
		}

		return 0;
	}

	/**
	 * Get a directory tree for the specified path.
	 *
	 * @param string $relative_path Path relative to WordPress root.
	 * @param array  $options       Optional parameters.
	 * @return ServiceResponse
	 */
	public function get_directory_tree( string $relative_path = '', array $options = [] ): ServiceResponse {
		$safe_path = $this->sanitize_path( $relative_path );
		$full_path = $this->base_path . $safe_path;

		// Security and validation checks
		if ( ! $this->is_path_safe( $full_path ) ) {
			return ServiceResponse::failure( __( 'Invalid path provided.', 'debug-suite' ), 'invalid_path' );
		}

		if ( ! FileSystem::is_dir( $full_path ) ) {
			return ServiceResponse::failure( __( 'Directory not found.', 'debug-suite' ), 'directory_not_found' );
		}

		$finder = new Finder();
		$finder->ignoreDotFiles( false )->depth( '== 0' )->in( $full_path );

		$files       = [];
		$directories = [];

		foreach ( $finder as $file ) {
			if ( $file->isDir() ) {
				$directories[] = $this->get_file_info( new SplFileInfo( $file ) );
			} else {
				$files[] = $this->get_file_info( new SplFileInfo( $file ) );
			}
		}

		$files       = wp_list_sort( $files, 'name' );
		$directories = wp_list_sort( $directories, 'name' );

		$tree = array_merge( $directories, $files );

		return ServiceResponse::success(
			[
				'tree' => $tree,
				'path' => $relative_path,
			]
		);
	}

	/**
	 * Get file contents.
	 *
	 * @param string $relative_path Path relative to WordPress root.
	 * @return ServiceResponse
	 */
	public function get_file_contents( string $relative_path ): ServiceResponse {
		$safe_path = $this->sanitize_path( $relative_path );
		$full_path = $this->base_path . $safe_path;

		// Security and validation checks
		if ( ! $this->is_path_safe( $full_path ) ) {
			return ServiceResponse::failure( __( 'Invalid path provided.', 'debug-suite' ), 'invalid_path' );
		}

		if ( ! FileSystem::exists( $full_path ) ) {
			return ServiceResponse::failure( __( 'File not found.', 'debug-suite' ), 'file_not_found' );
		}

		if ( ! FileSystem::is_file( $full_path ) ) {
			return ServiceResponse::failure( __( 'Path is not a file.', 'debug-suite' ), 'not_a_file' );
		}

		if ( ! FileSystem::is_readable( $full_path ) ) {
			return ServiceResponse::failure( __( 'File is not readable.', 'debug-suite' ), 'file_not_readable' );
		}

		$contents = FileSystem::get_contents( $full_path );
		$metadata = $this->get_file_metadata( $full_path );

		return ServiceResponse::success(
			[
				'contents' => $contents,
				'metadata' => $metadata,
				'path' => $relative_path,
			]
		);
	}

	/**
	 * Save file contents.
	 *
	 * @param string $relative_path Path relative to WordPress root.
	 * @param string $contents      File contents.
	 * @param array  $options       Save options.
	 * @return ServiceResponse
	 */
	public function save_file_contents( string $relative_path, string $contents, array $options = [] ): ServiceResponse {
		$safe_path = $this->sanitize_path( $relative_path );
		$full_path = $this->base_path . $safe_path;

		// Security and validation checks
		if ( ! $this->is_path_safe( $full_path ) ) {
			return ServiceResponse::failure( __( 'Invalid path provided.', 'debug-suite' ), 'invalid_path' );
		}

		if ( FileSystem::exists( $full_path ) && ! FileSystem::is_writable( $full_path ) ) {
			return ServiceResponse::failure( __( 'File is not writable.', 'debug-suite' ), 'file_not_writable' );
		}

		// Create backup if requested
		$backup_path = null;
		if ( isset( $options['create_backup'] ) ) {
			$backup_result = $this->create_backup( $full_path );
			if ( $backup_result->is_success() ) {
				$backup_path = $backup_result->get_data();
			}
		}

		// Write the file
		$result = FileSystem::put_contents( $full_path, $contents );
		if ( ! $result ) {
			return ServiceResponse::failure( __( 'Failed to save file.', 'debug-suite' ), 'file_save_failed' );
		}

		return ServiceResponse::success(
			[
				'bytes_written' => strlen( $contents ),
				'backup_path' => $backup_path,
				'path' => $relative_path,
			]
		);
	}

	/**
	 * Sanitize the file path.
	 *
	 * @param string $path Path to sanitize.
	 * @return string
	 */
	private function sanitize_path( string $path ): string {
		// Remove directory traversal attempts
		$path = str_replace( [ '../', '..\\' ], '', $path );
		// Normalize separators
		$path = str_replace( '\\', '/', $path );
		// Remove leading slash
		return ltrim( $path, '/' );
	}

	/**
	 * Check if path is within allowed boundaries.
	 *
	 * @param string $full_path Full path to check.
	 * @return bool
	 */
	private function is_path_safe( string $full_path ): bool {
		$real_base = realpath( $this->base_path );

		if ( ! $real_base ) {
			return false;
		}

		if ( FileSystem::exists( $full_path ) ) {
			// For existing paths, verify the resolved path starts with base path
			$real_path = realpath( $full_path );
			return $real_path && str_starts_with( $real_path, $real_base );
		}

		// For new files, verify the parent directory is within allowed boundaries
		$parent_dir = realpath( dirname( $full_path ) );
		return $parent_dir && str_starts_with( $parent_dir, $real_base );
	}

	/**
	 * Get file metadata.
	 *
	 * @param string $full_path Full path to file.
	 * @return array
	 */
	private function get_file_metadata( string $full_path ): array {
		$size = FileSystem::size( $full_path );
		$mtime = FileSystem::mtime( $full_path );

		return [
			'size' => $size,
			'size_mb' => round( ( $size ) / 1024 / 1024, 2 ),
			'modified' => $mtime,
			'modified_date' => wp_date( 'Y-m-d H:i:s', $mtime ),
			'permissions' => FileSystem::get_permissions( $full_path ),
			'is_readable' => FileSystem::is_readable( $full_path ),
			'is_writable' => FileSystem::is_writable( $full_path ),
		];
	}

	/**
	 * Create file backup.
	 *
	 * @param string $file_path Path to backup.
	 * @return ServiceResponse
	 */
	private function create_backup( string $file_path ): ServiceResponse {
		if ( ! FileSystem::exists( $file_path ) ) {
			return ServiceResponse::failure( __( 'File does not exist for backup.', 'debug-suite' ), 'file_not_found' );
		}

		$backup_path = $file_path . '.backup.' . gmdate( 'Y-m-d-H-i-s' );
		$result = FileSystem::copy( $file_path, $backup_path );

		if ( ! $result ) {
			return ServiceResponse::failure( __( 'Failed to create backup.', 'debug-suite' ), 'backup_failed' );
		}

		return ServiceResponse::success( $backup_path );
	}
}
