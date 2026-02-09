<?php

namespace DebugSuite\Dependency\Providers;

use DebugSuite\API\EmailLogController;
use DebugSuite\Dependency\BaseServiceProvider;
use DebugSuite\Pages\EmailLogPage;
use DebugSuite\Services\EmailLogService;

class EmailLogServiceProvider extends BaseServiceProvider {
	protected array $tags = [ 'email-log-service' ];

	protected array $services = [
		EmailLogPage::class,
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
