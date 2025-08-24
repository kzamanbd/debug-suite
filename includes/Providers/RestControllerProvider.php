<?php
/**
 * REST Controller provider for Debug Suite.
 *
 * Registers REST API controllers with automatic dependency injection.
 * Business logic services are registered separately in AppServiceProvider.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Providers;

use DebugSuite\API\EmailLogController;
use DebugSuite\API\LogsController;
use DebugSuite\API\FileManagerController;
use DebugSuite\API\OverviewController;
use DebugSuite\API\SettingsController;
use DebugSuite\Core\Container\AbstractServiceProvider;
use DebugSuite\Core\Container\Container;

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
class RestControllerProvider extends AbstractServiceProvider {

	protected array $provides = [
		LogsController::class,
		FileManagerController::class,
		SettingsController::class,
		OverviewController::class,
		EmailLogController::class,
	];

	public function register( Container $container ): void {
		// REST API Controllers with automatic dependency injection
		$container->add(
			[
				LogsController::class        => $container->autowire( LogsController::class ),
				FileManagerController::class => $container->autowire( FileManagerController::class ),
				SettingsController::class    => $container->autowire( SettingsController::class ),
				OverviewController::class    => $container->autowire( OverviewController::class ),
				EmailLogController::class    => $container->autowire( EmailLogController::class ),
			]
		);
	}
}
