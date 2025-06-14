<?php
/**
 * The admin-specific functionality of the plugin.
 */

namespace DebugSuite\Admin;

/**
 * The admin-specific functionality of the plugin.
 */
class Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
	}

	/**
	 * Add admin menu and initialize settings.
	 */
	public function add_admin_menu(): void {
		global $submenu;

		$capability     = 'manage_options';
		$slug           = 'debug-suite';
		$position       = 80; // Position in the admin menu
		$menu_icon      = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGlkPSJsb2dvLTE3IiB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSI+IDxwYXRoIGQ9Ik0xOS41IDJDMTcuMjAxOSAyIDE0LjkyNjIgMi40NTI3IDEyLjgwMyAzLjMzMjFDMTAuNjc5OCA0LjIxMTYgOC43NTA3IDUuNTAwNiA3LjEyNTYgNy4xMjU2QzUuNTAwNiA4Ljc1MDcgNC4yMTE2IDEwLjY3OTggMy4zMzIxIDEyLjgwM0MyLjQ1MjcgMTQuOTI2MiAyIDE3LjIwMTkgMiAxOS41QzIgMjEuNzk4MSAyLjQ1MjcgMjQuMDczOCAzLjMzMjEgMjYuMTk3QzQuMjExNiAyOC4zMjAyIDUuNTAwNiAzMC4yNDkzIDcuMTI1NiAzMS44NzQ0QzguNzUwNyAzMy40OTk0IDEwLjY3OTggMzQuNzg4NCAxMi44MDMgMzUuNjY3OUMxNC45MjYyIDM2LjU0NzMgMTcuMjAxOSAzNyAxOS41IDM3VjI4LjI1QzE4LjM1MDkgMjguMjUgMTcuMjEzMSAyOC4wMjM3IDE2LjE1MTUgMjcuNTgzOUMxNS4wODk5IDI3LjE0NDIgMTQuMTI1MyAyNi40OTk3IDEzLjMxMjggMjUuNjg3MkMxMi41MDAzIDI0Ljg3NDcgMTEuODU1OCAyMy45MTAxIDExLjQxNjEgMjIuODQ4NUMxMC45NzYzIDIxLjc4NjkgMTAuNzUgMjAuNjQ5MSAxMC43NSAxOS41QzEwLjc1IDE4LjM1MDkgMTAuOTc2MyAxNy4yMTMxIDExLjQxNjEgMTYuMTUxNUMxMS44NTU4IDE1LjA4OTkgMTIuNTAwMyAxNC4xMjUzIDEzLjMxMjggMTMuMzEyOEMxNC4xMjUzIDEyLjUwMDMgMTUuMDg5OSAxMS44NTU4IDE2LjE1MTUgMTEuNDE2MUMxNy4yMTMxIDEwLjk3NjMgMTguMzUwOSAxMC43NSAxOS41IDEwLjc1VjJaIiBjbGFzcz0iY2N1c3RvbSIgZmlsbD0iIzBGNTJGRiIvPiA8cGF0aCBkPSJNMTkuNTAwMSAyNS4zMzMzQzIyLjcyMTggMjUuMzMzMyAyNS4zMzM0IDIyLjcyMTcgMjUuMzMzNCAxOS41QzI1LjMzMzQgMTYuMjc4MyAyMi43MjE4IDEzLjY2NjcgMTkuNTAwMSAxMy42NjY3QzE2LjI3ODQgMTMuNjY2NyAxMy42NjY4IDE2LjI3ODMgMTMuNjY2OCAxOS41QzEzLjY2NjggMjIuNzIxNyAxNi4yNzg0IDI1LjMzMzMgMTkuNTAwMSAyNS4zMzMzWiIgY2xhc3M9ImNjdXN0b20iIGZpbGw9IiMwRjUyRkYiLz4gPHBhdGggZD0iTTIgMTkuNUMyIDIxLjc5ODEgMi40NTI3IDI0LjA3MzggMy4zMzIxIDI2LjE5N0M0LjIxMTYgMjguMzIwMiA1LjUwMDYgMzAuMjQ5MyA3LjEyNTYgMzEuODc0NEM4Ljc1MDcgMzMuNDk5NCAxMC42Nzk4IDM0Ljc4ODQgMTIuODAzIDM1LjY2NzlDMTQuOTI2MiAzNi41NDczIDE3LjIwMTkgMzcgMTkuNSAzN0MyMS43OTgxIDM3IDI0LjA3MzggMzYuNTQ3MyAyNi4xOTcgMzUuNjY3OUMyOC4zMjAyIDM0Ljc4ODQgMzAuMjQ5MyAzMy40OTk0IDMxLjg3NDQgMzEuODc0NEMzMy40OTk0IDMwLjI0OTMgMzQuNzg4NCAyOC4zMjAyIDM1LjY2NzkgMjYuMTk3QzM2LjU0NzMgMjQuMDczOCAzNyAyMS43OTgxIDM3IDE5LjVIMjguMjVDMjguMjUgMjAuNjQ5MSAyOC4wMjM3IDIxLjc4NjkgMjcuNTgzOSAyMi44NDg1QzI3LjE0NDIgMjMuOTEwMSAyNi40OTk3IDI0Ljg3NDcgMjUuNjg3MiAyNS42ODcyQzI0Ljg3NDcgMjYuNDk5NyAyMy45MTAxIDI3LjE0NDIgMjIuODQ4NSAyNy41ODM5QzIxLjc4NjkgMjguMDIzNyAyMC42NDkxIDI4LjI1IDE5LjUgMjguMjVDMTguMzUwOSAyOC4yNSAxNy4yMTMxIDI4LjAyMzcgMTYuMTUxNSAyNy41ODM5QzE1LjA4OTkgMjcuMTQ0MiAxNC4xMjUzIDI2LjQ5OTcgMTMuMzEyOCAyNS42ODcyQzEyLjUwMDMgMjQuODc0NyAxMS44NTU4IDIzLjkxMDEgMTEuNDE2MSAyMi44NDg1QzEwLjk3NjMgMjEuNzg2OSAxMC43NSAyMC42NDkxIDEwLjc1IDE5LjVIMloiIGNsYXNzPSJjY29tcGxpMSIgZmlsbD0iIzVCRDBGNCIvPiA8cGF0aCBkPSJNMjUuMzMzNCAxOS41QzI1LjMzMzQgMTcuOTUyOSAyNC43MTg4IDE2LjQ2OTIgMjMuNjI0OSAxNS4zNzUyQzIyLjUzMDkgMTQuMjgxMiAyMS4wNDcyIDEzLjY2NjYgMTkuNTAwMSAxMy42NjY2QzE3Ljk1MyAxMy42NjY2IDE2LjQ2OTMgMTQuMjgxMiAxNS4zNzUzIDE1LjM3NTJDMTQuMjgxMyAxNi40NjkyIDEzLjY2NjcgMTcuOTUyOSAxMy42NjY3IDE5LjVIMTkuNTAwMUgyNS4zMzM0WiIgY2xhc3M9ImNjb21wbGkxIiBmaWxsPSIjNUJEMEY0Ii8+ICAgICAgICAgIDwvc3ZnPg==';

		$dashboard = add_menu_page(
			__( 'Debug Suite', 'debug-suite' ),
			__( 'Debug Suite', 'debug-suite' ),
			$capability,
			$slug,
			[ $this, 'admin_page' ],
			$menu_icon,
			$position
		);

		$submenu[ $slug ][] = [ __( 'Dashboard', 'debug-suite' ), $capability, 'admin.php?page=' . $slug . '#' ];
		$submenu[ $slug ][] = [ __( 'File Logs', 'debug-suite' ), $capability, 'admin.php?page=' . $slug . '#file-logs/view' ];
		$submenu[ $slug ][] = [ __( 'Manage Logs', 'debug-suite' ), $capability, 'admin.php?page=' . $slug . '#file-logs/manage' ];

		add_action( $dashboard, [ $this, 'admin_enqueue_scripts' ] );
	}

	/**
	 * Render the admin page.
	 */
	public function admin_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'debug-suite' ) );
		}

		ob_start();
		echo '<div class="wrap"><div id="debug-suite-admin-app"></div></div>';
		echo ob_get_clean(); // phpcs:ignore
	}

	/**
	 * Enqueue admin scripts and styles.
	 */
	public function admin_enqueue_scripts(): void {
		wp_enqueue_script( 'debug-suite-admin' );
		wp_enqueue_style( 'debug-suite-admin' );
	}
}
