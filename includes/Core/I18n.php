<?php

/**
 * Internationalization functionality for Debug Suite with DI support.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core;

use DebugSuite\Interfaces\Hookable;

/**
 * Internationalization and translation management for Debug Suite.
 *
 * Handles loading of translation files and text domain registration.
 *
 * @since 1.0.0
 */
class I18n implements Hookable {


	/**
	 * Register hooks for WordPress.
	 *
	 * Registers the plugins_loaded hook to load the plugin text domain
	 * for translation support.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'plugins_loaded', [ $this, 'localization_setup' ] );
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * Loads the plugin's text domain from the languages directory
	 * to enable translation of user-facing strings.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function localization_setup(): void {
		load_plugin_textdomain(
			'debug-suite',
			false,
			dirname( plugin_basename( DEBUG_SUITE_PLUGIN_DIR ) ) . '/languages/'
		);
	}
}
