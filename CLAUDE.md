# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Stack

WordPress plugin. PHP 8.1+ backend (PSR-4 `DebugSuite\` → `includes/`, `DebugSuite\Packages\` → `override/`). React 18 + TypeScript frontend bundled via `@wordpress/scripts` (webpack 5). Tailwind CSS v4 via `@tailwindcss/postcss`. Package manager: **pnpm** (workspace defined).

## Common commands

JS/TS:

```bash
pnpm dev               # webpack watch
pnpm build             # production bundle → assets/js/, assets/css/
pnpm type-check        # tsc --noEmit
pnpm lint              # eslint src
pnpm lint:fix
pnpm format            # prettier
pnpm release           # build + make:pot + zip (release artifact)
```

PHP (run via Composer scripts, defined in `composer.json`):

```bash
composer phpcs          # WPCS lint (config: phpcs.xml)
composer phpcs:fix      # phpcbf
composer phpstan        # phpstan analyse --level=5
composer test           # phpunit (default suite: unit)
composer test:unit
composer test:integration
composer test:coverage  # HTML coverage → tests/coverage
composer security:check # selected WordPress.Security sniffs
composer qa             # phpcs:check + phpstan
composer wp-org:check   # phpcs + security + phpstan (org compliance)
```

Run a single PHPUnit test:

```bash
vendor/bin/phpunit --filter MyTestMethod tests/Unit/Path/MyTest.php
```

`composer install` triggers `vendor/bin/mozart compose` in dev mode — required for the prefixed container dependency (see Mozart note below).

## Architecture

### Bootstrap

`debug-suite.php` instantiates `DebugSuite\Container\Container` (extends League\Container) globally as `$debug_suite_container`, registers `Container\Providers\ServiceProvider`, and the `DebugSuite` singleton wires hooks from any container service implementing `Interfaces\Hookable`.

### DI providers (registration tree)

`Providers\ServiceProvider::boot()` adds:

1. `AppServiceProvider` — core services (`Admin`, `Assets`, `HookManager`, `DatabaseManager`, `Upgrader`, `SettingsService`, `FeatureService`, `OverviewService`, `LogsService`, `PageManager`, `OpenApiPage`).
2. `RestRouteProvider` — REST controllers (`SettingsController`, `LogsController`, `OverviewController`, `FeatureController`) with constructor-injected service deps (mapping defined in `$provides` array on the provider).
3. **Feature-gated providers** — `EmailLogServiceProvider` and `ApiLogServiceProvider` are only registered when `debug_suite_is_feature_enabled('email-log')` / `'api-logger'` returns true. The `FeatureService::OPTION_NAME` option is the source of truth; the React Settings page toggles it via `POST /debug-suite/v1/features` and reloads the page so the new providers boot.

`BaseServiceProvider` exposes `share_with_implements_tags()` and `add_tags()` — use these to register a service so League\Container indexes it by every interface it implements (enables tag-based lookup like `getByTag('rest-controller')`).

### Hookable pattern

Any class implementing `Interfaces\Hookable` exposes `register_hooks(): void`. The bootstrap auto-invokes it after resolution — **never call `add_action`/`add_filter` from constructors**. REST controllers extend `API\RestController` which implements `Hookable` and registers routes on `rest_api_init`. All controllers share namespace `debug-suite/v1` and inherit `permissions_check` (requires `manage_options`).

### Pageable pattern

`Interfaces\Pageable` describes admin sub-pages (`get_id`, `menu`, `settings`, `scripts`, `styles`, `register`). `Pages\PageManager` collects them via the `debug_suite_admin_dashboard_pages` filter, registers admin menus, and emits a JS-side menu list via `debug_suite_menu_items` filter. The hash router on the React side picks up these paths.

The default menu items (`Overview`, `Settings`) are hard-coded in `PageManager::get_menu_items()` — additional `Pageable` services append themselves automatically through `register_pages_to_menu()`.

### Service vs Controller

Strict separation, enforced by REST architecture doc:
- Controllers (thin) extend `RestController`, handle HTTP concerns only.
- Services implement `Interfaces\ServiceInterface` (marker interface), hold business logic, return `Core\ServiceResponse`.
- Constructor-injection only — no service locators inside services.

### Mozart-prefixed dependencies

`league/container` is rewritten under namespace `DebugSuite\Packages\League\Container\…` in `override/` by Mozart (see `composer.json` `extra.mozart`). When importing the container in new code, use the prefixed namespace (`DebugSuite\Packages\League\Container\…`) — not `League\Container\…`. Re-run `vendor/bin/mozart compose` after bumping the package.

### React app

Entry points (`webpack.config.js`):

- `debug-suite` → `src/index.tsx` (main admin SPA)
- `debug-console` → `src/console.tsx`
- `email-log` → `src/pages/email-log/index.tsx`
- `api-logger` → `src/pages/api-log/index.tsx`

Output: `assets/js/[name].js` + `assets/css/[name].css`. RTL CSS auto-generated via custom webpack plugin (`-rtl.css` siblings). Path alias `@` → `src/`.

`App.tsx` mounts a `createHashRouter` inside `SlotFillProvider`. Routes live in `src/routing/routes.tsx`; consumers can extend via the `debugSuite.routes` JS filter (`@wordpress/hooks`). The layout exposes a `debug-suite-layout-header-right` `<Slot>` so pages can `<Fill>` toolbar content.

State lives in component-local `useState` + REST round-trips via `@wordpress/api-fetch` (no Redux). Window globals (`window.debugSuite`) carry server-localized config (debug constant values, logo, etc.) typed in `src/types/index.ts`.

### Database & install

`Core\Activator` / `Core\Deactivator` run via plugin (de)activation hooks. `Core\DatabaseManager` owns custom-table schema. `Core\Upgrader` handles version-to-version migrations on load.

## Conventions

- PHP coding style: WordPress Coding Standards via `phpcs.xml` (excludes `src/`, `assets/`, `vendor/`, `tests/`, `dependencies/`, `override/`).
- TS/JS: ESLint flat config (`eslint.config.mjs`) + Prettier with `prettier-plugin-tailwindcss`.
- i18n: text domain `debug-suite`. Regenerate POT with `pnpm make:pot` (requires `wp` CLI).
- Versioning: `pnpm version` runs `bin/version.js` to sync version across `debug-suite.php`, `package.json`, `composer.json`, `readme.txt`.

## Files worth reading first

- `debug-suite.php` — bootstrap order, constants
- `includes/Container/Providers/ServiceProvider.php` — provider tree + feature gating
- `includes/Container/Providers/AppServiceProvider.php` — core service registration
- `includes/Container/Providers/RestRouteProvider.php` — controller→service wiring
- `includes/Pages/PageManager.php` — admin menu + JS handoff
- `src/App.tsx` + `src/routing/routes.tsx` — frontend entry
- `docs/REST_API_ARCHITECTURE.md`, `docs/HOOKABLE_INTERFACE.md` — pattern deep-dives
