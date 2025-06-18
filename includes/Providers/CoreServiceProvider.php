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

class CoreServiceProvider extends AbstractServiceProvider {

	protected array $provides = [
		Assets::class,
		I18n::class,
	];

	public function register( Container $container ): void {
		// Modern PHP-DI style definition array approach
		$container->add_definitions(
			[
				// Core services with object creation
				Assets::class => $container->object( Assets::class ),
				I18n::class   => $container->object( I18n::class ),
			]
		);
	}
}
