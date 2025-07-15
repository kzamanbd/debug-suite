<?php
/**
 * Application service provider for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Providers;

use DebugSuite\API\FileManagerController;
use DebugSuite\API\LogsController;
use DebugSuite\API\OverviewController;
use DebugSuite\API\SettingsController;
use DebugSuite\Assets;
use DebugSuite\Core\Container\AbstractServiceProvider;
use DebugSuite\Core\Container\Container;
use DebugSuite\Internal\HookManager;
use DebugSuite\Services\DebugLog\LogsService;
use DebugSuite\Services\DebugLog\WPLogReaderService;
use DebugSuite\Services\FileManagerService;
use DebugSuite\Services\FrontendRouterService;
use DebugSuite\Services\OverviewService;
use DebugSuite\Services\SettingsService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AppServiceProvider extends AbstractServiceProvider {

	protected array $provides = [
		Assets::class,
		HookManager::class,
		WPLogReaderService::class,
		LogsService::class,
		FileManagerService::class,
		SettingsService::class,
		OverviewService::class,
		FrontendRouterService::class,
		LogsController::class,
		FileManagerController::class,
		SettingsController::class,
		OverviewController::class,
	];

	public function register( Container $container ): void {
		// Simple service registration - merged from ServicesServiceProvider
		$container->add(
			[
				Assets::class                => $container->object( Assets::class ),
				HookManager::class           => $container->object( HookManager::class ),
				// Core services as singletons
				WPLogReaderService::class    => $container->object( WPLogReaderService::class ),
				LogsService::class           => $container->object( LogsService::class ),
				FileManagerService::class    => $container->object( FileManagerService::class ),
				SettingsService::class       => $container->object( SettingsService::class ),
				OverviewService::class       => $container->autowire( OverviewService::class ),
				FrontendRouterService::class => $container->autowire( FrontendRouterService::class ),

				// REST API Controllers with automatic dependency injection
				LogsController::class        => $container->autowire( LogsController::class ),
				FileManagerController::class => $container->autowire( FileManagerController::class ),
				SettingsController::class    => $container->autowire( SettingsController::class ),
				OverviewController::class    => $container->autowire( OverviewController::class ),
			]
		);
	}
}