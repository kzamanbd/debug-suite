=== Debug Suite ===
Contributors: kzamanbd
Donate link: https://kzaman.me/plugins/debug-suite/
Tags: debug, development, debugging, error-log, developer-tools
Requires at least: 6.8
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 2.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A powerful, enterprise-grade debugging toolkit for WordPress developers with advanced log management, error tracking, and development tools.

== Description ==

Debug Suite is a comprehensive WordPress development toolkit that transforms how developers debug and maintain WordPress sites. Built with enterprise-grade architecture and modern development practices, it provides powerful debugging capabilities while maintaining high performance and code quality.

= 🚀 Key Features =

* **Smart Error Log Viewer**
  * Read your WordPress error logs without ever leaving the dashboard or using FTP.
  * Say goodbye to messy text files—view errors in a clean, organized, and searchable layout.
  * Filter by severity (warnings, notices, errors) so you can fix what matters most first.

* **Outgoing Email Tracker**
  * Never wonder if a user received a receipt or notification again.
  * Keep a complete history of every email sent by your WordPress site.
  * Quickly view recipients, subject lines, contents, and delivery status at a glance.

* **API Traffic Monitor**
  * Keep an eye on the apps, plugins, and external services connecting to your site.
  * Easily view incoming requests, spot slow responses, and catch connection errors.
  * Search and filter through your site's API traffic to troubleshoot integrations fast.

* **Interactive API Documentation**
  * Explore your site's API endpoints through a beautiful, easy-to-read visual interface.
  * Automatically generates organized documentation for all your different API routes.
  * Perfect for helping developers and external services understand how to connect to your site.

* **Lightning-Fast & Customizable**
  * Only use what you need! Turn specific tools on or off with a single click.
  * Enjoy a smooth, modern experience that never slows down your admin area.
  * Designed to be lightweight, secure, and completely impact-free on your live website's performance.

= 🎯 Perfect for =

* WordPress developers building complex applications
* Agency developers managing multiple sites
* DevOps teams monitoring WordPress installations
* Site administrators debugging production issues

= 🌟 Pro Features (Coming Soon) =

* Remote debugging capabilities
* Advanced performance profiling
* Team collaboration tools
* Custom logging integrations

== Installation ==

1. Upload the `debug-suite` folder to the `/wp-content/plugins/` directory
2. Run `composer install` in the plugin directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to Debug Suite in the admin menu
5. Configure your logging preferences and access controls

For manual installation:

1. Download the plugin ZIP file
2. Go to Plugins > Add New in WordPress
3. Click the Upload Plugin button
4. Upload the ZIP and activate

== Frequently Asked Questions ==

= Is this plugin safe for production use? =

Yes, when configured properly. Debug Suite includes safety measures and role-based access control. However, we recommend initially testing in a staging environment.

= Will this plugin slow down my site? =

No. Debug Suite is built with performance in mind:
* Lazy loading of components
* Efficient log processing
* Minimal impact when not actively debugging
* Configurable performance settings

= How do I configure logging levels? =

1. Go to Debug Suite > Settings
2. Select your preferred log levels
3. Configure file locations and rotation settings
4. Save your preferences

= Can I use this with other debugging plugins? =

Yes! Debug Suite is designed to work alongside other development tools like Query Monitor and Debug Bar.

= What makes this different from other debugging plugins? =

* Enterprise-grade architecture
* Modern tech stack (React/TypeScript)
* Advanced service layer pattern
* Comprehensive debugging toolkit
* Performance-focused design

== Screenshots ==

1. Main Dashboard - Overview of debugging tools and recent logs
2. Log Viewer - Real-time log monitoring with filtering
3. Settings Panel - Comprehensive configuration options
4. Error Tracking - Detailed error reports and analysis

== Changelog ==

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