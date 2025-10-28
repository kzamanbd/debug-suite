<?php

namespace DebugSuite\DependencyInjection\Providers;

use DebugSuite\DependencyInjection\BootableServiceProvider;
use DebugSuite\Modules\EmailLog\Providers\EmailLogServiceProvider;

class ServiceProvider extends BootableServiceProvider {

	public function boot(): void {
		$this->getContainer()->addServiceProvider( new AppServiceProvider() );
		$this->getContainer()->addServiceProvider( new RestRouteProvider() );
		$this->getContainer()->addServiceProvider( new EmailLogServiceProvider() );
	}

	public function register(): void {
		// TODO: Implement register() method.
	}
}
