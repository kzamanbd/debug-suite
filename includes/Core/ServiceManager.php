<?php
/**
 * Service manager for handling service providers.
 *
 * @since      1.0.0
 * @package    DebugSuite
 * @subpackage DebugSuite/Core
 * @author     Kamruzzaman <kzamanbn@gmail.com>
 */

namespace DebugSuite\Core;

use DebugSuite\Interfaces\ServiceProviderInterface;

/**
 * Service Manager for managing service providers and the container.
 *
 * @since      1.0.0
 * @package    DebugSuite
 * @subpackage DebugSuite/Core
 * @author     Kamruzzaman <kzamanbn@gmail.com>
 */
class ServiceManager {

	/**
	 * Container instance.
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Registered service providers.
	 *
	 * @var ServiceProviderInterface[]
	 */
	private $providers = array();

	/**
	 * Booted service providers.
	 *
	 * @var ServiceProviderInterface[]
	 */
	private $booted_providers = array();

	/**
	 * Whether all providers have been booted.
	 *
	 * @var bool
	 */
	private $booted = false;

	/**
	 * Constructor.
	 *
	 * @param Container $container Container instance.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Register a service provider.
	 *
	 * @param ServiceProviderInterface|string $provider Provider instance or class name.
	 * @return ServiceProviderInterface
	 * @throws \Exception If provider is invalid.
	 */
	public function register( $provider ): ServiceProviderInterface {
		// If provider is a string, instantiate it
		if ( is_string( $provider ) ) {
			if ( ! class_exists( $provider ) ) {
				throw new \Exception( "Provider class [{$provider}] does not exist." );
			}

			$provider = new $provider();
		}

		// Validate provider implements interface
		if ( ! $provider instanceof ServiceProviderInterface ) {
			throw new \Exception( 'Provider must implement ServiceProviderInterface.' );
		}

		// Get provider class name for indexing
		$provider_class = get_class( $provider );

		// Skip if already registered
		if ( isset( $this->providers[ $provider_class ] ) ) {
			return $this->providers[ $provider_class ];
		}

		// Register the provider
		$provider->register( $this->container );

		// Store the provider
		$this->providers[ $provider_class ] = $provider;

		// If we've already booted, boot this provider immediately
		if ( $this->booted ) {
			$this->boot_provider( $provider );
		}

		return $provider;
	}

	/**
	 * Register multiple service providers.
	 *
	 * @param array $providers Array of provider instances or class names.
	 * @return void
	 */
	public function register_providers( array $providers ): void {
		foreach ( $providers as $provider ) {
			$this->register( $provider );
		}
	}

	/**
	 * Boot all registered service providers.
	 *
	 * @return void
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		foreach ( $this->providers as $provider ) {
			$this->boot_provider( $provider );
		}

		$this->booted = true;
	}

	/**
	 * Boot a specific service provider.
	 *
	 * @param ServiceProviderInterface $provider Provider to boot.
	 * @return void
	 */
	private function boot_provider( ServiceProviderInterface $provider ): void {
		$provider_class = get_class( $provider );

		// Skip if already booted
		if ( isset( $this->booted_providers[ $provider_class ] ) ) {
			return;
		}

		// Boot the provider
		$provider->boot( $this->container );

		// Mark as booted
		$this->booted_providers[ $provider_class ] = $provider;
	}

	/**
	 * Get the container instance.
	 *
	 * @return Container
	 */
	public function get_container(): Container {
		return $this->container;
	}

	/**
	 * Get all registered providers.
	 *
	 * @return ServiceProviderInterface[]
	 */
	public function get_providers(): array {
		return $this->providers;
	}

	/**
	 * Check if a provider is registered.
	 *
	 * @param string $provider_class Provider class name.
	 * @return bool
	 */
	public function has_provider( string $provider_class ): bool {
		return isset( $this->providers[ $provider_class ] );
	}

	/**
	 * Get a specific provider.
	 *
	 * @param string $provider_class Provider class name.
	 * @return ServiceProviderInterface|null
	 */
	public function get_provider( string $provider_class ): ?ServiceProviderInterface {
		return $this->providers[ $provider_class ] ?? null;
	}

	/**
	 * Check if all providers have been booted.
	 *
	 * @return bool
	 */
	public function is_booted(): bool {
		return $this->booted;
	}

	/**
	 * Resolve a service from the container.
	 *
	 * @param string $name Service name.
	 * @return mixed
	 */
	public function resolve( string $name ) {
		return $this->container->resolve( $name );
	}

	/**
	 * Magic method to resolve services.
	 *
	 * @param string $name Service name.
	 * @return mixed
	 */
	public function __get( string $name ) {
		return $this->resolve( $name );
	}

	/**
	 * Magic method to check if service exists.
	 *
	 * @param string $name Service name.
	 * @return bool
	 */
	public function __isset( string $name ): bool {
		return $this->container->has( $name );
	}
}
