<?php
/**
 * Frontend service provider for registering frontend services.
 *
 * @since      1.0.0
 * @package    DebugSuite
 * @author     Kamruzzaman <kzamanbn@gmail.com>
 */

namespace DebugSuite\Providers;

use DebugSuite\Core\AbstractServiceProvider;
use DebugSuite\Core\Container;
use DebugSuite\Frontend\Frontend;

/**
 * Frontend Service Provider for registering frontend services.
 *
 * @since      1.0.0
 * @package    DebugSuite
 * @author     Kamruzzaman <kzamanbn@gmail.com>
 */
class FrontendServiceProvider extends AbstractServiceProvider {

	/**
	 * Services provided by this provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		'frontend',
		Frontend::class,
	);

	/**
	 * Register services with the container.
	 *
	 * @param Container $container The container instance.
	 * @return void
	 */
	public function register( Container $container ): void {
		// Register Frontend service
		$container->singleton( 'frontend', function( Container $container ) {
			return new Frontend();
		});

		$container->singleton( Frontend::class, function( Container $container ) {
			return $container->resolve( 'frontend' );
		});

		$this->mark_registered();
	}

	/**
	 * Boot services after all providers have been registered.
	 *
	 * @param Container $container The container instance.
	 * @return void
	 */
	public function boot( Container $container ): void {
		// Frontend services are automatically initialized when resolved
		// No additional booting required for frontend services
		
		$this->mark_booted();
	}
}
