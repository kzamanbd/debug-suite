<?php
/**
 * Interface for Container service providers.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core\Container;

/**
 * Service Provider Interface for registering services with the DI Container.
 *
 * Defines the contract for service providers that register and boot services
 * within the dependency injection container. Providers should implement both
 * registration and booting phases for proper service lifecycle management.
 *
 * @since 1.0.0
 */
interface ServiceProviderInterface {

	/**
	 * Register services with the container.
	 *
	 * This method is called during the registration phase to bind services
	 * and their dependencies to the container. No services should be resolved
	 * during this phase.
	 *
	 * @since 1.0.0
	 *
	 * @param Container $container The container instance.
	 *
	 * @return void
	 */
	public function register( Container $container ): void;

	/**
	 * Boot services after all providers have been registered.
	 *
	 * This method is called after all service providers have completed their
	 * registration phase. It's safe to resolve services from the container
	 * during this phase.
	 *
	 * @since 1.0.0
	 *
	 * @param Container $container The container instance.
	 *
	 * @return void
	 */
	public function boot( Container $container ): void;

	/**
	 * Get the services provided by this provider.
	 *
	 * Returns an array of service identifiers that this provider
	 * registers with the container.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string> Array of service identifiers.
	 */
	public function provides(): array;
}
