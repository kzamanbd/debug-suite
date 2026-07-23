<?php

namespace DebugSuite\Tests\Unit\Services\Console;

use DebugSuite\Packages\Symfony\Component\VarDumper\Cloner\VarCloner;
use DebugSuite\Services\Console\CapturingHtmlDumper;
use PHPUnit\Framework\TestCase;

class CapturingHtmlDumperTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['debug_suite_console_dump'] = '';
	}

	protected function tearDown(): void {
		unset( $GLOBALS['debug_suite_console_dump'] );
		parent::tearDown();
	}

	public function test_dump_is_captured_into_global(): void {
		$dumper = new CapturingHtmlDumper();
		$cloner = new VarCloner();
		$dumper->dump( $cloner->cloneVar( [ 'answer' => 42 ] ) );
		$this->assertStringContainsString( '42', $GLOBALS['debug_suite_console_dump'] );
		$this->assertStringContainsString( 'answer', $GLOBALS['debug_suite_console_dump'] );
	}

	public function test_dump_header_is_empty(): void {
		$dumper     = new CapturingHtmlDumper();
		$reflection = new \ReflectionMethod( $dumper, 'getDumpHeader' );
		$reflection->setAccessible( true );
		$this->assertSame( '', $reflection->invoke( $dumper ) );
	}
}
