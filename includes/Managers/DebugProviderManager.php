<?php
/**
 * Manager for debug providers.
 */

namespace DebugSuite\Managers;

use DebugSuite\Core\Singleton;
use DebugSuite\Interfaces\DebugProviderInterface;

/**
 * Debug Provider Manager for managing debug providers.
 */
class DebugProviderManager {
	use Singleton;

	/**
	 * Registered debug providers.
	 *
	 * @var DebugProviderInterface[]
	 */
	private array $providers = array();

	/**
	 * Active debug providers.
	 *
	 * @var DebugProviderInterface[]
	 */
	private array $active_providers = array();

	/**
	 * Initialize the manager.
	 *
	 * @return void
	 */
	protected function init(): void {
		// Register built-in providers
		$this->register_built_in_providers();
	}

	/**
	 * Register a debug provider.
	 *
	 * @param string                     $name     Provider name.
	 * @param DebugProviderInterface     $provider Provider instance.
	 * @return void
	 */
	public function register_provider( string $name, DebugProviderInterface $provider ): void {
		$this->providers[ $name ] = $provider;
	}

	/**
	 * Get a registered provider.
	 *
	 * @param string $name Provider name.
	 * @return DebugProviderInterface|null
	 */
	public function get_provider( string $name ): ?DebugProviderInterface {
		return $this->providers[ $name ] ?? null;
	}

	/**
	 * Get all registered providers.
	 *
	 * @return DebugProviderInterface[]
	 */
	public function get_providers(): array {
		return $this->providers;
	}

	/**
	 * Activate a provider.
	 *
	 * @param string $name Provider name.
	 * @return bool
	 */
	public function activate_provider( string $name ): bool {
		if ( ! isset( $this->providers[ $name ] ) ) {
			return false;
		}

		if ( ! isset( $this->active_providers[ $name ] ) ) {
			$this->active_providers[ $name ] = $this->providers[ $name ];
			$this->providers[ $name ]->activate();
		}

		return true;
	}

	/**
	 * Deactivate a provider.
	 *
	 * @param string $name Provider name.
	 * @return bool
	 */
	public function deactivate_provider( string $name ): bool {
		if ( isset( $this->active_providers[ $name ] ) ) {
			$this->active_providers[ $name ]->deactivate();
			unset( $this->active_providers[ $name ] );
			return true;
		}

		return false;
	}

	/**
	 * Check if a provider is active.
	 *
	 * @param string $name Provider name.
	 * @return bool
	 */
	public function is_provider_active( string $name ): bool {
		return isset( $this->active_providers[ $name ] );
	}

	/**
	 * Get all active providers.
	 *
	 * @return DebugProviderInterface[]
	 */
	public function get_active_providers(): array {
		return $this->active_providers;
	}

	/**
	 * Register built-in debug providers.
	 *
	 * @return void
	 */
	private function register_built_in_providers(): void {
		// Register built-in providers here
		// Example providers can be added later
	}

	/**
	 * Get debug data from all active providers.
	 *
	 * @return array
	 */
	public function get_debug_data(): array {
		return array_map(
			function ( $provider ) {
				return $provider->get_debug_data();
			},
			$this->active_providers
		);
	}

	/**
	 * Clear debug data from all active providers.
	 *
	 * @return void
	 */
	public function clear_debug_data(): void {
		foreach ( $this->active_providers as $provider ) {
			if ( method_exists( $provider, 'clear_debug_data' ) ) {
				$provider->clear_debug_data();
			}
		}
	}
}
