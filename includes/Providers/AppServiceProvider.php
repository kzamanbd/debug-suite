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
		$container->singleton( FileLogsService::class, fn() => new FileLogsService() );
		$container->singleton( FileManagerService::class, fn() => new FileManagerService() );
		$container->singleton( SettingsService::class, fn() => new SettingsService() );

		$container->singleton( FileLogsController::class, fn( $c ) => new FileLogsController( $c->get( FileLogsService::class ) ) );
		$container->singleton( FileManagerController::class, fn( $c ) => new FileManagerController( $c->get( FileManagerService::class ) ) );
		$container->singleton( SettingsController::class, fn( $c ) => new SettingsController( $c->get( SettingsService::class ) ) );
	}
}
