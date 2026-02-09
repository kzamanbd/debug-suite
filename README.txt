=== Debug Suite ===
Contributors: kzamanbd
Donate link: https://kzaman.me/plugins/debug-suite/
Tags: debug, development, debugging, error-log, developer-tools
Requires at least: 6.8
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 1.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A powerful, enterprise-grade debugging toolkit for WordPress developers with advanced log management, error tracking, and development tools.

== Description ==

Debug Suite is a comprehensive WordPress development toolkit that transforms how developers debug and maintain WordPress sites. Built with enterprise-grade architecture and modern development practices, it provides powerful debugging capabilities while maintaining high performance and code quality.

= 🚀 Key Features =

* **Advanced Log Management**
  * Real-time log file monitoring
  * Configurable log levels and filtering
  * Searchable log entries with context
  * Log file rotation and cleanup

* **Error Tracking & Debugging**
  * Detailed error tracking and reporting
  * Stack trace analysis
  * Query debugging and optimization
  * Performance profiling

* **Developer Tools**
  * Built-in code editor with syntax highlighting
  * File system management interface
  * Database query analyzer
  * REST API debugging tools

* **Modern Architecture**
  * Dependency injection container
  * Service layer pattern
  * React/TypeScript frontend
  * Enterprise-grade codebase

= 🎯 Perfect for =

* WordPress developers building complex applications
* Agency developers managing multiple sites
* DevOps teams monitoring WordPress installations
* Site administrators debugging production issues

= 🛡️ Security First =

* Secure by default - strict file permissions
* Role-based access control
* Sanitized and escaped data handling
* WordPress security best practices

= 🔧 Technical Requirements =

* WordPress 5.7 or higher
* PHP 8.1 or higher
* Modern browser with JavaScript enabled
* Write permissions for log directories

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