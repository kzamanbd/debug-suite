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
		$container->singleton( Assets::class, fn() => new Assets() );
		$container->singleton( I18n::class, fn() => new I18n() );
	}
}
