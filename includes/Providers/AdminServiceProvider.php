<?php
/**
 * Admin service provider for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Providers;

use DebugSuite\Core\Container\AbstractServiceProvider;
use DebugSuite\Core\Container\Container;
use DebugSuite\Admin;

class AdminServiceProvider extends AbstractServiceProvider {

	protected array $provides = [
		Admin::class,
	];

	public function register( Container $container ): void {
		// Simple admin service registration
		$container->add_definitions(
			[
				Admin::class => $container->object( Admin::class ),
			]
		);
	}
}
