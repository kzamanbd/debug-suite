<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://kzaman.me/plugins/debug-suite
 * @since             1.0.0
 * @package           Debug_Suite
 *
 * @wordpress-plugin
 * Plugin Name:       Debug Suite
 * Plugin Slug:       debug-suite
 * Plugin URI:        https://kzaman.me/plugins/debug-suite
 * Description:       WP Debug Suite is a powerful, all-in-one development toolkit designed to make WordPress debugging and inspection faster, safer, and more intuitive. Whether you're building, maintaining, or debugging WordPress sites, this suite equips you with the tools you need — all in one place.
 * Version:           1.0.0
 * Author:            Kamruzzaman
 * Author URI:        https://kzaman.me/plugins/debug-suite/
 * License:           GPL-2.0 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       debug-suite
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// Include Composer autoloader
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}else {
    // Plugin won't work - show admin notice
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p>Debug Suite: Please run composer install</p></div>';
    });
    return;
}

use DebugSuite\Admin\Admin;
use DebugSuite\Core\Assets;
use DebugSuite\Core\I18n;
use DebugSuite\Core\Activator;
use DebugSuite\Core\Deactivator;

final class DebugSuite {

	/**
     * Instance of self
     *
     * @var $this
     */
    private static $instance = null;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->define_constants();
		$this->set_locale();
		$this->define_admin_hooks();

		// Activation hook
		register_activation_hook( __FILE__, [ Activator::class, 'activate' ] );
		// Deactivation hook
		register_deactivation_hook( __FILE__, [ Deactivator::class, 'deactivate' ] );
		new Assets();
	}

	public function define_constants() {
		define( 'DEBUG_SUITE_VERSION', '1.0.0' );
		define( 'DEBUG_SUITE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		define( 'DEBUG_SUITE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
	}

	/**
     * Initializes the WeDevs_Dokan() class
     *
     * Checks for an existing WeDevs_WeDevs_Dokan() instance
     * and if it doesn't find one, create it.
     */
    public static function init(): ?DebugSuite {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		add_action( 'plugins_loaded', array( I18n::class, 'load_plugin_textdomain' ) );
	}

	/**
	 * Register all of the hooks related to the admin area functionality.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Admin();
	}
}

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function run_debug_suite(): void {
	DebugSuite::init();
}

run_debug_suite();
