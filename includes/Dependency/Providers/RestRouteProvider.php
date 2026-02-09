<?php
/**
 * REST Controller provider for Debug Suite.
 *
 * Registers REST API controllers with automatic dependency injection.
 * Business logic services are registered separately in AppServiceProvider.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Dependency\Providers;

use DebugSuite\API\FeatureController;
use DebugSuite\API\LogsController;
use DebugSuite\API\OverviewController;
use DebugSuite\API\SettingsController;
use DebugSuite\Dependency\BaseServiceProvider;
use DebugSuite\Services\DebugLog\LogsService;
use DebugSuite\Services\FeatureService;
use DebugSuite\Services\OverviewService;
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
		LogsController::class      => LogsService::class,
		SettingsController::class  => SettingsService::class,
		OverviewController::class  => OverviewService::class,
		FeatureController::class   => FeatureService::class,
	];

	public function register(): void {
		// Register REST API controllers with dependency injection
		foreach ( $this->provides as $controller => $dependency ) {
			$definition = $this->share_with_implements_tags( $controller )->addArgument( $this->container->get( $dependency ) );
			$this->add_tags( $definition, [ 'rest-controller' ] );
		}
	}
}
