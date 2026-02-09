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

namespace DebugSuite\Dependency\Providers;

use DebugSuite\Admin;
use DebugSuite\Assets;
use DebugSuite\Core\DatabaseManager;
use DebugSuite\Core\HookManager;
use DebugSuite\Dependency\BaseServiceProvider;
use DebugSuite\Services\DebugLog\LogDiscoveryService;
use DebugSuite\Services\DebugLog\LogsService;
use DebugSuite\Services\DebugLog\WPLogReaderService;
use DebugSuite\Services\OverviewService;
use DebugSuite\Services\FeatureService;
use DebugSuite\Services\SettingsService;
use DebugSuite\Pages\PageManager;
use DebugSuite\Pages\Feature;

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
class AppServiceProvider extends BaseServiceProvider {

	protected array $tags = [ 'app-service' ];

	protected array $services = [
		Admin::class,
		Assets::class,
		HookManager::class,
		DatabaseManager::class,
		SettingsService::class,
		FeatureService::class,
		PageManager::class,
		Feature::class,
	];

	public function register(): void {
		// Register debug log services with dependency injection
		foreach ( $this->services as $service ) {
			$definition = $this->share_with_implements_tags( $service );
			$this->add_tags( $definition, $this->tags );
		}

		$this->add_tags(
			$this->share_with_implements_tags( LogsService::class )
			->addArgument( new WPLogReaderService() )
			->addArgument( new LogDiscoveryService() ),
			$this->tags
		);

		$this->add_tags(
			$this->share_with_implements_tags( OverviewService::class )
			->addArgument( $this->container->get( LogsService::class ) ),
			$this->tags
		);
	}
}
