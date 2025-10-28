<?php

namespace DebugSuite\Modules\EmailLog;

use DebugSuite\Interfaces\Hookable;

class Hooks implements Hookable {
	public function register_hooks(): void {
		add_filter( 'debug_suite_menu_items', [ $this, 'add_menu_item' ] );
	}

	public function add_menu_item( array $items ): array {
		$items['email-log'] = [
			'title' => __( 'Email Log', 'debug-suite' ),
			'path' => 'email-log',
			'order' => 2,
		];
		return $items;
	}
}
