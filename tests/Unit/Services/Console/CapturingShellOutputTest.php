<?php

namespace DebugSuite\Tests\Unit\Services\Console;

use DebugSuite\Services\Console\CapturingShellOutput;
use PHPUnit\Framework\TestCase;

class CapturingShellOutputTest extends TestCase {

	public function test_do_write_accumulates_into_output_message(): void {
		$output = new CapturingShellOutput();
		$output->writeln( 'hello' );
		$output->writeln( 'world' );
		$this->assertStringContainsString( 'hello', $output->outputMessage );
		$this->assertStringContainsString( 'world', $output->outputMessage );
	}

	public function test_exception_property_defaults_to_null(): void {
		$output = new CapturingShellOutput();
		$this->assertNull( $output->exception );
	}
}
