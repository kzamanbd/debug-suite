<?php
/**
 * HtmlDumper subclass that captures dump() output into a global buffer.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Services\Console;

use DebugSuite\Packages\Symfony\Component\VarDumper\Dumper\HtmlDumper;

if ( ! defined( 'ABSPATH' ) && ! defined( 'DEBUG_SUITE_TESTING' ) ) {
	exit;
}

/**
 * Routes every dumped line into $GLOBALS['debug_suite_console_dump'] and
 * strips the HtmlDumper's inline JS/CSS header so the REST payload is clean.
 *
 * @since 1.0.0
 */
class CapturingHtmlDumper extends HtmlDumper {

	/**
	 * Append a line to the capture buffer.
	 *
	 * @param string $line      Line content.
	 * @param int    $depth     Nesting depth.
	 * @param string $indentPad Indentation padding.
	 * @return void
	 */
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase -- parent signature, third-party contract.
	protected function echoLine( string $line, int $depth, string $indentPad ): void {
		if ( -1 !== $depth ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
			$GLOBALS['debug_suite_console_dump'] .= str_repeat( $indentPad, $depth ) . $line . "\n";
		}
	}

	/**
	 * Remove the dumper's JS/CSS header from the REST response.
	 *
	 * @return string
	 */
	protected function getDumpHeader(): string {
		return '';
	}
}
