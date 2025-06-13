<?php
/**
 * The admin-specific functionality of the plugin.
 */

namespace DebugSuite\Admin;

/**
 * The admin-specific functionality of the plugin.
 */
class Admin {

	private Settings $settings;

	public function __construct( Settings $settings = null ) {
		$this->settings = $settings ?: new Settings();
		
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	/**
	 * Add admin menu and initialize settings.
	 */
	public function add_admin_menu() {
		$this->settings->add_admin_menu();
	}

	/**
	 * Initialize admin settings.
	 */
	public function admin_init() {
		$this->settings->register_settings();
	}

	/**
	 * Enqueue admin scripts and styles.
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_script( 'debug-suite-admin' );
		wp_enqueue_style( 'debug-suite-admin' );
	}
}
