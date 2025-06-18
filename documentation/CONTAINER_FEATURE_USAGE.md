# Debug Suite Container System - Current Feature Usage Patterns

This document provides practical usage patterns for the Debug Suite Container System based on the current implementation. All examples are derived from the actual codebase and represent production-ready patterns.

## Current Implementation Overview

The Debug Suite Container System has been fully implemented with the following components:

### Core Components

- **Container**: PSR-11 compliant dependency injection container
- **Definitions**: AutowiredDefinition, FactoryDefinition, ValueDefinition, ConfigDefinition, DecoratorDefinition
- **Service Manager**: Provider lifecycle management with automatic hook registration
- **Container Builder**: Fluent interface for container configuration
- **Helper Functions**: Global utilities for easy container access

### Implemented Services

- **FileLogsService**: Debug log file operations
- **FileManagerService**: File system operations
- **SettingsService**: wp-config.php management
- **API Controllers**: REST API endpoints with dependency injection
        $container->set(DatabaseService::class, $container->object(DatabaseService::class));
    }
}

```md

### Advanced Service Configuration

```php
public function register(Container $container): void {
    // Environment-aware service
    $container->set(EmailService::class, 
        debug_suite_autowire_env(EmailService::class, [
            'development' => [
                'smtp_host' => 'localhost',
                'debug' => true,
                'queue_enabled' => false
            ],
            'production' => [
                'smtp_host' => 'smtp.mailgun.org',
                'debug' => false,
                'queue_enabled' => true
            ]
        ], true)
    );
    
    // Service with dynamic parameters
    $container->set(PaymentService::class,
        $container->autowire(PaymentService::class)
            ->constructor_parameter_callback('api_key', function($resolver) {
                return get_option('payment_api_key', '');
            })
            ->constructor_parameter('timeout', 30)
    );
}
```

## Common Usage Patterns

### 1. Service with Configuration

```php
class ApiService {
    public function __construct(
        private string $base_url,
        private string $api_key,
        private int $timeout = 30,
        private bool $debug = false
    ) {}
}

// Registration
$container->set(ApiService::class,
    debug_suite_autowire_with_params(ApiService::class, [
        'base_url' => 'https://api.example.com',
        'api_key' => $_ENV['API_KEY'],
        'timeout' => 60
    ])
    ->environment_parameters('development', ['debug' => true])
    ->environment_parameters('production', ['debug' => false])
);
```

### 2. Factory Pattern

```php
$container->set(ComplexService::class,
    debug_suite_factory(function($container) {
        $config = $container->get('app_config');
        $logger = $container->get(LoggerInterface::class);
        
        return new ComplexService($config, $logger);
    })
);
```

### 3. Interface Binding

```php
// Bind interface to implementation
$container->set(LoggerInterface::class, 
    $container->autowire(FileLogger::class)
        ->constructor_parameter('log_path', WP_CONTENT_DIR . '/debug.log')
);

// Service that depends on interface
$container->set(UserService::class, $container->autowire(UserService::class));
// UserService constructor: public function __construct(LoggerInterface $logger)
```

### 4. Service Decoration

```php
// Original service
$container->set(EmailService::class, $container->autowire(EmailService::class));

// Decorated service with caching
$container->set(CachedEmailService::class,
    debug_suite_decorate(CachedEmailService::class, EmailService::class, true)
);
```

### 5. Tagged Services

```php
// Register multiple notification services
$container->set(EmailNotifier::class, $container->autowire(EmailNotifier::class));
$container->set(SMSNotifier::class, $container->autowire(SMSNotifier::class));
$container->set(PushNotifier::class, $container->autowire(PushNotifier::class));

// Tag them
$container->tag(EmailNotifier::class, 'notifiers');
$container->tag(SMSNotifier::class, 'notifiers');
$container->tag(PushNotifier::class, 'notifiers');

// Use tagged services
$notifiers = debug_suite_tagged('notifiers');
foreach ($notifiers as $notifier) {
    $notifier->send($message);
}
```

## WordPress Integration Patterns

### 1. Admin Services with Hooks

```php
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
    
    public function add_admin_menu(): void {
        add_menu_page(
            'Debug Suite',
            'Debug Suite',
            'manage_options',
            $this->menu_slug,
            [$this, 'render_page']
        );
    }
}

// Registration
$container->set(AdminMenuService::class,
    debug_suite_autowire_with_params(AdminMenuService::class, [
        'menu_slug' => 'my-debug-suite'
    ])
);
```

### 2. REST API Controllers

```php
class DebugLogsController extends RestController {
    public function __construct(
        private FileLogsService $logs_service,
        private SecurityService $security
    ) {}
    
    public function register_routes(): void {
        register_rest_route($this->namespace, '/logs', [
            'methods' => 'GET',
            'callback' => [$this, 'get_logs'],
            'permission_callback' => [$this->security, 'can_view_logs'],
        ]);
    }
}

// Registration with dependency injection
$container->set(DebugLogsController::class, $container->autowire(DebugLogsController::class));
```

### 3. Scheduled Tasks

```php
class LogCleanupTask implements Hookable {
    public function __construct(
        private FileLogsService $logs_service,
        private int $retention_days = 30
    ) {}
    
    public function register_hooks(): void {
        add_action('debug_suite_cleanup_logs', [$this, 'cleanup_old_logs']);
        
        if (!wp_next_scheduled('debug_suite_cleanup_logs')) {
            wp_schedule_event(time(), 'daily', 'debug_suite_cleanup_logs');
        }
    }
    
    public function cleanup_old_logs(): void {
        $this->logs_service->cleanup_logs($this->retention_days);
    }
}

// Registration with environment-specific retention
$container->set(LogCleanupTask::class,
    debug_suite_autowire_env(LogCleanupTask::class, [
        'development' => ['retention_days' => 7],
        'staging' => ['retention_days' => 14],
        'production' => ['retention_days' => 30]
    ])
);
```

## Environment-Specific Configuration

### Development Environment

```php
$container->set(DatabaseService::class,
    debug_suite_autowire_env(DatabaseService::class, [
        'development' => [
            'host' => 'localhost',
            'debug_queries' => true,
            'slow_query_log' => true,
            'connection_pool_size' => 5
        ],
        'production' => [
            'host' => $_ENV['DB_HOST'],
            'debug_queries' => false,
            'slow_query_log' => false,
            'connection_pool_size' => 20
        ]
    ])
);
```

### Configuration from WordPress Options

```php
$container->set(CacheService::class,
    $container->autowire(CacheService::class)
        ->constructor_parameter_callback('cache_ttl', function() {
            return (int) get_option('debug_suite_cache_ttl', 3600);
        })
        ->constructor_parameter_callback('cache_prefix', function() {
            return get_option('debug_suite_cache_prefix', 'ds_');
        })
);
```

## Testing Patterns

### Mock Dependencies

```php
// In test setup
$container = Container::get_instance();

// Mock database service
$mockDatabase = $this->createMock(DatabaseInterface::class);
$container->instance(DatabaseInterface::class, $mockDatabase);

// Mock configuration
$container->bind('test_config', [
    'api_endpoint' => 'https://test-api.com',
    'debug' => true
]);

// Service under test will receive mocked dependencies
$service = $container->get(MyService::class);
```

### Test-Specific Services

```php
// Test service provider
class TestServiceProvider extends AbstractServiceProvider {
    protected array $provides = [
        DatabaseInterface::class,
        CacheInterface::class,
    ];
    
    public function register(Container $container): void {
        $container->instance(DatabaseInterface::class, new InMemoryDatabase());
        $container->instance(CacheInterface::class, new ArrayCache());
    }
}

// In test setup
debug_suite_service_manager()->register(TestServiceProvider::class);
```

## Performance Optimization

### Lazy Loading

```php
// Service is only created when first accessed
$container->set(ExpensiveService::class,
    debug_suite_factory(function($container) {
        // Heavy initialization only happens when needed
        return new ExpensiveService($container->get('heavy_dependency'));
    })
);
```

### Singleton Services

```php
// Ensure stateless services are singletons
$container->set(UtilityService::class, $container->object(UtilityService::class));
$container->set(ValidatorService::class, $container->object(ValidatorService::class));
$container->set(FormatterService::class, $container->object(FormatterService::class));
```

## Error Handling

### Service Resolution with Fallbacks

```php
try {
    $service = debug_suite_resolve(OptionalService::class);
} catch (NotFoundException $e) {
    // Use fallback service
    $service = debug_suite_resolve(DefaultService::class);
}
```

### Conditional Service Registration

```php
public function register(Container $container): void {
    // Only register if feature is enabled
    if (get_option('debug_suite_feature_enabled', false)) {
        $container->set(FeatureService::class, $container->autowire(FeatureService::class));
    }
    
    // Register development-only services
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $container->set(DebugToolsService::class, $container->autowire(DebugToolsService::class));
    }
}
```

## Best Practices Summary

1. **Use Environment Configuration**: Leverage environment-specific parameters for different setups
2. **Implement Hookable**: Use the Hookable interface for automatic WordPress hook registration
3. **Prefer Interfaces**: Bind interfaces to implementations for flexibility
4. **Singleton Stateless Services**: Use singletons for services without state
5. **Factory for Complex Setup**: Use factories for services requiring complex initialization
6. **Tag Related Services**: Group related services with tags for easier management
7. **Test with Mocks**: Use container binding for clean testing with mocked dependencies
8. **Handle Missing Services**: Always handle NotFoundException appropriately
9. **Service Providers Organization**: Organize services into logical providers
10. **Helper Functions**: Utilize helper functions for cleaner, more readable code

This feature usage guide provides practical patterns for effectively using the Debug Suite's advanced container system in real-world WordPress development scenarios.
