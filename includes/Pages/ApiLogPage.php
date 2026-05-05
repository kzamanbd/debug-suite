<?php

namespace DebugSuite\Pages;

class ApiLogPage extends AbstractPage {

	/**
	 * Get the ID of the page.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'api-logger';
	}

	/**
	 * @inheritDoc
	 */
	public function menu( string $capability, string $position ): array {
		return [
			'page_title' => __( 'Debug Suite API Logger', 'debug-suite' ),
			'menu_title' => __( 'API Logger', 'debug-suite' ),
			'route'      => 'api-logger',
			'capability' => $capability,
			'position'   => $position ?? 35,
		];
	}

	/**
	 * @inheritDoc
	 */
	public function settings(): array {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function scripts(): array {
		return [];
	}

	/**
	 * Get the styles.
	 *
	 * @since 1.2.0
	 *
	 * @return array<string> An array of style handles.
	 */
	public function styles(): array {
		return [];
	}

	/**
	 * Register the page scripts and styles.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function register(): void {
		// No specific scripts or styles to register for this page.
	}
}
