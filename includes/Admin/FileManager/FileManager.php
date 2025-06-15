<?php

namespace DebugSuite\Admin\FileManager;

use InvalidArgumentException;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class FileManager {

	/**
	 * Helper function to format file sizes into readable format
	 *
	 * @param $bytes int
	 *
	 * @return string
	 */
	private function format_size_units( int $bytes ): string {
		if ( $bytes >= 1073741824 ) {
			$bytes = number_format( $bytes / 1073741824, 2 ) . ' GB';
		} elseif ( $bytes >= 1048576 ) {
			$bytes = number_format( $bytes / 1048576, 2 ) . ' MB';
		} elseif ( $bytes >= 1024 ) {
			$bytes = number_format( $bytes / 1024, 2 ) . ' KB';
		} elseif ( $bytes > 1 ) {
			$bytes = $bytes . ' bytes';
		} elseif ( $bytes === 1 ) {
			$bytes = '1 byte';
		} else {
			$bytes = 'N/A';
		}

		return $bytes;
	}

	/**
	 * Get only directories
	 *
	 * @param $content
	 *
	 * @return array
	 */
	private function filter_dir( $content ): array {
		$dirs = array_map(
			function ( $item ) {
				$path = pathinfo( $item['path'] );

				return [
					'type'       => $item['type'],
					'path'       => $item['path'],
					'basename'   => $path['basename'],
					'dirname'    => $path['dirname'] === '.' ? '' : $path['dirname'],
					'timestamp'  => $item['lastModified'],
					'visibility' => $item['visibility'],
				];
			},
			array_filter( $content, fn( $item ) => $item['type'] === 'dir' )
		);

		return array_values( $dirs );
	}

	/**
	 * Get only files
	 *
	 * @param $content
	 *
	 * @return array
	 */
	private function filter_file( $content ): array {
		$files = array_map(
			function ( $item ) {
				$path = pathinfo( $item['path'] );

				return [
					'type'       => $item['type'],
					'path'       => $item['path'],
					'basename'   => $path['basename'],
					'dirname'    => $path['dirname'] === '.' ? '' : $path['dirname'],
					'extension'  => $path['extension'] ?? '',
					'filename'   => $path['filename'],
					'size'       => $item['fileSize'],
					'timestamp'  => $item['lastModified'],
					'visibility' => $item['visibility'],
				];
			},
			array_filter( $content, fn( $item ) => $item['type'] === 'file' )
		);

		return array_values( $files );
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
			'modified_at' => $item->getMTime(),
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

		if ( ! is_dir( $path ) ) {
			return filesize( $path );
		}

		// if os is Unix-based or macOS, then use the du command
		if ( PHP_OS_FAMILY === 'Darwin' || PHP_OS_FAMILY === 'Linux' ) {
			return (int) shell_exec( "du -sb $path | awk '{print $1}'" );
		}

		return 0;
	}

	/**
	 * Validate if the given path is an absolute path with a file extension.
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	private static function is_valid_file_path( string $path ): bool {

		// Check if the path has a file extension
		$has_extension = pathinfo( $path, PATHINFO_EXTENSION ) !== '';

		return $has_extension && is_file( $path );
	}

	/**
	 * Function to recursively build the directory tree
	 *
	 * @param string $path
	 *
	 * @return array
	 */
	public function get_directory_tree( string $path ): array {
		$finder = new Finder();
		$finder->ignoreDotFiles( false )->depth( '== 0' )->in( $path );

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

		return array_merge( $directories, $files );
	}

	/**
	 * Get the contents of a file
	 *
	 * @param string $file_path
	 *
	 * @return string|null
	 */
	public static function get_file_contents( string $file_path ): ?string {
		$full_path = ABSPATH . $file_path;
		if ( ! self::is_valid_file_path( $full_path ) ) {
			throw new InvalidArgumentException( 'Invalid file path provided.' );
		}
		if ( ! file_exists( $full_path ) ) {
			return null;
		}

		return file_get_contents( $full_path );
	}
}
