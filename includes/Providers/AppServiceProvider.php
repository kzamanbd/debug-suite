<?php
/**
 * Application service provider for Debug Suite.
 *
 * Registers business logic services, core WordPress integration services,
 * and admin interface components. REST API controllers are registered
 * separately in RestControllerProvider.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Providers;

use DebugSuite\Admin;
use DebugSuite\Assets;
use DebugSuite\Core\Container\AbstractServiceProvider;
use DebugSuite\Core\Container\Container;
use DebugSuite\Internal\HookManager;
use DebugSuite\Services\DebugLog\LogsService;
use DebugSuite\Services\DebugLog\WPLogReaderService;
use DebugSuite\Services\EmailLog\EmailLogService;
use DebugSuite\Services\OverviewService;
use DebugSuite\Services\SettingsService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Application service provider that registers core business services.
 *
 * This provider handles:
 * - Business logic services (Services namespace)
 * - Core WordPress integration (Admin, Assets, HookManager)
 * - Service dependencies with automatic resolution
 *
 * @since 1.0.0
 */
class AppServiceProvider extends AbstractServiceProvider {

	protected array $provides = [
		Admin::class,
		Assets::class,
		HookManager::class,
		WPLogReaderService::class,
		LogsService::class,
		SettingsService::class,
		OverviewService::class,
		\DebugSuite\Services\EmailLog\EmailLogService::class,
	];

	public function register( Container $container ): void {
		// Simple service registration - merged from ServicesServiceProvider
		$container->add(
			[
				Admin::class                => $container->object( Admin::class ),
				Assets::class               => $container->object( Assets::class ),
				HookManager::class          => $container->object( HookManager::class ),
				// Core services as singletons
				WPLogReaderService::class   => $container->object( WPLogReaderService::class ),
				LogsService::class          => $container->object( LogsService::class ),
				SettingsService::class      => $container->object( SettingsService::class ),
				OverviewService::class      => $container->autowire( OverviewService::class ),
				EmailLogService::class      => $container->object( EmailLogService::class ),
			]
		);
	}
}
