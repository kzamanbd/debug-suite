<?php
/**
 * Abstract base class for service providers in Debug Suite with DI namespace.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core\DI;

/**
 * Abstract base class for service providers in Debug Suite with DI namespace.
 *
 * Provides common functionality for service providers including
 * registration tracking and service listing capabilities.
 *
 * @since DEBUG_SUITE_SINCE
 */
abstract class AbstractServiceProvider implements ServiceProviderInterface {

	/**
	 * Services provided by this provider.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var array<string>
	 */
	protected array $provides = [];

	/**
	 * Whether the provider has been registered.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var bool
	 */
	protected bool $registered = false;

	/**
	 * Register services with the container.
	 *
	 * @since DEBUG_SUITE_SINCE
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
	 * @since DEBUG_SUITE_SINCE
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
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return array<string>
	 */
	public function provides(): array {
		return $this->provides;
	}

	/**
	 * Check if the provider has been registered.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return bool
	 */
	public function is_registered(): bool {
		return $this->registered;
	}

	/**
	 * Mark the provider as registered.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return void
	 */
	protected function mark_registered(): void {
		$this->registered = true;
	}
}
