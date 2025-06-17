# GitHub Copilot Instructions for Debug Suite Plugin

This document provides comprehensive guidelines for GitHub Copilot to assist with the development of the Debug Suite WordPress plugin, a sophisticated development toolkit designed to make WordPress debugging and inspection more efficient.

## Project Overview

Debug Suite is a WordPress plugin that provides advanced debugging tools for WordPress developers. It features a modern, enterprise-grade architecture with a **PSR-11 compliant dependency injection container** system, **PHP-DI compatibility features**, PSR-4 autoloading, and a React/TypeScript frontend with Tailwind CSS v4 styling.

### Core Architecture

- **PSR-11 Compliant DI Container**: Full compliance with PSR-11 DI Interface specification in `DebugSuite\Core\Container` namespace
- **PHP-DI Style Definitions**: Support for `AutowiredDefinition`, `FactoryDefinition`, and `ValueDefinition` patterns
- **Enhanced Service Provider System**: Advanced lifecycle management with registration and booting phases
- **Container Builder Pattern**: Fluent interface for container configuration and dependency setup
- **Service Manager**: Centralized provider lifecycle management with automatic hook registration
- **Hookable Interface**: Automatic WordPress hook registration for services implementing `Hookable`
- **WordPress Integration**: Seamless integration with WordPress hooks, lifecycle, and admin interfaces
- **Helper Functions**: Global functions for easy container access and dependency injection operations

## Code Standards and Requirements

### PHP Backend

1. **PHP Version**: Use PHP 8.2 features including:

    - Union types
    - Named arguments
    - Constructor property promotion
    - Match expressions
    - Readonly properties
    - First-class callable syntax
    - DNF (Disjunctive Normal Form) types

2. **Type Hinting**:

    - Use return type declarations for all methods and functions
    - Use parameter type hints for all method and function parameters
    - Use union types where appropriate (`string|null`, etc.)
    - Use nullable types when applicable

3. **Coding Standards**:

    - Follow PSR-12 coding standard
    - Follow WordPress coding standards (where not in conflict with PSR-12)
    - Use full DocBlocks for all classes, methods, and properties
    - Use PHP_CodeSniffer rules defined in `phpcs.xml`

4. **Autoloading**:
    - Follow PSR-4 autoloading standards
    - Namespace all classes appropriately under the `DebugSuite` namespace

### JavaScript/TypeScript Frontend

1. **TypeScript**:

    - Use TypeScript for all frontend components
    - Properly type all variables, functions, and components
    - Use interfaces for object shapes

2. **React**:

    - Use `@wordpress/element` for React components
    - Follow functional component patterns with hooks
    - Use typed props for all components

3. **ESLint**:

    - Follow ESLint rules for JavaScript and TypeScript
    - Maintain code quality with ESLint static analysis

4. **Tailwind CSS v4**:

    - Use Tailwind CSS v4 with the Oxide engine for styling
    - **Always use the `primary` color as the brand color for all UI elements.**
    - Follow utility-first CSS approach with Tailwind classes
    - Use `@tailwindcss/cli` for building and purging unused styles
    - **Use the `classNames` utility from `@/utils` for conditional class merging instead of using `tailwind-merge` directly.**
    - Maintain consistent spacing and sizing using Tailwind's design system
    - Utilize Tailwind v4's CSS variables system for theme customization
    - Apply responsive design using Tailwind's breakpoint utilities

5. **React Component String internationalization**:

    - Use `@wordpress/i18n` for string internationalization
    - Use `__()` and `_x()` functions for translating strings
    - Ensure all user-facing strings are translatable

6. **Icons**:

    - **Always use Lucide React icons for all UI icons**
    - Import icons from `lucide-react` package
    - Never use inline SVG code or other icon libraries
    - Use consistent icon sizing (typically 16px or 24px)
    - Example: `import { FolderOpen, Settings, X } from 'lucide-react';`

## TypeScript and Build Configuration

### Project Configuration
- **TypeScript**: Version 5.x with strict mode enabled
- **Build Tool**: WordPress Scripts (@wordpress/scripts) for bundling
- **Path Aliases**: Use `@/*` for `src/*` imports (configured in tsconfig.json)
- **Module Resolution**: Node.js style module resolution
- **Target**: ES2020 with DOM libraries

### Package Dependencies
```json
{
  "dependencies": {
    "@wordpress/element": "React components",
    "@wordpress/i18n": "Internationalization",
    "@wordpress/api-fetch": "API requests",
    "lucide-react": "Icon library",
    "react-router-dom": "Routing",
    "clsx": "Class name utility",
    "tailwind-merge": "Tailwind class merging",
    "react-toastify": "Toast notifications",
    "simplebar-react": "Custom scrollbars",
    "@monaco-editor/react": "Code editor"
  }
}
```

### Build Scripts
```bash
# Development with watch mode
npm run dev

# Production build
npm run build

# Type checking only
npm run type-check

# Linting
npm run lint
```

## Architecture Guidelines

1. **PSR-11 Dependency Injection Container System**:

    - **Container Location**: Use `DebugSuite\Core\Container\Container` which implements `Psr\Container\ContainerInterface`
    - **PSR-11 Methods**: Support for `get()` and `has()` methods with proper exception handling
    - **Exception Handling**: Throw `DebugSuite\Core\Container\Exceptions\NotFoundException` for missing services and `DebugSuite\Core\Container\Exceptions\ContainerException` for container errors
    - **Singleton Pattern**: Container uses singleton pattern accessible via `Container::get_instance()`
    - **Magic Methods**: Support for property-style access (`$container->service_name`) and `isset()` checks

2. **PHP-DI Style Definition System**:

    - **AutowiredDefinition**: Use for automatic dependency resolution with reflection-based injection
    - **FactoryDefinition**: Use for factory-based service creation with callable factories
    - **ValueDefinition**: Use for static values and configuration data
    - **Definition Interface**: All definitions implement `DefinitionInterface` with `resolve()` method
    - **Singleton Support**: Definitions support both singleton and transient service lifetimes
    - **Parameter Injection**: Support for constructor parameter injection in autowired definitions

3. **Enhanced Service Provider System**:

    - **Base Class**: Extend `DebugSuite\Core\Container\AbstractServiceProvider` for new service providers
    - **Registration Phase**: Register services with the container in the `register()` method - no service resolution allowed
    - **Boot Phase**: Boot services and perform initialization in the `boot()` method - safe to resolve services
    - **Service Dependencies**: Use proper dependency injection for all service dependencies
    - **Provider Services**: List provided services in the `$provides` array property for tracking
    - **Registration Tracking**: Use `mark_registered()` method to indicate provider registration completion

4. **Container Builder Pattern**:

    - **Builder Class**: Use `DebugSuite\Core\Container\ContainerBuilder` for fluent container configuration
    - **Autowiring Control**: Enable/disable autowiring with `enable_autowiring()` method
    - **Definition Management**: Add definitions using `add_definitions()` with fluent interface
    - **Container Creation**: Configure settings then call `build()` to create configured container
    - **Fluent Interface**: All builder methods return `$this` for method chaining

5. **Service Manager Lifecycle**:

    - **Manager Class**: Use `DebugSuite\Core\Container\ServiceManager` for provider lifecycle management
    - **Provider Registration**: Register providers with `register()` or `register_providers()` methods
    - **Boot Process**: Call `boot()` to initialize all providers and register hooks
    - **Hook Registration**: Automatically register hooks for services implementing `Hookable` interface
    - **Service Resolution**: Resolve services through the container with proper dependency injection
    - **Boot State**: Track boot state with `is_booted()` method

6. **Hookable Interface Pattern**:

    - **Interface Implementation**: Implement `DebugSuite\Interfaces\Hookable` for classes needing WordPress hooks
    - **Hook Method**: Use `register_hooks()` method to register all WordPress hooks and filters
    - **Automatic Registration**: Hook registration handled automatically by ServiceManager after provider booting
    - **Manual Registration**: Avoid manual hook registration in constructors or boot methods
    - **Testing Benefits**: Allows testing services without triggering WordPress hooks

7. **Debug Provider System**:

    - **Base Provider**: Extend `AbstractDebugProvider` for new debug providers
    - **Provider Interface**: Implement `DebugProviderInterface` methods for consistent debug provider behavior
    - **Provider Manager**: Use `DebugProviderManager` for debug provider registration and lifecycle
    - **Provider Integration**: Integrate debug providers with the main DI container system

8. **Helper Functions and Global Access**:

    - **Container Access**: Use `debug_suite_container()` to get container instance
    - **Service Resolution**: Use `debug_suite_resolve(string $service)` to resolve services
    - **Service Manager**: Use `debug_suite_service_manager()` to get service manager instance
    - **Main Instance**: Use `debug_suite()` to get main plugin instance
    - **DI Definitions**: Use helper functions like `debug_suite_autowire()`, `debug_suite_factory()` for creating definitions
    - **Legacy Compatibility**: All legacy helper functions remain functional for backward compatibility

## REST API Architecture

### API Controllers Structure
- **Base Controller**: `DebugSuite\API\RestController` - Provides common functionality
- **Settings Controller**: `DebugSuite\API\SettingsController` - Manages wp-config.php updates
- **File Manager Controller**: `DebugSuite\API\FileManagerController` - Handles file operations
- **File Logs Controller**: `DebugSuite\API\FileLogsController` - Processes debug.log entries

### API Endpoints
```php
// Settings endpoints
POST /wp-json/debug-suite/v1/settings

// File manager endpoints  
GET /wp-json/debug-suite/v1/files?path={path}
GET /wp-json/debug-suite/v1/files/content?path={path}

// File logs endpoints
GET /wp-json/debug-suite/v1/logs
```

### Permission System
- All endpoints require `manage_options` capability
- Consistent permission checking via `permissions_check()` method
- Proper error handling with WP_Error responses

## Frontend Architecture

### React Application Structure
```
src/
├── App.tsx                 # Main application component
├── index.tsx              # Entry point
├── index.css              # Tailwind CSS configuration
├── components/            # Reusable UI components
│   ├── base-layout.tsx    # Layout wrapper
│   ├── ui/                # Base UI components
│   └── *.tsx              # Feature components
├── pages/                 # Route components
│   ├── overview-settings.tsx
│   ├── error-logs.tsx
│   ├── manage-logs.tsx
│   └── file-manager/
├── routing/               # Router configuration
├── types/                 # TypeScript definitions
└── utils/                 # Utility functions
```

### Key Frontend Features
- **Hash-based routing** with React Router DOM
- **Monaco Editor** integration for file editing
- **Toast notifications** with react-toastify
- **Custom scrollbars** with simplebar-react
- **Responsive design** with Tailwind CSS v4
- **Dark mode support** throughout the interface

### State Management
- Local state with React hooks
- Global settings via `window.debugSuiteSettings`
- API data fetching with `@wordpress/api-fetch`

## Helper Functions Reference

### Container Access Functions
```php
// Get the main plugin instance
$plugin = debug_suite();

// Get the DI container
$container = debug_suite_container();

// Resolve a service from the container
$service = debug_suite_resolve(MyService::class);

// Get the service manager
$manager = debug_suite_service_manager();
```

### Definition Helper Functions
```php
// Create autowired definition
$autowired = debug_suite_autowire(MyService::class);

// Create factory definition
$factory = debug_suite_factory(function() {
    return new ComplexService();
});

// Create singleton factory definition
$singleton = debug_suite_singleton(function() {
    return new SingletonService();
});

// Create value definition
$value = debug_suite_value(['key' => 'value']);
```

### Container Methods
```php
// PSR-11 methods
$service = $container->get(MyService::class);
$exists = $container->has(MyService::class);

// Enhanced container methods
$container->singleton(MyService::class, fn($c) => new MyService());
$container->bind('config', ['debug' => true]);
$container->instance('logger', $loggerInstance);

// Magic property access
$service = $container->MyService;
$exists = isset($container->MyService);
```

## File Manager System

### Backend File Operations
- **Symfony Finder** component for file discovery
- **FileManager class** in `DebugSuite\Admin\FileManager\FileManager`
- **Directory tree** generation with metadata
- **File content** reading and writing capabilities
- **Security validation** for file access

### Frontend File Manager
- **Tree view** for directory navigation
- **Table view** for file details
- **Monaco Editor** for file editing
- **Breadcrumb navigation** for path tracking
- **File type icons** with Lucide React icons

## Development Tools and Quality Assurance

### PHP Code Quality Tools
- **PHPStan**: Static analysis with level 5 configuration (`phpstan.neon`)
- **PHP_CodeSniffer**: WordPress and PSR-12 coding standards (`phpcs.xml`)
- **Composer Scripts**: Quality assurance scripts available
  - `composer run phpstan` - Static analysis
  - `composer run phpcs` - Code style checking
  - `composer run phpcs:fix` - Automatic code style fixes
  - `composer run qa` - Combined quality checks

### Frontend Quality Tools
- **ESLint**: JavaScript/TypeScript linting with WordPress standards
- **Prettier**: Code formatting with Tailwind plugin
- **TypeScript**: Strict mode compilation
- **WordPress Scripts**: Build and development tools

### Code Standards Configuration
```bash
# Run static analysis
composer run phpstan

# Check coding standards
composer run phpcs:check

# Fix coding standards automatically
composer run phpcs:fix

# Combined QA checks
composer run qa
```

### WordPress Integration Standards
- **Plugin Structure**: Follow WordPress plugin development best practices
- **Hooks and Filters**: Use WordPress action/filter system properly
- **Internationalization**: All strings must be translatable with text domain `debug-suite`
- **Capabilities**: Implement proper capability checks for admin functions
- **Nonces**: Use WordPress nonce system for form security
- **Sanitization**: Sanitize all input data using WordPress functions

## Project Constants and Versioning

### Plugin Constants
```php
// Defined in debug-suite.php
define('DEBUG_SUITE_VERSION', '1.0.0');
define('DEBUG_SUITE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DEBUG_SUITE_PLUGIN_URL', plugin_dir_url(__FILE__));
```

### Version Information
- **Current Version**: 1.0.0
- **PHP Requirement**: 8.2+
- **WordPress Requirement**: 5.7+
- **License**: GPL-2.0-or-later

### Text Domain
- **Text Domain**: `debug-suite`
- **Domain Path**: `/languages`
- **POT File**: `languages/debug-suite.pot`

### Package Management
- **PNPM**: Package manager for frontend dependencies
- **Composer**: PHP dependency management
- **WordPress Scripts**: Build tooling
- **Tailwind CLI**: CSS processing

## Build Configuration

### Webpack Configuration
```js
// webpack.config.js
module.exports = {
    entry: { 'debug-suite-admin': './src/index.tsx' },
    resolve: {
        alias: { '@': path.resolve(__dirname, 'src') }
    },
    externals: {
        react: 'React',
        'react-dom': 'ReactDOM'
    }
};
```

### TypeScript Configuration
```json
// tsconfig.json
{
    "compilerOptions": {
        "target": "ES2020",
        "strict": true,
        "jsx": "react-jsx",
        "paths": { "@/*": ["src/*"] }
    }
}
```

## Security Considerations

### Backend Security
- **Capability checks** for all admin functionality
- **Nonce verification** for form submissions
- **Path validation** for file operations
- **Input sanitization** for all user data
- **ABSPATH protection** in all PHP files

### Frontend Security
- **XSS prevention** with proper escaping
- **CSRF protection** via WordPress nonces
- **API authentication** via WordPress REST API
- **File path validation** for file manager operations

## Performance Optimization

### Backend Performance
- **Singleton containers** for service caching
- **Lazy loading** of services
- **Optimized autoloading** with Composer
- **Efficient file operations** with Symfony Finder

### Frontend Performance
- **Code splitting** with webpack
- **Tree shaking** for unused code elimination
- **CSS optimization** with Tailwind's purge
- **Asset minification** in production builds

## Testing Guidelines

### PHP Testing
- **PHPUnit** for unit testing
- **Mock objects** for dependencies
- **Container testing** without WordPress hooks
- **Integration testing** with WordPress APIs

### Frontend Testing
- **Jest** for unit testing (if implemented)
- **React Testing Library** for component testing
- **Type checking** with TypeScript compiler
- **Linting** with ESLint for code quality

This comprehensive guide ensures consistent development practices across the Debug Suite plugin while leveraging modern PHP and JavaScript/TypeScript development standards.
