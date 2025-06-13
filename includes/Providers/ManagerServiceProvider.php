<?php
/**
 * Manager service provider for registering manager services.
 */

namespace DebugSuite\Providers;

use DebugSuite\Core\AbstractServiceProvider;
use DebugSuite\Core\Container;
use DebugSuite\Managers\DebugProviderManager;

/**
 * Manager Service Provider for registering manager classes.
 */
class ManagerServiceProvider extends AbstractServiceProvider {

	/**
	 * Services provided by this provider.
	 */
	protected $provides = array(
		'debug_provider_manager',
		DebugProviderManager::class,
	);

	/**
	 * Register services with the container.
	 */
	public function register( Container $container ): void {
		// Register Debug Provider Manager
		$container->singleton(
			'debug_provider_manager', function ( Container $container ) {
				return DebugProviderManager::get_instance();
			}
		);

		$container->singleton(
			DebugProviderManager::class, function ( Container $container ) {
				return $container->resolve( 'debug_provider_manager' );
			}
		);

		$this->mark_registered();
	}

	/**
	 * Boot services after all providers have been registered.
	 */
	public function boot( Container $container ): void {
		// Managers are automatically initialized when resolved
		// No additional booting required for manager services

		$this->mark_booted();
	}
}
