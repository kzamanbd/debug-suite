<?php
/**
 * Core service provider for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Providers;

use DebugSuite\Core\Container\AbstractServiceProvider;
use DebugSuite\Core\Container\Container;
use DebugSuite\Assets;
use DebugSuite\Core\Plugin;

class CoreServiceProvider extends AbstractServiceProvider {

	protected array $provides = [
		Assets::class,
		Plugin::class,
	];

	public function register( Container $container ): void {
		// Simple core services registration
		$container->add_definitions(
			[
				Assets::class => $container->object( Assets::class ),
				Plugin::class => $container->object( Plugin::class ),
			]
		);
	}
}
