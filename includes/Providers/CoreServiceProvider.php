<?php
/**
 * Core service provider for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Providers;

use DebugSuite\Core\Container\AbstractServiceProvider;
use DebugSuite\Core\Container\Container;
use DebugSuite\Core\Assets;
use DebugSuite\Core\I18n;
use DebugSuite\Core\Plugin;

class CoreServiceProvider extends AbstractServiceProvider {

	protected array $provides = [
		Assets::class,
		I18n::class,
		Plugin::class,
	];

	public function register( Container $container ): void {
		// Simple core services registration
		$container->add_definitions(
			[
				Assets::class => $container->object( Assets::class ),
				I18n::class   => $container->object( I18n::class ),
				Plugin::class => $container->object( Plugin::class ),
			]
		);
	}
}
