<?php

namespace DebugSuite\DependencyInjection\Providers;

use DebugSuite\DependencyInjection\BootableServiceProvider;
use DebugSuite\EmailLog\Providers\EmailLogServiceProvider;

class ServiceProvider extends BootableServiceProvider {

	public function boot(): void {
		$this->getContainer()->addServiceProvider( new AppServiceProvider() );
		$this->getContainer()->addServiceProvider( new RestRouteProvider() );

		// Enable Email Log service provider based on the option.
		if ( get_option( 'debug_suite_enable_email_log', 'no' ) === 'yes' ) {
			$this->getContainer()->addServiceProvider( new EmailLogServiceProvider() );
		}
	}

	public function register(): void {
		// TODO: Implement register() method.
	}
}
