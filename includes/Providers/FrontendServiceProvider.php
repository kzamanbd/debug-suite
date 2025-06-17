<?php

/**
 * Frontend service provider for registering frontend services.
 */

namespace DebugSuite\Providers;

use DebugSuite\Core\DI\AbstractServiceProvider;
use DebugSuite\Core\DI\Container;
use DebugSuite\Frontend\Frontend;

/**
 * Frontend Service Provider for registering frontend services.
 */
class FrontendServiceProvider extends AbstractServiceProvider {

	/**
	 * Services provided by this provider.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var array<string>
	 */
	protected array $provides = [
		Frontend::class,
	];

	/**
	 * Register services with the container.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param Container $container The container instance.
	 *
	 * @return void
	 */
	public function register( Container $container ): void {
		// Register Frontend service
		$container->singleton(
			Frontend::class,
			function ( Container $container ) {
				return new Frontend();
			}
		);

		$this->mark_registered();
	}

	/**
	 * Boot services after all providers have been registered.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param Container $container The container instance.
	 *
	 * @return void
	 */
	public function boot( Container $container ): void {
		// Hook registration is now handled centrally by ServiceManager
		// Nothing special needed here for frontend services
	}
}
