<?php

namespace DebugSuite\Admin;

/**
 * Settings class for admin configuration.
 *
 * @since      1.0.0
 * @package    DebugSuite
 * @subpackage DebugSuite/Admin
 * @author     Kamruzzaman <kzamanbn@gmail.com>
 */
class Settings {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		// Constructor logic can be added here if needed
	}

	/**
	 * Add admin menu pages.
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Debug Suite', 'debug-suite' ),
			__( 'Debug Suite', 'debug-suite' ),
			'manage_options',
			'debug-suite',
			array( $this, 'admin_page' ),
			'dashicons-admin-tools',
			80
		);
	}

	/**
	 * Render the admin page.
	 *
	 * @since    1.0.0
	 */
	public function admin_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<div id="debug-suite-admin-app"></div>
		</div>
		<?php
	}

	/**
	 * Register settings.
	 *
	 * @since    1.0.0
	 */
	public function register_settings() {
		register_setting(
			'debug_suite_settings',
			'debug_suite_options',
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);
	}

	/**
	 * Sanitize settings input.
	 *
	 * @since    1.0.0
	 * @param    array $input The input array to sanitize.
	 * @return   array The sanitized input.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		if ( isset( $input['enable_debug_bar'] ) ) {
			$sanitized['enable_debug_bar'] = (bool) $input['enable_debug_bar'];
		}

		if ( isset( $input['enable_query_monitor'] ) ) {
			$sanitized['enable_query_monitor'] = (bool) $input['enable_query_monitor'];
		}

		if ( isset( $input['log_level'] ) ) {
			$sanitized['log_level'] = sanitize_text_field( $input['log_level'] );
		}

		return $sanitized;
	}

	/**
	 * Get plugin option.
	 *
	 * @since    1.0.0
	 * @param    string $option  The option name.
	 * @param    mixed  $default The default value.
	 * @return   mixed The option value.
	 */
	public function get_option( $option, $default = null ) {
		$options = get_option( 'debug_suite_options', array() );
		
		return isset( $options[ $option ] ) ? $options[ $option ] : $default;
	}

	/**
	 * Update plugin option.
	 *
	 * @since    1.0.0
	 * @param    string $option The option name.
	 * @param    mixed  $value  The option value.
	 * @return   bool True if option was updated, false otherwise.
	 */
	public function update_option( $option, $value ) {
		$options = get_option( 'debug_suite_options', array() );
		$options[ $option ] = $value;
		
		return update_option( 'debug_suite_options', $options );
	}
}
