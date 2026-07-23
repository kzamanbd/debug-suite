# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Debug Suite — a WordPress developer-toolkit plugin (PHP 8.1+, WP 6.8+). PHP backend under `includes/` (PSR-4 `DebugSuite\`), React/TypeScript SPA under `src/`, built into `assets/js` + `assets/css` (both gitignored — always build before testing in a browser).

## Commands

```bash
# Setup
composer install          # also runs `mozart compose` (dev mode) → generates override/
pnpm install

# Frontend
pnpm dev                  # wp-scripts watch
pnpm build                # production build
pnpm lint / lint:fix      # eslint src
pnpm type-check           # tsc --noEmit
pnpm format               # prettier

# PHP quality
composer phpcs            # WordPress standard (phpcs.xml)
composer phpcs:fix        # phpcbf
composer phpstan          # level 5
composer qa               # phpcs:check + phpstan
composer security:check   # security sniffs only

# Tests (need a WP test env)
composer test                                  # unit suite (default)
composer test:unit / test:integration / test:coverage
vendor/bin/phpunit --filter=SomeTest           # single test class/method
vendor/bin/phpunit --group=api                 # by @group

# Tests via wp-env (what CI uses — no local MySQL needed)
pnpm env:start
pnpm env:test:unit / env:test:integration / env:test
pnpm env:stop

# Release
pnpm release              # build + make:pot + zip → debug-suite-v<version>.zip
pnpm release:org          # also runs bin/version.js first
```

Local WP test env without wp-env: `bin/install-wp-tests.sh <db> <user> <pass> [host] [wp-version]`, or set `WP_TESTS_DIR`.

## Architecture

### Bootstrap → container → hooks

`debug-suite.php` creates a global `$debug_suite_container` (a scoped League Container subclass), registers `Container\Providers\ServiceProvider`, then resolves **every service tagged with the `Hookable` interface** and calls `register_hooks()` on each. Nothing self-registers hooks in its constructor.

Provider tree:

- `ServiceProvider::boot()` — the root; adds `AppServiceProvider`, `RestRouteProvider`, and conditionally `EmailLogServiceProvider` when `debug_suite_is_feature_enabled('email-log')`.
- `AppServiceProvider` — business services, `Admin`, `Assets`, `PageManager`, `Upgrader`, `DatabaseManager`.
- `RestRouteProvider` — REST controllers; its `$provides` array maps controller class → its constructor dependency (or array of them), injected positionally.

`BaseServiceProvider::share_with_implements_tags()` registers a class as shared **and tags it with every interface it implements** — that tagging is what makes `container()->get(Hookable::class)` return all hookable services. A new service that needs hooks only has to implement `Hookable` and be listed in a provider's `$services`.

### Adding a feature

1. Service in `includes/Services/` (implement `Hookable` if it needs WP hooks) → add to `AppServiceProvider::$services`.
2. Controller extending `API\RestController` → add to `RestRouteProvider::$provides` with its service dependency.
3. Optional feature flag: add the id to `FeatureService::get_default_features()` (ids must match the frontend feature list in `src/pages/settings/index.tsx`), gate the provider in `ServiceProvider::boot()`.
4. Frontend: page under `src/pages/`, register a route via the `debugSuite.routes` JS filter (see `src/pages/email-log/index.tsx`).

Enabling a feature fires `debug_suite_{feature_id}_activated` — this is how `DatabaseManager` lazily creates the email-log table, i.e. tables are created on feature activation, not on plugin activation.

### REST API

Namespace `debug-suite/v1`. `RestController` implements `Hookable` and hooks `rest_api_init` → `register_routes()`, so controllers never register themselves manually. All endpoints use `permissions_check()` (logged in + `manage_options`).

Convention: controllers are thin HTTP adapters; services hold logic and return **either their data or a `WP_Error`** — the controller converts that to a response. Existing bases: `settings`, `logs`, `console`, `features`, `email-logs`.

### Admin pages

One WP menu item only: **Tools → Debug Suite** (`PageManager::add_admin_menu`), rendering `<div id="debug-suite-root-app">`; everything else is SPA routing (hash router). Additional "pages" are `Pageable` implementations (extend `AbstractPage`) that hook `debug_suite_admin_dashboard_pages` via their own `register_hooks()`, and contribute script/style handles that `PageManager::enqueue_scripts` enqueues on Debug Suite screens.

`Assets::get_localized_data()` builds the `window.debugSuite` object (log files, settings, versions, URLs), filterable via `debug_suite_localized_data`.

The admin-bar "Debug" node (`Admin::add_admin_bar_menu`) mounts a **separate** React app (`debug-console` entry) into `#wp-admin-bar-debug-suite` — the Query Console + Debug Log overlay, available on every admin screen.

### Models

`Models\BaseModel` is an Eloquent-ish ORM over `$wpdb`; `Models\QueryBuilder` is the single query engine. Terminal finders (`find`, `all`, `count`, `create`, `destroy`, `truncate`, …) are `protected` and reached through the `__call`/`__callStatic` proxy; any other call falls through to a fresh `QueryBuilder`, so `EmailLog::where('status','success')->get()` works. Supports `$fillable`, `$casts`, `$hidden`, `$appends`, `get_{key}_attribute()` accessors / `set_{key}_attribute()` mutators, and dirty tracking. Tables are `{prefix}debug_suite_{$table}`.

### Scoped dependencies (mozart)

`league/container` and `psy/psysh` are prefixed into `DebugSuite\Packages\` and written to `override/`. **`override/` is generated build output and gitignored** — commit only `composer.json`/`composer.lock`. Import scoped classes as `DebugSuite\Packages\Psy\Shell`, etc.

`includes/console-functions.php` exists solely to break a chicken-and-egg problem: it lazily requires `override/Psy/functions.php` only if present, because that file does not exist during the first `composer install` (mozart runs in `post-install-cmd`).

### Query Console

`Services\Console\ConsoleService::execute()` evaluates PHP inside the live WP runtime via a scoped PsySH `Shell` + `eval`, capturing stdout, `dump()` output, and timing. It installs an error handler and output buffer and restores both in `finally` — preserve that discipline when touching it. Errors come back as `WP_Error` with status 422.

## Frontend notes

- Three webpack entries (`webpack.config.js`): `debug-suite` (`src/index.tsx`, admin SPA), `debug-console` (`src/console.tsx`, admin-bar overlay), `email-log` (feature bundle). Output goes to `assets/js/[name].js`, CSS to `assets/css/`, with RTL variants auto-generated.
- `react`/`react-dom` are webpack externals (WP-provided globals); import from `@wordpress/element` in app code.
- `@/` aliases `src/`. Tailwind v4 via postcss; UI primitives in `src/components/ui/` (Base UI + CVA + `cn()` from `src/lib/utils.ts`).
- Data fetching is `@wordpress/api-fetch` with `path: '/debug-suite/v1/...'`.
- Extensibility mirrors PHP: `@wordpress/hooks` filters (`debugSuite.routes`) and `@wordpress/components` Slot/Fill (`console-logs-actions`).

## Conventions

- PHP follows WordPress Coding Standards (tabs, snake_case methods, Yoda off). Everything global must be prefixed `debug_suite` / `DebugSuite` / `DEBUG_SUITE` (enforced by phpcs). Text domain `debug-suite`.
- `phpcs.xml` excludes `src/`, `override/`, `tests/`, `vendor/` — run eslint/tsc for the frontend instead.
- New public APIs use `@since PLUGIN_SINCE`; `bin/version.js` (run by `pnpm release:org`) replaces the placeholder with the `package.json` version.
- Bumping a release means updating the version in `debug-suite.php` (header + `$version` property) and `package.json`.
- Tests: unit tests extend `Tests\Helpers\TestCase` (no WP), integration tests extend `Tests\Helpers\DebugSuiteTestCase` (full WP + REST). Files must end in `Test.php`. `phpunit.xml` is strict — output during tests, risky tests, and warnings all fail the run.
- Further docs in `docs/` (`TESTING.md`, `REST_API_ARCHITECTURE.md`, `HOOKABLE_INTERFACE.md`), and some endpoint lists there are stale — trust the code in `includes/API/`.
