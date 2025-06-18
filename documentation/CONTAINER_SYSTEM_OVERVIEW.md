# Debug Suite - Complete Container Documentation Index

This document serves as a comprehensive index to the Debug Suite Container System documentation, organizing all container-related features and implementation patterns.

## Current Implementation Status

The Debug Suite Container System is **fully implemented** with all core components operational:

✅ **PSR-11 Compliant Container** - Full interface compliance with proper exception handling  
✅ **Advanced Autowiring** - Parameter injection with environment awareness  
✅ **Service Providers** - Enhanced lifecycle management  
✅ **Helper Functions** - Global utilities for container access  
✅ **WordPress Integration** - Hookable interface and seamless WP integration  
✅ **Service Layer** - Business logic separation with ServiceResponse pattern  
✅ **REST API Integration** - Controllers with dependency injection  

## Documentation Structure

### Core Documentation Files

1. **[ENHANCED_DI_CONTAINER.md](ENHANCED_DI_CONTAINER.md)** - Main container system overview
2. **[CONTAINER_COMPLETE_GUIDE.md](CONTAINER_COMPLETE_GUIDE.md)** - Complete implementation guide
3. **[ENHANCED_AUTOWIRED_DEFINITION.md](ENHANCED_AUTOWIRED_DEFINITION.md)** - Advanced autowiring features
4. **[CONTAINER_FEATURE_USAGE.md](CONTAINER_FEATURE_USAGE.md)** - Current production usage patterns
5. **[SERVICE_LAYER_ARCHITECTURE.md](SERVICE_LAYER_ARCHITECTURE.md)** - Service layer implementation
6. **[PROVIDER.md](PROVIDER.md)** - Service provider patterns
7. **[HOOKABLE_INTERFACE.md](HOOKABLE_INTERFACE.md)** - WordPress hook integration

## Quick Reference

### Core Classes and Interfaces

```text
DebugSuite\Core\Container\
├── Container.php                    # Main PSR-11 container
├── ServiceManager.php               # Provider lifecycle management
├── ContainerBuilder.php             # Fluent container configuration
├── AbstractServiceProvider.php      # Base service provider
├── ServiceProviderInterface.php     # Provider contract
├── Definitions/
│   ├── AutowiredDefinition.php     # Advanced autowiring
│   ├── FactoryDefinition.php       # Factory pattern
│   ├── ValueDefinition.php         # Static values
│   ├── ConfigDefinition.php        # Environment config
│   ├── DecoratorDefinition.php     # Service decoration
│   └── DefinitionInterface.php     # Definition contract
└── Exceptions/
    ├── ContainerException.php      # General container errors
    └── NotFoundException.php       # Service not found errors
```

### Current Service Providers

```text
DebugSuite\Providers\
├── CoreServiceProvider.php         # Core WordPress integration
├── AdminServiceProvider.php        # Admin area services
├── FrontendServiceProvider.php     # Frontend services
└── AppServiceProvider.php          # Application services & APIs
```

### Implemented Services

```text
DebugSuite\Services\
├── FileLogsService.php             # Debug log operations
├── FileManagerService.php          # File system operations
└── SettingsService.php             # wp-config.php management

DebugSuite\API\
├── FileLogsController.php          # Debug logs REST API
├── FileManagerController.php       # File manager REST API
└── SettingsController.php          # Settings REST API
```

## Current Feature Usage Patterns

### 1. Service Registration (Production Ready)

```php
// Modern definition array approach (Current Implementation)
class AppServiceProvider extends AbstractServiceProvider {
    protected array $provides = [
        FileLogsService::class,
        FileManagerService::class,
        SettingsService::class,
    ];

    public function register(Container $container): void {
        $container->add_definitions([
            // Services with simple autowiring
            FileLogsService::class    => $container->object(FileLogsService::class),
            FileManagerService::class => $container->object(FileManagerService::class),
            SettingsService::class   => $container->object(SettingsService::class),

            // Controllers with dependency injection
            FileLogsController::class => $container->autowire(FileLogsController::class),
        ]);
    }
}
```

### 2. Advanced Autowiring (Implemented)

```php
// Environment-specific configuration
$container->set(DatabaseService::class,
    debug_suite_autowire_env(DatabaseService::class, [
        'development' => [
            'host' => 'localhost',
            'debug' => true,
            'pool_size' => 5
        ],
        'production' => [
            'host' => $_ENV['DB_HOST'],
            'debug' => false,
            'pool_size' => 20
        ]
    ])
);

// Dynamic parameter injection
$container->set(ApiService::class,
    $container->autowire(ApiService::class)
        ->constructor_parameter_callback('api_key', function($resolver) {
            $config = $resolver(ConfigService::class);
            return $config->get('api.key');
        })
        ->constructor_parameter('timeout', 30)
);
```

### 3. WordPress Integration (Active)

```php
// Services with automatic hook registration
use DebugSuite\Interfaces\Hookable;

class AdminMenuService implements Hookable {
    public function __construct(
        private SettingsService $settings,
        private string $menu_slug = 'debug-suite'
    ) {}
    
    public function register_hooks(): void {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'init_settings']);
    }
}

// Automatic registration and hook setup
$container->set(AdminMenuService::class, $container->autowire(AdminMenuService::class));
```

### 4. Helper Functions (Available)

```php
// Container access
$container = debug_suite_container();
$service = debug_suite_resolve(FileLogsService::class);
$manager = debug_suite_service_manager();

// Definition creation
$autowired = debug_suite_autowire(MyService::class);
$singleton = debug_suite_object(MyService::class);
$factory = debug_suite_factory(fn() => new Service());
$value = debug_suite_value(['config' => 'value']);

// Advanced patterns
$env_aware = debug_suite_autowire_env(Service::class, [
    'development' => ['debug' => true],
    'production' => ['debug' => false]
]);

$with_params = debug_suite_autowire_with_params(Service::class, [
    'param1' => 'value1',
    'param2' => 'value2'
], true);
```

## Implementation Examples by Use Case

### REST API Controller with Service Dependencies

```php
// Current implementation from codebase
class FileLogsController extends RestController {
    public function __construct(
        private FileLogsService $file_logs_service
    ) {
        parent::__construct();
    }
    
    public function get_logs(WP_REST_Request $request): WP_REST_Response|WP_Error {
        $result = $this->file_logs_service->get_log_entries([
            'limit' => $request->get_param('limit'),
            'search' => $request->get_param('search')
        ]);
        
        return $result->is_failure()
            ? new WP_Error($result->get_error_code(), $result->get_error_message())
            : rest_ensure_response($result->to_array());
    }
}
```

### Service with Business Logic

```php
// Current implementation pattern
class FileLogsService implements ServiceInterface {
    public function __construct(
        private ?string $log_file_path = null
    ) {
        $this->log_file_path = $log_file_path ?? WP_CONTENT_DIR . '/debug.log';
    }
    
    public function get_log_entries(array $options = []): ServiceResponse {
        // Business logic implementation
        if (!file_exists($this->log_file_path)) {
            return ServiceResponse::failure(
                __('Debug log file not found.', 'debug-suite'),
                'file_not_found'
            );
        }
        
        // Process and return data
        return ServiceResponse::success($entries);
    }
}
```

### Plugin Integration

```php
// Main plugin initialization (from debug-suite.php)
class DebugSuite {
    private Container $container;
    private ServiceManager $service_manager;

    public function __construct() {
        $this->init_container();
        $this->register_providers();
        $this->boot_services();
    }

    private function init_container(): void {
        $this->container = Container::get_instance();
        $this->service_manager = new ServiceManager($this->container);
        
        // Register core instances
        $this->container->instance('container', $this->container);
        $this->container->instance(ServiceManager::class, $this->service_manager);
    }

    private function register_providers(): void {
        $this->service_manager->register_providers([
            CoreServiceProvider::class,
            AdminServiceProvider::class,
            FrontendServiceProvider::class,
            AppServiceProvider::class,
        ]);
    }

    private function boot_services(): void {
        $this->service_manager->boot();
    }
}
```

## Development Guidelines

### When to Use Each Definition Type

- **AutowiredDefinition**: Classes with constructor dependencies
- **FactoryDefinition**: Complex object creation logic
- **ValueDefinition**: Static configuration values
- **ConfigDefinition**: Environment-specific configuration
- **DecoratorDefinition**: Service decoration patterns

### Service Provider Organization

- **CoreServiceProvider**: WordPress integration services (Assets, I18n)
- **AdminServiceProvider**: Admin-only services (Admin interface)
- **FrontendServiceProvider**: Public-facing services
- **AppServiceProvider**: Business logic services and API controllers

### Testing Strategy

```php
// Mock dependencies in tests
$container = debug_suite_container();
$container->instance(FileLogsService::class, $this->createMock(FileLogsService::class));

// Test service resolution
$service = debug_suite_resolve(MyService::class);
$this->assertInstanceOf(MyService::class, $service);
```

## Migration and Compatibility

### Legacy Support

- All existing helper functions remain functional
- Backward compatibility with previous registration methods
- Gradual migration path to modern patterns

### Future Enhancements

- Additional definition types as needed
- Extended WordPress integration patterns
- Performance optimizations for large-scale deployments

## Troubleshooting

### Common Issues

1. **Service Not Found**: Check provider registration and service listing in `$provides` array
2. **Parameter Resolution**: Use detailed error messages for parameter injection debugging
3. **Hook Registration**: Ensure services implement `Hookable` interface for automatic hooks
4. **Environment Detection**: Verify `WP_ENVIRONMENT_TYPE` or `WP_DEBUG` constants

### Debug Tools

```php
// Check if service exists
if (debug_suite_container()->has(MyService::class)) {
    $service = debug_suite_resolve(MyService::class);
}

// Get all registered services
$services = debug_suite_container()->get_services();

// Check provider registration
$providers = debug_suite_service_manager()->get_providers();
```

This documentation index provides a complete overview of the Debug Suite Container System's current implementation and usage patterns. All examples are derived from the actual codebase and represent production-ready patterns.
