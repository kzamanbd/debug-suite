<?php
/**
 * Settings service for Debug Suite business logic.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Services;

use DebugSuite\Core\FileSystem;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Simple settings service for managing WordPress configuration.
 *
 * @since 1.0.0
 */
class SettingsService {

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
	public array $supported_constants = [
		'WP_DEBUG' => 'false',
		'WP_DEBUG_LOG' => 'false',
		'WP_DEBUG_DISPLAY' => 'false',
	];

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Use WordPress function to get proper config file path
		if ( ! function_exists( 'get_home_path' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$this->config_file_path = get_home_path() . 'wp-config.php';
	}

	/**
	 * Update debug settings in wp-config.php.
	 *
	 * @param array $settings The settings to update.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function update_settings( array $settings ): bool|WP_Error {
		// Validate input
		if ( empty( $settings ) ) {
			return new WP_Error( 'empty_settings', __( 'No settings provided to update.', 'debug-suite' ) );
		}

		if ( ! FileSystem::is_writable( $this->config_file_path ) ) {
			return new WP_Error( 'config_file_not_writable', __( 'wp-config.php file is not writable.', 'debug-suite' ) );
		}

		// Validate settings
		foreach ( $settings as $key => $value ) {
			if ( ! array_key_exists( $key, $this->supported_constants ) ) {
				return new WP_Error(
					'invalid_setting',
					// translators: %s is the unsupported setting key.
					sprintf( __( 'Unsupported setting: %s', 'debug-suite' ), $key )
				);
			}

			if ( ! in_array( $value, [ 'true', 'false' ], true ) ) {
				return new WP_Error(
					'invalid_value',
					// translators: %s is the setting key that has an invalid value.
					sprintf( __( 'Invalid value for %s. Must be "true" or "false".', 'debug-suite' ), $key )
				);
			}
		}

		// Read and update file
		$content = FileSystem::get_contents( $this->config_file_path );
		if ( $content === false ) {
			return new WP_Error( 'file_read_error', __( 'Failed to read wp-config.php file.', 'debug-suite' ) );
		}

		$updated_content = $this->update_constants( $content, $settings );

		$result = FileSystem::put_contents( $this->config_file_path, $updated_content );
		if ( ! $result ) {
			return new WP_Error( 'file_write_error', __( 'Failed to write wp-config.php file.', 'debug-suite' ) );
		}

		return true;
	}

	/**
	 * Get current debug settings from wp-config.php.
	 *
	 * @return array|WP_Error Settings array on success, WP_Error on failure.
	 */
	public function get_settings(): array|WP_Error {
		if ( ! FileSystem::exists( $this->config_file_path ) ) {
			return new WP_Error( 'config_file_not_found', __( 'wp-config.php file not found.', 'debug-suite' ) );
		}

		$content = FileSystem::get_contents( $this->config_file_path );
		if ( $content === false ) {
			return new WP_Error( 'file_read_error', __( 'Failed to read wp-config.php file.', 'debug-suite' ) );
		}

		$settings = [
			'onboarding_completed' => get_option( 'debug_suite_onboarding_completed', false ),
		];
		foreach ( $this->supported_constants as $constant => $default ) {
			$value = $this->extract_constant_value( $content, $constant, $default );
			$settings[ strtolower( $constant ) ] = $value === 'true'; // Convert to boolean
		}

		return $settings;
	}

	/**
	 * Reset debug settings to default values.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function reset_debug_settings(): bool|WP_Error {
		return $this->update_settings( $this->supported_constants );
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
