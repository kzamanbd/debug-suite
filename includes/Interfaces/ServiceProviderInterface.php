<?php
/**
 * Interface for service providers.
 *
 * @since      1.0.0
 * @package    DebugSuite
 * @author     Kamruzzaman <kzamanbn@gmail.com>
 */

namespace DebugSuite\Interfaces;

use DebugSuite\Core\Container;

/**
 * Service Provider Interface for registering services with the container.
 *
 * @since      1.0.0
 * @package    DebugSuite
 * @author     Kamruzzaman <kzamanbn@gmail.com>
 */
interface ServiceProviderInterface {

	/**
	 * Register services with the container.
	 *
	 * @param Container $container The container instance.
	 * @return void
	 */
	public function register( Container $container ): void;

	/**
	 * Boot services after all providers have been registered.
	 *
	 * @param Container $container The container instance.
	 * @return void
	 */
	public function boot( Container $container ): void;
}
