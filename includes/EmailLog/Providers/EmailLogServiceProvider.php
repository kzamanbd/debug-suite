<?php

namespace DebugSuite\EmailLog\Providers;

use DebugSuite\DependencyInjection\BaseServiceProvider;
use DebugSuite\EmailLog\API\EmailLogController;
use DebugSuite\EmailLog\EmailLogHookRegistry;
use DebugSuite\EmailLog\Services\EmailLogService;

class EmailLogServiceProvider extends BaseServiceProvider {
	protected array $tags = [ 'email-log-service' ];

	protected array $services = [
		EmailLogHookRegistry::class,
		EmailLogService::class,
	];
	public function register(): void {
		foreach ( $this->services as $service ) {
			$definition = $this->share_with_implements_tags( $service );
			$this->add_tags( $definition, $this->tags );
		}
		$this->add_tags(
			$this->share_with_implements_tags( EmailLogController::class )->addArgument( new EmailLogService() ),
			$this->tags
		);
	}
}
