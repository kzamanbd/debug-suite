<?php
/**
 * Settings class for admin configuration.
 */

namespace DebugSuite\Admin;

/**
 * Settings class for admin configuration.
 */
class Settings {

	public function __construct() {
		// Constructor logic can be added here if needed
	}

	/**
	 * Add admin menu pages.
	 */
	public function add_admin_menu() {
		// Main menu page
		add_menu_page(
			__( 'Debug Suite', 'debug-suite' ),
			__( 'Debug Suite', 'debug-suite' ),
			'manage_options',
			'debug-suite',
			array( $this, 'admin_page' ),
			'dashicons-admin-tools',
			80
		);

		// Add submenu for File Logs - View Logs (using same slug with hash)
		add_submenu_page(
			'debug-suite',
			__( 'File Logs - View', 'debug-suite' ),
			__( 'View Logs', 'debug-suite' ),
			'manage_options',
			'debug-suite#/file-logs/view',
			array( $this, 'admin_page' )
		);

		// Add submenu for File Logs - Manage Logs (using same slug with hash)
		add_submenu_page(
			'debug-suite',
			__( 'File Logs - Manage', 'debug-suite' ),
			__( 'Manage Logs', 'debug-suite' ),
			'manage_options',
			'debug-suite#/file-logs/manage',
			array( $this, 'admin_page' )
		);
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
}
