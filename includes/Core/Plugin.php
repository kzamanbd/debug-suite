<?php

namespace DebugSuite\Core;

use DebugSuite\Interfaces\Hookable;

class Plugin implements Hookable {

	public function register_hooks(): void {
		add_filter( 'plugin_action_links_' . plugin_basename( DEBUG_SUITE_FILE ), [ $this, 'plugin_action_links' ] );
	}

	public function plugin_action_links( array $links ): array {
		$links[] = '<a href="' . admin_url( 'admin.php?page=debug-suite' ) . '">' . __( 'Settings', 'debug-suite' ) . '</a>';

		return $links;
	}
}
