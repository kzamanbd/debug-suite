<?php
/**
 * Interface for service providers.
 */

namespace DebugSuite\Interfaces;

use DebugSuite\Core\Container;

/**
 * Service Provider Interface for registering services with the container.
 */
interface ServiceProviderInterface {

	/**
	 * Register services with the container.
	 */
	public function register( Container $container ): void;

	/**
	 * Boot services after all providers have been registered.
	 */
	public function boot( Container $container ): void;
}
