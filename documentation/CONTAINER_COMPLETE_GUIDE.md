# Debug Suite Container System - Complete Implementation Guide

This document provides comprehensive documentation for the Debug Suite's PSR-11 compliant dependency injection container system with PHP-DI style definitions and advanced autowiring capabilities.

## Architecture Overview

The Debug Suite Container system is built around the `DebugSuite\Core\Container` namespace and provides:

- **PSR-11 Compliance**: Full implementation of `Psr\Container\ContainerInterface`
- **PHP-DI Compatibility**: Support for definition patterns similar to PHP-DI
- **Advanced Autowiring**: Reflection-based dependency injection with parameter overrides
- **Service Providers**: Organized service registration with lifecycle management
- **Helper Functions**: Global utility functions for container access and definition creation

## Core Components

### 1. Container (`DebugSuite\Core\Container\Container`)

The main PSR-11 compliant container with singleton pattern implementation.

**Key Features:**

- Implements `Psr\Container\ContainerInterface`
- Singleton pattern with `get_instance()` method
- Magic methods for property-style access
- Service tagging and resolution
- Autowiring with reflection
- Legacy binding support for backward compatibility

**Usage Examples:**

```php
use DebugSuite\Core\Container\Container;

// Get singleton instance
$container = Container::get_instance();

// PSR-11 methods
$service = $container->get(MyService::class);
$exists = $container->has(MyService::class);

// Enhanced methods
$container->singleton(MyService::class, fn() => new MyService());
$container->bind('config', ['debug' => true]);
$container->instance('logger', $loggerInstance);

// Magic property access
$service = $container->MyService;
$exists = isset($container->MyService);
```

### 2. Definition System

#### AutowiredDefinition

Advanced autowiring with multiple parameter injection strategies:

```php
use DebugSuite\Core\Container\Definitions\AutowiredDefinition;

$definition = new AutowiredDefinition(MyService::class, true); // singleton

// Static parameter overrides
$definition->constructor_parameter('config_path', '/etc/config.ini');

// Multiple parameters
$definition->constructor_parameters([
    'host' => 'localhost',
    'port' => 3306,
    'debug' => true
]);

// Dynamic parameter callbacks
$definition->constructor_parameter_callback('api_key', function($resolver) {
    $vault = $resolver(SecretVault::class);
    return $vault->get('api_key');
});

// Environment-specific parameters
$definition->environment_parameters('development', [
    'debug' => true,
    'cache_ttl' => 60
]);
$definition->environment_parameters('production', [
    'debug' => false,
    'cache_ttl' => 3600
]);
```

**Parameter Resolution Priority:**

1. Environment-specific parameters (highest)
2. Dynamic parameter callbacks
3. Static parameter overrides
4. Type-based dependency injection
5. Default parameter values
6. Container exception (if none match)

#### FactoryDefinition

Factory-based service creation:

```php
use DebugSuite\Core\Container\Definitions\FactoryDefinition;

$definition = new FactoryDefinition(function($resolver) {
    $config = $resolver('config');
    return new ComplexService($config);
}, true); // singleton
```

#### ValueDefinition

Static value injection:

```php
use DebugSuite\Core\Container\Definitions\ValueDefinition;

$definition = new ValueDefinition([
    'host' => 'localhost',
    'port' => 3306,
    'debug' => true
]);
```

#### ConfigDefinition

Environment-aware configuration:

```php
use DebugSuite\Core\Container\Definitions\ConfigDefinition;

$definition = new ConfigDefinition([
    'development' => ['api_url' => 'https://dev-api.com'],
    'production' => ['api_url' => 'https://api.com'],
    'testing' => ['api_url' => 'https://test-api.com']
], $default_config, true); // singleton
```

#### DecoratorDefinition

Service decoration pattern:

```php
use DebugSuite\Core\Container\Definitions\DecoratorDefinition;

$definition = new DecoratorDefinition(
    CachedEmailService::class,  // decorator
    EmailService::class,        // original service
    true                        // singleton
);
```

### 3. Service Providers (`DebugSuite\Core\Container\AbstractServiceProvider`)

Organized service registration with lifecycle management:

```php
use DebugSuite\Core\Container\AbstractServiceProvider;
use DebugSuite\Core\Container\Container;

class MyServiceProvider extends AbstractServiceProvider {
    
    protected array $provides = [
        DatabaseService::class,
        EmailService::class,
        LoggerService::class,
    ];
    
    public function register(Container $container): void {
        // Using definitions
        $container->set(DatabaseService::class, 
            $container->autowire(DatabaseService::class)
                ->environment_parameters('development', [
                    'host' => 'localhost',
                    'debug' => true
                ])
                ->environment_parameters('production', [
                    'host' => $_ENV['DB_HOST'],
                    'debug' => false
                ])
        );
        
        // Traditional singleton binding
        $container->singleton(EmailService::class, fn($c) => 
            new EmailService($c->get('email_config'))
        );
        
        // Value binding
        $container->bind('email_config', [
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587
        ]);
    }
    
    public function boot(Container $container): void {
        // Post-registration initialization
        $logger = $container->get(LoggerService::class);
        $logger->info('Services booted successfully');
    }
}
```

### 4. Service Manager (`DebugSuite\Core\Container\ServiceManager`)

Centralized service provider lifecycle management:

```php
use DebugSuite\Core\Container\ServiceManager;
use DebugSuite\Core\Container\Container;

$container = Container::get_instance();
$serviceManager = new ServiceManager($container);

// Register providers
$serviceManager->register_providers([
    CoreServiceProvider::class,
    AdminServiceProvider::class,
    FrontendServiceProvider::class,
]);

// Boot all providers
$serviceManager->boot();

// Check provider status
if ($serviceManager->has_provider(CoreServiceProvider::class)) {
    $provider = $serviceManager->get_provider(CoreServiceProvider::class);
}
```

### 5. Container Builder (`DebugSuite\Core\Container\ContainerBuilder`)

Fluent interface for container configuration:

```php
use DebugSuite\Core\Container\ContainerBuilder;

$container = (new ContainerBuilder())
    ->enable_autowiring(true)
    ->add_definitions([
        'database.host' => debug_suite_value('localhost'),
        'database.port' => debug_suite_value(3306),
        DatabaseInterface::class => debug_suite_autowire(MySQLDatabase::class),
        'logger' => debug_suite_factory(fn() => new Logger()),
        EmailService::class => debug_suite_autowire_env(EmailService::class, [
            'development' => ['debug' => true],
            'production' => ['debug' => false]
        ])
    ])
    ->build();
```

## Helper Functions

The system provides comprehensive helper functions for easy access:

### Container Access

```php
// Get main plugin instance
$plugin = debug_suite();

// Get container instance
$container = debug_suite_container();

// Resolve service
$service = debug_suite_resolve(MyService::class);

// Get service manager
$manager = debug_suite_service_manager();
```

### Definition Helpers

```php
// Autowired definition
$autowired = debug_suite_autowire(MyService::class);

// Factory definition
$factory = debug_suite_factory(fn() => new Service());

// Singleton factory
$singleton = debug_suite_singleton(fn() => new Service());

// Value definition
$value = debug_suite_value(['key' => 'value']);

// Object definition (autowired singleton)
$object = debug_suite_object(MyService::class);

// Configuration definition
$config = debug_suite_config([
    'development' => ['debug' => true],
    'production' => ['debug' => false]
]);

// Decorator definition
$decorator = debug_suite_decorate(
    CachedService::class,
    OriginalService::class,
    true // singleton
);
```

### Advanced Helpers

```php
// Autowiring with parameters
$definition = debug_suite_autowire_with_params(LoggerService::class, [
    'log_level' => 'debug',
    'log_file' => '/var/log/app.log'
], true); // singleton

// Environment-aware autowiring
$definition = debug_suite_autowire_env(DatabaseService::class, [
    'development' => ['host' => 'localhost', 'debug' => true],
    'production' => ['host' => 'prod-db.com', 'debug' => false]
], true); // singleton

// Tagged services
$tagged = debug_suite_tagged('event_listeners');

// Container builder
$builder = debug_suite_container_builder();
```

## Environment Detection

The system automatically detects the WordPress environment using:

1. `WP_ENVIRONMENT_TYPE` constant (WordPress 5.5+)
2. `WP_DEBUG` constant (fallback)
3. Default to 'production' if neither is set

**Supported Environments:**

- `development`
- `staging`
- `production`
- `testing`

## Hookable Interface

Services implementing `DebugSuite\Interfaces\Hookable` automatically register WordPress hooks:

```php
use DebugSuite\Interfaces\Hookable;

class MyService implements Hookable {
    
    public function register_hooks(): void {
        add_action('wp_loaded', [$this, 'on_wp_loaded']);
        add_filter('the_content', [$this, 'filter_content']);
    }
    
    public function on_wp_loaded(): void {
        // Hook implementation
    }
    
    public function filter_content(string $content): string {
        // Filter implementation
        return $content;
    }
}
```

## Service Tagging

Organize services by functionality using tags:

```php
// Tag services
$container->tag(EmailService::class, 'notifications');
$container->tag(SMSService::class, 'notifications');
$container->tag(PushService::class, 'notifications');

// Retrieve tagged services
$notificationServices = $container->tagged('notifications');

// Get tags for a service
$tags = $container->get_tags(EmailService::class);
```

## Error Handling

The system provides PSR-11 compliant exception handling:

```php
use DebugSuite\Core\Container\Exceptions\{ContainerException, NotFoundException};

try {
    $service = $container->get('non_existent_service');
} catch (NotFoundException $e) {
    // Service not found
    error_log("Service not found: " . $e->getMessage());
} catch (ContainerException $e) {
    // General container error
    error_log("Container error: " . $e->getMessage());
}
```

## Best Practices

1. **Use Service Providers**: Always register services via providers for organization
2. **Leverage Autowiring**: Use autowiring for clean dependency injection
3. **Environment Configuration**: Use environment-specific parameters for different setups
4. **Interface Binding**: Bind interfaces to implementations for flexibility
5. **Singleton Pattern**: Use singletons for stateless services
6. **Helper Functions**: Utilize helper functions for cleaner code
7. **Error Handling**: Always handle container exceptions appropriately

## Performance Considerations

- **Autowiring**: Uses reflection - consider explicit bindings for performance-critical code
- **Singleton Caching**: Singleton services are cached automatically
- **Lazy Loading**: Services are only created when needed
- **Container Building**: Done once during application bootstrap
- **Definition Resolution**: Optimized for minimal overhead

## Testing

The container system enhances testability:

```php
// Test with mock dependencies
$container = Container::get_instance();
$container->bind(DatabaseInterface::class, $mockDatabase);
$container->bind('config', ['test' => true]);

$service = $container->resolve(MyService::class);
// Service now uses mock dependencies
```

## Integration Examples

### Complete Service Setup

```php
// 1. Define service provider
class PaymentServiceProvider extends AbstractServiceProvider {
    protected array $provides = [PaymentService::class];
    
    public function register(Container $container): void {
        $container->set(PaymentService::class,
            debug_suite_autowire_env(PaymentService::class, [
                'development' => [
                    'api_endpoint' => 'https://sandbox.payment.com',
                    'timeout' => 30,
                    'debug' => true
                ],
                'production' => [
                    'api_endpoint' => 'https://api.payment.com',
                    'timeout' => 10,
                    'debug' => false
                ]
            ], true)
            ->constructor_parameter_callback('api_key', function($resolver) {
                $vault = $resolver(SecretVault::class);
                return $vault->get('payment.api_key');
            })
        );
    }
}

// 2. Register provider
debug_suite_service_manager()->register(PaymentServiceProvider::class);

// 3. Use service
$paymentService = debug_suite_resolve(PaymentService::class);
```

This comprehensive container system provides enterprise-grade dependency injection capabilities while maintaining full WordPress compatibility and ease of use.
