<?php

/**
 * Admin service provider for registering admin services.
 */

namespace DebugSuite\Providers;

use DebugSuite\Core\AbstractServiceProvider;
use DebugSuite\Core\Container;
use DebugSuite\Admin\Admin;
use DebugSuite\Interfaces\Hookable;

/**
 * Admin Service Provider for registering admin services.
 */
class AdminServiceProvider extends AbstractServiceProvider {

	/**
	 * Services provided by this provider.
	 */
	protected $provides = [
		Admin::class,
	];

	/**
	 * Register services with the container.
	 */
	public function register( Container $container ): void {
		// Register Admin service
		$container->singleton(
			Admin::class,
			function ( Container $container ) {
				return new Admin();
			}
		);

		$this->mark_registered();
	}

	/**
	 * Boot services after all providers have been registered.
	 */
	public function boot( Container $container ): void {
		// Hook registration is now handled centrally by ServiceManager
		// Nothing special needed here for admin services
	}
}
