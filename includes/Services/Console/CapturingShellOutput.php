<?php
/**
 * ShellOutput subclass that captures PsySH output into a string.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Services\Console;

use DebugSuite\Packages\Psy\Output\ShellOutput;
use Throwable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Accumulates all written output instead of streaming it, so it can be
 * returned in a REST response.
 *
 * @since 1.0.0
 */
class CapturingShellOutput extends ShellOutput {

	/**
	 * Accumulated output.
	 *
	 * @var string
	 */
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase -- property name is part of the ConsoleService interface contract.
	public string $outputMessage = '';

	/**
	 * Captured execution exception, if any.
	 *
	 * @var Throwable|null
	 */
	public ?Throwable $exception = null;

	/**
	 * Append the message to the buffer instead of writing to a stream.
	 *
	 * @param string $message Message to write.
	 * @param bool   $newline Whether a newline should follow.
	 * @return void
	 */
	public function doWrite( $message, $newline ): void {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$this->outputMessage .= $message;
		if ( $newline ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$this->outputMessage .= "\n";
		}
	}
}
