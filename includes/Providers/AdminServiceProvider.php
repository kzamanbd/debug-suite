<?php
/**
 * Admin service provider for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Providers;

use DebugSuite\Core\Container\AbstractServiceProvider;
use DebugSuite\Core\Container\Container;
use DebugSuite\Admin\Admin;

class AdminServiceProvider extends AbstractServiceProvider {

	protected array $provides = [
		Admin::class,
	];

	public function register( Container $container ): void {
		// Modern PHP-DI style definition array approach
		$container->add_definitions(
			[
				// Admin service with object creation
				Admin::class => $container->object( Admin::class ),
			]
		);
	}
}
