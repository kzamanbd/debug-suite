<?php

namespace DebugSuite\Pages;

use DebugSuite\Interfaces\Hookable;
use DebugSuite\Interfaces\Pageable;
use DebugSuite\Assets;
use Exception;

/**
 * Page Manager class.
 *
 * @since 1.0.0
 */
class PageManager implements Hookable {

    /**
     * @var array< Pageable >
     */
    protected array $pages = [];

    /**
     * Register hooks.
     */
    public function register_hooks(): void {
        add_action( 'admin_init', [ $this, 'register_pages' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_filter( 'admin_footer_text', [ $this, 'admin_footer' ], 1, 2 );
		add_filter( 'admin_body_class', [ $this, 'add_admin_body_class' ] );
    }

    /**
     * Get all pages.
     *
     * @since 1.0.0
     *
     * @return array< Pageable >
     */
    public function get_pages(): array {
        $pages = apply_filters( 'debug_suite_admin_dashboard_pages', $this->pages );

        if ( ! is_array( $pages ) ) {
            return $this->pages;
        }

        return array_filter(
            $pages,
            function ( $page ) {
                return $page instanceof Pageable;
            }
        );
    }

    /**
     * Register all pages.
     */
    public function register_pages(): void {
        foreach ( $this->get_pages() as $page ) {
            $page->register();
        }
    }

    /**
     * Get all scripts ids.
     *
     * @since 4.0.0
     *
     * @return array<string>
     */
    public function scripts(): array {
        return array_reduce(
            $this->get_pages(),
            fn( $carry, $page ) => array_merge( $carry, $page->scripts() ),
            [
                'debug-suite-script',
            ]
        );
    }

    /**
     * Get all styles ids.
     *
     * @since 4.0.0
     *
     * @return array<string>
     */
    public function styles(): array {
        return array_reduce(
            $this->get_pages(),
            fn( $carry, $page ) => array_merge( $carry, $page->styles() ),
            [
                'debug-suite-style',
            ]
        );
    }

    /**
     * Enqueue scripts and styles for pages.
     */
    public function enqueue_scripts(): void {
        if ( ! debug_suite_current_page() ) {
            return;
        }

		// From Admin
		wp_localize_script(
			'debug-suite-script',
			'debugSuite',
			Assets::get_localized_data()
		);

        foreach ( $this->scripts() as $handle ) {
            wp_enqueue_script( $handle );
            wp_set_script_translations( $handle, 'debug-suite' );
        }

        foreach ( $this->styles() as $handle ) {
            wp_enqueue_style( $handle );
        }
    }

	/**
	 * Register the Debug Suite page under the WordPress Tools menu.
	 *
	 * Adds a single "Debug Suite" item (Tools → Debug Suite) that renders the
	 * React app; in-app sections are handled by the SPA router.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_admin_menu(): void {
		global $debug_suite_page;

		$debug_suite_page = add_submenu_page(
			'tools.php',
			__( 'Debug Suite', 'debug-suite' ),
			__( 'Debug Suite', 'debug-suite' ),
			'manage_options',
			'debug-suite',
			[ $this, 'admin_page' ]
		);
	}

	/**
	 * Render the admin page.
	 *
	 * Outputs the main container div for the React application and
	 * verifies user permissions before rendering.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 * @throws Exception
	 */
	public function admin_page(): void {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'debug-suite' ) );
		}

		ob_start();
		echo '<div id="debug-suite-root-app" class="wrap debug-suite-root-app"></div>';
		echo ob_get_clean(); // phpcs:ignore
	}

	/**
	 * Add a custom body class for the admin interface.
	 *
	 * Adds a specific class to the body tag of the admin interface
	 * to allow for custom styling or JavaScript targeting.
	 *
	 * @since 1.0.0
	 *
	 * @param string $classes Existing body classes.
	 *
	 * @return string Modified body classes.
	 */
	public function add_admin_body_class( string $classes ): string {
		// Add a custom class for the Debug Suite admin interface
		$user_meta = get_user_meta( get_current_user_id(), 'debug_suite_full_view', true );
		if ( ! $user_meta ) {
			return $classes;
		}
		return "$classes debug-suite-full-view";
	}

	/**
	 * When user is on a Debug Suite Logging related admin page, display footer text
	 * that graciously asks them to rate us.
	 *
	 * @param string $text Footer text.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function admin_footer( string $text ): string {

		if ( ! debug_suite_current_page() ) {
			return $text;
		}

		return sprintf(
			wp_kses( /* translators: 1: GitHub URL, 2: Buy Me a Coffee URL */
				__( '<a href="%1$s" target="_blank" rel="noopener noreferrer">View on GitHub</a> &nbsp;|&nbsp; <a href="%2$s" target="_blank" rel="noopener noreferrer">Buy Me a Coffee</a>', 'debug-suite' ),
				[
					'a' => [
						'href'   => [],
						'target' => [],
						'rel'    => [],
					],
				]
			),
			'https://github.com/kzamanbd/debug-suite',
			'https://coff.ee/kzamanbd'
		);
	}
}
