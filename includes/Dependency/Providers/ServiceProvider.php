<?php

namespace DebugSuite\Dependency\Providers;

use DebugSuite\Dependency\BootableServiceProvider;

class ServiceProvider extends BootableServiceProvider {

	public function boot(): void {
		$this->getContainer()->addServiceProvider( new AppServiceProvider() );
		$this->getContainer()->addServiceProvider( new RestRouteProvider() );

		if ( debug_suite_is_feature_enabled( 'email-log' ) ) {
			$this->getContainer()->addServiceProvider( new EmailLogServiceProvider() );
		}
	}

	public function register(): void {
		// TODO: Implement register() method.
	}
}
