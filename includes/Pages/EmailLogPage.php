<?php

namespace DebugSuite\Pages;

class EmailLogPage extends AbstractPage {

    /**
     * Get the ID of the page.
     *
     * @since 1.0.0
     *
     * @return string
     */
	public function get_id(): string {
		return 'email-log';
	}

	/**
	 * @inheritDoc
	 */
	public function menu( string $capability, string $position ): array {
		return [
            'page_title' => __( 'Debug Suite Email Log', 'debug-suite' ),
            'menu_title' => __( 'Email Log', 'debug-suite' ),
            'route'      => 'email-log',
            'capability' => $capability,
            'position'   => $position ?? 30,
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
        return [ 'debug-suite-email-log' ];
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
    public function register(): void {
		wp_register_script(
			'debug-suite-email-log',
			DEBUG_SUITE_PLUGIN_URL . 'assets/js/email-log.js',
			[ 'debug-suite-script' ],
			DEBUG_SUITE_VERSION,
			true
		);
	}
}
