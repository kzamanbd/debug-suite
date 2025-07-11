<?php

namespace DebugSuite\Core;

use DebugSuite\Interfaces\Hookable;

class Plugin implements Hookable {

	public function register_hooks(): void {
		add_action( 'plugins_loaded', [ $this, 'localization_setup' ] );
		add_filter( 'plugin_action_links_' . plugin_basename( DEBUG_SUITE_FILE ), [ $this, 'plugin_action_links' ] );
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

	public function plugin_action_links( array $links ): array {
		$links[] = '<a href="' . admin_url( 'admin.php?page=debug-suite' ) . '">' . __( 'Settings', 'debug-suite' ) . '</a>';

		return $links;
	}
}
