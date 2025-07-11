<?php
/**
 * Application service provider for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Providers;

use DebugSuite\Core\Container\AbstractServiceProvider;
use DebugSuite\Core\Container\Container;
use DebugSuite\Services\DebugLog\FileLogsService;
use DebugSuite\Services\FileManagerService;
use DebugSuite\Services\SettingsService;
use DebugSuite\Services\OverviewService;
use DebugSuite\Services\DebugLog\WPLogReaderService;
use DebugSuite\API\FileLogsController;
use DebugSuite\API\FileManagerController;
use DebugSuite\API\SettingsController;
use DebugSuite\API\OverviewController;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AppServiceProvider extends AbstractServiceProvider {

	protected array $provides = [
		WPLogReaderService::class,
		FileLogsService::class,
		FileManagerService::class,
		SettingsService::class,
		OverviewService::class,
		FileLogsController::class,
		FileManagerController::class,
		SettingsController::class,
		OverviewController::class,
	];

	public function register( Container $container ): void {
		// Simple service registration - merged from ServicesServiceProvider
		$container->add_definitions(
			[
				// Core services as singletons
				WPLogReaderService::class => $container->object( WPLogReaderService::class ),
				FileLogsService::class    => $container->object( FileLogsService::class ),
				FileManagerService::class => $container->object( FileManagerService::class ),
				SettingsService::class    => $container->object( SettingsService::class ),
				OverviewService::class    => $container->autowire( OverviewService::class ),

				// REST API Controllers with automatic dependency injection
				FileLogsController::class    => $container->autowire( FileLogsController::class ),
				FileManagerController::class => $container->autowire( FileManagerController::class ),
				SettingsController::class    => $container->autowire( SettingsController::class ),
				OverviewController::class    => $container->autowire( OverviewController::class ),
			]
		);
	}
}
