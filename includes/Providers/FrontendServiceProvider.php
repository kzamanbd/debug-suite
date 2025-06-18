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
		// Modern PHP-DI style definition array approach
		$container->add_definitions(
			[
				// Frontend service with object creation
				Frontend::class => $container->object( Frontend::class ),
			]
		);
	}
}
