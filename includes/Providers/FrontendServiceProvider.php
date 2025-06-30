<?php
/**
 * Frontend service provider for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Providers;

use DebugSuite\Core\Container\AbstractServiceProvider;
use DebugSuite\Core\Container\Container;
use DebugSuite\Frontend\Frontend;

class FrontendServiceProvider extends AbstractServiceProvider {

	protected array $provides = [
		Frontend::class,
	];

	public function register( Container $container ): void {
		// Simple frontend service registration
		$container->add_definitions(
			[
				Frontend::class => $container->object( Frontend::class ),
			]
		);
	}
}
