<?php
/**
 * Abstract base class for service providers in Debug Suite with Container namespace.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core\Container;

/**
 * Abstract base class for service providers in Debug Suite with Container namespace.
 *
 * Provides common functionality for service providers including
 * registration tracking and service listing capabilities.
 *
 * @since 1.0.0
 */
abstract class AbstractServiceProvider implements ServiceProviderInterface {

	/**
	 * Services provided by this provider.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string>
	 */
	protected array $provides = [];

	/**
	 * Register services with the container.
	 *
	 * @since 1.0.0
	 *
	 * @param Container $container The container instance.
	 *
	 * @return void
	 */
	abstract public function register( Container $container ): void;

	/**
	 * Boot services after all providers have been registered.
	 * Override this method in child classes if needed.
	 *
	 * @since 1.0.0
	 *
	 * @param Container $container The container instance.
	 *
	 * @return void
	 */
	public function boot( Container $container ): void {
		// Default implementation - override in child classes
	}

	/**
	 * Get the services provided by this provider.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string>
	 */
	public function provides(): array {
		return $this->provides;
	}
}
