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
		Assets::class,
		I18n::class,
	);

	/**
	 * Register services with the container.
	 */
	public function register( Container $container ): void {
		// Register Assets service
		$container->singleton(
			Assets::class,
			function ( Container $container ) {
				return new Assets();
			}
		);

		// Register I18n service
		$container->singleton(
			I18n::class,
			function ( Container $container ) {
				return new I18n();
			}
		);

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
		// Auto-register hooks for services that implement Hookable interface
		$this->register_hookable_services( $container );

		$this->mark_booted();
	}
}
