=== Debug Suite ===
Contributors: kzamanbd
Donate link: https://kzaman.me/plugins/debug-suite/
Tags: debug, debug-log, error-log, developer-tools, php-console
Requires at least: 6.8
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 2.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Debug WordPress from the admin bar: a live PHP console, a searchable debug log viewer, email logging and generated REST API docs.

== Description ==

Debug Suite is a developer toolkit that puts the tools you normally reach for over SSH right inside wp-admin: run PHP against the live site in a browser console, read the debug log without touching FTP, watch outgoing mail, and browse auto-generated docs for every REST endpoint your site exposes. Everything is one click away from the admin bar, on any admin screen.

= 🚀 Key Features =

* **Query Console — a PHP REPL in your browser**
  * Run PHP against your live WordPress runtime, right from the admin bar. No SSH, no WP-CLI, no scratch plugin files.
  * Monaco editor with syntax highlighting, plus a split output pane that captures return values, `dump()` output and execution time.
  * Save frequently used snippets and re-run them in one click; recall what you ran before from the local history.

* **Debug Console wherever the admin bar is**
  * A "Debug" item in the admin bar opens a full-screen overlay with the Query Console and the Debug Log as two tabs — in wp-admin and on the front end alike.
  * Stay on the page you are debugging — no navigating away, and the overlay remembers whether you left it open.

* **Smart Debug Log Viewer**
  * Read your WordPress debug logs without ever leaving the dashboard or using FTP.
  * Switch between the raw file and a parsed view; filter by severity (warnings, notices, errors, fatals) and search.
  * Auto-discovers the log files it can find, loads long logs incrementally, and clears them in one click.

* **Debug Constants Management**
  * Toggle `WP_DEBUG` and `WP_DEBUG_LOG` from Settings — Debug Suite writes them straight into `wp-config.php` for you.
  * No FTP, no code editor, and no guessing which debug constants are currently active on the site.

* **Outgoing Email Log** (optional)
  * Never wonder if a user received a receipt or notification again.
  * Keep a complete history of every email sent by your WordPress site.
  * Quickly view recipients, subject lines, contents, and delivery status at a glance.

* **Interactive API Documentation** (on by default, switchable in Settings)
  * Explore your site's REST API endpoints at `/debug-suite/api/docs` through a beautiful, easy-to-read visual interface.
  * Automatically generates OpenAPI documentation for every registered REST namespace, with bearer-token auth support.
  * Perfect for helping developers and external services understand how to connect to your site.

* **Lightning-Fast & Customizable**
  * Only use what you need! Turn optional tools on or off with a single click in Settings.
  * Optional features create their database tables only when you activate them — nothing is provisioned up front.
  * Modern React/TypeScript interface, loaded only on Debug Suite screens, so your admin stays fast.

* **Built to extend**
  * PHP: service container, `Hookable` services and a `debug_suite_localized_data` filter for third-party integrations.
  * JavaScript: register your own admin pages through the `debugSuite.routes` filter and inject UI via Slot/Fill.

= 🎯 Perfect for =

* WordPress developers building complex applications
* Agency developers managing multiple sites
* DevOps teams monitoring WordPress installations
* Site administrators debugging production issues

= 🌟 On the Roadmap =

* System Info — server, PHP and WordPress environment details
* Query Monitor — database query analysis and slow-query detection
* Cron Manager — inspect and control scheduled events
* Options Viewer — browse and edit the options table
* File Manager — browse the WordPress filesystem from the dashboard
* Asset Manager — audit enqueued scripts and styles

== Installation ==

1. Upload the `debug-suite` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Tools → Debug Suite
4. Turn on `WP_DEBUG` / `WP_DEBUG_LOG` and enable the optional features you need
5. Use the "Debug" item in the admin bar to open the Query Console and Debug Log from anywhere in wp-admin

For manual installation:

1. Download the plugin ZIP file
2. Go to Plugins > Add New in WordPress
3. Click the Upload Plugin button
4. Upload the ZIP and activate

If you install from the Git repository instead of a release ZIP, run `composer install` and `pnpm install && pnpm build` in the plugin directory first — the vendor libraries and the compiled JS/CSS are not committed.

== Frequently Asked Questions ==

= Where do I find the plugin after activating it? =

Under Tools → Debug Suite. Debug Suite deliberately does not add a top-level admin menu. The Query Console and Debug Log are also one click away from the "Debug" item in the admin bar, on every admin screen.

= Is the Query Console safe? =

The Query Console executes the PHP you type inside your live WordPress runtime, so treat it exactly like SSH access to the site. Every Debug Suite REST endpoint — the console included — requires a logged-in user with the `manage_options` capability, so only administrators can reach it. On a multi-site or shared install, only grant that capability to people you would trust with server access, and prefer a staging environment for experiments.

= Is this plugin safe for production use? =

Yes, when configured properly. Access is restricted to administrators, and Debug Suite only loads its interface on its own admin screens. Because the Query Console runs arbitrary PHP, we still recommend testing in staging first and deactivating the plugin when you are not actively debugging a production site.

= Will this plugin slow down my site? =

No. Logged-out visitors load nothing at all — the console bundle only loads where the admin bar is rendered, i.e. for logged-in users. The main admin app loads only on the Debug Suite screens, logs are read incrementally rather than all at once, and optional features do no work (and create no tables) until you enable them.

= How do I turn on WordPress debugging? =

1. Go to Tools → Debug Suite
2. Enable `WP_DEBUG`, plus `WP_DEBUG_LOG` to write errors to a log file
3. Save — Debug Suite updates `wp-config.php` for you (the file must be writable)
4. Read the resulting entries in the Debug Log tab

= Can I use this with other debugging plugins? =

Yes! Debug Suite is designed to work alongside other development tools like Query Monitor and Debug Bar.

= Can I extend it with my own tools? =

Yes. Register a page through the `debugSuite.routes` JavaScript filter, add UI into existing screens with `@wordpress/components` Slot/Fill, and hook the PHP side through the service container and the `debug_suite_localized_data` filter.

= What makes this different from other debugging plugins? =

* A real PHP REPL against the live site, not just read-only inspection
* Available from the admin bar on every screen, not a separate page you have to navigate to
* Modern tech stack (React/TypeScript) with a service-container PHP architecture
* Opt-in features, so you only run what you actually use

== Screenshots ==

1. Query Console - PHP REPL with Monaco editor, saved snippets and captured output
2. Debug Console - Console and Debug Log overlay opened from the admin bar
3. Debug Log Viewer - Parsed and raw log views with severity filtering and search
4. Settings - Debug constants and optional feature toggles
5. Email Log - History of every email sent by the site
6. API Documentation - Generated OpenAPI docs for the site's REST namespaces

== Changelog ==

= 2.0.1 =
* fix: the API Docs toggle in Settings now saves — the `api-docs` feature ID was missing from the backend feature list, so the switch silently reverted.
* fix: the API Docs routes now follow that toggle — disabling the feature removes the /debug-suite/api/docs and /schema rewrite rules, enabling it restores them without a manual permalink save.
* docs: rewrote the plugin description, installation steps and FAQ for the 2.0.0 feature set (Query Console, Debug Console overlay, debug constant management).

= 2.0.0 =
* feat: Query Console — an in-browser PHP REPL powered by a scoped PsySH, with Monaco editor, saved snippets, output/dump capture and execution timing.
* feat: Debug Console overlay available from the admin bar on every admin screen.
* refactor: BREAKING — removed the Overview dashboard and the Features admin page; Settings is now the default screen (feature flags still available as toggles in Settings).
* refactor: BREAKING — removed the API Log module and the `wp_debug_display` setting; debug settings keys renamed.
* refactor: BREAKING — Debug Suite now lives under Tools → Debug Suite instead of a top-level menu; the nested WP submenu and the `debug_suite_menu_items` filter were removed in favour of in-app navigation.
* refactor: BREAKING — `ServiceResponse` and the empty `ServiceInterface` marker removed; services now return their data or a native `WP_Error`.
* refactor: unified BaseModel on QueryBuilder as the single query engine; added `delete()` / `truncate()` and chainable static starters (e.g. `EmailLog::where('status','success')->get()`).
* refactor: modernized admin UI — consolidated components into a single `ui/` library, Base UI Select/Tabs, new Navigation and Input components.
* refactor: renamed Swagger to OpenApi across services, pages and templates; added bearer-token auth and better schema/request-body handling to the API docs.
* fix: OpenAPI docs URL no longer 404s — rewrite rules now self-heal on activation and version change instead of needing a manual permalink save.
* fix: Query Console syntax highlighting and editor height on the bare-PHP buffer.
* chore: CI now runs PHPUnit on wp-env; composer platform pinned to PHP 8.1.

= 1.1.3 =
* feat: added an interactive database upgrader using WP Admin Notices and jQuery AJAX.
* feat: implemented OOP-based `AbstractUpgrader` framework for repeatable database upgrades.
* fix: enhanced OpenAPI docs with improved schema title handling and dynamic API document rendering.
* fix: added fallback default logo rendering in the OpenAPI docs when none is configured.

= 1.1.2 =
* feat: add REST API logger/debugger feature
* refactor(container): restructure container and service provider classes
* refactor: improve code structure for readability and maintainability
* chore: update debug bar integration

= 1.1.1 =
* Fix hook name for email logs table creation in DatabaseManager
* Use plugin slug when creating email logs table

= 1.1.0 =
* Feature management: new Features page with REST API and service integration
* Page structure: new PageManager, AbstractPage, Pageable interfaces for admin pages
* Email Log: restructured module, new entry points, viewer, controls, and skeleton components
* Refactored Email Log module and dependency namespaces (moved from EmailLog subfolder to core)
* Renamed Module page to Modules and updated routes
* New Feature and Email Log admin pages with PageManager integration
* Debug Config moved to Settings (from Setup Guide) with updated settings structure
* Fix ESLint configuration and improve code formatting in SetupGuide component
* Bump WordPress compatibility (Requires at least: 6.8, Tested up to: 6.8)

= 1.0.0 =
* Initial release with core features
* Advanced log management system
* Real-time log monitoring
* File system management
* Modern React/TypeScript interface
* Enterprise-grade architecture

== Upgrade Notice ==

= 2.0.0 =
Major release. Adds the Query Console (PHP REPL) and the admin-bar Debug Console. Contains breaking changes: the plugin moved to Tools → Debug Suite, the Overview and Features pages were removed (feature toggles now live in Settings), and the API Log module, the `debug_suite_menu_items` filter and `ServiceResponse` were dropped. Review any custom integrations before updating.

= 1.1.3 =
Introduces robust interactive database upgrader and enhanced OpenAPI documentation support with fallback rendering. Recommended for all users.

= 1.1.2 =
Introduces REST API logger/debugger feature and improved container service registration. Recommended for all users.

= 1.1.1 =
Fixes email logs table creation (hook name and plugin slug). Recommended for all 1.1.0 users.

= 1.1.0 =
Feature management, new page architecture, and restructured Email Log module. Requires PHP 8.1 and WordPress 6.8 or higher.

= 1.0.0 =
Initial release of Debug Suite - Requires PHP 8.1 or higher.

== Development ==

* [GitHub Repository](https://github.com/kzamanbd/debug-suite)
* [Documentation](https://kzaman.me/plugins/debug-suite/docs)
* [Issue Tracker](https://github.com/kzamanbd/debug-suite/issues)

== Credits ==

* Built with modern WordPress development practices
* Uses React and TypeScript for the frontend
* Implements PSR standards and enterprise patterns
* Special thanks to the WordPress development community