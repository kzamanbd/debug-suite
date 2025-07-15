<?php

namespace DebugSuite;

use DebugSuite\Interfaces\Hookable;
use DebugSuite\Services\DebugLog\LogsService;

class Assets implements Hookable {

	/**
	 * Register hooks for WordPress.
	 *
	 * Registers the necessary WordPress hooks for asset management,
	 * including script and style registration and enqueuing.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', [ $this, 'register_all_scripts' ] );
	}

	/**
	 * Get all available scripts configuration.
	 *
	 * Returns an array of JavaScript files that can be registered and enqueued.
	 * Includes information about dependencies, versions, and source paths.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array{src: string, version: string, deps: array<string>}> Scripts configuration array.
	 */
	public function scripts(): array {
		$scripts = [];

		$admin_assets = DEBUG_SUITE_PLUGIN_DIR . 'assets/js/debug-suite.asset.php';
		if ( file_exists( $admin_assets ) ) {
			$admin_assets = require $admin_assets;

			$scripts['debug-suite-script'] = [
				'src'     => DEBUG_SUITE_PLUGIN_URL . 'assets/js/debug-suite.js',
				'version' => $admin_assets['version'],
				'deps'    => $admin_assets['dependencies'],
			];
		}

		return apply_filters( 'debug_suite_assets_scripts', $scripts );
	}

	/**
	 * Get all available styles configuration.
	 *
	 * Returns an array of CSS files that can be registered and enqueued.
	 * Includes information about dependencies, versions, and source paths.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array{src: string, version: string, deps: array<string>}> Styles configuration array.
	 */
	public function styles(): array {
		$styles = [];

		$admin_assets = DEBUG_SUITE_PLUGIN_DIR . 'assets/js/debug-suite.asset.php';
		if ( file_exists( $admin_assets ) ) {
			$admin_assets = require $admin_assets;

			$styles['debug-suite-style'] = [
				'src'     => DEBUG_SUITE_PLUGIN_URL . 'assets/css/debug-suite.css',
				'version' => $admin_assets['version'],
				'deps'    => [], // CSS files typically don't need JavaScript dependencies
			];
		}

		return apply_filters( 'debug_suite_assets_styles', $styles );
	}

	/**
	 * Register all scripts and styles with WordPress.
	 *
	 * Registers all available scripts and styles with WordPress using
	 * wp_register_script() and wp_register_style() functions.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_all_scripts(): void {
		$scripts = $this->scripts();
		$styles  = $this->styles();

		foreach ( $scripts as $handle => $script ) {
			$deps = $script['deps'] ?? [];

			wp_register_script( $handle, $script['src'], $deps, $script['version'], true );
		}

		foreach ( $styles as $handle => $style ) {
			$deps = $style['deps'] ?? [];

			wp_register_style( $handle, $style['src'], $deps, $style['version'] );
		}
	}

	public static function get_localized_data(): array {
		$files = debug_suite()->resolve( LogsService::class )->supported_log_files();
		$constants = [
			'debug'         => WP_DEBUG,
			'debug_log'     => WP_DEBUG_LOG,
			'debug_display' => WP_DEBUG_DISPLAY,
			'root_path'     => ABSPATH,
			'content_url'   => content_url(),
			'favicon'       => DEBUG_SUITE_PLUGIN_URL . 'assets/images/brand-logo.png',
			'wp_version'    => get_bloginfo( 'version' ),
			'php_version'   => phpversion(),
			'logs'          => $files,
		];
		$settings  = get_option( 'debug_suite_settings', [] );
		$settings  = array_merge( $constants, $settings );

		return apply_filters( 'debug_suite_localized_data', $settings );
	}
}
