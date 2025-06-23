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
    - **Use snake_case for all method and function names** (WordPress standard)
    - **Use snake_case for all variable names** (WordPress standard)
    - **Use PascalCase for class names only** (WordPress standard)
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

7. **UI Components**:

    - **Use Headless UI for interactive components**: Prefer `@headlessui/react` components for dropdowns, modals, dialogs, and other interactive elements
    - **Combobox for Dropdowns**: Use the custom `Combobox` component (built on Headless UI) for all dropdown selections instead of native selects
    - **Accessibility First**: Always use Headless UI components which provide built-in accessibility features
    - **Custom Components**: Build custom UI components in `src/components/ui/` following the established patterns
    - **Component Examples**:
        ```typescript
        // ✅ Good - Use Headless UI Combobox
        import Combobox from '@/components/ui/combobox';
        <Combobox options={options} value={selected} onChange={setSelected} />
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
    "react-toastify": "Toast notifications",
    "simplebar-react": "Custom scrollbars",
    "@monaco-editor/react": "Code editor"
  },
  "devDependencies": {
    "@wordpress/scripts": "Build tooling and configuration",
    "typescript": "TypeScript language support",
    "@types/react": "React type definitions",
    "@types/wordpress__element": "WordPress element types"
  }
}

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
````

## Architecture Guidelines

1. **PSR-11 Dependency Injection Container System**:

    - **Container Location**: Use `DebugSuite\Core\Container\Container` which implements `Psr\Container\ContainerInterface`
    - **PSR-11 Methods**: Support for `get()` and `has()` methods with proper exception handling
    - **Exception Handling**: Throw `DebugSuite\Core\Container\Exceptions\NotFoundException` for missing services and `DebugSuite\Core\Container\Exceptions\ContainerException` for container errors
    - **Singleton Pattern**: Container uses singleton pattern accessible via `Container::get_instance()`
    - **Magic Methods**: Support for property-style access (`$container->service_name`) and `isset()` checks

2. **PHP-DI Style Definition System**:

    - **AutowiredDefinition**: Use for automatic dependency resolution with reflection-based injection
        - Advanced parameter injection with static overrides, dynamic callbacks, and environment-aware configuration
        - Multiple parameter resolution strategies with priority-based fallback system
        - Enhanced error messages with actionable suggestions for parameter resolution failures
        - Convenience methods for bulk parameter setting and introspection
    - **FactoryDefinition**: Use for factory-based service creation with callable factories
    - **ValueDefinition**: Use for static values and configuration data
    - **ConfigDefinition**: Use for environment-aware configuration management
    - **DecoratorDefinition**: Use for service decoration patterns
    - **Definition Interface**: All definitions implement `DefinitionInterface` with `resolve()` method
    - **Singleton Support**: Definitions support both singleton and transient service lifetimes
    - **Parameter Injection**: Comprehensive constructor parameter injection with multiple strategies

3. **Enhanced Service Provider System**:

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
            $container->singleton( ExampleService::class, fn() => new ExampleService() );
            $container->singleton( ExampleController::class, fn( $c ) => new ExampleController( $c->get( ExampleService::class ) ) );
        }
    }
    ```

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

8. **Service Layer Pattern Architecture**:

    - **Service Layer Location**: All business logic services are located in `DebugSuite\Services` namespace
    - **Service Interface**: All services implement `DebugSuite\Interfaces\ServiceInterface` marker interface
    - **ServiceResponse Pattern**: All service methods return `DebugSuite\Core\ServiceResponse` objects for consistent error handling
    - **Separation of Concerns**: REST controllers only handle HTTP requests/responses, services handle business logic
    - **Dependency Injection**: Services are registered as singletons in the PSR-11 container via `ServicesServiceProvider`
    - **Configuration Support**: Services accept configurable dependencies through container bindings (e.g., custom file paths)
    - **Error Handling**: Use `ServiceResponse::success($data)` and `ServiceResponse::failure($message, $code)` for consistent responses
    - **Service Registration**: Add new services to `ServicesServiceProvider::$provides` array and register in `register()` method
    - **Implemented Services**: `FileLogsService` (debug log operations), `SettingsService` (wp-config.php management), `FileManagerService` (file system operations)
    - **Service Dependencies**: Services accept optional constructor parameters for configuration (log file paths, base directories, config files)
    - **Container Integration**: Services are resolved via `debug_suite_resolve()` helper or direct container access
    - **Testing Architecture**: Services are easily unit testable without WordPress dependencies or global state

9. **Helper Functions and Global Access**:

    - **Container Access**: Use `debug_suite_container()` to get container instance
    - **Service Resolution**: Use `debug_suite_resolve(string $service)` to resolve services
    - **Service Manager**: Use `debug_suite_service_manager()` to get service manager instance
    - **Main Instance**: Use `debug_suite()` to get main plugin instance
    - **DI Definitions**: Use helper functions like `debug_suite_autowire()`, `debug_suite_factory()` for creating definitions
    - **Advanced Autowiring**: Use `debug_suite_autowire_with_params()` for quick parameter injection
    - **Environment Autowiring**: Use `debug_suite_autowire_env()` for environment-specific service configuration
    - **Configuration Management**: Use `debug_suite_config()` for environment-aware configuration
    - **Service Decoration**: Use `debug_suite_decorate()` for decorator pattern implementation
    - **Tagged Services**: Use `debug_suite_tagged()` to retrieve services by tag
    - **Legacy Compatibility**: All legacy helper functions remain functional for backward compatibility

## Advanced Dependency Injection Patterns

### AutowiredDefinition Parameter Injection

The `AutowiredDefinition` class supports multiple parameter injection strategies with priority-based resolution:

1. **Environment-Specific Parameters** (highest priority)

    ```php
    $definition = $container->autowire(DatabaseService::class)
        ->environment_parameters('development', [
            'host' => 'localhost',
            'debug' => true
        ])
        ->environment_parameters('production', [
            'host' => 'prod-db.example.com',
            'debug' => false
        ]);
    ```

2. **Dynamic Parameter Callbacks**

    ```php
    $definition = $container->autowire(EmailService::class)
        ->constructor_parameter_callback('api_key', function($resolver) {
            $config = $resolver(ConfigService::class);
            return $config->get('email.api_key');
        });
    ```

3. **Static Parameter Overrides**

    ```php
    // Single parameter
    $definition = $container->autowire(LoggerService::class)
        ->constructor_parameter('log_level', 'debug');

    // Multiple parameters
    $definition = $container->autowire(LoggerService::class)
        ->constructor_parameters([
            'log_level' => 'debug',
            'log_file' => '/var/log/app.log'
        ]);
    ```

4. **Type-Based Dependency Injection** (automatic)
5. **Default Parameter Values** (from constructor)
6. **Enhanced Error Messages** (with actionable suggestions)

### Environment-Aware Services

Services automatically adapt to WordPress environment using:

- `WP_ENVIRONMENT_TYPE` constant (WordPress 5.5+)
- `WP_DEBUG` constant (fallback)
- Default to 'production' if neither is set

Supported environments: `development`, `staging`, `production`, `testing`

### Convenience Helper Functions

```php
// Quick autowiring with parameters
$definition = debug_suite_autowire_with_params(LoggerService::class, [
    'log_level' => 'debug',
    'log_file' => '/var/log/app.log'
], true); // singleton

// Environment-aware autowiring
$definition = debug_suite_autowire_env(DatabaseService::class, [
    'development' => ['host' => 'localhost', 'debug' => true],
    'production' => ['host' => 'prod-db.com', 'debug' => false]
], true); // singleton

// Configuration management
$config = debug_suite_config([
    'development' => ['api_url' => 'https://dev-api.com'],
    'production' => ['api_url' => 'https://api.com']
]);

// Service decoration
$decorated = debug_suite_decorate(CachedEmailService::class, EmailService::class, true);
```

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
// In AppServiceProvider::register()
$container->singleton( ExampleService::class, fn() => new ExampleService() );

// Register controller with dependency injection
$container->singleton( ExampleController::class, fn( $c ) => new ExampleController( $c->get( ExampleService::class ) ) );

// Add to $provides array (follow existing pattern)
protected array $provides = [
	FileLogsService::class,
	FileManagerService::class,
	SettingsService::class,
	ExampleService::class,      // Add new service here
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
    - `FileManagerService`: File system operations only

- **Return ServiceResponse**: Always return `ServiceResponse` objects, never throw exceptions to controllers

    ```php
    // ✅ Good - From FileLogsService
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
    public function update_debug_settings( array $settings ): ServiceResponse {
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
    // From FileManagerService - accepts custom base path
    public function __construct( ?string $base_path = null ) {
        $this->base_path = $base_path ?? ABSPATH;
    }
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
        $user_id = $this->factory->user->create(['role' => 'administrator']);
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

#### Modern PHP-DI Style Registration (Current Implementation)

```php
class AppServiceProvider extends AbstractServiceProvider {
    protected array $provides = [
        FileLogsService::class,
        FileManagerService::class,
        SettingsService::class,
        FileLogsController::class,
        FileManagerController::class,
        SettingsController::class,
    ];

    public function register(Container $container): void {
        // Modern definition array approach
        $container->add_definitions([
            // Services with simple autowiring
            FileLogsService::class    => $container->object(FileLogsService::class),
            FileManagerService::class => $container->object(FileManagerService::class),
            SettingsService::class   => $container->object(SettingsService::class),

            // Controllers with dependency injection
            FileLogsController::class    => $container->autowire(FileLogsController::class),
            FileManagerController::class => $container->autowire(FileManagerController::class),
            SettingsController::class   => $container->autowire(SettingsController::class),
        ]);
    }
}
```

#### Environment-Aware Service Registration

```php
public function register(Container $container): void {
    // Environment-specific configuration
    $container->set(ApiService::class,
        debug_suite_autowire_env(ApiService::class, [
            'development' => [
                'base_url' => 'https://dev-api.example.com',
                'timeout' => 30,
                'debug' => true,
                'rate_limit' => false
            ],
            'staging' => [
                'base_url' => 'https://staging-api.example.com',
                'timeout' => 20,
                'debug' => true,
                'rate_limit' => true
            ],
            'production' => [
                'base_url' => 'https://api.example.com',
                'timeout' => 10,
                'debug' => false,
                'rate_limit' => true
            ]
        ], true) // singleton
    );
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

// Registration with parameter injection
$container->set(AdminDashboardService::class,
    debug_suite_autowire_with_params(AdminDashboardService::class, [
        'menu_slug' => 'my-debug-suite'
    ])
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
$logger = debug_suite_resolve(LoggerInterface::class);

// Container builder for complex setups
$container = debug_suite_container_builder()
    ->enable_autowiring(true)
    ->add_definitions([
        'database.host' => debug_suite_value('localhost'),
        'database.port' => debug_suite_value(3306),
        DatabaseInterface::class => debug_suite_autowire_env(MySQLDatabase::class, [
            'development' => ['debug' => true],
            'production' => ['debug' => false]
        ]),
        'logger' => debug_suite_factory(fn() => new FileLogger()),
    ])
    ->build();

// Tagged service management
$notifiers = debug_suite_tagged('notifiers');
foreach ($notifiers as $notifier) {
    $notifier->send($message);
}

// Quick autowiring with parameters
$service = debug_suite_autowire_with_params(ApiService::class, [
    'api_key' => $_ENV['API_KEY'],
    'timeout' => 30,
    'debug' => WP_DEBUG
]);
```

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

```typescript
// log-viewer.tsx - Handles all display concerns
const LogViewer = ({ logs, loading, currentPage, perPage }: LogViewerProps) => {
    // Table rendering, log entry details, expandable rows, etc.
};

// log-controls.tsx - Handles all control concerns
const LogControls = ({
    filters,
    onFiltersChange,
    logFiles,
    selectedFile,
    onFileChange,
    pagination,
    actions
}: LogControlsProps) => {
    // File selection, filtering, search, pagination, export, clear actions
};
```

**Avoid: Excessive Micro-Components**

```typescript
// ❌ Too granular - creates maintenance overhead
-log -
    entry.tsx -
    log -
    table.tsx -
    log -
    file -
    selector.tsx -
    log -
    filters -
    bar.tsx -
    log -
    pagination.tsx -
    log -
    stats -
    footer.tsx;
```

#### Implementation Guidelines

- **Start with Consolidation**: When creating new features, begin with 2-3 consolidated components
- **Refactor Micro-Components**: When encountering micro-component patterns, consolidate them into substantial components
- **Component Size**: Each component should be 100-300 lines, focusing on cohesive functionality
- **Internal Organization**: Use internal helper functions, interfaces, and constants within component files
- **Testing Strategy**: Test consolidated components as complete units rather than individual micro-pieces
- **Documentation**: Provide comprehensive props documentation for consolidated components

### UI Component Standards

#### Dropdown/Select Components

**Always use the custom Combobox component** built on Headless UI for all dropdown selections:

```typescript
import Combobox from '@/components/ui/combobox';

// Basic usage
<Combobox
    options={[
        { value: 'option1', label: 'Option 1' },
        { value: 'option2', label: 'Option 2' }
    ]}
    value={selectedOption}
    onChange={setSelectedOption}
/>

// With additional features
<Combobox
    options={options}
    value={selected}
    onChange={setSelected}
    placeholder="Select an option..."
    label="Choose option"
    error={validationError}
    isDisabled={loading}
    className="min-w-[200px]"
    formatOptionLabel={(option) => (
        <div>
            <div className="font-medium">{option.label}</div>
            <div className="text-xs text-gray-500">{option.meta}</div>
        </div>
    )}
/>
```

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
- ❌ External libraries like `react-select` (removed from project)
- ❌ Custom dropdown implementations without accessibility
