<?php

namespace DebugSuite\Dependency\Providers;

use DebugSuite\Dependency\BootableServiceProvider;
use DebugSuite\Modules\EmailLog\Providers\EmailLogServiceProvider;

class ServiceProvider extends BootableServiceProvider {

	public function boot(): void {
		$this->getContainer()->addServiceProvider( new AppServiceProvider() );
		$this->getContainer()->addServiceProvider( new RestRouteProvider() );
	}

	public function register(): void {
		// TODO: Implement register() method.
	}
}
