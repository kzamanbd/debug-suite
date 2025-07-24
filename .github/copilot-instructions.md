# GitHub Copilot Instructions for Debug Suite Plugin

This document provides comprehensive guidelines for GitHub Copilot to assist with the development of the Debug Suite WordPress plugin, a sophisticated development toolkit designed to make WordPress debugging and inspection more efficient.

## Project Overview

Debug Suite is a WordPress plugin that provides advanced debugging tools for WordPress developers. It features a modern architecture with a **dependency injection container** system, PSR-4 autoloading, and a React/TypeScript frontend with Tailwind CSS v4 styling.

**Current Version**: 1.0.0  
**PHP Requirements**: PHP 8.1+  
**Compatibility**: Compatible with PHP 8.1, 8.2, and 8.3
**Node/NPM**: Uses pnpm for package management

### Core Architecture

- **Dependency Injection Container**: Service container for managing dependencies in `DebugSuite\Core\Container` namespace
- **Service Provider System**: Lifecycle management with registration and booting phases
- **Service Manager**: Centralized provider lifecycle management with automatic hook registration
- **Hookable Interface**: Automatic WordPress hook registration for services implementing `Hookable`
- **WordPress Integration**: Seamless integration with WordPress hooks, lifecycle, and admin interfaces
- **Helper Functions**: Global functions for easy container access and service resolution

## Code Standards and Requirements

### PHP Backend

1. **PHP Version**: Use PHP 8.1+ features including:
    - Union types (`string|null`, `array|false`)
    - Named arguments in function calls
    - Constructor property promotion with typed properties
    - Match expressions over switch statements
    - Nullable types (`?string`)
    - First-class callable syntax (`strlen(...)`)
    - Mixed type for flexible parameters

2. **PHP 8.1 Compatibility**:
    - Avoid PHP 8.2+ exclusive features (readonly classes, DNF types)
    - Use runtime version checks when needed
    - Include proper fallbacks for reflection API differences
    - Test on multiple PHP versions (8.1, 8.2, 8.3) in CI/CD

3. **Type Hinting**:
    - Use return type declarations for all methods and functions
    - Use parameter type hints for all method and function parameters
    - Use union types where appropriate (`string|null`, etc.)
    - Use nullable types when applicable

4. **Coding Standards**:
    - Follow PSR-12 coding standard
    - Follow WordPress coding standards (where not in conflict with PSR-12)
    - **Use snake_case for all method and function names** (WordPress standard)
    - **Use snake_case for all variable names** (WordPress standard)
    - **Use PascalCase for class names only** (WordPress standard)
    - Use full DocBlocks for all classes, methods, and properties
    - Use PHP_CodeSniffer rules defined in `phpcs.xml`

5. **Autoloading**:
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
    - **Primary color configuration**: Uses indigo color scale (`--color-primary: #6366f1`) defined in `src/index.css`
    - **No separate tailwind.config file**: Uses inline configuration in CSS with `@theme` directive

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

7. **UI Components**:
    - **Use Headless UI for interactive components**: Prefer `@headlessui/react` components for dropdowns, modals, dialogs, and other interactive elements
    - **Combobox for Dropdowns**: Use the custom `SearchableSelect` component (built on react-select) for all dropdown selections instead of native selects
    - **Toast Notifications**: Use the custom `Toast` component with variation methods for user feedback
    - **Accessibility First**: Always use Headless UI components which provide built-in accessibility features
    - **Custom Components**: Build custom UI components in `src/components/base/` following the established patterns
    - **Component Examples**:

        ```typescript
        // ✅ Good - Use react-select based SearchableSelect
        import SearchableSelect from '@/components/base/select';
        <SearchableSelect options={options} value={selected} onChange={setSelected} />

        // ✅ Good - Use Toast variations
        import { useToast } from '@/components/base/toast';
        const { toast } = useToast();
        toast.success('Operation completed successfully!');
        toast.error('Something went wrong');
        ```

## TypeScript and Build Configuration

### Project Configuration

- **TypeScript**: Version 5.x with strict mode enabled
- **Build Tool**: WordPress Scripts (@wordpress/scripts) for bundling
- **Path Aliases**: Use `@/*` for `src/*` imports (configured in tsconfig.json)
- **Module Resolution**: Node.js style module resolution
- **Target**: ES2020 with DOM libraries

### Package Dependencies

````json
{
  "dependencies": {
    "@wordpress/element": "React components",
    "@wordpress/i18n": "Internationalization",
    "@wordpress/api-fetch": "API requests",
    "@headlessui/react": "Headless UI components for accessibility",
    "lucide-react": "Icon library",
    "react-router-dom": "Routing",
    "clsx": "Class name utility",
    "tailwind-merge": "Tailwind class merging",
    "simplebar-react": "Custom scrollbars",
    "@monaco-editor/react": "Code editor",
    "react-select": "Dropdown component library"
  },
  "devDependencies": {
    "@wordpress/scripts": "Build tooling and configuration",
    "typescript": "TypeScript language support",
    "@types/react": "React type definitions",
    "@types/wordpress__element": "WordPress element types",
    "@tailwindcss/cli": "Tailwind CSS v4 CLI",
    "concurrently": "Run multiple commands"
  }
}

### Build Scripts
```bash
# Development with watch mode (runs both Tailwind and WordPress scripts)
npm run dev

# Production build
npm run build

# Type checking only
npm run type-check

# Linting
npm run lint

# WordPress build only
npm run wp:build

# Tailwind build only
npm run tailwind:build
````

## Architecture Guidelines

### **IMPORTANT: Current DI Container Usage**

**� Use the current established patterns:**

```php
// ✅ CORRECT - Current service registration in AppServiceProvider
$container->add([
    // Use object() for simple services (singletons)
    MyService::class => $container->object(MyService::class),

    // Use autowire() for services with constructor dependencies
    MyController::class => $container->autowire(MyController::class),
]);
```

**✅ Available Definition Types:**

- `$container->object(Class::class)` - Creates autowired singletons
- `$container->autowire(Class::class)` - Creates autowired instances with dependency injection
- `$container->factory(callable)` - Creates factory definitions
- `$container->value(mixed)` - Creates value definitions
- `$container->config(array, mixed, bool)` - Creates configuration definitions
- `$container->decorate(string, string, bool)` - Creates decorator definitions

**✅ Current Service Registration Pattern:**

- Business services registered in `AppServiceProvider` using `add()`
- REST API controllers registered in `RestControllerProvider` using `add()`
- Services use `object()` for simple singletons
- Controllers use `autowire()` for dependency injection
- Service classes implement `ServiceInterface` marker interface

1. **Dependency Injection Container System**:
    - **Container Location**: Use `DebugSuite\Core\Container\Container` for service management
    - **Simple Registration**: Use only `$container->object()` and `$container->autowire()` methods
    - **Singleton Pattern**: Container uses singleton pattern accessible via `Container::get_instance()`
    - **No Complex Definitions**: Avoid using ValueDefinition, AutowiredDefinition, etc. directly

2. **Simple Service Registration**:
    - **Object Registration**: Use `$container->object(Class::class)` for simple service registration
    - **Autowired Registration**: Use `$container->autowire(Class::class)` for services with dependencies
    - **Clean Structure**: Use minimal boilerplate with focused, readable code
    - **Separated Providers**: Register business services in `AppServiceProvider`, REST controllers in `RestControllerProvider`

3. **Service Provider System**:
    - **Base Class**: Extend `DebugSuite\Core\Container\AbstractServiceProvider` for new service providers
    - **Simple Registration**: Register services with concise arrow function singletons
    - **Provider Services**: List provided services in the `$provides` array property for tracking
    - **Clean Structure**: Use minimal boilerplate with focused, readable code

    **Example Service Provider Pattern**:

    ```php
    <?php
    namespace DebugSuite\Providers;

    use DebugSuite\Core\Container\AbstractServiceProvider;
    use DebugSuite\Core\Container\Container;

    class ExampleServiceProvider extends AbstractServiceProvider {

        protected array $provides = [
            ExampleService::class,
            ExampleController::class,
        ];

        public function register( Container $container ): void {
            $container->add([
                ExampleService::class => $container->object( ExampleService::class ),
                ExampleController::class => $container->autowire( ExampleController::class ),
            ]);
        }
    }
    ```

4. **Service Manager Lifecycle**:
    - **Manager Class**: Use `DebugSuite\Core\Container\ServiceManager` for provider lifecycle management
    - **Provider Registration**: Register providers with `register()` or `register_providers()` methods
    - **Boot Process**: Call `boot()` to initialize all providers and register hooks
    - **Hook Registration**: Automatically register hooks for services implementing `Hookable` interface
    - **Service Resolution**: Resolve services through the container with dependency injection
    - **Boot State**: Track boot state with `is_booted()` method

5. **Hookable Interface Pattern**:
    - **Interface Implementation**: Implement `DebugSuite\Interfaces\Hookable` for classes needing WordPress hooks
    - **Hook Method**: Use `register_hooks()` method to register all WordPress hooks and filters
    - **Automatic Registration**: Hook registration handled automatically by ServiceManager after provider booting
    - **Manual Registration**: Avoid manual hook registration in constructors or boot methods
    - **Testing Benefits**: Allows testing services without triggering WordPress hooks

6. **Service Layer Pattern Architecture**:
    - **Service Layer Location**: All business logic services are located in `DebugSuite\Services` namespace
    - **Service Interface**: All services implement `DebugSuite\Interfaces\ServiceInterface` marker interface
    - **ServiceResponse Pattern**: All service methods return `DebugSuite\Core\ServiceResponse` objects for consistent error handling
    - **Separation of Concerns**: REST controllers only handle HTTP requests/responses, services handle business logic
    - **Dependency Injection**: Services are registered as singletons in the container via `AppServiceProvider`
    - **Error Handling**: Use `ServiceResponse::success($data)` and `ServiceResponse::failure($message, $code)` for consistent responses
    - **Service Registration**: Add new services to `AppServiceProvider::$provides` array and register in `register()` method
    - **Controller Registration**: Add new REST controllers to `RestControllerProvider::$provides` array and register in `register()` method
    - **Implemented Services**:
        - `FileLogsService` (debug log operations)
        - `SettingsService` (wp-config.php management)
        - `OnboardingService` (onboarding flow)
        - `OverviewService` (dashboard overview)
        - `WPLogReaderService` (WordPress log file reading)
        - `LogFileDiscoveryService` (log file discovery)
    - **Service Dependencies**: Services accept optional constructor parameters for configuration (log file paths, base directories, config files)
    - **Container Integration**: Services are resolved via `debug_suite()->resolve()` helper or direct container access
    - **Testing Architecture**: Services are easily unit testable without WordPress dependencies or global state

7. **Helper Functions and Global Access**:
    - **Container Access**: Use `debug_suite()->container()` to get container instance
    - **Service Resolution**: Use `debug_suite()->resolve(string $service)` to resolve services
    - **Service Manager**: Use `debug_suite_service_manager()` to get service manager instance
    - **Main Instance**: Use `debug_suite()` to get main plugin instance
    - **Date Utility**: Use `debug_suite_date(string $timestamp)` for consistent date formatting

**Note**: The helper functions available are:

- `debug_suite()` - Main plugin instance
- `debug_suite()->container()` - DI Container instance
- `debug_suite()->resolve($service)` - Resolve service from container
- `debug_suite_service_manager()` - Service manager instance
- `debug_suite_date($timestamp)` - Date formatting utility

## Feature Implementation Guidelines

When implementing new features in Debug Suite, follow the Service Layer Pattern for optimal separation of concerns. This approach has been successfully implemented for all existing API endpoints including debug logs, settings management, and file operations.

### 1. **New API Endpoint Implementation**

**Step 1: Create the Service Class**

```php
<?php
/**
 * Example service for Debug Suite business logic.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Services;

use DebugSuite\Core\ServiceResponse;
use DebugSuite\Interfaces\ServiceInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Simple example service for handling business operations.
 *
 * @since DEBUG_SUITE_SINCE
 */
class ExampleService implements ServiceInterface {

	/**
	 * Service Variable
	 *
	 * @var mixed
	 */
	private mixed $variable;

	/**
	 * Constructor.
	 *
	 * @param mixed $variable Optional custom config path.
	 */
	public function __construct( $variable ) {
		$this->variable = $variable
	}

	/**
	 * Process input data.
	 *
	 * @param array $input Input data to process.
	 * @return ServiceResponse
	 */
	public function process_data( array $input ): ServiceResponse {
		// Validate required fields
		if ( empty( $input['required_field'] ) ) {
			return ServiceResponse::failure(
				__( 'Required field is missing.', 'debug-suite' ),
				'validation_error'
			);
		}

		// Process the data
		$result = $this->do_business_logic( $input );

		return ServiceResponse::success([
			'processed_data' => $result,
			'timestamp'      => current_time( 'mysql' ),
			'config_used'    => $this->config_path,
		]);
	}

	/**
	 * Execute business logic.
	 *
	 * @param array $input Input data.
	 * @return array
	 */
	private function do_business_logic( array $input ): array {
		// Implementation details...
		return [
			'processed' => true,
			'data'      => $input,
		];
	}
}
```

**Step 2: Register Service with DI Container**

```php
// In AppServiceProvider::register() - for business services
$container->add([
    ExampleService::class => $container->object( ExampleService::class ),
]);

// In RestControllerProvider::register() - for REST controllers
$container->add([
    ExampleController::class => $container->autowire( ExampleController::class ),
]);

// Add to respective $provides arrays
// AppServiceProvider::$provides
protected array $provides = [
	WPLogReaderService::class,
	FileLogsService::class,
	SettingsService::class,
	OnboardingService::class,
	OverviewService::class,
	ExampleService::class,      // Add new service here
];

// RestControllerProvider::$provides
protected array $provides = [
	FileLogsController::class,
	SettingsController::class,
	OverviewController::class,
	ExampleController::class,   // Add new controller here
];
```

**Step 3: Create/Update REST Controller**

```php
<?php
/**
 * Example REST API controller for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\API;

use DebugSuite\Services\ExampleService;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Example controller for demonstration purposes.
 *
 * @since DEBUG_SUITE_SINCE
 */
class ExampleController extends RestController {

	/**
	 * Example service instance.
	 *
	 * @var ExampleService
	 */
	private ExampleService $service;

	/**
	 * Route base for endpoints.
	 *
	 * @var string
	 */
	protected $rest_base = 'example';

	/**
	 * Constructor.
	 *
	 * @param ExampleService $service Service instance.
	 */
	public function __construct( ExampleService $service ) {
		$this->example_service = $service;
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'process_request' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'required_field' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);
	}

	/**
	 * Process request.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function process_request( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		// Extract and prepare input
		$input = [
			'required_field' => $request->get_param( 'required_field' ),
			'optional_field' => $request->get_param( 'optional_field' ),
		];

		// Delegate to service
		$result = $this->example_service->process_data( $input );

		// Transform service result to HTTP response
		if ( $result->is_failure() ) {
			$status_code = match( $result->get_error_code() ) {
				'validation_error'   => 400,
				'not_found'          => 404,
				'permission_denied'  => 403,
				default              => 500
			};

			return new WP_Error(
				$result->get_error_code(),
				$result->get_error_message(),
				[ 'status' => $status_code ]
			);
		}

		return rest_ensure_response( $result->to_array() );
	}
}
```

### 2. **Service Layer Best Practices**

- **Single Responsibility**: Each service handles one domain of business logic
    - `FileLogsService`: Debug log operations only
    - `SettingsService`: wp-config.php management only

- **Return ServiceResponse**: Always return `ServiceResponse` objects, never throw exceptions to controllers

    ```php
    // ✅ Good - From LogsService
    if ( ! file_exists( $this->log_file_path ) ) {
        return ServiceResponse::failure(
            __( 'Debug log file not found.', 'debug-suite' ),
            'file_not_found',
            [ 'path' => $this->log_file_path ]
        );
    }

    // ❌ Bad - Don't throw exceptions to controllers
    if ( ! file_exists( $this->log_file_path ) ) {
        throw new Exception( 'File not found' );
    }
    ```

- **Validate Input**: Perform all business validation in the service layer

    ```php
    // Example from SettingsService
    public function update_settings( array $settings ): ServiceResponse {
        $valid_keys = [ 'WP_DEBUG', 'WP_DEBUG_LOG', 'WP_DEBUG_DISPLAY' ];

        foreach ( $settings as $key => $value ) {
            if ( ! in_array( $key, $valid_keys, true ) ) {
                return ServiceResponse::failure(
                    sprintf( __( 'Invalid setting key: %s', 'debug-suite' ), $key ),
                    'invalid_setting',
                    [ 'key' => $key, 'valid_keys' => $valid_keys ]
                );
            }
        }
        // Continue with processing...
    }
    ```

- **Error Context**: Include helpful context data in error results
- **Configuration**: Accept dependencies through constructor for testability

    ```php

    ```

- **Documentation**: Fully document all public methods with PHPDoc
    ```php
    /**
     * Get directory tree structure with file metadata.
     *
     * @param string $relative_path The path relative to the base directory.
     * @param array  $options {
     *     Optional arguments.
     *     @type bool $include_hidden Whether to include hidden files. Default false.
     *     @type int  $max_depth     Maximum directory depth. Default 3.
     * }
     * @return ServiceResponse Success with tree data or failure with error message.
     */
    public function get_directory_tree( string $relative_path = '', array $options = [] ): ServiceResponse
    ```

### 3. **API Controller Testing Best Practices**

For testing API controllers, use the WordPress REST API testing framework:

```php
/**
 * @covers \DebugSuite\API\ExampleController
 * @group api
 * @group integration
 */
class ExampleControllerTest extends DebugSuiteTestCase {
    private $controller;
    private $service;

    protected function set_up() {
        parent::set_up();
        $this->service = $this->createMock(ExampleService::class);
        $this->controller = new ExampleController($this->service);
        $this->controller->register_routes();
    }

    public function test_permission_check() {
        // Test without admin privileges
        wp_set_current_user(0);
        $request = new WP_REST_Request('GET', '/debug-suite/v1/example');
        $response = $this->controller->permissions_check($request);
        $this->assertInstanceOf(WP_Error::class, $response);

        // Test with admin privileges
        $user_id = $this->factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($user_id);
        $response = $this->controller->permissions_check($request);
        $this->assertTrue($response);
    }

    public function test_process_request() {
        // Mock service response
        $this->service->method('process_data')
            ->willReturn(ServiceResponse::success(['key' => 'value']));

        // Create request
        $request = new WP_REST_Request('POST', '/debug-suite/v1/example');
        $request->set_param('required_field', 'test value');

        // Execute controller method
        $response = $this->controller->process_request($request);

        // Assert response
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertArrayHasKey('key', $data);
        $this->assertEquals('value', $data['key']);
    }
}
```

## Container System Feature Usage Patterns

The Debug Suite Container System provides enterprise-grade dependency injection capabilities. Here are the most common and recommended usage patterns based on the current implementation:

### 1. **Service Registration Patterns**

#### Modern Service Registration (Current Implementation)

```php
class AppServiceProvider extends AbstractServiceProvider {
    protected array $provides = [
        WPLogReaderService::class,
        FileLogsService::class,
        SettingsService::class,
        OnboardingService::class,
        OverviewService::class,
    ];

    public function register(Container $container): void {
        // Modern definition array approach - Business Services Only
        $container->add([
            // Services with simple autowiring
            WPLogReaderService::class    => $container->object(WPLogReaderService::class),
            FileLogsService::class       => $container->object(FileLogsService::class),
            SettingsService::class       => $container->object(SettingsService::class),
            OnboardingService::class     => $container->object(OnboardingService::class),
            OverviewService::class       => $container->autowire(OverviewService::class),
        ]);
    }
}

class RestControllerProvider extends AbstractServiceProvider {
    protected array $provides = [
        FileLogsController::class,
        SettingsController::class,
        OverviewController::class,
    ];

    public function register(Container $container): void {
        // REST API Controllers with dependency injection
        $container->add([
            FileLogsController::class    => $container->autowire(FileLogsController::class),
            SettingsController::class   => $container->autowire(SettingsController::class),
            OverviewController::class   => $container->autowire(OverviewController::class),
        ]);
    }
}
```

### 2. **WordPress Integration Patterns**

#### Services with WordPress Hooks

```php
use DebugSuite\Interfaces\Hookable;

class AdminDashboardService implements Hookable {
    public function __construct(
        private SettingsService $settings,
        private SecurityService $security,
        private string $menu_slug = 'debug-suite'
    ) {}

    public function register_hooks(): void {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'init_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function add_admin_menu(): void {
        add_menu_page(
            __('Debug Suite', 'debug-suite'),
            __('Debug Suite', 'debug-suite'),
            'manage_options',
            $this->menu_slug,
            [$this, 'render_dashboard']
        );
    }
}

// Registration with parameter injection (advanced pattern)
$container->set(AdminDashboardService::class,
    $container->autowire(AdminDashboardService::class)
);
```

#### REST API Controllers with DI

```php
class LogsApiController extends RestController {
    public function __construct(
        private FileLogsService $logs_service,
        private SecurityService $security,
        private ValidationService $validator
    ) {
        parent::__construct();
    }

    public function register_routes(): void {
        register_rest_route($this->namespace, '/logs', [
            'methods' => 'GET',
            'callback' => [$this, 'get_logs'],
            'permission_callback' => [$this->security, 'can_view_logs'],
            'args' => $this->validator->get_logs_args(),
        ]);
    }

    public function get_logs(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $options = [
            'limit' => $request->get_param('limit'),
            'level' => $request->get_param('level'),
            'search' => $request->get_param('search'),
        ];

        $result = $this->logs_service->get_log_entries($options);

        return $result->is_failure()
            ? new WP_Error($result->get_error_code(), $result->get_error_message())
            : rest_ensure_response($result->to_array());
    }
}

// Automatic registration with dependency injection
$container->set(LogsApiController::class, $container->autowire(LogsApiController::class));
```

### 3. **Helper Function Usage Examples**

```php
// Quick service resolution
$logger = debug_suite()->resolve(LoggerInterface::class);

// Container access
$container = debug_suite()->container();

// Service manager access
$service_manager = debug_suite_service_manager();

// Get main plugin instance
$plugin = debug_suite();
```

Note: Advanced helper functions like `debug_suite_container_builder()`, `debug_suite_tagged()`, and `debug_suite_autowire_with_params()` are not currently implemented in the project.

These patterns provide comprehensive coverage for most real-world scenarios when using the Debug Suite Container System. Always prefer dependency injection over service location, use environment-specific configuration for different deployment stages, and leverage the Hookable interface for automatic WordPress integration.

### Frontend Component Architecture Standards

Following WordPress coding standards adapted for React/TypeScript development with a preference for consolidation over micro-components:

#### File Naming Conventions

- **Component files**: Use kebab-case naming (e.g., `log-viewer.tsx`, `log-controls.tsx`)
- **Hook files**: Use kebab-case naming (e.g., `use-log-entries.ts`, `use-api-client.ts`)
- **Utility files**: Use kebab-case naming (e.g., `date-helpers.ts`, `format-utils.ts`)
- **Type definition files**: Use kebab-case naming (e.g., `log-types.ts`, `api-types.ts`)

#### Component Structure Standards

- **Component names**: PascalCase for React components (e.g., `LogViewer`, `LogControls`)
- **Variable names**: camelCase for JavaScript/TypeScript variables (e.g., `logFiles`, `selectedFile`)
- **Function names**: camelCase for JavaScript/TypeScript functions (e.g., `handleFileChange`, `fetchLogEntries`)
- **Interface names**: PascalCase with descriptive suffixes (e.g., `LogViewerProps`, `LogEntry`)
- **Hook names**: camelCase starting with "use" (e.g., `useLogFiles`, `useDebounce`)

#### Directory Structure for Components

```
src/pages/[feature-name]/
├── index.tsx                 # Main page component
├── types.ts                  # TypeScript type definitions
├── constants.ts              # Feature-specific constants
├── hooks.ts                  # Custom hooks for the feature
└── components/
    ├── index.ts              # Barrel exports
    ├── feature-viewer.tsx    # Consolidated view component
    └── feature-controls.tsx  # Consolidated controls component
```

#### Component Architecture Principles - Consolidation SOP

- **Favor Substantial Components over Micro-Components**: Prefer 2-3 well-structured components over many micro-level components
- **Logical Grouping**: Group related functionality into cohesive components:
    - **Viewer Components**: Handle display, tables, content rendering, and detail views
    - **Controls Components**: Handle filtering, search, pagination, actions, and form controls
- **Clear Separation of Concerns**: Each consolidated component should have a distinct purpose
- **Single Responsibility per Consolidated Component**: Each component handles one major UI domain
- **Props Interface**: Every component has a comprehensive typed Props interface
- **Barrel Exports**: Use index.ts files for clean import paths
- **Composition over Inheritance**: Favor composition patterns within consolidated components
- **Data Flow**: Props down, events up pattern maintained across consolidated components
- **Custom Hooks**: Extract complex data logic into reusable hooks
- **TypeScript First**: Full type coverage for all components and data flows
- **Internal Component Structure**: Within consolidated components, use internal helper functions rather than breaking into separate files
- **Maintainability Focus**: Optimize for long-term maintainability rather than micro-level modularity

#### Consolidated Component Examples

**Good: Two Substantial Components**

- **Viewer Components**: Handle display, tables, content rendering, and detail views
- **Controls Components**: Handle filtering, search, pagination, actions, and form controls

**Avoid: Excessive Micro-Components**

- ❌ Too granular - creates maintenance overhead
- ❌ Separate components for entry, table, file selector, filters bar, pagination, stats footer

#### Implementation Guidelines

- **Start with Consolidation**: When creating new features, begin with 2-3 consolidated components
- **Refactor Micro-Components**: When encountering micro-component patterns, consolidate them into substantial components
- **Component Size**: Each component should be 100-300 lines, focusing on cohesive functionality
- **Internal Organization**: Use internal helper functions, interfaces, and constants within component files
- **Testing Strategy**: Test consolidated components as complete units rather than individual micro-pieces
- **Documentation**: Provide comprehensive props documentation for consolidated components

### UI Component Standards

#### Dropdown/Select Components

**Always use the custom SearchableSelect component** built on react-select for all dropdown selections.

**Features:**

- ✅ Full TypeScript support with typed options
- ✅ Search/filter functionality built-in
- ✅ Keyboard navigation (arrows, enter, escape)
- ✅ Accessibility compliance (ARIA, screen readers)
- ✅ Custom option rendering
- ✅ Loading and disabled states
- ✅ Error handling and validation
- ✅ Consistent styling with design system

**Do NOT use:**

- ❌ Native HTML `<select>` elements (poor UX)
- ❌ Direct react-select imports (use the custom wrapper)
- ❌ Custom dropdown implementations without accessibility

**Usage Example:**

```typescript
import SearchableSelect from '@/components/base/select';

const options = [
    { value: 'option1', label: 'Option 1' },
    { value: 'option2', label: 'Option 2' },
];

<SearchableSelect
    options={options}
    value={selectedOption}
    onChange={(option) => setSelectedOption(option)}
    placeholder="Select an option..."
/>
```

### Toast Notification Standards

**Always use the custom Toast component** with built-in variation methods for user feedback.

**Features:**

- ✅ Success and error variation methods (`toast.success()`, `toast.error()`)
- ✅ Custom icons with automatic color coding (green for success, red for error)
- ✅ Configurable duration with sensible defaults
- ✅ Dismissible with close button
- ✅ Multiple toast stacking support
- ✅ Smooth animations and transitions
- ✅ Consistent styling with design system
- ✅ TypeScript support with proper typing

**Usage Guidelines:**

- Use `toast.success()` for positive feedback (saves, updates, successful actions)
- Use `toast.error()` for error feedback (validation errors, API failures)
- Use `toast()` for neutral notifications (info, warnings, custom messages)
- Keep messages concise and actionable
- Use consistent duration (2000ms default, 3000ms for important messages)

## Current Project Organization

### Core Service Providers

The project follows a clean service provider architecture:

- **AppServiceProvider**: Registers business logic services only
- **RestControllerProvider**: Registers REST API controllers only
- **AdminServiceProvider**: Registers admin-specific services (`Admin`)
- **FrontendServiceProvider**: Registers frontend services (`Frontend`)

### Service Layer Implementation

All business logic is implemented in the `includes/Services/` directory:

- **DebugLog/** - Debug log related services
    - `FileLogsService` - Debug log operations
    - `WPLogReaderService` - WordPress log file reading
    - `LogFileDiscoveryService` - Log file discovery
- **SettingsService** - wp-config.php management
- **OnboardingService** - Onboarding flow management
- **OverviewService** - Dashboard overview functionality

### REST API Controllers

All API endpoints are handled by controllers in `includes/API/`:

- `FileLogsController` - Debug log API endpoints
- `SettingsController` - Settings management API endpoints
- `OverviewController` - Dashboard overview API endpoints

### Testing Infrastructure

- **Unit Tests**: `tests/Unit/` - Isolated component testing
- **Integration Tests**: `tests/Integration/` - Service integration testing
- **Test Helpers**: `tests/Helpers/DebugSuiteTestCase.php` - Base test case
- **Coverage Reports**: `tests/coverage/` - Code coverage analysis
