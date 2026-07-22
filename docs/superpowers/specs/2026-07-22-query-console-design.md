# QueryConsole — Design

**Date:** 2026-07-22
**Status:** Approved
**Component:** Debug Suite → Console panel (PHP REPL)

## Purpose

Run arbitrary PHP inside the WordPress runtime from an in-browser console, in the
spirit of the `wp-console` plugin. Replaces the current `QueryConsole` stub
(`src/pages/query-console/index.tsx`) with a working PHP REPL mounted in the
existing admin-bar Console dialog (`src/console.tsx`).

## Decisions (locked)

- **Engine:** PsySH (`psy/psysh`) — same execution core as wp-console.
- **Bundling:** `composer require psy/psysh`, scoped via mozart into
  `DebugSuite\Packages\`.
- **Scope:** Full parity — REPL + run history + saved snippets +
  horizontal/vertical window split + server-persisted user settings.

### Deviations from wp-console

- **Snippets** = named saved scripts (`title` + `code`), NOT wp-console's
  VSCode-snippet-JSON format. Simpler and more directly useful.
- **History** = client-side `localStorage` (not server-synced). Snippets and
  window-split preference ARE server-synced via user meta.

## Execution model

Stateless per run, matching wp-console. A fresh `Psy\Shell` is built for each
REST request, the input is `eval()`'d in a controlled scope, and output is
captured. There is **no** cross-request PHP variable persistence — "history" is
saved editor input, not live scope.

## Architecture

### Backend — PsySH via mozart scope

`composer require psy/psysh`, then extend the mozart `packages` list so the
scoped tree lands under `DebugSuite\Packages\` (e.g. `DebugSuite\Packages\Psy\`,
`DebugSuite\Packages\Symfony\…`, `DebugSuite\Packages\PhpParser\`).

Rather than patching vendored files (wp-console's approach), the wp-console
overrides are reimplemented as **clean subclasses of the scoped classes**, living
in the plugin namespace:

| Class | Extends (scoped) | Responsibility |
|---|---|---|
| `Services\Console\CapturingShellOutput` | `…\Psy\Output\ShellOutput` | Override `doWrite()` to accumulate into a public `$outputMessage`; hold a public `$exception`. |
| `Services\Console\CapturingHtmlDumper` | `…\Symfony\Component\VarDumper\Dumper\HtmlDumper` | Override `echoLine()` to append into `$GLOBALS['debug_suite_console_dump']`; return `''` from `getDumpHeader()`. |

User `dump()` calls are routed to the capturing dumper at request time via
`VarDumper::setHandler()` — no need to override the Symfony `VarDumper` class.
ANSI color is disabled through `Psy\Configuration::COLOR_MODE_DISABLED`, removing
the need for a Symfony `Color` override.

### Evaluation flow (`ConsoleService::execute`)

Mirrors `wp-console`'s `RestController::create_item`:

1. Start timer.
2. Build `Psy\Configuration` (`configDir` = `WP_CONTENT_DIR`), set output to a
   `CapturingShellOutput`, color mode disabled.
3. `new Shell( $config )`, `setOutput`, `addCode( $input )`.
4. `extract( $psysh->getScopeVariablesDiff( get_defined_vars() ) )`.
5. `ob_start( [ $psysh, 'writeStdout' ], 1 )`, `set_error_handler( [ $psysh, 'handleError' ] )`.
6. `$_ = eval( $psysh->onExecute( $psysh->flushCode() ?: NOOP_INPUT ) )`.
7. `restore_error_handler()`, `setScopeVariables( get_defined_vars() )`,
   `writeReturnValue( $_ )`, `ob_end_flush()`.
8. If `$output->exception` → throw it.
9. Return `[ output, dump, execution_time ]`.
10. `catch ( Throwable )` → `WP_Error( 'debug_suite_console_error', message, [ status => 422, input, trace ] )`.

### Services / controllers

- `Services\Console\ConsoleService` — orchestrates the eval flow above; returns
  `[ 'output' => string, 'dump' => string, 'execution_time' => string ]` or a
  `WP_Error`.
- `Services\Console\ConsoleSettingsService` — get/save per-user console prefs in
  user meta key `debug_suite_console` = `{ window_split, snippets: [] }`.
- `API\ConsoleController` (namespace `debug-suite/v1`, base `console`):
  - `POST /console/execute` — arg `input` (required, non-empty string). Returns
    the result shape. A request header (e.g. `X-Debug-Suite-Console: 1`) marks
    the request so the dump handler activates.
  - `GET /console/settings` — return the current user's console settings.
  - `POST /console/settings` — persist `window_split` and/or `snippets`.
  - Permissions: inherit `RestController::permissions_check` (logged-in +
    `manage_options`).

### Container registration

- `AppServiceProvider::$services` gains `ConsoleService` and
  `ConsoleSettingsService`.
- `RestRouteProvider` registers `ConsoleController`. The provider's current
  `$provides` map is one-controller-to-one-dependency; extend it so a controller
  can receive **multiple** constructor args (`ConsoleController` needs both
  `ConsoleService` and `ConsoleSettingsService`).

### Frontend — `src/pages/query-console/`

Replace the stub. Reuse the existing Monaco `Editor` (`src/components/editor`,
`php` language) and fill the `console-logs-actions` Slot already present in
`src/console.tsx` for toolbar actions.

```
src/pages/query-console/
  index.tsx                    layout: split (editor | output) + toolbar
  types.ts
  constants.ts
  components/
    output-pane.tsx            renders output / dump / exec-time / error
    snippets-menu.tsx          list, save, insert snippets
    split-layout.tsx           horizontal/vertical split (driven by settings)
  hooks/
    use-console-api.ts         apiFetch: execute, getSettings, saveSettings
    use-console-history.ts     localStorage-backed run history
```

- **Run** = `Ctrl/Cmd+Enter`.
- Split orientation is read from and written to user settings.

### Data flow

`editor input → useConsoleApi.execute() → POST /console/execute { input }
→ ConsoleService (PsySH eval) → { output, dump, execution_time } → output-pane`.

On success, the run is pushed to `localStorage` history. Snippet saves and split
toggles persist to user meta via `POST /console/settings`.

### Rendering & error handling

- `output` (PsySH-rendered return value + captured stdout) → rendered as
  **preformatted text**; React auto-escaping keeps it XSS-safe.
- `dump` (from user `dump()` calls) → controlled `HtmlDumper` HTML rendered via
  `dangerouslySetInnerHTML`. Admin-only, and Symfony's `HtmlDumper` escapes
  scalar values.
- `WP_Error` 422 `{ message, trace, input }` → red error block with a collapsible
  stack trace. `apiFetch` rejects → caught in the hook → surfaced by `output-pane`.

## Security

Executing arbitrary PHP is the feature, not an accident. It is gated by the
existing `permissions_check` (logged-in **and** `manage_options`) and the REST
nonce injected by `@wordpress/api-fetch`. This matches wp-console's posture. No
additional sandboxing is attempted — a user with `manage_options` can already run
arbitrary code by other means.

## Testing

- **Unit** (`ConsoleService`): `1+1` → output `2`; `echo` capture; `dump()`
  capture into the dump field; a thrown exception → `WP_Error` carrying message +
  trace. **`ConsoleSettingsService`**: get returns defaults, save→get roundtrip.
- **Integration** (REST): `POST /console/execute` returns the expected shape;
  non-admin receives 403; `GET`/`POST /console/settings` roundtrip.

## Risk gate — mozart scoping (first plan step)

`coenjacobs/mozart` is archived and currently scopes only the small, clean
`league/container`. Scoping the full PsySH tree (`symfony/console`,
`symfony/var-dumper`, `nikic/php-parser`, …) is the highest-risk part of this
work: PsySH references some classes by string, which mozart's find/replace can
miss.

**The plan opens with a scope + smoke-test spike:** scope PsySH, then evaluate
`1+1` through the scoped `Shell` in a throwaway test. Only once that passes does
the rest of the backend get built.

**Fallback if mozart chokes:** switch the scoping tool to
`brianhenryie/strauss` or `humbug/php-scoper` (drop-in replacements that handle
string class references better), or commit a hand-vendored `lib/` with a
dedicated autoloader (wp-console's approach). This is a decision gate, not a
blocker.
