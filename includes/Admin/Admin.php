<?php

namespace DebugSuite\Admin;


/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    DebugSuite
 * @subpackage DebugSuite/Admin
 * @author     Kamruzzaman <kzamanbn@gmail.com>
 */
class Admin {

	/**
	 * The settings instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Settings    $settings    The settings instance.
	 */
	private Settings $settings;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    Settings $settings Optional settings instance.
	 */
	public function __construct( Settings $settings = null ) {
		$this->settings = $settings ?: new Settings();
		
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	/**
	 * Add admin menu and initialize settings.
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu() {
		$this->settings->add_admin_menu();
	}

	/**
	 * Initialize admin settings.
	 *
	 * @since    1.0.0
	 */
	public function admin_init() {
		$this->settings->register_settings();
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since    1.0.0
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_script( 'debug-suite-admin' );
        wp_enqueue_style( 'debug-suite-admin' );
	}
}
