<?php
/**
 * PHP evaluation service backed by a scoped PsySH shell.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Services\Console;

use DebugSuite\Interfaces\Hookable;
use DebugSuite\Packages\Psy\Configuration;
use DebugSuite\Packages\Psy\ExecutionClosure;
use DebugSuite\Packages\Psy\Shell;
use DebugSuite\Packages\Symfony\Component\VarDumper\Cloner\VarCloner;
use DebugSuite\Packages\Symfony\Component\VarDumper\VarDumper;
use Throwable;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Evaluates a snippet of PHP inside the WordPress runtime and returns the
 * rendered output, captured dumps, and execution time.
 *
 * @since 1.0.0
 */
class ConsoleService implements Hookable {

	/**
	 * No hooks needed; the container resolves this as a shared service.
	 *
	 * @return void
	 */
	public function register_hooks(): void {}

	/**
	 * Evaluate a PHP snippet.
	 *
	 * @param string $input PHP source to evaluate.
	 * @return array{output: string, dump: string, execution_time: string}|WP_Error
	 */
	public function execute( string $input ) {
		$GLOBALS['debug_suite_console_dump'] = '';
		$this->register_dump_handler();

		$timer = microtime( true );

		$config = new Configuration( [ 'configDir' => WP_CONTENT_DIR ] );
		$output = new CapturingShellOutput();

		$config->setOutput( $output );
		$config->setColorMode( Configuration::COLOR_MODE_DISABLED );

		$shell = new Shell( $config );
		$shell->setOutput( $output );
		$shell->addCode( $input );

		extract( $shell->getScopeVariablesDiff( get_defined_vars() ) ); // phpcs:ignore WordPress.PHP.DontExtract

		// Tracks whether our ob_start() call is still the active buffer, so
		// `finally` only ever closes a buffer *we* opened - never an outer one.
		$buffering = false;

		try {
			ob_start( [ $shell, 'writeStdout' ], 1 );
			$buffering = true;
			set_error_handler( [ $shell, 'handleError' ] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors

			$code = $shell->flushCode();
			$_    = eval( $shell->onExecute( $code ? $code : ExecutionClosure::NOOP_INPUT ) ); // phpcs:ignore Squiz.PHP.Eval.Discouraged

			$shell->setScopeVariables( get_defined_vars() );
			$shell->writeReturnValue( $_ );

			ob_end_flush();
			$buffering = false;

			if ( $output->exception instanceof Throwable ) {
				throw $output->exception;
			}

			return [
				'output'         => $output->outputMessage, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				'dump'           => $GLOBALS['debug_suite_console_dump'],
				'execution_time' => number_format( microtime( true ) - $timer, 3, '.', '' ),
			];
		} catch ( Throwable $error ) {
			return new WP_Error(
				'debug_suite_console_error',
				$error->getMessage(),
				[
					'status' => 422,
					'input'  => $input,
					'trace'  => $error->getTraceAsString(),
				]
			);
		} finally {
			// Always restore the error handler and close any buffer we
			// opened - even when eval() throws and jumps straight past the
			// happy-path cleanup above - so a failing snippet never leaks
			// PsySH's error handler or an open output buffer into the rest
			// of the request/test run.
			restore_error_handler();
			if ( $buffering ) {
				ob_end_clean();
			}
		}
	}

	/**
	 * Route dump() calls to the capturing HTML dumper.
	 *
	 * @return void
	 */
	private function register_dump_handler(): void {
		$cloner = new VarCloner();
		$dumper = new CapturingHtmlDumper();

		VarDumper::setHandler(
			static function ( $var ) use ( $cloner, $dumper ) {
				$dumper->dump( $cloner->cloneVar( $var ) );
			}
		);
	}
}
