<?php
/**
 * Service manager for handling service providers.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core\Container;

use DebugSuite\Interfaces\Hookable;

/**
 * Service Manager for managing service providers and the container.
 *
 * Provides centralized management of service provider registration, booting,
 * and hook registration. Ensures proper lifecycle management for all services.
 *
 * @since DEBUG_SUITE_SINCE
 */
class ServiceManager {

	/**
	 * Container instance.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * Registered service providers.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var ServiceProviderInterface[]
	 */
	private array $providers = [];

	/**
	 * Whether all providers have been booted.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var bool
	 */
	private bool $booted = false;

	/**
	 * Constructor.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param Container $DI Container instance.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Register a service provider.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param ServiceProviderInterface|string $provider Provider instance or class name.
	 *
	 * @return ServiceProviderInterface
	 * @throws \Exception If provider is invalid.
	 */
	public function register( $provider ): ServiceProviderInterface {
		// If provider is a string, instantiate it
		if ( is_string( $provider ) ) {
			if ( ! class_exists( $provider ) ) {
				throw new \Exception( "Provider class [{$provider}] does not exist." ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
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

		return $provider;
	}

	/**
	 * Register multiple service providers.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param array $providers Array of provider instances or class names.
	 *
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
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return void
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		// First, boot all providers (register their services)
		foreach ( $this->providers as $provider ) {
			$this->boot_provider( $provider );
		}

		// Then, centrally register hooks for all Hookable services
		$this->register_all_hooks();

		$this->booted = true;
	}

	/**
	 * Boot a specific service provider.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param ServiceProviderInterface $provider Provider to boot.
	 *
	 * @return void
	 */
	private function boot_provider( ServiceProviderInterface $provider ): void {
		// Simply boot the provider - no need for complex tracking
		$provider->boot( $this->container );
	}

	/**
	 * Register hooks for all services that implement Hookable interface.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return void
	 */
	private function register_all_hooks(): void {
		// Get all services from all providers
		foreach ( $this->providers as $provider ) {
			foreach ( $provider->provides() as $service_class ) {
				if ( $this->container->has( $service_class ) ) {
					$instance = $this->container->resolve( $service_class );

					if ( $instance instanceof Hookable ) {
						$instance->register_hooks();
					}
				}
			}
		}
	}

	/**
	 * Get the container instance.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return Container
	 */
	public function get_container(): Container {
		return $this->container;
	}

	/**
	 * Get all registered providers.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return ServiceProviderInterface[]
	 */
	public function get_providers(): array {
		return $this->providers;
	}

	/**
	 * Check if a provider is registered.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $provider_class Provider class name.
	 *
	 * @return bool
	 */
	public function has_provider( string $provider_class ): bool {
		return isset( $this->providers[ $provider_class ] );
	}

	/**
	 * Get a specific provider.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $provider_class Provider class name.
	 *
	 * @return ServiceProviderInterface|null
	 */
	public function get_provider( string $provider_class ): ?ServiceProviderInterface {
		return $this->providers[ $provider_class ] ?? null;
	}

	/**
	 * Check if all providers have been booted.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return bool
	 */
	public function is_booted(): bool {
		return $this->booted;
	}

	/**
	 * Resolve a service from the container.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $name Service name.
	 *
	 * @return mixed
	 */
	public function resolve( string $name ) {
		return $this->container->resolve( $name );
	}

	/**
	 * Magic method to resolve services.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $name Service name.
	 *
	 * @return mixed
	 */
	public function __get( string $name ) {
		return $this->resolve( $name );
	}

	/**
	 * Magic method to check if service exists.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $name Service name.
	 *
	 * @return bool
	 */
	public function __isset( string $name ): bool {
		return $this->container->has( $name );
	}
}
