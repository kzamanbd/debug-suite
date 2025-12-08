<?php

namespace DebugSuite\Modules\EmailLog;

use DebugSuite\Core\DatabaseManager;
use DebugSuite\Interfaces\Hookable;

class HookRegistry implements Hookable {
	public function register_hooks(): void {
		add_filter( 'debug_suite_menu_items', [ $this, 'add_email_log_menu_item' ] );
		add_action( 'debug_suite_activate_email_log', [ $this, 'activate_email_log_module' ] );
	}

	public function add_email_log_menu_item( array $menu_items ): array {
		$menu_items[] = [
			'title' => __( 'Email Log', 'debug-suite' ),
			'path' => 'email-log',
			'order' => 2,
		];

		return $menu_items;
	}

	public function activate_email_log_module(): void {
		DatabaseManager::create_email_logs_table();
	}
}
