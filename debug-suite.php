<?php
/**
 * The plugin bootstrap file
 *
 * WordPress reads this file to generate the plugin information in the plugin
 * admin area. This file includes all dependencies, registers activation and
 * deactivation functions, and initializes the PSR-11 compliant dependency
 * injection container with PHP-DI style features.
 *
 * The plugin uses a modern architecture with:
 * - PSR-11 compliant dependency injection container
 * - PHP-DI style definitions for service configuration
 * - Service provider pattern for modular service registration
 * - Automatic hook registration for services implementing Hookable interface
 * - Type-safe service resolution with proper exception handling
 *
 * @link              https://kzaman.me/plugins/debug-suite
 * @since             1.0.0
 * @package           Debug_Suite
 *
 * @wordpress-plugin
 * Plugin Name: Debug Suite
 * Plugin Slug: debug-suite
 * Plugin URI: https://kzaman.me/plugins/debug-suite
 * Description: WP Debug Suite is a powerful, all-in-one development toolkit designed to make WordPress debugging and inspection faster, safer, and more intuitive. Whether you're building, maintaining, or debugging WordPress sites, this suite equips you with the tools you need — all in one place.
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
} else {
	wp_die( esc_html__( 'Missing Dependencies Detected [Debug Suite Plugin]', 'debug-suite' ) );
}

use DebugSuite\Admin\Admin;
use DebugSuite\Core\Activator;
use DebugSuite\Core\Deactivator;
use DebugSuite\Core\Container\Container;
use DebugSuite\Core\Container\ServiceManager;
use DebugSuite\Frontend\Frontend;
use DebugSuite\Providers\CoreServiceProvider;
use DebugSuite\Providers\AdminServiceProvider;
use DebugSuite\Providers\FrontendServiceProvider;

/**
 * Main plugin bootstrap and orchestration class for Debug Suite.
 *
 * Primary entry point that manages PSR-11 DI Container, service providers,
 * and coordinates the entire plugin initialization process.
 *
 * @since DEBUG_SUITE_SINCE
 */
final class DebugSuite {

	/**
	 * Singleton instance of the Debug Suite plugin.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var DebugSuite|null
	 */
	private static ?DebugSuite $instance = null;

	/**
	 * PSR-11 compliant service manager instance.
	 *
	 * Manages the lifecycle of service providers including registration,
	 * booting, and automatic hook registration for Hookable services.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var ServiceManager
	 */
	private ServiceManager $service_manager;

	/**
	 * PSR-11 compliant dependency injection container.
	 *
	 * Provides service resolution, autowiring, and dependency management
	 * with full PSR-11 DI Interface compliance and PHP-DI style
	 * definition support.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * Initialize the Debug Suite plugin.
	 *
	 * Sets up the PSR-11 compliant dependency injection container,
	 * registers service providers, and initializes the plugin's
	 * core functionality. This method orchestrates the entire
	 * plugin initialization process.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @throws Exception If a service cannot be resolved during initialization.
	 */
	public function __construct() {
		$this->define_constants();
		$this->init_container();
		$this->register_providers();
		$this->boot_services();

		// Activation hook
		register_activation_hook( __FILE__, [ Activator::class, 'activate' ] );
		// Deactivation hook
		register_deactivation_hook( __FILE__, [ Deactivator::class, 'deactivate' ] );
	}

	/**
	 * Initialize the PSR-11 compliant dependency injection container.
	 *
	 * Creates and configures the DI Container with the service manager,
	 * then registers the container and manager as singleton instances
	 * for easy access throughout the application lifecycle.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return void
	 */
	private function init_container(): void {
		$this->container       = Container::get_instance();
		$this->service_manager = new ServiceManager( $this->container );

		// Register the container and service manager as singletons
		$this->container->instance( 'container', $this->container );
		$this->container->instance( 'service_manager', $this->service_manager );
		$this->container->instance( ServiceManager::class, $this->service_manager );
		$this->container->instance( Container::class, $this->container );
	}

	/**
	 * Register all service providers.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return void
	 */
	private function register_providers(): void {
		$providers = [
			CoreServiceProvider::class,
			AdminServiceProvider::class,
			FrontendServiceProvider::class,
		];

		$this->service_manager->register_providers( $providers );
	}

	/**
	 * Boot all registered services.
	 *
	 * @throws Exception If a service cannot be resolved.
	 * @since    1.0.0
	 * @access   private
	 * @return void
	 */
	private function boot_services(): void {
		$this->service_manager->boot();

		// Initialize admin functionality if in admin area
		if ( is_admin() ) {
			$this->container->resolve( Admin::class );
		}

		// Initialize frontend functionality
		$this->container->resolve( Frontend::class );
	}

	/**
	 * Define the constants used by the plugin.
	 *
	 * @return void
	 */
	public function define_constants(): void {
		define( 'DEBUG_SUITE_VERSION', '1.0.0' );
		define( 'DEBUG_SUITE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		define( 'DEBUG_SUITE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
	}

	/**
	 * Initializes the DebugSuite class
	 *
	 * Checks for an existing DebugSuite instance
	 * and if it doesn't find one, create it.
	 */
	public static function init(): ?DebugSuite {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get the container instance.
	 *
	 * @return   Container
	 * @since    1.0.0
	 */
	public function get_container(): Container {
		return $this->container;
	}

	/**
	 * Get the service manager instance.
	 *
	 * @return   ServiceManager
	 * @since    1.0.0
	 */
	public function get_service_manager(): ServiceManager {
		return $this->service_manager;
	}

	/**
	 * Resolve a service from the container.
	 *
	 * @param string $service Service name.
	 *
	 * @return   mixed
	 * @throws   Exception If the service cannot be resolved.
	 * @since    1.0.0
	 */
	public function resolve( string $service ) {
		return $this->container->resolve( $service );
	}
}

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function debug_suite_init(): void { // phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed
	DebugSuite::init();
}

debug_suite_init();
