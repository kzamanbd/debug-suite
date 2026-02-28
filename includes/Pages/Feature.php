<?php

namespace DebugSuite\Pages;

class Feature extends AbstractPage {

    /**
     * Get the ID of the page.
     *
     * @since 1.0.0
     *
     * @return string
     */
	public function get_id(): string {
		return 'feature';
	}

	/**
	 * @inheritDoc
	 */
	public function menu( string $capability, string $position ): array {
		return [
            'page_title' => __( 'Debug Suite Features', 'debug-suite' ),
            'menu_title' => __( 'Features', 'debug-suite' ),
            'route'      => 'feature',
            'capability' => $capability,
            'position'   => $position ?? 30,
        ];
	}

	/**
	 * @inheritDoc
	 */
	public function settings(): array {
		return [
            'feature' => [],
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
