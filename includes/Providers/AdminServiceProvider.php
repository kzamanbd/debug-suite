<?php

/**
 * Admin service provider for registering WordPress admin-specific services.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Providers;

use DebugSuite\Core\Container\AbstractServiceProvider;
use DebugSuite\Core\Container\Container;
use DebugSuite\Admin\Admin;

/**
 * Admin Service Provider for registering WordPress admin functionality.
 *
 * Registers admin-specific services and WordPress admin interface components.
 *
 * @since DEBUG_SUITE_SINCE
 */
class AdminServiceProvider extends AbstractServiceProvider {

	/**
	 * Services provided by this provider.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var array<string>
	 */
	protected array $provides = [
		Admin::class,
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
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param Container $container The container instance.
	 *
	 * @return void
	 */
	public function boot( Container $container ): void {
		// Hook registration is now handled centrally by ServiceManager
		// Nothing special needed here for admin services
	}
}
