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
	 */
	public function get_option( $option, $default = null ) {
		$options = get_option( 'debug_suite_options', array() );
		
		return isset( $options[ $option ] ) ? $options[ $option ] : $default;
	}

	/**
	 * Update plugin option.
	 */
	public function update_option( $option, $value ) {
		$options = get_option( 'debug_suite_options', array() );
		$options[ $option ] = $value;
		
		return update_option( 'debug_suite_options', $options );
	}
}
