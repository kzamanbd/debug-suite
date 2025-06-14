<?php

/**
 * Assets management for the plugin.
 */

namespace DebugSuite\Core;

use DebugSuite\Interfaces\Hookable;

/**
 * The assets-specific functionality of the plugin.
 *
 * Handles the enqueuing of stylesheets and JavaScript files.
 */
class Assets implements Hookable {

	/**
	 * Register hooks for WordPress.
	 * This method will be called automatically to register the hooks.
	 */
	public function register_hooks(): void {
		add_action( 'init', [ $this, 'register_all_scripts' ] );
	}

	/**
	 * All available scripts
	 */
	public function scripts(): array {
		$scripts = [];

		$admin_assets = DEBUG_SUITE_PLUGIN_DIR . 'assets/js/debug-suite-admin.asset.php';
		if ( file_exists( $admin_assets ) ) {
			$admin_assets = require $admin_assets;

			$scripts['debug-suite-admin'] = [
				'src'     => DEBUG_SUITE_PLUGIN_URL . 'assets/js/debug-suite-admin.js',
				'version' => $admin_assets['version'],
				'deps'    => $admin_assets['dependencies'],
			];
		}

		return apply_filters( 'debug_suite_assets_scripts', $scripts );
	}

	/**
	 * All available styles
	 */
	public function styles(): array {
		$styles = [];

		$admin_assets = DEBUG_SUITE_PLUGIN_DIR . 'assets/js/debug-suite-admin.asset.php';
		if ( file_exists( $admin_assets ) ) {
			$admin_assets = require $admin_assets;

			$styles['debug-suite-admin'] = [
				'src'     => DEBUG_SUITE_PLUGIN_URL . 'assets/css/debug-suite-admin.css',
				'version' => $admin_assets['version'],
				'deps'    => [], // CSS files typically don't need JavaScript dependencies
			];
		}

		return apply_filters( 'debug_suite_assets_styles', $styles );
	}

	/**
	 * Register scripts and styles
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
}
