<?php

namespace DebugSuite\Tests\Unit\Services\Console;

use DebugSuite\Services\Console\ConsoleSettingsService;
use DebugSuite\Tests\Helpers\DebugSuiteTestCase;

class ConsoleSettingsServiceTest extends DebugSuiteTestCase {

	private ConsoleSettingsService $service;

	public function set_up(): void {
		parent::set_up();
		if ( ! $this->is_wordpress_available() ) {
			$this->markTestSkipped( 'WordPress test environment not available' );
		}
		$this->service = new ConsoleSettingsService();
	}

	public function test_get_returns_defaults_for_new_user(): void {
		$user_id  = $this->factory()->user->create();
		$settings = $this->service->get( $user_id );
		$this->assertSame( 'vertical', $settings['window_split'] );
		$this->assertSame( [], $settings['snippets'] );
	}

	public function test_save_then_get_roundtrip(): void {
		$user_id = $this->factory()->user->create();
		$this->service->save(
			$user_id,
			[
				'window_split' => 'horizontal',
				'snippets'     => [ [ 'id' => 'a1', 'title' => 'List users', 'code' => 'get_users();' ] ],
			]
		);
		$settings = $this->service->get( $user_id );
		$this->assertSame( 'horizontal', $settings['window_split'] );
		$this->assertCount( 1, $settings['snippets'] );
		$this->assertSame( 'List users', $settings['snippets'][0]['title'] );
	}

	public function test_save_rejects_invalid_window_split(): void {
		$user_id  = $this->factory()->user->create();
		$this->service->save( $user_id, [ 'window_split' => 'diagonal' ] );
		$settings = $this->service->get( $user_id );
		$this->assertSame( 'vertical', $settings['window_split'] );
	}
}
