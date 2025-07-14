<?php

/**
 * Admin functionality for the Debug Suite plugin.
 *
 * @package DebugSuite
 */

namespace DebugSuite;

use DebugSuite\Interfaces\Hookable;
use WP_Roles;

/**
 * Admin functionality for the Debug Suite plugin.
 *
 * Handles admin menu registration, script enqueuing, API route registration,
 * and the main admin interface for the Debug Suite plugin.
 *
 * @since 1.0.0
 */
class Admin implements Hookable {


	/**
	 * Register hooks for WordPress.
	 *
	 * Registers admin-specific hooks including menu registration
	 * and script enqueuing. REST API routes are automatically registered
	 * by controllers implementing the Hookable interface.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
		add_action( 'admin_init', [ $this, 'handle_activation_redirect' ] );
		add_filter( 'admin_body_class', [ $this, 'add_admin_body_class' ] );
	}

	/**
	 * Get the menu items for the Debug Suite admin menu.
	 *
	 * @since 1.0.0
	 *
	 * @return array The menu items.
	 */
	public function get_menu_items(): array {
		return [
			[
				'title' => __( 'Overview', 'debug-suite' ),
				'path' => '/',
			],
			[
				'title' => __( 'Debug Log', 'debug-suite' ),
				'path' => 'debug-log',
			],
			[
				'title' => __( 'File Manager', 'debug-suite' ),
				'path' => 'file-manager',
			],
			[
				'title' => __( 'Configuration', 'debug-suite' ),
				'path' => 'config',
			],
		];
	}

	/**
	 * Handle activation redirect.
	 *
	 * Redirects to the onboarding page after plugin activation
	 * if onboarding hasn't been completed.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_activation_redirect(): void {
		// Check if we should redirect
		if ( ! get_transient( 'debug_suite_activation_redirect' ) ) {
			return;
		}

		// Delete the redirect transient
		delete_transient( 'debug_suite_activation_redirect' );

		// Only redirect to onboarding if it hasn't been completed
		if ( ! get_option( 'debug_suite_onboarding_completed', false ) ) {
			// Get the onboarding URL
			$onboarding_url = add_query_arg(
				[
					'page' => 'debug-suite',
					'path' => 'onboarding',
				],
				admin_url( 'admin.php' )
			);

			// Redirect and exit
			wp_safe_redirect( $onboarding_url );
			exit;
		}
	}

	/**
	 * Add an admin menu and initialize settings.
	 *
	 * Creates the main Debug Suite admin menu and submenus for different
	 * sections like file manager, error logs, and log management.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_admin_menu(): void {
		global $submenu;

		$capability = 'manage_options';
		$slug       = 'debug-suite';
		$position   = apply_filters( 'debug_suite_menu_position', '50' );
		$menu_icon  = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGlkPSJsb2dvLTE3IiB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSI+IDxwYXRoIGQ9Ik0xOS41IDJDMTcuMjAxOSAyIDE0LjkyNjIgMi40NTI3IDEyLjgwMyAzLjMzMjFDMTAuNjc5OCA0LjIxMTYgOC43NTA3IDUuNTAwNiA3LjEyNTYgNy4xMjU2QzUuNTAwNiA4Ljc1MDcgNC4yMTE2IDEwLjY3OTggMy4zMzIxIDEyLjgwM0MyLjQ1MjcgMTQuOTI2MiAyIDE3LjIwMTkgMiAxOS41QzIgMjEuNzk4MSAyLjQ1MjcgMjQuMDczOCAzLjMzMjEgMjYuMTk3QzQuMjExNiAyOC4zMjAyIDUuNTAwNiAzMC4yNDkzIDcuMTI1NiAzMS44NzQ0QzguNzUwNyAzMy40OTk0IDEwLjY3OTggMzQuNzg4NCAxMi44MDMgMzUuNjY3OUMxNC45MjYyIDM2LjU0NzMgMTcuMjAxOSAzNyAxOS41IDM3VjI4LjI1QzE4LjM1MDkgMjguMjUgMTcuMjEzMSAyOC4wMjM3IDE2LjE1MTUgMjcuNTgzOUMxNS4wODk5IDI3LjE0NDIgMTQuMTI1MyAyNi40OTk3IDEzLjMxMjggMjUuNjg3MkMxMi41MDAzIDI0Ljg3NDcgMTEuODU1OCAyMy45MTAxIDExLjQxNjEgMjIuODQ4NUMxMC45NzYzIDIxLjc4NjkgMTAuNzUgMjAuNjQ5MSAxMC43NSAxOS41QzEwLjc1IDE4LjM1MDkgMTAuOTc2MyAxNy4yMTMxIDExLjQxNjEgMTYuMTUxNUMxMS44NTU4IDE1LjA4OTkgMTIuNTAwMyAxNC4xMjUzIDEzLjMxMjggMTMuMzEyOEMxNC4xMjUzIDEyLjUwMDMgMTUuMDg5OSAxMS44NTU4IDE2LjE1MTUgMTEuNDE2MUMxNy4yMTMxIDEwLjk3NjMgMTguMzUwOSAxMC43NSAxOS41IDEwLjc1VjJaIiBjbGFzcz0iY2N1c3RvbSIgZmlsbD0iIzBGNTJGRiIvPiA8cGF0aCBkPSJNMTkuNTAwMSAyNS4zMzMzQzIyLjcyMTggMjUuMzMzMyAyNS4zMzM0IDIyLjcyMTcgMjUuMzMzNCAxOS41QzI1LjMzMzQgMTYuMjc4MyAyMi43MjE4IDEzLjY2NjcgMTkuNTAwMSAxMy42NjY3QzE2LjI3ODQgMTMuNjY2NyAxMy42NjY4IDE2LjI3ODMgMTMuNjY2OCAxOS41QzEzLjY2NjggMjIuNzIxNyAxNi4yNzg0IDI1LjMzMzMgMTkuNTAwMSAyNS4zMzMzWiIgY2xhc3M9ImNjdXN0b20iIGZpbGw9IiMwRjUyRkYiLz4gPHBhdGggZD0iTTIgMTkuNUMyIDIxLjc5ODEgMi40NTI3IDI0LjA3MzggMy4zMzIxIDI2LjE5N0M0LjIxMTYgMjguMzIwMiA1LjUwMDYgMzAuMjQ5MyA3LjEyNTYgMzEuODc0NEM4Ljc1MDcgMzMuNDk5NCAxMC42Nzk4IDM0Ljc4ODQgMTIuODAzIDM1LjY2NzlDMTQuOTI2MiAzNi41NDczIDE3LjIwMTkgMzcgMTkuNSAzN0MyMS43OTgxIDM3IDI0LjA3MzggMzYuNTQ3MyAyNi4xOTcgMzUuNjY3OUMyOC4zMjAyIDM0Ljc4ODQgMzAuMjQ5MyAzMy40OTk0IDMxLjg3NDQgMzEuODc0NEMzMy40OTk0IDMwLjI0OTMgMzQuNzg4NCAyOC4zMjAyIDM1LjY2NzkgMjYuMTk3QzM2LjU0NzMgMjQuMDczOCAzNyAyMS43OTgxIDM3IDE5LjVIMjguMjVDMjguMjUgMjAuNjQ5MSAyOC4wMjM3IDIxLjc4NjkgMjcuNTgzOSAyMi44NDg1QzI3LjE0NDIgMjMuOTEwMSAyNi40OTk3IDI0Ljg3NDcgMjUuNjg3MiAyNS42ODcyQzI0Ljg3NDcgMjYuNDk5NyAyMy45MTAxIDI3LjE0NDIgMjIuODQ4NSAyNy41ODM5QzIxLjc4NjkgMjguMDIzNyAyMC42NDkxIDI4LjI1IDE5LjUgMjguMjVDMTguMzUwOSAyOC4yNSAxNy4yMTMxIDI4LjAyMzcgMTYuMTUxNSAyNy41ODM5QzE1LjA4OTkgMjcuMTQ0MiAxNC4xMjUzIDI2LjQ5OTcgMTMuMzEyOCAyNS42ODcyQzEyLjUwMDMgMjQuODc0NyAxMS44NTU4IDIzLjkxMDEgMTEuNDE2MSAyMi44NDg1QzEwLjk3NjMgMjEuNzg2OSAxMC43NSAyMC42NDkxIDEwLjc1IDE5LjVIMloiIGNsYXNzPSJjY29tcGxpMSIgZmlsbD0iIzVCRDBGNCIvPiA8cGF0aCBkPSJNMjUuMzMzNCAxOS41QzI1LjMzMzQgMTcuOTUyOSAyNC43MTg4IDE2LjQ2OTIgMjMuNjI0OSAxNS4zNzUyQzIyLjUzMDkgMTQuMjgxMiAyMS4wNDcyIDEzLjY2NjYgMTkuNTAwMSAxMy42NjY2QzE3Ljk1MyAxMy42NjY2IDE2LjQ2OTMgMTQuMjgxMiAxNS4zNzUzIDE1LjM3NTJDMTQuMjgxMyAxNi40NjkyIDEzLjY2NjcgMTcuOTUyOSAxMy42NjY3IDE5LjVIMTkuNTAwMUgyNS4zMzM0WiIgY2xhc3M9ImNjb21wbGkxIiBmaWxsPSIjNUJEMEY0Ii8+ICAgICAgICAgIDwvc3ZnPg==';

		add_menu_page(
			__( 'Debug Suite', 'debug-suite' ),
			__( 'Debug Suite', 'debug-suite' ),
			$capability,
			$slug,
			[ $this, 'admin_page' ],
			$menu_icon,
			$position
		);

		foreach ( $this->get_menu_items() as $menu ) {
			$path = str_replace( '#/', '#', 'admin.php?page=' . $slug . '#' . $menu['path'], );
			$submenu[ $slug ][] = [
				$menu['title'],
				$capability,
				$path,
			];
		}
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
	 */
	public function admin_page(): void {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'debug-suite' ) );
		}

		ob_start();
		echo '<div class="wrap"><div id="debug-suite-root-app" class="debug-suite-root-app"></div></div>';
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
	 * Enqueue admin scripts and styles.
	 *
	 * Loads the necessary JavaScript and CSS files for the Debug Suite admin interface.
	 * Also localizes script data including WordPress debug constants and user roles.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts( $hook_suffix ): void {
		// Only enqueue on our plugin's admin page
		if ( 'toplevel_page_debug-suite' !== $hook_suffix ) {
			return;
		}
		$favicon   = DEBUG_SUITE_PLUGIN_URL . 'assets/images/brand-logo.png';
		$constants = [
			'wpDebug'        => WP_DEBUG,
			'wpDebugLog'     => WP_DEBUG_LOG,
			'wpDebugDisplay' => WP_DEBUG_DISPLAY,
			'publicRootPath' => ABSPATH,
			'filesUrl'       => content_url(),
			'favicon'       => $favicon,
			'wpVersion'      => get_bloginfo( 'version' ),
			'phpVersion'     => phpversion(),
		];
		$settings  = get_option( 'debug_suite_settings', [] );
		$settings  = array_merge( $constants, $settings );

		wp_enqueue_script( 'debug-suite-script' );
		wp_enqueue_style( 'debug-suite-style' );
		wp_set_script_translations( 'debug-suite-script', 'debug-suite' );
		wp_localize_script(
			'debug-suite-script',
			'debugSuite',
			$settings
		);
	}
}
