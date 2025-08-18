<!-- Exactly 500 words. Keep concise to minimize prompt tokens while preserving enforceable rules. -->

# GitHub Copilot – Debug Suite Core Rules

Purpose: Enterprise WordPress plugin (PHP 8.1+) with DI container + Service Layer backend and React/TypeScript + Tailwind v4 frontend. Controllers stay thin; ALL business logic resides in services returning `ServiceResponse` objects.

## 1. Architecture

Services (in `includes/Services/*`) implement `ServiceInterface` and return `ServiceResponse::success()` or `::failure()`. Controllers (in `includes/API/*`) translate HTTP → service calls, perform capability checks, map error codes to HTTP (400 validation, 403 permission, 404 not_found, else 500). Dependency Injection: register business services in `AppServiceProvider` via `$container->object()` (singleton) or `$container->autowire()` (needs dependencies). Register REST controllers in `RestControllerProvider` with `autowire()`. List every service/controller in provider `$provides`. Hooks: only classes implementing `Hookable` define `register_hooks()`. ServiceManager auto-registers; never hook in constructors. Helper functions: `debug_suite()`, `debug_suite()->resolve(Foo::class)`, `debug_suite_service_manager()`, `debug_suite_date($ts)`.

## 2. PHP Standards

Target PHP 8.1; avoid 8.2+ exclusives. Use strict typing, union + nullable types, constructor promotion, match expressions, first-class callables. Naming: Classes PascalCase; functions/methods/variables snake_case; constants SCREAMING_SNAKE_CASE. Every class & public method requires PHPDoc including `@since DEBUG_SUITE_SINCE`. Services never throw for expected failures—always return `ServiceResponse::failure($message, $code, $context=[])` with short machine codes (`file_not_found`, `validation_error`). Constructors perform only lightweight assignment.

## 3. Service Implementation Checklist

1. Create class under correct namespace.
2. Implement `ServiceInterface` and add PHPDoc.
3. Validate + sanitize inputs first (paths, user data).
4. Perform domain logic; keep methods focused.
5. Return only arrays/scalars (no resources / open handles) in success payload.
6. Provide meaningful failure code + context.
7. Register in `AppServiceProvider` and add to `$provides`.
8. Add unit tests (and integration tests if surfaced via API).

## 4. REST Controller Pattern

Extend `RestController`; inject needed service(s) via constructor. Define `$rest_base`, use `register_rest_route` with args + `permission_callback` (check `manage_options` unless otherwise justified). Convert failures to `WP_Error` using error code mapping; on success call `rest_ensure_response($result->to_array())`. No business branching beyond translation/security.

## 5. Frontend Standards

Strict TypeScript; avoid micro-components—favor consolidated feature components (viewer, controls, optional skeleton). All user-visible strings localized via `__()` / `_x()` domain `debug-suite`. Icons: lucide-react ONLY. Styling: Tailwind v4 utilities; brand color is `primary`; conditional merging via local `classNames` util (not direct tailwind-merge). Dropdowns: always use `SimpleSelect` wrapper (never native `<select>`). Toasts: `toast.success()`, `toast.error()`. Debounce search inputs at 300ms with existing `useDebounce` hook.

## 6. Security & Performance

Sanitize and normalize file paths; block traversal. Capability checks belong in controllers. Do not fully load huge log files if tailing / windowing suffices. Cache repeat-expensive scans within a request (private property) where safe. Never expose absolute sensitive paths in public responses; include relative or redacted context.

## 7. Testing

Unit tests cover service success + at least one failure path (use custom assertions: `assert_service_result_success`, `assert_service_result_failure`). Integration tests cover REST endpoints (route availability, permission denial, success, failure mapping). Keep tests deterministic; mock filesystem/time when helpful. All new error codes must appear in at least one test.

## 8. Quality & Anti‑Patterns

Pre-PR gate: service/controller registered & in `$provides`; full typing; docs with `@since`; localized strings; lint (PHPCS + ESLint) + static analysis (PHPStan + TS) green; tests pass. Reject: business logic in controllers/views/hooks; throwing for normal validation; untyped params; alternative icon libs; raw inline SVG for standard icons; native `<select>` for feature UI; silent failures (always return structured failure). Keep file concise—expand only if a rule materially changes.

````md

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
````

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

```md
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

- **Good: Two Substantial Components**

- **Viewer Components**: Handle display, tables, content rendering, and detail views
- **Controls Components**: Handle filtering, search, pagination, actions, and form controls

- **Avoid: Excessive Micro-Components**

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

**Always use the custom SimpleSelect component** built on react-select for all dropdown selections.

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
import SimpleSelect from '@/components/base/select';

const options = [
    { value: 'option1', label: 'Option 1' },
    { value: 'option2', label: 'Option 2' },
];

<SimpleSelect
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

## Development Standard Operating Procedures (SOPs)

### SOP 1: Service Layer Architecture Pattern

**Established Pattern**: All business logic must be implemented using the Service Layer Pattern with ServiceResponse objects.

#### Service Implementation Standards

- **Service Location**: All services in `includes/Services/` namespace
- **Service Interface**: Implement `ServiceInterface` marker interface
- **Return Pattern**: Always return `ServiceResponse::success($data)` or `ServiceResponse::failure($message, $code, $context)`
- **Error Handling**: Never throw exceptions to controllers, always return ServiceResponse
- **Validation**: Perform all input validation in service methods
- **Dependencies**: Accept dependencies through constructor (constructor injection)

#### Current Service Classes

- **Core Debug Services**: `DebugLog/LogsService`, `DebugLog/WPLogReaderService`, `DebugLog/LogDiscoveryService`
- **Email Logging**: `EmailLog/EmailLogService` with wp_mail hooks integration
- **Configuration**: `SettingsService` for wp-config.php management
- **Dashboard**: `OverviewService` with service aggregation pattern

### SOP 2: Dependency Injection Container System

**Container Registration Pattern**: Use established container definition methods with service providers.

#### Service Registration Standards

```php
// Business Services (AppServiceProvider)
$container->add([
    ServiceName::class => $container->object(ServiceName::class),     // For simple singletons
    ComplexService::class => $container->autowire(ComplexService::class), // For dependency injection
]);

// REST Controllers (RestControllerProvider)
$container->add([
    ControllerName::class => $container->autowire(ControllerName::class), // Always autowire controllers
]);
```

#### Service Provider Organization

- **AppServiceProvider**: Business logic services, WordPress integration (Admin, Assets, HookManager)
- **RestControllerProvider**: REST API controllers with dependency injection
- **Separation**: Keep business services and controllers in separate providers

### SOP 3: REST API Controller Pattern

**Controller Implementation Standards**: Controllers handle only HTTP requests/responses, delegate business logic to services.

#### Controller Structure

```php
class ExampleController extends RestController {
    private ExampleService $service;
    protected $rest_base = 'example';

    public function __construct(ExampleService $service) {
        $this->service = $service;
    }

    public function register_routes(): void {
        // Route registration with validation
    }

    public function handle_request(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $result = $this->service->process_data($input);

        // Transform ServiceResponse to HTTP response
        if ($result->is_failure()) {
            return new WP_Error($result->get_error_code(), $result->get_error_message());
        }

        return rest_ensure_response($result->to_array());
    }
}
```

#### Current Controller Classes

- **LogsController**: Debug log operations (`/debug-suite/v1/logs/*`)
- **EmailLogController**: Email log management (`/debug-suite/v1/email-logs/*`)
- **SettingsController**: WordPress configuration (`/debug-suite/v1/settings`)
- **OverviewController**: Dashboard data aggregation (`/debug-suite/v1/overview`)

### SOP 4: WordPress Hooks Integration Pattern

**Hookable Interface Pattern**: Services implementing `Hookable` get automatic hook registration.

#### Hook Registration Standards

```php
class EmailLogService implements ServiceInterface, Hookable {
    public function register_hooks(): void {
        add_action('wp_mail', [$this, 'capture_email_data']);
        add_action('wp_mail_succeeded', [$this, 'log_email_success']);
        add_action('wp_mail_failed', [$this, 'log_email_failure']);
    }
}
```

#### Automatic Hook Registration

- **ServiceManager**: Automatically calls `register_hooks()` after provider booting
- **No Manual Registration**: Avoid manual hook registration in constructors
- **Separation**: Keep hook logic separate from business logic

### SOP 5: Frontend React/TypeScript Architecture

**Component Consolidation Pattern**: Prefer substantial components over micro-components following the 2-3 component rule.

#### Component Organization Standards

```typescript
// Feature Structure (e.g., debug-log/, email-log/)
src/pages/[feature-name]/
├── index.tsx                 // Main page component (100-300 lines)
├── types.ts                  // TypeScript interfaces
├── hooks.ts                  // Custom hooks (useEmailLogEntries, useLogFiltering)
├── constants.ts              // Feature constants
└── components/
    ├── index.ts              // Barrel exports
    ├── feature-viewer.tsx    // Display component (table, details, formatting)
    ├── feature-controls.tsx  // Controls component (filters, search, actions)
    └── feature-skeleton.tsx  // Loading states
```

#### Reusable Component Standards

- **Base Components**: `src/components/base/` for reusable UI (Button, SimpleSelect, Toast, Modal)
- **SimpleSelect**: Always use for dropdowns (react-select wrapper with accessibility)
- **Toast System**: Use `toast.success()`, `toast.error()` for user feedback
- **Lucide Icons**: Only icon library allowed (`import { Icon } from 'lucide-react'`)
- **Tailwind Primary**: Always use `primary` color as brand color

### SOP 6: Model-Based Database Operations

**ActiveRecord Pattern**: Use model classes for database operations with WordPress wpdb integration.

#### Model Implementation Standards

```php
class EmailLog extends BaseModel {
    protected static string $table = 'email_logs';
    protected static string $primary_key = 'id';
    protected static array $fillable = ['to_email', 'subject', 'message', 'status'];

    // Static methods for queries
    public static function get_filtered(array $filters): array
    public static function count_filtered(array $filters): int
    public static function get_statistics(): array
}
```

#### Current Models

- **EmailLog**: Email logging with filtering, statistics, bulk operations
- **BaseModel**: Abstract base with common CRUD operations

### SOP 7: Client-Side Data Management

**Custom Hooks Pattern**: Implement data management with custom hooks for consistent state handling.

#### Data Hook Standards

```typescript
// Example: useEmailLogEntries hook
export function useEmailLogEntries() {
    const [entries, setEntries] = useState<EmailLogEntry[]>([]);
    const [filters, setFilters] = useState<EmailLogFilters>({});
    const [loading, setLoading] = useState(false);

    // Client-side filtering with debounced search
    const filteredEntries = useMemo(() => {
        return entries.filter(/* filtering logic */);
    }, [entries, filters]);

    return {
        entries: filteredEntries,
        filters,
        updateFilters,
        loading,
        refetch,
        selectedItems,
        onSelectAll,
        onSelectItem,
        paginationInfo
    };
}
```

#### Current Hook Implementations

- **useEmailLogEntries**: Email log data management with filtering and pagination
- **useEmailLogActions**: Bulk actions and individual item operations
- **useLogEntries**: Debug log data management with infinite scroll
- **useDebounce**: 300ms debounced search for real-time filtering

### SOP 8: Testing Architecture Standards

**Comprehensive Testing Strategy**: Separate unit and integration tests with proper WordPress integration.

#### Test Organization

```php
// Unit Tests (tests/Unit/) - No WordPress dependencies
class ServiceTest extends TestCase {
    public function test_service_success(): void {
        $result = $this->service->process_data($input);
        $this->assert_service_result_success($result);
    }
}

// Integration Tests (tests/Integration/) - With WordPress
class ControllerTest extends DebugSuiteTestCase {
    public function test_api_endpoint(): void {
        $request = new WP_REST_Request('GET', '/debug-suite/v1/endpoint');
        $response = rest_get_server()->dispatch($request);
        $this->assertEquals(200, $response->get_status());
    }
}
```

#### Test Helpers and Utilities

- **TestCase**: Base unit test class with assertion helpers
- **DebugSuiteTestCase**: Integration test base with WordPress setup
- **MockFactory**: Test fixture creation utilities
- **Custom Assertions**: `assert_service_result_success()`, `assert_service_result_failure()`

### SOP 9: CSS and Styling Standards

**Tailwind CSS v4 with Primary Color System**: Consistent design system using utility-first approach.

#### Styling Standards

```css
/* Primary Color System (src/index.css) */
@theme {
    --color-primary: #6366f1; /* Indigo-500 base */
    --color-primary-50: #eef2ff;
    /* ... full indigo scale ... */
}

/* Component Classes */
.debug-suite-root-app {
    @import 'tailwindcss/preflight.css' layer(base) important;
    @import 'tailwindcss/utilities.css' layer(utilities) important;
}
```

#### Design System Guidelines

- **Primary Color**: Always use `primary` for brand elements (buttons, links, highlights)
- **Conditional Classes**: Use `classNames` utility from `@/utils` for conditional styling
- **Responsive Design**: Apply breakpoint utilities (sm:, md:, lg:, xl:)
- **Component Consistency**: Follow established patterns in base components

### SOP 10: Container Helper Functions Usage

**Global Access Pattern**: Use established helper functions for service resolution and container access.

#### Helper Function Standards

```php
// Service Resolution
$service = debug_suite()->resolve(ServiceName::class);

// Container Access
$container = debug_suite()->container();

// Service Manager Access
$service_manager = debug_suite_service_manager();

// Main Plugin Instance
$plugin = debug_suite();

// Date Formatting Utility
$formatted_date = debug_suite_date($timestamp);
```

#### Helper Function Implementation

- **debug_suite()**: Main plugin instance with container access
- **Service Resolution**: Direct service retrieval with type safety
- **Container Operations**: Full container functionality through helpers

## Current Project Organization

### Core Service Providers

The project follows a clean service provider architecture:

- **AppServiceProvider**: Registers business logic services, WordPress integration (Admin, Assets, HookManager)
- **RestControllerProvider**: Registers REST API controllers with dependency injection

### Service Layer Implementation

All business logic is implemented in the `includes/Services/` directory:

- **DebugLog/** - Debug log related services
  - `LogsService` - Debug log operations with service delegation pattern
  - `WPLogReaderService` - Advanced log parsing with stack trace detection
  - `LogDiscoveryService` - Log file discovery across multiple locations
- **EmailLog/** - Email logging services
  - `EmailLogService` - wp_mail integration with Hookable interface
- **SettingsService** - wp-config.php management with validation
- **OverviewService** - Dashboard overview with service aggregation

### REST API Controllers

All API endpoints are handled by controllers in `includes/API/`:

- `LogsController` - Debug log API endpoints (`/debug-suite/v1/logs/*`)
- `EmailLogController` - Email log management (`/debug-suite/v1/email-logs/*`)
- `SettingsController` - Settings management API endpoints (`/debug-suite/v1/settings`)
- `OverviewController` - Dashboard overview API endpoints (`/debug-suite/v1/overview`)

### Model Layer

Database operations handled by model classes in `includes/Models/`:

- **EmailLog** - Email log database operations with filtering and statistics
- **BaseModel** - Abstract base model with common CRUD operations

### Frontend Architecture

React/TypeScript components following consolidation pattern:

- **Pages**: Feature-specific pages (`debug-log/`, `email-log/`, `overview/`)
- **Components**: Reusable UI components (`base/`, `editor/`)
- **Hooks**: Custom data management hooks (`use-entries.ts`, `use-actions.ts`)
- **Utils**: Utility functions and helpers

### Testing Infrastructure

- **Unit Tests**: `tests/Unit/` - Isolated component testing without WordPress
- **Integration Tests**: `tests/Integration/` - Full WordPress integration testing
- **Test Helpers**: `tests/Helpers/` with base classes and utilities
- **Coverage Reports**: `tests/coverage/` - Code coverage analysis
