<?php
/**
 * Admin service provider for registering admin services.
 */

namespace DebugSuite\Providers;

use DebugSuite\Core\AbstractServiceProvider;
use DebugSuite\Core\Container;
use DebugSuite\Admin\Admin;

/**
 * Admin Service Provider for registering admin services.
 */
class AdminServiceProvider extends AbstractServiceProvider {

	/**
	 * Services provided by this provider.
	 */
	protected $provides = array(
		'admin',
		Admin::class,
	);

	/**
	 * Register services with the container.
	 */
	public function register( Container $container ): void {
		// Register Admin service
		$container->singleton(
			'admin',
			function ( Container $container ) {
				return new Admin();
			}
		);

		$container->singleton(
			Admin::class,
			function ( Container $container ) {
				return $container->resolve( 'admin' );
			}
		);

		$this->mark_registered();
	}

	/**
	 * Boot services after all providers have been registered.
	 */
	public function boot( Container $container ): void {
		// Admin services are automatically initialized when resolved
		// No additional booting required for admin services

		$this->mark_booted();
	}
}
