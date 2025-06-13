<?php

namespace DebugSuite\Core;

/**
 * Define the internationalization functionality.
 *
 * @since      1.0.0
 * @package    DebugSuite
 * @subpackage DebugSuite/Core
 * @author     Kamruzzaman <kzamanbn@gmail.com>
 */
class I18n {

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public static function load_plugin_textdomain() {
		load_plugin_textdomain(
			'debug-suite',
			false,
			dirname( plugin_basename( DEBUG_SUITE_PLUGIN_DIR ) ) . '/languages/'
		);
	}
}
