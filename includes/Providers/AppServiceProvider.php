<?php
/**
 * Services service provider for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Providers;

use DebugSuite\Core\Container\AbstractServiceProvider;
use DebugSuite\Core\Container\Container;
use DebugSuite\Services\FileLogsService;
use DebugSuite\Services\FileManagerService;
use DebugSuite\Services\SettingsService;
use DebugSuite\API\FileLogsController;
use DebugSuite\API\FileManagerController;
use DebugSuite\API\SettingsController;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AppServiceProvider extends AbstractServiceProvider {

	protected array $provides = [
		FileLogsService::class,
		FileManagerService::class,
		SettingsService::class,
		FileLogsController::class,
		FileManagerController::class,
		SettingsController::class,
	];

	public function register( Container $container ): void {
		// Modern PHP-DI style definition array approach
		$container->add_definitions(
			[
				// Services with simple autowiring
				FileLogsService::class    => $container->object( FileLogsService::class ),
				FileManagerService::class => $container->object( FileManagerService::class ),
				SettingsService::class   => $container->object( SettingsService::class ),

				// Controllers with dependency injection
				FileLogsController::class    => $container->autowire( FileLogsController::class )->set_name( FileLogsController::class ),
				FileManagerController::class => $container->autowire( FileManagerController::class )->set_name( FileManagerController::class ),
				SettingsController::class   => $container->autowire( SettingsController::class )->set_name( SettingsController::class ),
			]
		);
	}
}
