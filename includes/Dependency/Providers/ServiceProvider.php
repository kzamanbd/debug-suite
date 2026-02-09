<?php

namespace DebugSuite\Dependency\Providers;

use DebugSuite\Dependency\BootableServiceProvider;

class ServiceProvider extends BootableServiceProvider {

	public function boot(): void {
		$this->getContainer()->addServiceProvider( new AppServiceProvider() );
		$this->getContainer()->addServiceProvider( new RestRouteProvider() );

		if ( get_option( 'debug_suite_email_log_enable', false ) ) {
			$this->getContainer()->addServiceProvider( new EmailLogServiceProvider() );
		}
	}

	public function register(): void {
		// TODO: Implement register() method.
	}
}
