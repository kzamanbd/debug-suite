<?php
/**
 * Filesystem utilities for Debug Suite using WordPress Filesystem API.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core;

use WP_Filesystem_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Filesystem wrapper for WordPress Filesystem API.
 *
 * Provides a consistent interface for file operations using WordPress
 * Filesystem API instead of direct PHP file functions.
 *
 * @since 1.0.0
 */
class FileSystem {

	/**
	 * WordPress filesystem instance.
	 *
	 * @var WP_Filesystem_Base|null
	 */
	private static ?WP_Filesystem_Base $filesystem = null;

	/**
	 * Initialize WordPress filesystem.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function init(): bool {
		if ( self::$filesystem !== null ) {
			return true;
		}

		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$credentials = request_filesystem_credentials( '', '', false, false, null );

		if ( ! WP_Filesystem( $credentials ) ) {
			return false;
		}

		self::$filesystem = $wp_filesystem;
		return true;
	}

	/**
	 * Get the filesystem instance.
	 *
	 * @return WP_Filesystem_Base|null
	 */
	private static function get_instance(): ?WP_Filesystem_Base {
		if ( ! self::init() ) {
			return null;
		}

		return self::$filesystem;
	}

	/**
	 * Check if a file exists.
	 *
	 * @param string $file_path The file path to check.
	 * @return bool True if file exists, false otherwise.
	 */
	public static function exists( string $file_path ): bool {
		$filesystem = self::get_instance();

		if ( ! $filesystem ) {
			return file_exists( $file_path );
		}

		return $filesystem->exists( $file_path );
	}

	/**
	 * Check if a file is readable.
	 *
	 * @param string $file_path The file path to check.
	 * @return bool True if file is readable, false otherwise.
	 */
	public static function is_readable( string $file_path ): bool {
		$filesystem = self::get_instance();

		if ( ! $filesystem ) {
			return is_readable( $file_path );
		}

		return $filesystem->is_readable( $file_path );
	}

	/**
	 * Check if a file is writable.
	 *
	 * @param string $file_path The file path to check.
	 * @return bool True if file is writable, false otherwise.
	 */
	public static function is_writable( string $file_path ): bool {
		$filesystem = self::get_instance();

		if ( ! $filesystem ) {
			// Use WordPress helper instead of direct PHP is_writable for compatibility with various FS methods.
			return function_exists( 'wp_is_writable' ) ? wp_is_writable( $file_path ) : false;
		}

		return $filesystem->is_writable( $file_path );
	}

	/**
	 * Read file contents.
	 *
	 * @param string $file_path The file path to read.
	 * @return string|false File contents on success, false on failure.
	 */
	public static function get_contents( string $file_path ): string|false {
		$filesystem = self::get_instance();

		if ( ! $filesystem ) {
			return file_get_contents( $file_path );
		}

		return $filesystem->get_contents( $file_path );
	}

	/**
	 * Write content to a file.
	 *
	 * @param string $file_path The file path to write to.
	 * @param string $contents  The content to write.
	 * @param int    $mode      Optional. File permissions. Default 0644.
	 * @return bool True on success, false on failure.
	 */
	public static function put_contents( string $file_path, string $contents, int $mode = 0644 ): bool {
		$filesystem = self::get_instance();

		if ( ! $filesystem ) {
			return file_put_contents( $file_path, $contents ) !== false;
		}

		return $filesystem->put_contents( $file_path, $contents, $mode );
	}

	/**
	 * Copy a file.
	 *
	 * @param string $source      Source file path.
	 * @param string $destination Destination file path.
	 * @param bool   $overwrite   Whether to overwrite existing files. Default false.
	 * @return bool True on success, false on failure.
	 */
	public static function copy( string $source, string $destination, bool $overwrite = false ): bool {
		$filesystem = self::get_instance();

		if ( ! $filesystem ) {
			return copy( $source, $destination );
		}

		return $filesystem->copy( $source, $destination, $overwrite );
	}

	/**
	 * Get file size.
	 *
	 * @param string $file_path The file path.
	 * @return int|false File size in bytes, false on failure.
	 */
	public static function size( string $file_path ): int|false {
		$filesystem = self::get_instance();

		if ( ! $filesystem ) {
			return filesize( $file_path );
		}

		$size = $filesystem->size( $file_path );

		return $size !== false ? $size : 0;
	}

	/**
	 * Get file modification time.
	 *
	 * @param string $file_path The file path.
	 * @return int|false Modification time as Unix timestamp, false on failure.
	 */
	public static function mtime( string $file_path ): int|false {
		$filesystem = self::get_instance();

		if ( ! $filesystem ) {
			return filemtime( $file_path );
		}
		$mtime = $filesystem->mtime( $file_path );
		return $mtime !== false ? $mtime : 0;
	}

	/**
	 * Check if path is a directory.
	 *
	 * @param string $path The path to check.
	 * @return bool True if directory, false otherwise.
	 */
	public static function is_dir( string $path ): bool {
		$filesystem = self::get_instance();

		if ( ! $filesystem ) {
			return is_dir( $path );
		}

		return $filesystem->is_dir( $path );
	}

	/**
	 * Check if path is a file.
	 *
	 * @param string $path The path to check.
	 * @return bool True if file, false otherwise.
	 */
	public static function is_file( string $path ): bool {
		$filesystem = self::get_instance();

		if ( ! $filesystem ) {
			return is_file( $path );
		}

		return $filesystem->is_file( $path );
	}

	/**
	 * Read file contents with tail functionality for large files.
	 *
	 * @param string $file_path The file path to read.
	 * @param int    $bytes     Number of bytes to read from the end.
	 * @return string|false File contents on success, false on failure.
	 */
	public static function read_tail( string $file_path, int $bytes ): string|false {
		// Read file contents via WP_Filesystem when available, otherwise via our wrapper fallback.
		$content = self::get_contents( $file_path );

		if ( $content === false ) {
			return false;
		}

		if ( strlen( $content ) <= $bytes ) {
			return $content;
		}

		return substr( $content, -$bytes );
	}

	/**
	 * Get file permissions.
	 *
	 * @param string $file_path The file path.
	 * @return string|false File permissions as string (e.g., '0644'), false on failure.
	 */
	public static function get_permissions( string $file_path ): string|false {
		$filesystem = self::get_instance();

		if ( ! $filesystem ) {
			$perms = fileperms( $file_path );
			return $perms !== false ? substr( sprintf( '%o', $perms ), -4 ) : false;
		}

		if ( method_exists( $filesystem, 'getchmod' ) ) {
			return $filesystem->getchmod( $file_path );
		}

		$perms = fileperms( $file_path );
		return $perms !== false ? substr( sprintf( '%o', $perms ), -4 ) : false;
	}

	/**
	 * Format file size into human readable format.
	 *
	 * @param int $bytes File size in bytes.
	 * @return string Formatted file size.
	 */
	public static function format_size( int $bytes ): string {
		if ( $bytes >= 1073741824 ) {
			return number_format( $bytes / 1073741824, 2 ) . ' GB';
		} elseif ( $bytes >= 1048576 ) {
			return number_format( $bytes / 1048576, 2 ) . ' MB';
		} elseif ( $bytes >= 1024 ) {
			return number_format( $bytes / 1024, 2 ) . ' KB';
		} elseif ( $bytes > 1 ) {
			return $bytes . ' bytes';
		} elseif ( $bytes === 1 ) {
			return '1 byte';
		} else {
			return 'N/A';
		}
	}

	/**
	 * Check if filesystem is available.
	 *
	 * @return bool True if filesystem is available, false otherwise.
	 */
	public static function is_available(): bool {
		return self::get_instance() !== null;
	}
}
