<?php
/**
 * Settings service for Debug Suite business logic.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Services;

use DebugSuite\Core\ServiceResult;
use DebugSuite\Interfaces\ServiceInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Simple settings service for managing WordPress configuration.
 *
 * @since DEBUG_SUITE_SINCE
 */
class SettingsService implements ServiceInterface {

	/**
	 * Path to the wp-config.php file.
	 *
	 * @var string
	 */
	private string $config_file_path;

	/**
	 * Supported debug constants.
	 *
	 * @var array
	 */
	private array $supported_constants = [
		'WP_DEBUG' => 'false',
		'WP_DEBUG_LOG' => 'false',
		'WP_DEBUG_DISPLAY' => 'false',
	];

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->config_file_path = ABSPATH . 'wp-config.php';
	}

	/**
	 * Update debug settings in wp-config.php.
	 *
	 * @param array $settings The settings to update.
	 * @return ServiceResult
	 */
	public function update_debug_settings( array $settings ): ServiceResult {
		// Validate input
		if ( empty( $settings ) ) {
			return ServiceResult::failure( __( 'No settings provided.', 'debug-suite' ), 'empty_settings' );
		}

		if ( ! file_exists( $this->config_file_path ) ) {
			return ServiceResult::failure( __( 'wp-config.php file not found.', 'debug-suite' ), 'config_file_not_found' );
		}

		if ( ! is_writable( $this->config_file_path ) ) {
			return ServiceResult::failure( __( 'wp-config.php file is not writable.', 'debug-suite' ), 'config_file_not_writable' );
		}

		// Validate settings
		foreach ( $settings as $key => $value ) {
			if ( ! array_key_exists( $key, $this->supported_constants ) ) {
				return ServiceResult::failure(
					// translators: %s is the unsupported setting key.
					sprintf( __( 'Unsupported setting: %s', 'debug-suite' ), $key ),
					'invalid_setting'
				);
			}

			if ( ! in_array( $value, [ 'true', 'false' ], true ) ) {
				return ServiceResult::failure(
					// translators: %s is the setting key that has an invalid value.
					sprintf( __( 'Invalid value for %s. Must be "true" or "false".', 'debug-suite' ), $key ),
					'invalid_value'
				);
			}
		}

		// Read and update file
		$content = file_get_contents( $this->config_file_path );
		if ( $content === false ) {
			return ServiceResult::failure( __( 'Failed to read wp-config.php file.', 'debug-suite' ), 'file_read_error' );
		}

		$updated_content = $this->update_constants( $content, $settings );

		$result = file_put_contents( $this->config_file_path, $updated_content );
		if ( $result === false ) {
			return ServiceResult::failure( __( 'Failed to write wp-config.php file.', 'debug-suite' ), 'file_write_error' );
		}

		return ServiceResult::success( true );
	}

	/**
	 * Get current debug settings from wp-config.php.
	 *
	 * @return ServiceResult
	 */
	public function get_current_debug_settings(): ServiceResult {
		if ( ! file_exists( $this->config_file_path ) ) {
			return ServiceResult::failure( __( 'wp-config.php file not found.', 'debug-suite' ), 'config_file_not_found' );
		}

		$content = file_get_contents( $this->config_file_path );
		if ( $content === false ) {
			return ServiceResult::failure( __( 'Failed to read wp-config.php file.', 'debug-suite' ), 'file_read_error' );
		}

		$settings = [];
		foreach ( $this->supported_constants as $constant => $default ) {
			$settings[ $constant ] = $this->extract_constant_value( $content, $constant, $default );
		}

		return ServiceResult::success( $settings );
	}

	/**
	 * Reset debug settings to default values.
	 *
	 * @return ServiceResult
	 */
	public function reset_debug_settings(): ServiceResult {
		return $this->update_debug_settings( $this->supported_constants );
	}

	/**
	 * Update constants in file content.
	 *
	 * @param string $content File content.
	 * @param array $settings Settings to update.
	 * @return string
	 */
	private function update_constants( string $content, array $settings ): string {
		foreach ( $settings as $constant => $value ) {
			$pattern = "/define\s*\(\s*['\"]" . preg_quote( $constant, '/' ) . "['\"]\s*,\s*.*?\);/";
			$replacement = "define('$constant', $value);";

			if ( preg_match( $pattern, $content ) ) {
				// Update existing constant
				$content = preg_replace( $pattern, $replacement, $content );
			} else {
				// Add new constant before the WordPress loading comment
				$insertion_point = "/* That's all, stop editing!";
				if ( str_contains( $content, $insertion_point ) ) {
					$content = str_replace(
						$insertion_point,
						$replacement . "\n\n/* That's all, stop editing!",
						$content
					);
				}
			}
		}

		return $content;
	}

	/**
	 * Extract constant value from file content.
	 *
	 * @param string $content File content.
	 * @param string $constant Constant name.
	 * @param string $default Default value.
	 * @return string
	 */
	private function extract_constant_value( string $content, string $constant, string $default ): string {
		$pattern = "/define\s*\(\s*['\"]" . preg_quote( $constant, '/' ) . "['\"]\s*,\s*(.*?)\);/";

		if ( preg_match( $pattern, $content, $matches ) ) {
			$value = trim( $matches[1] );
			$value = trim( $value, '\'"' ); // Remove quotes
			return in_array( $value, [ 'true', 'false' ], true ) ? $value : $default;
		}

		return $default;
	}
}
