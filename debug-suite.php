<?php
/**
 * Plugin Name:       Debug Suite
 * Plugin Slug:       debug-suite
 * Plugin URI:        https://kzaman.me/plugins/debug-suite?utm_source=wp-plugins&utm_campaign=plugin-uri&utm_medium=wp-dash
 * Description:       WP Debug Suite is a powerful, all-in-one development toolkit designed to make WordPress debugging and inspection faster, safer, and more intuitive. Whether you're building, maintaining, or debugging WordPress sites, this suite equips you with the tools you need — all in one place.
 * Version:           1.1.1
 * Author:            Kamruzzaman
 * Author URI:        https://kzaman.me/plugins/debug-suite/
 * License:           GPL-2.0 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       debug-suite
 * Domain Path:       /languages
 * Requires PHP:      8.1
 * Requires at least: 6.8
 * Tested up to: 6.8
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// Include Composer autoloader
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
} else {
	die( esc_html__( 'Missing Dependencies Detected [Debug Suite Plugin]', 'debug-suite' ) );
}

use DebugSuite\Core\Activator;
use DebugSuite\Core\Deactivator;
use DebugSuite\Core\Singleton;
use DebugSuite\Dependency\Container;
use DebugSuite\Dependency\Providers\ServiceProvider;
use DebugSuite\Interfaces\Hookable;


global $debug_suite_container;

// Declare the $wc_pocket_cart as global to access from the inside of the function.
$debug_suite_container = new Container();

// Register the service providers.
$debug_suite_container->addServiceProvider( new ServiceProvider() );

/**
 * Main plugin bootstrap and orchestration class for Debug Suite.
 *
 * Primary entry point that manages DI Container, service providers,
 * and coordinates the entire plugin initialization process.
 *
 * @since 1.0.0
 */
final class DebugSuite {

	use Singleton;

	/**
	* Plugin version
	*
	* @var string
	*/
	public string $version = '1.1.1';

	/**
	 * Initialize the Debug Suite plugin.
	 *
	 * Sets up the dependency injection container,
	 * registers service providers, and initializes the plugin's
	 * core functionality. This method orchestrates the entire
	 * plugin initialization process.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception|Throwable If a service cannot be resolved during initialization.
	 */
	private function __construct() {
		$this->define_constants();
		$this->init_hooks();
	}

	/**
	 * Define the constants used by the plugin.
	 *
	 * @return void
	 */
	public function define_constants(): void {
		if ( ! defined( 'DEBUG_SUITE_VERSION' ) ) {
			define( 'DEBUG_SUITE_VERSION', $this->version );
		}
		if ( ! defined( 'DEBUG_SUITE_FILE' ) ) {
			define( 'DEBUG_SUITE_FILE', __FILE__ );
		}
		if ( ! defined( 'DEBUG_SUITE_PLUGIN_DIR' ) ) {
			define( 'DEBUG_SUITE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}
		if ( ! defined( 'DEBUG_SUITE_PLUGIN_URL' ) ) {
			define( 'DEBUG_SUITE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}
	}


	public function init_hooks(): void {
		// Activation hook
		register_activation_hook( __FILE__, [ Activator::class, 'activate' ] );
		// Deactivation hook
		register_deactivation_hook( __FILE__, [ Deactivator::class, 'deactivate' ] );

		$hooks = $this->container()->get( Hookable::class );
		foreach ( $hooks as $hook ) {
			/**
			 * Hookable $hook
			 *
			 * @var Hookable $hook
			 */
			$hook->register_hooks();
		}
	}

	/**
	 * Get the container instance.
	 *
	 * @return   Container
	 * @since    1.0.0
	 */
	public function container(): Container {
		global $debug_suite_container;
		return $debug_suite_container;
	}
}

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function debug_suite_init(): void { // phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed
	DebugSuite::instance();
}

debug_suite_init();
