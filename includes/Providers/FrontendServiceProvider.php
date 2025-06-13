<?php
/**
 * Frontend service provider for registering frontend services.
 */

namespace DebugSuite\Providers;

use DebugSuite\Core\AbstractServiceProvider;
use DebugSuite\Core\Container;
use DebugSuite\Frontend\Frontend;

/**
 * Frontend Service Provider for registering frontend services.
 */
class FrontendServiceProvider extends AbstractServiceProvider {

	/**
	 * Services provided by this provider.
	 */
	protected $provides = array(
		'frontend',
		Frontend::class,
	);

	/**
	 * Register services with the container.
	 */
	public function register( Container $container ): void {
		// Register Frontend service
		$container->singleton(
			'frontend',
			function ( Container $container ) {
				return new Frontend();
			}
		);

		$container->singleton(
			Frontend::class,
			function ( Container $container ) {
				return $container->resolve( 'frontend' );
			}
		);

		$this->mark_registered();
	}

	/**
	 * Boot services after all providers have been registered.
	 */
	public function boot( Container $container ): void {
		// Frontend services are automatically initialized when resolved
		// No additional booting required for frontend services

		$this->mark_booted();
	}
}
