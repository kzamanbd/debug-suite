<?php

namespace DebugSuite\Container\Providers;

use DebugSuite\Container\BootableServiceProvider;

class ServiceProvider extends BootableServiceProvider {

	public function boot(): void {
		$this->getContainer()->addServiceProvider( new AppServiceProvider() );
		$this->getContainer()->addServiceProvider( new RestRouteProvider() );

		if ( debug_suite_is_feature_enabled( 'email-log' ) ) {
			$this->getContainer()->addServiceProvider( new EmailLogServiceProvider() );
		}

		if ( debug_suite_is_feature_enabled( 'api-logger' ) ) {
			$this->getContainer()->addServiceProvider( new ApiLogServiceProvider() );
		}
	}

	public function register(): void {
		// TODO: Implement register() method.
	}
}
