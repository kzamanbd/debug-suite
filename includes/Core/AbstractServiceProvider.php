<?php

/**
 * Abstract base class for service providers.
 */

namespace DebugSuite\Core;

use DebugSuite\Interfaces\ServiceProviderInterface;
use DebugSuite\Interfaces\Hookable;

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
	 * Whether the provider has been booted.
	 */
	protected $booted = false;

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
		$this->booted = true;
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
	 * Check if the provider has been booted.
	 */
	public function is_booted(): bool {
		return $this->booted;
	}

	/**
	 * Mark the provider as registered.
	 */
	protected function mark_registered(): void {
		$this->registered = true;
	}

	/**
	 * Mark the provider as booted.
	 */
	protected function mark_booted(): void {
		$this->booted = true;
	}

	/**
	 * Automatically register hooks for the provider.
	 */
	protected function register_hooks( Hookable $hookable ): void {
		// Default implementation - override in child classes
	}

	/**
	 * Automatically register hooks for services that implement Hookable interface.
	 *
	 * @param Container $container The dependency injection container.
	 */
	protected function register_hookable_services( Container $container ): void {
		foreach ( $this->provides as $service ) {
			$instance = $container->resolve( $service );

			if ( $instance instanceof Hookable ) {
				$instance->register_hooks();
			}
		}
	}
}
