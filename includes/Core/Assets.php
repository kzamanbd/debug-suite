<?php
/**
 * Assets management for the plugin.
 *
 * @since      1.0.0
 * @package    DebugSuite
 * @subpackage DebugSuite/Core
 * @author     Kamruzzaman <kzamanbn@gmail.com>
 */

namespace DebugSuite\Core;

/**
 * The assets-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and handles the enqueuing of all
 * stylesheets and JavaScript files.
 *
 * @since      1.0.0
 * @package    DebugSuite
 * @subpackage DebugSuite/Core
 * @author     Kamruzzaman <kzamanbn@gmail.com>
 */
class Assets {
	/**
	 * Class constructor
	 *
	 * @return void
	 */
	public function __construct() {
		// Constructor now only sets up the instance
		// Initialization happens in init() method
	}

	/**
	 * Initialize the Assets class
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'register_all_scripts' ] );
	}

	/**
	 * All available scripts
	 *
	 * @return array
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
	 *
	 * @return array
	 */
	public function styles(): array {
		$styles = [];

		$admin_assets = DEBUG_SUITE_PLUGIN_DIR . 'assets/js/debug-suite-admin.asset.php';
		if ( file_exists( $admin_assets ) ) {
			$admin_assets = require $admin_assets;

			$styles['debug-suite-admin'] = [
				'src'     => DEBUG_SUITE_PLUGIN_URL . 'assets/js/debug-suite-admin.css',
				'version' => $admin_assets['version'],
				'deps'    => $admin_assets['dependencies'],
			];
		}

		return apply_filters( 'debug_suite_assets_styles', $styles );
	}

	/**
	 * Register scripts and styles
	 *
	 * @return void
	 */
	public function register_all_scripts(): void {
		$scripts = $this->scripts();
		$styles  = $this->styles();

		foreach ( $scripts as $handle => $script ) {
			$deps = $script['deps'] ?? false;

			wp_register_script( $handle, $script['src'], $deps, $script['version'], true );
		}

		foreach ( $styles as $handle => $style ) {
			$deps = $style['deps'] ?? false;

			wp_register_style( $handle, $style['src'], $deps, $style['version'] );
		}
	}
}
