<?php
/**
 * Abstract base class for service providers.
 */

namespace DebugSuite\Core;

use DebugSuite\Interfaces\ServiceProviderInterface;

/**
 * Abstract Service Provider class.
 */
abstract class AbstractServiceProvider implements ServiceProviderInterface {

	/**
	 * Services provided by this provider.
	 */
	protected $provides = array();

	/**
	 * Whether the provider has been registered.
	 */
	protected $registered = false;

	/**
	 * Register services with the container.
	 */
	abstract public function register( Container $container ): void;

	/**
	 * Boot services after all providers have been registered.
	 * Override this method in child classes if needed.
	 */
	public function boot( Container $container ): void {
		// Default implementation - override in child classes
	}

	/**
	 * Get the services provided by this provider.
	 */
	public function provides(): array {
		return $this->provides;
	}

	/**
	 * Check if the provider has been registered.
	 */
	public function is_registered(): bool {
		return $this->registered;
	}

	/**
	 * Mark the provider as registered.
	 */
	protected function mark_registered(): void {
		$this->registered = true;
	}
}
