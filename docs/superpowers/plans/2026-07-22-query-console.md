# QueryConsole Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the `QueryConsole` stub with a working in-browser PHP REPL that evaluates arbitrary PHP in the WordPress runtime via PsySH, with run history, saved snippets, and a horizontal/vertical split layout.

**Architecture:** PsySH is added with `composer require` and namespace-scoped into `DebugSuite\Packages\` by mozart. Output capture is done with plugin-owned subclasses of the *scoped* PsySH/Symfony classes (no vendor-file patching). A `ConsoleService` runs the eval flow and returns `{ output, dump, execution_time }` or a `WP_Error`; a `ConsoleSettingsService` persists per-user prefs in user meta. `ConsoleController` exposes `POST /console/execute` and `GET|POST /console/settings` under `debug-suite/v1`. The React page uses the existing Monaco `Editor`, an output pane, a snippets menu, and a split layout, wired through `apiFetch`.

**Tech Stack:** PHP 8.1+, PsySH (`psy/psysh`), mozart scoping, WordPress REST API, league/container DI, React + `@wordpress/element`, `@wordpress/api-fetch`, Monaco, Tailwind, `@wordpress/scripts` (webpack).

## Global Constraints

- **PHP floor:** `>=8.1` (composer `require.php`). No syntax newer than 8.1.
- **Text domain:** `debug-suite` for every `__()`/`esc_html__()` call.
- **REST namespace:** `debug-suite/v1`. Console base: `console`.
- **Permissions:** every route uses `RestController::permissions_check` (logged-in **and** `manage_options`).
- **Scoped namespace:** all bundled deps live under `DebugSuite\Packages\` (e.g. `DebugSuite\Packages\Psy\…`, `DebugSuite\Packages\Symfony\…`). Never reference the un-scoped `Psy\` / `Symfony\` namespaces from plugin code.
- **User meta key:** `debug_suite_console`.
- **Dump global:** `$GLOBALS['debug_suite_console_dump']` — reset to `''` at the start of every execute() so PHPUnit's `beStrictAboutChangesToGlobalState` stays green.
- **No JS test runner** is configured. Frontend verification = `pnpm type-check` + `pnpm lint` + `pnpm build` succeed. TypeScript strictness is the safety net.
- **PHP test commands:** `vendor/bin/phpunit --testsuite=unit` and `--testsuite=integration`. Filter a single test with `--filter=<name>`.
- **PHPCS:** `composer phpcs:check` must pass (WordPress standard, tabs for indentation in PHP).

## File Structure

**Backend (create):**
- `includes/Services/Console/CapturingShellOutput.php` — subclass of scoped `Psy\Output\ShellOutput`; accumulates output.
- `includes/Services/Console/CapturingHtmlDumper.php` — subclass of scoped `Symfony\…\VarDumper\Dumper\HtmlDumper`; captures `dump()` calls.
- `includes/Services/Console/ConsoleService.php` — eval orchestration.
- `includes/Services/Console/ConsoleSettingsService.php` — per-user prefs in user meta.
- `includes/API/ConsoleController.php` — REST routes.

**Backend (modify):**
- `composer.json` — add `psy/psysh` to `require` and to `extra.mozart.packages`.
- `includes/Container/Providers/AppServiceProvider.php` — register the two services.
- `includes/Container/Providers/RestRouteProvider.php` — support multi-dependency controllers; register `ConsoleController`.

**Backend (tests, create):**
- `tests/Unit/Services/Console/CapturingShellOutputTest.php`
- `tests/Unit/Services/Console/CapturingHtmlDumperTest.php`
- `tests/Unit/Services/Console/ConsoleServiceTest.php`
- `tests/Unit/Services/Console/ConsoleSettingsServiceTest.php`
- `tests/Integration/API/ConsoleControllerTest.php`

**Frontend (create):**
- `src/pages/query-console/types.ts`
- `src/pages/query-console/constants.ts`
- `src/pages/query-console/hooks/use-console-api.ts`
- `src/pages/query-console/hooks/use-console-history.ts`
- `src/pages/query-console/components/output-pane.tsx`
- `src/pages/query-console/components/split-layout.tsx`
- `src/pages/query-console/components/snippets-menu.tsx`

**Frontend (modify):**
- `src/pages/query-console/index.tsx` — replace stub with full page.

---

## Task 1: Scope PsySH + smoke test (RISK GATE)

**Files:**
- Modify: `composer.json` (`require`, `extra.mozart.packages`)
- Test: `tests/Unit/Services/Console/PsyshScopingSmokeTest.php` (throwaway — deleted at end of task)

**Interfaces:**
- Produces: the scoped class `DebugSuite\Packages\Psy\Shell` and the rest of the scoped PsySH tree, usable by all later backend tasks.

**This is a decision gate.** If mozart cannot cleanly scope PsySH, stop and switch tooling (see Fallback) before continuing.

- [ ] **Step 1: Add PsySH to composer**

Edit `composer.json`. In `require` add `"psy/psysh": "^0.12"` (keep `"php": ">=8.1"`). In `extra.mozart.packages` add `"psy/psysh"` so the array reads:

```json
"packages": [
    "league/container",
    "psy/psysh"
]
```

- [ ] **Step 2: Install + scope**

Run: `COMPOSER_DEV_MODE=1 composer update psy/psysh --with-all-dependencies`
Then: `vendor/bin/mozart compose && composer dump-autoload`
Expected: `override/` now contains `Psy/` and `Symfony/` (and `PhpParser/`) directories under the `DebugSuite\Packages\` PSR-4 root.

Note: mozart may not follow every transitive dependency automatically. If `override/` is missing `Symfony/Component/Console`, `Symfony/Component/VarDumper`, or `PhpParser`, add those package names explicitly to `extra.mozart.packages` (`symfony/console`, `symfony/var-dumper`, `nikic/php-parser`, plus any `symfony/polyfill-*` and `psr/*` that PsySH pulls in) and re-run mozart.

- [ ] **Step 3: Write the smoke test**

Create `tests/Unit/Services/Console/PsyshScopingSmokeTest.php`:

```php
<?php

namespace DebugSuite\Tests\Unit\Services\Console;

use DebugSuite\Packages\Psy\Configuration;
use DebugSuite\Packages\Psy\Shell;
use PHPUnit\Framework\TestCase;

class PsyshScopingSmokeTest extends TestCase {

	public function test_scoped_psysh_classes_exist(): void {
		$this->assertTrue( class_exists( Shell::class ), 'Scoped Psy\\Shell must exist' );
		$this->assertTrue( class_exists( Configuration::class ), 'Scoped Psy\\Configuration must exist' );
	}

	public function test_scoped_shell_evaluates_expression(): void {
		$config = new Configuration( [ 'configDir' => sys_get_temp_dir() ] );
		$shell  = new Shell( $config );
		$shell->addCode( '2 + 3' );
		$result = eval( $shell->flushCode() );
		$this->assertSame( 5, $result );
	}
}
```

- [ ] **Step 4: Run the smoke test**

Run: `vendor/bin/phpunit --testsuite=unit --filter=PsyshScopingSmokeTest`
Expected: PASS (both tests green).

**Fallback if this fails:** mozart is archived and may mangle PsySH's string-based class references. Switch the scoper to `brianhenryie/strauss` (`composer require --dev brianhenryie/strauss`, add a `strauss` config block mirroring the mozart namespace/target) or `humbug/php-scoper`, re-run, and re-run this test. Only proceed past this task once Step 4 is green.

- [ ] **Step 5: Delete the smoke test and commit the scoping**

```bash
rm tests/Unit/Services/Console/PsyshScopingSmokeTest.php
git add composer.json composer.lock override
git commit -m "build: scope PsySH into DebugSuite\\Packages via mozart"
```

---

## Task 2: CapturingShellOutput

**Files:**
- Create: `includes/Services/Console/CapturingShellOutput.php`
- Test: `tests/Unit/Services/Console/CapturingShellOutputTest.php`

**Interfaces:**
- Consumes: scoped `DebugSuite\Packages\Psy\Output\ShellOutput` (from Task 1).
- Produces: `class CapturingShellOutput` with public `string $outputMessage = ''`, public `?\Throwable $exception = null`, and `doWrite(string $message, bool $newline): void` that appends to `$outputMessage`.

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Services/Console/CapturingShellOutputTest.php`:

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --testsuite=unit --filter=CapturingShellOutputTest`
Expected: FAIL — class `DebugSuite\Services\Console\CapturingShellOutput` not found.

- [ ] **Step 3: Write minimal implementation**

Create `includes/Services/Console/CapturingShellOutput.php`:

```php
<?php
/**
 * ShellOutput subclass that captures PsySH output into a string.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Services\Console;

use DebugSuite\Packages\Psy\Output\ShellOutput;
use Throwable;

if ( ! defined( 'ABSPATH' ) && ! defined( 'DEBUG_SUITE_TESTING' ) ) {
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
		$this->outputMessage .= $message;
		if ( $newline ) {
			$this->outputMessage .= "\n";
		}
	}
}
```

Note: if the installed PsySH `ShellOutput::doWrite` signature differs (e.g. typed params), match it exactly — check `override/Psy/Output/ShellOutput.php` and mirror the parent signature.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit --testsuite=unit --filter=CapturingShellOutputTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add includes/Services/Console/CapturingShellOutput.php tests/Unit/Services/Console/CapturingShellOutputTest.php
git commit -m "feat: add CapturingShellOutput for PsySH output capture"
```

---

## Task 3: CapturingHtmlDumper

**Files:**
- Create: `includes/Services/Console/CapturingHtmlDumper.php`
- Test: `tests/Unit/Services/Console/CapturingHtmlDumperTest.php`

**Interfaces:**
- Consumes: scoped `DebugSuite\Packages\Symfony\Component\VarDumper\Dumper\HtmlDumper` and `…\Cloner\VarCloner`.
- Produces: `class CapturingHtmlDumper` — `echoLine()` appends into `$GLOBALS['debug_suite_console_dump']`; `getDumpHeader()` returns `''`.

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Services/Console/CapturingHtmlDumperTest.php`:

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --testsuite=unit --filter=CapturingHtmlDumperTest`
Expected: FAIL — class not found.

- [ ] **Step 3: Write minimal implementation**

Create `includes/Services/Console/CapturingHtmlDumper.php`:

```php
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
	protected function echoLine( string $line, int $depth, string $indentPad ): void {
		if ( -1 !== $depth ) {
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
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit --testsuite=unit --filter=CapturingHtmlDumperTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add includes/Services/Console/CapturingHtmlDumper.php tests/Unit/Services/Console/CapturingHtmlDumperTest.php
git commit -m "feat: add CapturingHtmlDumper for dump() capture"
```

---

## Task 4: ConsoleService (eval orchestration)

**Files:**
- Create: `includes/Services/Console/ConsoleService.php`
- Test: `tests/Unit/Services/Console/ConsoleServiceTest.php`

**Interfaces:**
- Consumes: `CapturingShellOutput` (Task 2), `CapturingHtmlDumper` (Task 3), scoped `DebugSuite\Packages\Psy\Configuration`, `…\Psy\Shell`, `…\Psy\ExecutionClosure`, `…\Symfony\Component\VarDumper\VarDumper`, `…\Symfony\Component\VarDumper\Cloner\VarCloner`.
- Produces: `ConsoleService::execute( string $input ): array|\WP_Error`. Success array shape: `[ 'output' => string, 'dump' => string, 'execution_time' => string ]`. Failure: `WP_Error` code `debug_suite_console_error`, data `[ 'status' => 422, 'input' => string, 'trace' => string ]`.

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Services/Console/ConsoleServiceTest.php`:

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --testsuite=unit --filter=ConsoleServiceTest`
Expected: FAIL — class `ConsoleService` not found.

- [ ] **Step 3: Write minimal implementation**

Create `includes/Services/Console/ConsoleService.php`:

```php
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

if ( ! defined( 'ABSPATH' ) && ! defined( 'DEBUG_SUITE_TESTING' ) ) {
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

		try {
			$config = new Configuration( [ 'configDir' => WP_CONTENT_DIR ] );
			$output = new CapturingShellOutput();

			$config->setOutput( $output );
			$config->setColorMode( Configuration::COLOR_MODE_DISABLED );

			$shell = new Shell( $config );
			$shell->setOutput( $output );
			$shell->addCode( $input );

			extract( $shell->getScopeVariablesDiff( get_defined_vars() ) ); // phpcs:ignore WordPress.PHP.DontExtract

			ob_start( [ $shell, 'writeStdout' ], 1 );
			set_error_handler( [ $shell, 'handleError' ] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors

			$_ = eval( $shell->onExecute( $shell->flushCode() ?: ExecutionClosure::NOOP_INPUT ) ); // phpcs:ignore Squiz.PHP.Eval.Discouraged

			restore_error_handler();

			$shell->setScopeVariables( get_defined_vars() );
			$shell->writeReturnValue( $_ );

			ob_end_flush();

			if ( $output->exception instanceof Throwable ) {
				throw $output->exception;
			}

			return [
				'output'         => $output->outputMessage,
				'dump'           => $GLOBALS['debug_suite_console_dump'],
				'execution_time' => number_format( microtime( true ) - $timer, 3, '.', '' ),
			];
		} catch ( Throwable $error ) {
			if ( ob_get_length() ) {
				ob_end_clean();
			}

			return new WP_Error(
				'debug_suite_console_error',
				$error->getMessage(),
				[
					'status' => 422,
					'input'  => $input,
					'trace'  => $error->getTraceAsString(),
				]
			);
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
```

Note on `dump()` resolution: this sets the handler on the *scoped* `VarDumper`. If a console snippet calls the global `dump()` and the scoped Symfony global `dump()` function is not autoloaded after scoping (verify with the Task 4 test `test_captures_dump_calls`), add a guarded shim to `includes/helpers.php`:

```php
if ( ! function_exists( 'dump' ) ) {
	function dump( ...$vars ) {
		foreach ( $vars as $var ) {
			\DebugSuite\Packages\Symfony\Component\VarDumper\VarDumper::dump( $var );
		}
		return $vars[ array_key_first( $vars ) ] ?? null;
	}
}
```

Only add the shim if the test proves it is needed.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit --testsuite=unit --filter=ConsoleServiceTest`
Expected: PASS (all five tests). If `test_captures_dump_calls` fails, apply the helpers.php shim above and re-run.

- [ ] **Step 5: Commit**

```bash
git add includes/Services/Console/ConsoleService.php tests/Unit/Services/Console/ConsoleServiceTest.php includes/helpers.php
git commit -m "feat: add ConsoleService PHP evaluation via scoped PsySH"
```

---

## Task 5: ConsoleSettingsService

**Files:**
- Create: `includes/Services/Console/ConsoleSettingsService.php`
- Test: `tests/Unit/Services/Console/ConsoleSettingsServiceTest.php`

**Interfaces:**
- Produces:
  - `ConsoleSettingsService::get( int $user_id ): array` → `[ 'window_split' => 'vertical'|'horizontal', 'snippets' => array<int, array{id:string,title:string,code:string}> ]`. Missing keys fall back to defaults.
  - `ConsoleSettingsService::save( int $user_id, array $settings ): array` → merged, persisted settings. Only `window_split` (must be `horizontal`|`vertical`) and `snippets` (array) are accepted.

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Services/Console/ConsoleSettingsServiceTest.php`:

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --testsuite=unit --filter=ConsoleSettingsServiceTest`
Expected: FAIL — class not found.

- [ ] **Step 3: Write minimal implementation**

Create `includes/Services/Console/ConsoleSettingsService.php`:

```php
<?php
/**
 * Per-user console preferences stored in user meta.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Services\Console;

use DebugSuite\Interfaces\Hookable;

if ( ! defined( 'ABSPATH' ) && ! defined( 'DEBUG_SUITE_TESTING' ) ) {
	exit;
}

/**
 * Reads and writes the current user's console settings.
 *
 * @since 1.0.0
 */
class ConsoleSettingsService implements Hookable {

	private const META_KEY = 'debug_suite_console';

	/**
	 * No hooks needed.
	 *
	 * @return void
	 */
	public function register_hooks(): void {}

	/**
	 * Default settings.
	 *
	 * @return array{window_split: string, snippets: array}
	 */
	private function defaults(): array {
		return [
			'window_split' => 'vertical',
			'snippets'     => [],
		];
	}

	/**
	 * Get a user's console settings, merged over defaults.
	 *
	 * @param int $user_id User ID.
	 * @return array{window_split: string, snippets: array}
	 */
	public function get( int $user_id ): array {
		$saved = get_user_meta( $user_id, self::META_KEY, true );
		$saved = is_array( $saved ) ? $saved : [];

		return array_merge( $this->defaults(), $saved );
	}

	/**
	 * Persist a user's console settings.
	 *
	 * @param int   $user_id  User ID.
	 * @param array $settings Partial settings to merge and save.
	 * @return array{window_split: string, snippets: array}
	 */
	public function save( int $user_id, array $settings ): array {
		$current = $this->get( $user_id );

		if ( isset( $settings['window_split'] ) && in_array( $settings['window_split'], [ 'horizontal', 'vertical' ], true ) ) {
			$current['window_split'] = $settings['window_split'];
		}

		if ( isset( $settings['snippets'] ) && is_array( $settings['snippets'] ) ) {
			$current['snippets'] = array_values(
				array_map(
					static function ( $snippet ) {
						return [
							'id'    => (string) ( $snippet['id'] ?? wp_generate_uuid4() ),
							'title' => sanitize_text_field( (string) ( $snippet['title'] ?? '' ) ),
							'code'  => (string) ( $snippet['code'] ?? '' ),
						];
					},
					$settings['snippets']
				)
			);
		}

		update_user_meta( $user_id, self::META_KEY, $current );

		return $current;
	}
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit --testsuite=unit --filter=ConsoleSettingsServiceTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add includes/Services/Console/ConsoleSettingsService.php tests/Unit/Services/Console/ConsoleSettingsServiceTest.php
git commit -m "feat: add ConsoleSettingsService for per-user console prefs"
```

---

## Task 6: Container registration (multi-dependency controllers)

**Files:**
- Modify: `includes/Container/Providers/AppServiceProvider.php`
- Modify: `includes/Container/Providers/RestRouteProvider.php`

**Interfaces:**
- Consumes: `ConsoleService`, `ConsoleSettingsService` (Tasks 4–5), and `ConsoleController` (Task 7 — its constructor signature is `__construct( ConsoleService $console, ConsoleSettingsService $settings )`).
- Produces: `ConsoleService` and `ConsoleSettingsService` resolvable from the container and tagged `app-service`; `ConsoleController` resolvable, constructed with both dependencies, tagged `rest-controller`.

This task is wiring; it has no standalone unit test. It is verified by Task 7's integration test (routes only register if the controller resolves).

- [ ] **Step 1: Register the services in AppServiceProvider**

In `includes/Container/Providers/AppServiceProvider.php`, add the imports near the other `use DebugSuite\Services\…` lines:

```php
use DebugSuite\Services\Console\ConsoleService;
use DebugSuite\Services\Console\ConsoleSettingsService;
```

Add both classes to the `$services` array (after `SettingsService::class`):

```php
		ConsoleService::class,
		ConsoleSettingsService::class,
```

- [ ] **Step 2: Support multi-dependency controllers in RestRouteProvider**

Rewrite the `$provides` map and `register()` loop in `includes/Container/Providers/RestRouteProvider.php` so a controller can map to either a single dependency class **or** an array of them. Do **not** add the `ConsoleController` entry yet — the class doesn't exist until Task 7 (adding it now would fatal on `$this->container->get()`). The existing three entries stay as bare class strings; the `(array)` cast handles both forms:

```php
	protected array $provides = [
		LogsController::class     => LogsService::class,
		SettingsController::class => SettingsService::class,
		FeatureController::class  => FeatureService::class,
	];

	public function register(): void {
		foreach ( $this->provides as $controller => $dependencies ) {
			$definition = $this->share_with_implements_tags( $controller );

			foreach ( (array) $dependencies as $dependency ) {
				$definition->addArgument( $this->container->get( $dependency ) );
			}

			$this->add_tags( $definition, [ 'rest-controller' ] );
		}
	}
```

The `ConsoleController => [ ConsoleService::class, ConsoleSettingsService::class ]` map entry (and its `use` imports) is added in Task 7 Step 4, once the controller class exists.

- [ ] **Step 3: Verify the container still boots (no fatal)**

Run: `vendor/bin/phpunit --testsuite=integration --filter=SettingsControllerTest`
Expected: PASS — existing controllers still resolve, confirming the `(array)` refactor didn't break single-dependency registration.

- [ ] **Step 4: Commit**

```bash
git add includes/Container/Providers/AppServiceProvider.php includes/Container/Providers/RestRouteProvider.php
git commit -m "feat: register console services and support multi-dependency controllers"
```

---

## Task 7: ConsoleController (REST routes)

**Files:**
- Create: `includes/API/ConsoleController.php`
- Modify: `includes/Container/Providers/RestRouteProvider.php` (activate the `ConsoleController` map entry)
- Test: `tests/Integration/API/ConsoleControllerTest.php`

**Interfaces:**
- Consumes: `ConsoleService::execute()` (Task 4), `ConsoleSettingsService::get()/save()` (Task 5).
- Produces: routes under `debug-suite/v1`:
  - `POST /console/execute` — body `{ input: string }` → `{ output, dump, execution_time }` (200) or WP_Error 422.
  - `GET /console/settings` → `{ window_split, snippets }`.
  - `POST /console/settings` — body `{ window_split?, snippets? }` → merged settings.

- [ ] **Step 1: Write the failing test**

Create `tests/Integration/API/ConsoleControllerTest.php`:

```php
<?php
/**
 * Integration tests for ConsoleController REST API.
 *
 * @package DebugSuite\Tests\Integration\API
 * @group api
 * @group integration
 * @group rest-api
 */

namespace DebugSuite\Tests\Integration\API;

use DebugSuite\API\ConsoleController;
use DebugSuite\Services\Console\ConsoleService;
use DebugSuite\Services\Console\ConsoleSettingsService;
use DebugSuite\Tests\Helpers\DebugSuiteTestCase;
use WP_REST_Request;
use WP_REST_Server;

class ConsoleControllerTest extends DebugSuiteTestCase {

	protected $namespace = 'debug-suite/v1';
	private $controller;

	public function set_up(): void {
		parent::set_up();
		if ( ! $this->is_wordpress_available() ) {
			$this->markTestSkipped( 'WordPress test environment not available' );
		}

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->controller = new ConsoleController( new ConsoleService(), new ConsoleSettingsService() );
		$this->controller->register_routes();
		do_action( 'rest_api_init' );

		$this->create_admin_user();
	}

	public function test_execute_returns_output_shape(): void {
		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/console/execute' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( json_encode( [ 'input' => 'return 40 + 2;' ] ) );

		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'output', $data );
		$this->assertArrayHasKey( 'dump', $data );
		$this->assertArrayHasKey( 'execution_time', $data );
		$this->assertStringContainsString( '42', $data['output'] );
	}

	public function test_execute_rejects_empty_input(): void {
		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/console/execute' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( json_encode( [ 'input' => '   ' ] ) );

		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 400, $response->get_status() );
	}

	public function test_execute_forbidden_for_non_admin(): void {
		wp_set_current_user( $this->factory()->user->create() );
		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/console/execute' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( json_encode( [ 'input' => 'return 1;' ] ) );

		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 403, $response->get_status() );
	}

	public function test_settings_roundtrip(): void {
		$post = new WP_REST_Request( 'POST', '/' . $this->namespace . '/console/settings' );
		$post->set_header( 'content-type', 'application/json' );
		$post->set_body( json_encode( [ 'window_split' => 'horizontal' ] ) );
		rest_get_server()->dispatch( $post );

		$get      = new WP_REST_Request( 'GET', '/' . $this->namespace . '/console/settings' );
		$response = rest_get_server()->dispatch( $get );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'horizontal', $response->get_data()['window_split'] );
	}
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --testsuite=integration --filter=ConsoleControllerTest`
Expected: FAIL — class `DebugSuite\API\ConsoleController` not found.

- [ ] **Step 3: Write the controller**

Create `includes/API/ConsoleController.php`:

```php
<?php
/**
 * Console REST API controller for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\API;

use DebugSuite\Services\Console\ConsoleService;
use DebugSuite\Services\Console\ConsoleSettingsService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exposes PHP evaluation and per-user console preferences.
 *
 * @since 1.0.0
 */
class ConsoleController extends RestController {

	protected $rest_base = 'console';

	private ConsoleService $console;
	private ConsoleSettingsService $settings;

	public function __construct( ConsoleService $console, ConsoleSettingsService $settings ) {
		$this->console  = $console;
		$this->settings = $settings;
	}

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/execute',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'execute' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'input' => [
						'required'          => true,
						'type'              => 'string',
						'description'       => __( 'PHP code to evaluate.', 'debug-suite' ),
						'validate_callback' => [ $this, 'validate_input' ],
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/settings',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_settings' ],
					'permission_callback' => [ $this, 'permissions_check' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'save_settings' ],
					'permission_callback' => [ $this, 'permissions_check' ],
				],
			]
		);
	}

	/**
	 * Reject empty input.
	 *
	 * @param string $value Raw input.
	 * @return bool|WP_Error
	 */
	public function validate_input( $value ) {
		if ( is_string( $value ) && '' !== trim( $value ) ) {
			return true;
		}

		return new WP_Error( 'rest_invalid_param', __( 'Input is empty.', 'debug-suite' ), [ 'status' => 400 ] );
	}

	/**
	 * Evaluate PHP.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function execute( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$result = $this->console->execute( (string) $request->get_param( 'input' ) );

		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	/**
	 * Get the current user's console settings.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_settings( WP_REST_Request $request ): WP_REST_Response {
		return rest_ensure_response( $this->settings->get( get_current_user_id() ) );
	}

	/**
	 * Save the current user's console settings.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function save_settings( WP_REST_Request $request ): WP_REST_Response {
		$payload = [];

		if ( null !== $request->get_param( 'window_split' ) ) {
			$payload['window_split'] = (string) $request->get_param( 'window_split' );
		}
		if ( null !== $request->get_param( 'snippets' ) ) {
			$payload['snippets'] = (array) $request->get_param( 'snippets' );
		}

		return rest_ensure_response( $this->settings->save( get_current_user_id(), $payload ) );
	}
}
```

- [ ] **Step 4: Activate the container registration**

In `includes/Container/Providers/RestRouteProvider.php`, add the imports:

```php
use DebugSuite\API\ConsoleController;
use DebugSuite\Services\Console\ConsoleService;
use DebugSuite\Services\Console\ConsoleSettingsService;
```

and add the `ConsoleController` entry to the `$provides` map:

```php
		ConsoleController::class  => [ ConsoleService::class, ConsoleSettingsService::class ],
```

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/phpunit --testsuite=integration --filter=ConsoleControllerTest`
Expected: PASS (all four tests).

- [ ] **Step 6: Run the full PHP suite + PHPCS**

Run: `vendor/bin/phpunit --testsuite=all`
Expected: PASS.
Run: `composer phpcs:check`
Expected: no errors in the new files.

- [ ] **Step 7: Commit**

```bash
git add includes/API/ConsoleController.php includes/Container/Providers/RestRouteProvider.php tests/Integration/API/ConsoleControllerTest.php
git commit -m "feat: add ConsoleController REST endpoints for execute and settings"
```

---

## Task 8: Frontend types + constants + API hook

**Files:**
- Create: `src/pages/query-console/types.ts`
- Create: `src/pages/query-console/constants.ts`
- Create: `src/pages/query-console/hooks/use-console-api.ts`

**Interfaces:**
- Produces:
  - `types.ts`: `ExecuteResult { output: string; dump: string; execution_time: string }`; `ConsoleError { message: string; trace?: string; input?: string }`; `Snippet { id: string; title: string; code: string }`; `SplitOrientation = 'horizontal' | 'vertical'`; `ConsoleSettings { window_split: SplitOrientation; snippets: Snippet[] }`; `HistoryEntry { id: string; code: string; ranAt: number }`.
  - `constants.ts`: `HISTORY_KEY`, `DEFAULT_CODE`, `MAX_HISTORY`.
  - `use-console-api.ts`: `useConsoleApi()` → `{ execute(input: string): Promise<ExecuteResult>; getSettings(): Promise<ConsoleSettings>; saveSettings(patch: Partial<ConsoleSettings>): Promise<ConsoleSettings> }`.

- [ ] **Step 1: Create types.ts**

```ts
export type SplitOrientation = 'horizontal' | 'vertical';

export interface ExecuteResult {
    output: string;
    dump: string;
    execution_time: string;
}

export interface ConsoleError {
    message: string;
    trace?: string;
    input?: string;
}

export interface Snippet {
    id: string;
    title: string;
    code: string;
}

export interface ConsoleSettings {
    window_split: SplitOrientation;
    snippets: Snippet[];
}

export interface HistoryEntry {
    id: string;
    code: string;
    ranAt: number;
}
```

- [ ] **Step 2: Create constants.ts**

```ts
export const HISTORY_KEY = 'debug-suite-console-history';
export const MAX_HISTORY = 50;
export const DEFAULT_CODE = "// Run PHP in the WordPress runtime.\n// Example:\nreturn get_bloginfo( 'version' );\n";
```

- [ ] **Step 3: Create the API hook**

```ts
import apiFetch from '@wordpress/api-fetch';
import { useCallback } from '@wordpress/element';
import type { ConsoleSettings, ExecuteResult } from '../types';

interface UseConsoleApi {
    execute: (input: string) => Promise<ExecuteResult>;
    getSettings: () => Promise<ConsoleSettings>;
    saveSettings: (patch: Partial<ConsoleSettings>) => Promise<ConsoleSettings>;
}

const useConsoleApi = (): UseConsoleApi => {
    const execute = useCallback(async (input: string) => {
        return await apiFetch<ExecuteResult>({
            path: '/debug-suite/v1/console/execute',
            method: 'POST',
            data: { input }
        });
    }, []);

    const getSettings = useCallback(async () => {
        return await apiFetch<ConsoleSettings>({
            path: '/debug-suite/v1/console/settings'
        });
    }, []);

    const saveSettings = useCallback(async (patch: Partial<ConsoleSettings>) => {
        return await apiFetch<ConsoleSettings>({
            path: '/debug-suite/v1/console/settings',
            method: 'POST',
            data: patch
        });
    }, []);

    return { execute, getSettings, saveSettings };
};

export default useConsoleApi;
```

- [ ] **Step 4: Type-check**

Run: `pnpm type-check`
Expected: no errors.

- [ ] **Step 5: Commit**

```bash
git add src/pages/query-console/types.ts src/pages/query-console/constants.ts src/pages/query-console/hooks/use-console-api.ts
git commit -m "feat: add console frontend types, constants, and API hook"
```

---

## Task 9: Run history hook

**Files:**
- Create: `src/pages/query-console/hooks/use-console-history.ts`

**Interfaces:**
- Consumes: `HistoryEntry` (Task 8), `HISTORY_KEY`, `MAX_HISTORY` (Task 8).
- Produces: `useConsoleHistory()` → `{ history: HistoryEntry[]; push(code: string): void; clear(): void }`. `push` prepends, dedupes identical consecutive code, caps at `MAX_HISTORY`, persists to `localStorage`.

- [ ] **Step 1: Create the hook**

```ts
import { useCallback, useState } from '@wordpress/element';
import { HISTORY_KEY, MAX_HISTORY } from '../constants';
import type { HistoryEntry } from '../types';

const read = (): HistoryEntry[] => {
    try {
        const raw = localStorage.getItem(HISTORY_KEY);
        return raw ? (JSON.parse(raw) as HistoryEntry[]) : [];
    } catch {
        return [];
    }
};

const useConsoleHistory = () => {
    const [history, setHistory] = useState<HistoryEntry[]>(read);

    const persist = useCallback((entries: HistoryEntry[]) => {
        setHistory(entries);
        localStorage.setItem(HISTORY_KEY, JSON.stringify(entries));
    }, []);

    const push = useCallback(
        (code: string) => {
            const trimmed = code.trim();
            if (!trimmed) return;
            setHistory((prev) => {
                if (prev[0]?.code === trimmed) return prev;
                const entry: HistoryEntry = {
                    id: `${prev.length}-${trimmed.length}-${trimmed.slice(0, 8)}`,
                    code: trimmed,
                    ranAt: Date.now()
                };
                const next = [entry, ...prev].slice(0, MAX_HISTORY);
                localStorage.setItem(HISTORY_KEY, JSON.stringify(next));
                return next;
            });
        },
        []
    );

    const clear = useCallback(() => persist([]), [persist]);

    return { history, push, clear };
};

export default useConsoleHistory;
```

- [ ] **Step 2: Type-check**

Run: `pnpm type-check`
Expected: no errors.

- [ ] **Step 3: Commit**

```bash
git add src/pages/query-console/hooks/use-console-history.ts
git commit -m "feat: add localStorage-backed console run history hook"
```

---

## Task 10: Output pane

**Files:**
- Create: `src/pages/query-console/components/output-pane.tsx`

**Interfaces:**
- Consumes: `ExecuteResult`, `ConsoleError` (Task 8).
- Produces: `OutputPane` component. Props: `{ result: ExecuteResult | null; error: ConsoleError | null; loading: boolean }`. Renders `output` as escaped preformatted text, `dump` HTML via `dangerouslySetInnerHTML`, execution time, and errors (message + collapsible trace).

- [ ] **Step 1: Create the component**

```tsx
import { classNames } from '@/utils';
import { __ } from '@wordpress/i18n';
import type { ConsoleError, ExecuteResult } from '../types';

interface OutputPaneProps {
    result: ExecuteResult | null;
    error: ConsoleError | null;
    loading: boolean;
}

const OutputPane = ({ result, error, loading }: OutputPaneProps) => {
    return (
        <div className="bg-background flex h-full min-h-0 flex-col overflow-y-auto p-3 font-mono text-sm">
            {loading && <div className="text-muted-foreground">{__('Running…', 'debug-suite')}</div>}

            {!loading && error && (
                <div className="text-red-600">
                    <div className="font-semibold">{error.message}</div>
                    {error.trace && (
                        <details className="mt-2">
                            <summary className="cursor-pointer select-none">
                                {__('Stack trace', 'debug-suite')}
                            </summary>
                            <pre className="mt-1 whitespace-pre-wrap text-xs opacity-80">{error.trace}</pre>
                        </details>
                    )}
                </div>
            )}

            {!loading && !error && result && (
                <div className="flex flex-col gap-3">
                    {result.output && <pre className="whitespace-pre-wrap">{result.output}</pre>}
                    {result.dump && (
                        <div
                            className={classNames('debug-suite-dump')}
                            // eslint-disable-next-line react/no-danger -- dump HTML comes from our controlled HtmlDumper (admin only).
                            dangerouslySetInnerHTML={{ __html: result.dump }}
                        />
                    )}
                    <div className="text-muted-foreground text-xs">
                        {__('Executed in', 'debug-suite')} {result.execution_time}s
                    </div>
                </div>
            )}

            {!loading && !error && !result && (
                <div className="text-muted-foreground">{__('Output will appear here.', 'debug-suite')}</div>
            )}
        </div>
    );
};

export default OutputPane;
```

- [ ] **Step 2: Type-check + lint**

Run: `pnpm type-check && pnpm lint`
Expected: no errors.

- [ ] **Step 3: Commit**

```bash
git add src/pages/query-console/components/output-pane.tsx
git commit -m "feat: add console output pane"
```

---

## Task 11: Split layout

**Files:**
- Create: `src/pages/query-console/components/split-layout.tsx`

**Interfaces:**
- Consumes: `SplitOrientation` (Task 8).
- Produces: `SplitLayout` component. Props: `{ orientation: SplitOrientation; first: React.ReactNode; second: React.ReactNode; className?: string }`. Renders two equal panes side-by-side (`vertical`) or stacked (`horizontal`) using flex; no drag-resize in the MVP (equal 50/50 with a divider border).

- [ ] **Step 1: Create the component**

```tsx
import { classNames } from '@/utils';
import type { ReactNode } from 'react';
import type { SplitOrientation } from '../types';

interface SplitLayoutProps {
    orientation: SplitOrientation;
    first: ReactNode;
    second: ReactNode;
    className?: string;
}

const SplitLayout = ({ orientation, first, second, className }: SplitLayoutProps) => {
    const isVertical = orientation === 'vertical';
    return (
        <div
            className={classNames(
                'flex min-h-0 flex-1',
                isVertical ? 'flex-row' : 'flex-col',
                className
            )}>
            <div className={classNames('min-h-0 min-w-0 flex-1', isVertical ? 'border-r' : 'border-b', 'border-border')}>
                {first}
            </div>
            <div className="min-h-0 min-w-0 flex-1">{second}</div>
        </div>
    );
};

export default SplitLayout;
```

- [ ] **Step 2: Type-check + lint**

Run: `pnpm type-check && pnpm lint`
Expected: no errors.

- [ ] **Step 3: Commit**

```bash
git add src/pages/query-console/components/split-layout.tsx
git commit -m "feat: add console split layout component"
```

---

## Task 12: Snippets menu

**Files:**
- Create: `src/pages/query-console/components/snippets-menu.tsx`

**Interfaces:**
- Consumes: `Snippet` (Task 8), and the UI `Button` from `@/components/ui`.
- Produces: `SnippetsMenu` component. Props: `{ snippets: Snippet[]; onInsert(code: string): void; onSave(title: string): void; onDelete(id: string): void }`. Renders a list of saved snippets (click → insert), a "Save current" control (prompts for a title via a controlled input), and per-row delete.

- [ ] **Step 1: Create the component**

```tsx
import { Button } from '@/components/ui';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Plus, Trash2 } from 'lucide-react';
import type { Snippet } from '../types';

interface SnippetsMenuProps {
    snippets: Snippet[];
    onInsert: (code: string) => void;
    onSave: (title: string) => void;
    onDelete: (id: string) => void;
}

const SnippetsMenu = ({ snippets, onInsert, onSave, onDelete }: SnippetsMenuProps) => {
    const [title, setTitle] = useState('');

    const handleSave = () => {
        const trimmed = title.trim();
        if (!trimmed) return;
        onSave(trimmed);
        setTitle('');
    };

    return (
        <div className="flex w-64 flex-col gap-2 p-2">
            <div className="flex items-center gap-1">
                <input
                    value={title}
                    onChange={(e) => setTitle(e.target.value)}
                    placeholder={__('Snippet title…', 'debug-suite')}
                    className="border-border w-full rounded border px-2 py-1 text-sm"
                />
                <Button size="icon-sm" variant="secondary" onClick={handleSave} title={__('Save current code', 'debug-suite')}>
                    <Plus size={16} />
                </Button>
            </div>

            <ul className="flex flex-col gap-1">
                {snippets.length === 0 && (
                    <li className="text-muted-foreground text-xs">{__('No snippets saved.', 'debug-suite')}</li>
                )}
                {snippets.map((snippet) => (
                    <li key={snippet.id} className="flex items-center justify-between gap-1">
                        <button
                            type="button"
                            className="hover:bg-secondary flex-1 truncate rounded px-2 py-1 text-left text-sm"
                            onClick={() => onInsert(snippet.code)}>
                            {snippet.title}
                        </button>
                        <Button
                            size="icon-sm"
                            variant="ghost"
                            onClick={() => onDelete(snippet.id)}
                            title={__('Delete snippet', 'debug-suite')}>
                            <Trash2 size={14} />
                        </Button>
                    </li>
                ))}
            </ul>
        </div>
    );
};

export default SnippetsMenu;
```

Note: verify `Button` and the `icon-sm` size + `Plus`/`Trash2` icons are exported the same way as in `src/console.tsx` (they are: `Button` from `@/components/ui`, lucide-react icons). If `icon-sm` is not a valid size in the `Button` variants, use `size="sm"`.

- [ ] **Step 2: Type-check + lint**

Run: `pnpm type-check && pnpm lint`
Expected: no errors.

- [ ] **Step 3: Commit**

```bash
git add src/pages/query-console/components/snippets-menu.tsx
git commit -m "feat: add console snippets menu"
```

---

## Task 13: Assemble the QueryConsole page

**Files:**
- Modify: `src/pages/query-console/index.tsx` (replace stub)

**Interfaces:**
- Consumes: `Editor` (`@/components/editor`), `Fill` (`@wordpress/components`), `Button` (`@/components/ui`), `useConsoleApi` (Task 8), `useConsoleHistory` (Task 9), `OutputPane` (Task 10), `SplitLayout` (Task 11), `SnippetsMenu` (Task 12), types + constants (Task 8).
- Produces: default-exported `QueryConsole` component that renders the full console, filling the `console-logs-actions` Slot with a Run button + split toggle.

- [ ] **Step 1: Replace the stub**

Overwrite `src/pages/query-console/index.tsx`:

```tsx
import Editor from '@/components/editor';
import { Button } from '@/components/ui';
import { classNames } from '@/utils';
import { Fill } from '@wordpress/components';
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Columns2, Play, Rows2 } from 'lucide-react';
import OutputPane from './components/output-pane';
import SnippetsMenu from './components/snippets-menu';
import SplitLayout from './components/split-layout';
import { DEFAULT_CODE } from './constants';
import useConsoleApi from './hooks/use-console-api';
import useConsoleHistory from './hooks/use-console-history';
import type { ConsoleError, ConsoleSettings, ExecuteResult, SplitOrientation } from './types';

const QueryConsole = ({ className }: { className?: string }) => {
    const { execute, getSettings, saveSettings } = useConsoleApi();
    const { push } = useConsoleHistory();

    const [code, setCode] = useState(DEFAULT_CODE);
    const [result, setResult] = useState<ExecuteResult | null>(null);
    const [error, setError] = useState<ConsoleError | null>(null);
    const [loading, setLoading] = useState(false);
    const [settings, setSettings] = useState<ConsoleSettings>({ window_split: 'vertical', snippets: [] });

    const codeRef = useRef(code);
    codeRef.current = code;

    useEffect(() => {
        getSettings()
            .then(setSettings)
            .catch(() => undefined);
    }, [getSettings]);

    const run = useCallback(async () => {
        const input = codeRef.current;
        if (!input.trim()) return;
        setLoading(true);
        setError(null);
        try {
            const res = await execute(input);
            setResult(res);
            push(input);
        } catch (e) {
            const err = e as { message?: string; data?: { trace?: string; input?: string } };
            setResult(null);
            setError({ message: err.message ?? 'Error', trace: err.data?.trace, input: err.data?.input });
        } finally {
            setLoading(false);
        }
    }, [execute, push]);

    const persistSettings = useCallback(
        (patch: Partial<ConsoleSettings>) => {
            setSettings((prev) => {
                const next = { ...prev, ...patch };
                saveSettings(patch).catch(() => undefined);
                return next;
            });
        },
        [saveSettings]
    );

    const toggleSplit = useCallback(() => {
        const next: SplitOrientation = settings.window_split === 'vertical' ? 'horizontal' : 'vertical';
        persistSettings({ window_split: next });
    }, [settings.window_split, persistSettings]);

    const insertSnippet = useCallback((snippetCode: string) => setCode(snippetCode), []);

    const saveSnippet = useCallback(
        (title: string) => {
            const snippet = { id: `${Date.now()}`, title, code: codeRef.current };
            persistSettings({ snippets: [...settings.snippets, snippet] });
        },
        [settings.snippets, persistSettings]
    );

    const deleteSnippet = useCallback(
        (id: string) => persistSettings({ snippets: settings.snippets.filter((s) => s.id !== id) }),
        [settings.snippets, persistSettings]
    );

    // Ctrl/Cmd+Enter to run.
    useEffect(() => {
        const handler = (e: KeyboardEvent) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
                e.preventDefault();
                run();
            }
        };
        window.addEventListener('keydown', handler);
        return () => window.removeEventListener('keydown', handler);
    }, [run]);

    return (
        <div className={classNames('flex h-full min-h-0 flex-col', className)}>
            <Fill name="console-logs-actions">
                <Button size="sm" variant="ghost" onClick={toggleSplit} title={__('Toggle split', 'debug-suite')}>
                    {settings.window_split === 'vertical' ? <Rows2 size={18} /> : <Columns2 size={18} />}
                </Button>
                <Button size="sm" variant="secondary" onClick={run} disabled={loading}>
                    <Play size={16} />
                    <span>{__('Run', 'debug-suite')}</span>
                </Button>
            </Fill>

            <SplitLayout
                orientation={settings.window_split}
                first={
                    <div className="flex h-full min-h-0">
                        <SnippetsMenu
                            snippets={settings.snippets}
                            onInsert={insertSnippet}
                            onSave={saveSnippet}
                            onDelete={deleteSnippet}
                        />
                        <div className="min-w-0 flex-1">
                            <Editor
                                value={code}
                                filename="console.php"
                                height="100%"
                                onChange={(value) => setCode(value ?? '')}
                            />
                        </div>
                    </div>
                }
                second={<OutputPane result={result} error={error} loading={loading} />}
            />
        </div>
    );
};

export default QueryConsole;
```

Note: `Editor`'s `onChange` signature is `(value, error) => void` (see `src/components/editor/index.tsx`); passing a one-arg arrow is fine. Confirm `filename="console.php"` maps to the `php` language via `src/components/editor/languages.ts`; if `.php` is not registered there, add a `php` case following the existing pattern.

- [ ] **Step 2: Type-check + lint**

Run: `pnpm type-check && pnpm lint`
Expected: no errors. Fix any icon/name mismatches surfaced (e.g. `Rows2`/`Columns2` exist in the installed lucide-react version; if not, use `SplitSquareHorizontal`/`SplitSquareVertical`).

- [ ] **Step 3: Build**

Run: `pnpm build`
Expected: builds `assets/js/debug-console.js` with no errors.

- [ ] **Step 4: Commit**

```bash
git add src/pages/query-console/index.tsx src/components/editor/languages.ts
git commit -m "feat: assemble QueryConsole PHP REPL page"
```

---

## Task 14: Full verification pass

**Files:** none (verification only).

- [ ] **Step 1: Full PHP suite**

Run: `vendor/bin/phpunit --testsuite=all`
Expected: PASS.

- [ ] **Step 2: PHP static + standards**

Run: `composer phpcs:check && composer phpstan`
Expected: no errors in new files. Fix any reported issues and re-run.

- [ ] **Step 3: Frontend gates**

Run: `pnpm type-check && pnpm lint && pnpm build`
Expected: all pass.

- [ ] **Step 4: Manual smoke (wp-env)**

Run: `pnpm env:start` (if not already running). In the browser admin bar, open **Debug → Console**, type `return get_bloginfo( 'version' );`, press Ctrl/Cmd+Enter. Confirm the WP version renders in the output pane. Then `dump( wp_get_current_user() );` and confirm the dump renders. Then trigger an error (`throw new Exception('x');`) and confirm the red error + trace appears. Toggle split; save and re-insert a snippet.

- [ ] **Step 5: Final commit (if any fixes were made)**

```bash
git add -A
git commit -m "test: verify QueryConsole end-to-end"
```

---

## Self-Review Notes

- **Spec coverage:** Engine (Tasks 1–4) · bundling via composer+mozart (Task 1) · REPL execute (Tasks 4, 7, 13) · history (Task 9) · snippets (Tasks 5, 12, 13) · split (Tasks 5, 11, 13) · server-persisted settings (Tasks 5, 7) · rendering & errors (Task 10) · security via `permissions_check` (Task 7) · testing (Tasks 2–7) · risk gate (Task 1). All spec sections map to a task.
- **Type consistency:** `ExecuteResult`/`ConsoleSettings`/`Snippet`/`SplitOrientation`/`HistoryEntry` defined once in Task 8 and consumed unchanged in Tasks 9–13. Backend `execute()` result shape `{ output, dump, execution_time }` is identical in Tasks 4, 7, and the `ExecuteResult` type. Dump global name `debug_suite_console_dump` and user meta key `debug_suite_console` match the Global Constraints.
- **Fallbacks are guidance, not placeholders:** the mozart→strauss fallback (Task 1) and the `dump()` shim (Task 4) are conditional, test-gated instructions with concrete code, applied only when a named test fails.
