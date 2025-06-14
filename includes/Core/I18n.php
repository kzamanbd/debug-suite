<?php

/**
 * Internationalization functionality.
 */

namespace DebugSuite\Core;

use DebugSuite\Interfaces\Hookable;

/**
 * Define the internationalization functionality.
 */
class I18n implements Hookable {


	/**
	 * Register hooks for WordPress.
	 * This method will be called automatically to register the hooks.
	 */
	public function register_hooks(): void {
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @return void
	 */
	public function load_plugin_textdomain(): void {
		load_plugin_textdomain(
			'debug-suite',
			false,
			dirname( plugin_basename( DEBUG_SUITE_PLUGIN_DIR ) ) . '/languages/'
		);
	}
}
