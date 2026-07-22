<?php

namespace DebugSuite\Tests\Unit\Services\Console;

use DebugSuite\Services\Console\ConsoleService;
use PHPUnit\Framework\TestCase;

class ConsoleServiceTest extends TestCase {

	private ConsoleService $service;

	protected function setUp(): void {
		parent::setUp();
		$this->service = new ConsoleService();
	}

	public function test_evaluates_expression_and_returns_value_in_output(): void {
		$result = $this->service->execute( 'return 1 + 1;' );
		$this->assertIsArray( $result );
		$this->assertStringContainsString( '2', $result['output'] );
		$this->assertArrayHasKey( 'execution_time', $result );
	}

	public function test_captures_echoed_output(): void {
		$result = $this->service->execute( 'echo "pinged";' );
		$this->assertStringContainsString( 'pinged', $result['output'] );
	}

	public function test_captures_dump_calls(): void {
		$result = $this->service->execute( 'dump( [ "k" => "v" ] );' );
		$this->assertStringContainsString( 'v', $result['dump'] );
	}

	public function test_dump_global_is_reset_between_runs(): void {
		$this->service->execute( 'dump( "first" );' );
		$second = $this->service->execute( 'return true;' );
		$this->assertStringNotContainsString( 'first', $second['dump'] );
	}

	public function test_thrown_exception_returns_wp_error_with_trace(): void {
		$result = $this->service->execute( 'throw new \RuntimeException( "boom" );' );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'debug_suite_console_error', $result->get_error_code() );
		$this->assertStringContainsString( 'boom', $result->get_error_message() );
		$data = $result->get_error_data();
		$this->assertSame( 422, $data['status'] );
		$this->assertArrayHasKey( 'trace', $data );
	}
}
