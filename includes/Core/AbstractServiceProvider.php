<?php

namespace DebugSuite\Core;

use DebugSuite\Interfaces\ServiceProviderInterface;

/**
 * Abstract Service Provider class.
 *
 * @since      1.0.0
 * @package    DebugSuite
 * @subpackage DebugSuite/Core
 * @author     Kamruzzaman <kzamanbn@gmail.com>
 */
abstract class AbstractServiceProvider implements ServiceProviderInterface {

	/**
	 * Services provided by this provider.
	 *
	 * @var array
	 */
	protected $provides = array();

	/**
	 * Whether the provider has been registered.
	 *
	 * @var bool
	 */
	protected $registered = false;

	/**
	 * Whether the provider has been booted.
	 *
	 * @var bool
	 */
	protected $booted = false;

	/**
	 * Register services with the container.
	 *
	 * @param Container $container The container instance.
	 * @return void
	 */
	abstract public function register( Container $container ): void;

	/**
	 * Boot services after all providers have been registered.
	 * Override this method in child classes if needed.
	 *
	 * @param Container $container The container instance.
	 * @return void
	 */
	public function boot( Container $container ): void {
		// Default implementation - override in child classes
		$this->booted = true;
	}

	/**
	 * Get the services provided by this provider.
	 *
	 * @return array
	 */
	public function provides(): array {
		return $this->provides;
	}

	/**
	 * Check if the provider has been registered.
	 *
	 * @return bool
	 */
	public function is_registered(): bool {
		return $this->registered;
	}

	/**
	 * Check if the provider has been booted.
	 *
	 * @return bool
	 */
	public function is_booted(): bool {
		return $this->booted;
	}

	/**
	 * Mark the provider as registered.
	 *
	 * @return void
	 */
	protected function mark_registered(): void {
		$this->registered = true;
	}

	/**
	 * Mark the provider as booted.
	 *
	 * @return void
	 */
	protected function mark_booted(): void {
		$this->booted = true;
	}
}
