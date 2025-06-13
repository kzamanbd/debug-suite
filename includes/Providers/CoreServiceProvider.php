<?php
/**
 * Core service provider for registering core services.
 */

namespace DebugSuite\Providers;

use DebugSuite\Core\AbstractServiceProvider;
use DebugSuite\Core\Container;
use DebugSuite\Core\Assets;
use DebugSuite\Core\I18n;
use Exception;

/**
 * Core Service Provider for registering core services.
 */
class CoreServiceProvider extends AbstractServiceProvider {

	/**
	 * Services provided by this provider.
	 */
	protected $provides = array(
		'assets',
		'i18n',
		Assets::class,
		I18n::class,
	);

	/**
	 * Register services with the container.
	 */
	public function register( Container $container ): void {
		// Register Assets service
		$container->singleton( 'assets', function ( Container $container ) {
			return new Assets();
		} );

		$container->singleton( Assets::class, function ( Container $container ) {
			return $container->resolve( 'assets' );
		} );

		// Register I18n service
		$container->singleton( 'i18n', function ( Container $container ) {
			return new I18n();
		} );

		$container->singleton( I18n::class, function ( Container $container ) {
			return $container->resolve( 'i18n' );
		} );

		$this->mark_registered();
	}

	/**
	 * Boot services after all providers have been registered.
	 *
	 * @param Container $container The dependency injection container.
	 *
	 * @throws Exception If a service cannot be resolved.
	 */
	public function boot( Container $container ): void {
		// Initialize Assets (this will run the init method and register WordPress hooks)
		$assets = $container->resolve( 'assets' );
		$assets->init();

		// Initialize I18n
		$i18n = $container->resolve( 'i18n' );
		add_action( 'plugins_loaded', array( $i18n, 'load_plugin_textdomain' ) );

		$this->mark_booted();
	}
}
