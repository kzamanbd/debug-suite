<?php

/**
 * Core service provider for registering essential Debug Suite services.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Providers;

use DebugSuite\Core\Container\AbstractServiceProvider;
use DebugSuite\Core\Container\Container;
use DebugSuite\Core\Assets;
use DebugSuite\Core\I18n;
use Exception;

/**
 * Core Service Provider for registering essential Debug Suite services.
 *
 * Registers core services such as Assets, I18n, and foundational components.
 *
 * @since DEBUG_SUITE_SINCE
 */
class CoreServiceProvider extends AbstractServiceProvider {

	/**
	 * Services provided by this provider.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var array<string>
	 */
	protected array $provides = [
		Assets::class,
		I18n::class,
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
		// Hook registration is now handled centrally by ServiceManager
		// Nothing special needed here for core services
	}
}
