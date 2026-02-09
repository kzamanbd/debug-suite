<?php

namespace DebugSuite\Pages;

class Modules extends AbstractPage {

    /**
     * Get the ID of the page.
     *
     * @since 1.0.0
     *
     * @return string
     */
	public function get_id(): string {
		return 'modules';
	}

	/**
	 * @inheritDoc
	 */
	public function menu( string $capability, string $position ): array {
		return [
            'page_title' => __( 'Debug Suite Modules', 'debug-suite' ),
            'menu_title' => __( 'Modules', 'debug-suite' ),
            'route'      => 'modules',
            'capability' => $capability,
            'position'   => 30,
        ];
	}

	/**
	 * @inheritDoc
	 */
	public function settings(): array {
		return [
            'modules' => [],
        ];
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
     * @since 1.0.0
     *
     * @return array<string> An array of style handles.
     */
    public function styles(): array {
        return [];
    }

    /**
     * Register the page scripts and styles.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register(): void {}
}
