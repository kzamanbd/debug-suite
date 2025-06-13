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
	public function add_admin_menu() {
		global $submenu;

		$capability     = 'manage_options';
		$slug           = 'debug-suite';
		$position       = 80; // Position in the admin menu
		$menu_icon      = 'dashicons-admin-tools';

		$dashboard = add_menu_page(
			__( 'Debug Suite', 'debug-suite' ),
			__( 'Debug Suite', 'debug-suite' ),
			$capability,
			$slug,
			[ $this, 'admin_page' ],
			$menu_icon,
			$position
		);

		$submenu[ $slug ][] = [ __( 'Dashboard', 'debug-suite' ), $capability, 'admin.php?page=' . $slug . '#/' ];
		$submenu[ $slug ][] = [ __( 'File Logs', 'debug-suite' ), $capability, 'admin.php?page=' . $slug . '#/file-logs/view' ];
		$submenu[ $slug ][] = [ __( 'Manage Logs', 'debug-suite' ), $capability, 'admin.php?page=' . $slug . '#/file-logs/manage' ];

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
	public function admin_enqueue_scripts() {
		wp_enqueue_script( 'debug-suite-admin' );
		wp_enqueue_style( 'debug-suite-admin' );
	}
}
