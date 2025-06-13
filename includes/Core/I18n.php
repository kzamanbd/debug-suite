<?php
/**
 * Internationalization functionality.
 */

namespace DebugSuite\Core;

/**
 * Define the internationalization functionality.
 */
class I18n {

	/**
	 * Load the plugin text domain for translation.
	 */
	public static function load_plugin_textdomain() {
		load_plugin_textdomain(
			'debug-suite',
			false,
			dirname( plugin_basename( DEBUG_SUITE_PLUGIN_DIR ) ) . '/languages/'
		);
	}
}
