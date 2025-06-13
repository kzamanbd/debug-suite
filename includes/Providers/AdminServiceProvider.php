<?php
/**
 * Admin service provider for registering admin services.
 *
 * @since      1.0.0
 * @package    DebugSuite
 * @subpackage DebugSuite/Providers
 * @author     Kamruzzaman <kzamanbn@gmail.com>
 */

namespace DebugSuite\Providers;

use DebugSuite\Core\AbstractServiceProvider;
use DebugSuite\Core\Container;
use DebugSuite\Admin\Admin;
use DebugSuite\Admin\Settings;

/**
 * Admin Service Provider for registering admin services.
 *
 * @since      1.0.0
 * @package    DebugSuite
 * @subpackage DebugSuite/Providers
 * @author     Kamruzzaman <kzamanbn@gmail.com>
 */
class AdminServiceProvider extends AbstractServiceProvider {

	/**
	 * Services provided by this provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		'admin',
		'settings',
		Admin::class,
		Settings::class,
	);

	/**
	 * Register services with the container.
	 *
	 * @param Container $container The container instance.
	 * @return void
	 */
	public function register( Container $container ): void {
		// Register Settings service
		$container->singleton( 'settings', function( Container $container ) {
			return new Settings();
		});

		$container->singleton( Settings::class, function( Container $container ) {
			return $container->resolve( 'settings' );
		});

		// Register Admin service
		$container->singleton( 'admin', function( Container $container ) {
			return new Admin( $container->resolve( 'settings' ) );
		});

		$container->singleton( Admin::class, function( Container $container ) {
			return $container->resolve( 'admin' );
		});

		$this->mark_registered();
	}

	/**
	 * Boot services after all providers have been registered.
	 *
	 * @param Container $container The container instance.
	 * @return void
	 */
	public function boot( Container $container ): void {
		// Admin services are automatically initialized when resolved
		// No additional booting required for admin services
		
		$this->mark_booted();
	}
}
