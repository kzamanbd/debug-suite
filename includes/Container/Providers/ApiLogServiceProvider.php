<?php

namespace DebugSuite\Container\Providers;

use DebugSuite\API\ApiLogController;
use DebugSuite\Container\BaseServiceProvider;
use DebugSuite\Pages\ApiLogPage;
use DebugSuite\Services\ApiLogService;

class ApiLogServiceProvider extends BaseServiceProvider {
	protected array $tags = [ 'api-log-service' ];

	protected array $services = [
		ApiLogPage::class,
		ApiLogService::class,
	];

	public function register(): void {
		foreach ( $this->services as $service ) {
			$definition = $this->share_with_implements_tags( $service );
			$this->add_tags( $definition, $this->tags );
		}
		$this->add_tags(
			$this->share_with_implements_tags( ApiLogController::class )->addArgument( new ApiLogService() ),
			$this->tags
		);
	}
}
