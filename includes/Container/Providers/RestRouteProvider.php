<?php
/**
 * REST Controller provider for Debug Suite.
 *
 * Registers REST API controllers with automatic dependency injection.
 * Business logic services are registered separately in AppServiceProvider.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Container\Providers;

use DebugSuite\API\ConsoleController;
use DebugSuite\API\FeatureController;
use DebugSuite\API\LogsController;
use DebugSuite\API\SettingsController;
use DebugSuite\Container\BaseServiceProvider;
use DebugSuite\Services\Console\ConsoleService;
use DebugSuite\Services\Console\ConsoleSettingsService;
use DebugSuite\Services\DebugLog\LogsService;
use DebugSuite\Services\FeatureService;
use DebugSuite\Services\SettingsService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST controller provider that registers API controllers with dependency injection.
 *
 * This provider handles:
 * - REST API controllers (API namespace)
 * - Automatic dependency injection for controllers
 * - Service resolution from AppServiceProvider services
 *
 * @since 1.0.0
 */
class RestRouteProvider extends BaseServiceProvider {

	protected array $provides = [
		LogsController::class     => LogsService::class,
		SettingsController::class => SettingsService::class,
		FeatureController::class  => FeatureService::class,
		ConsoleController::class  => [ ConsoleService::class, ConsoleSettingsService::class ],
	];

	public function register(): void {
		// Register REST API controllers with dependency injection
		foreach ( $this->provides as $controller => $dependencies ) {
			$definition = $this->share_with_implements_tags( $controller );

			foreach ( (array) $dependencies as $dependency ) {
				$definition->addArgument( $this->container->get( $dependency ) );
			}

			$this->add_tags( $definition, [ 'rest-controller' ] );
		}
	}
}
