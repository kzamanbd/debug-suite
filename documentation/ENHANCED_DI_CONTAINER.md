# Debug Suite - PSR-11 Compliant DI Container System

This document explains the PSR-11 compliant dependency injection container system with PHP-DI compatibility features that powers the Debug Suite WordPress plugin.

## Overview

The Debug Suite includes a comprehensive DI container system located in the `DebugSuite\Core\DI` namespace that provides:

- **Full PSR-11 Compliance**: Implements `Psr\Container\ContainerInterface` with proper exception handling
- **PHP-DI Style Definitions**: Support for autowiring, factory, and value definitions with PHP-DI patterns
- **Advanced Autowiring**: Automatic dependency resolution using reflection
- **Definition System**: Factory, Autowired, and Value definitions for flexible service configuration
- **Container Builder**: Fluent interface for container configuration and setup
- **Service Management**: Enhanced service provider system with lifecycle management
- **WordPress Integration**: Seamless integration with WordPress hooks and lifecycle events

## Key Components

### 1. Container (`DebugSuite\Core\DI\Container`)

The main PSR-11 compliant dependency injection container:

```php
use DebugSuite\Core\DI\Container;

$container = Container::get_instance();

// PSR-11 methods
$service = $container->get('MyService');
$exists = $container->has('MyService');

// Enhanced methods
$container->singleton(MyService::class, fn($c) => new MyService());
$container->bind('config', ['debug' => true]);
$container->instance('logger', $loggerInstance);
```

### 2. Container Builder (`DebugSuite\Core\DI\ContainerBuilder`)

Fluent interface for container configuration:

```php
use DebugSuite\Core\DI\ContainerBuilder;
use function DebugSuite\Core\DI\Definitions\{autowire, factory, value};

$container = (new ContainerBuilder())
    ->enable_autowiring(true)
    ->add_definitions([
        'database.host' => value('localhost'),
        'database.port' => value(3306),
        DatabaseInterface::class => autowire(MySQLDatabase::class),
        'logger' => factory(fn() => new Logger()),
    ])
    ->build();
```

### 3. Service Manager (`DebugSuite\Core\DI\ServiceManager`)

Enhanced service provider management:

```php
use DebugSuite\Core\DI\ServiceManager;

$serviceManager = new ServiceManager($container);
$serviceManager->register_providers([
    CoreServiceProvider::class,
    AdminServiceProvider::class,
]);
$serviceManager->boot();
```

### 4. Definition System

#### Autowired Definition

```php
use DebugSuite\Core\DI\Definitions\AutowiredDefinition;

$definition = new AutowiredDefinition(MyService::class, true); // singleton
$definition->constructor_parameter('config', ['debug' => true]);
```

#### Factory Definition

```php
use DebugSuite\Core\DI\Definitions\FactoryDefinition;

$definition = new FactoryDefinition(function($container) {
    return new ComplexService($container->get('dependency'));
}, true); // singleton
```

#### Value Definition

```php
use DebugSuite\Core\DI\Definitions\ValueDefinition;

$definition = new ValueDefinition(['host' => 'localhost', 'port' => 3306]);
```

## PHP-DI Compatibility

### Global Functions

The system provides PHP-DI compatible global functions:

```php
use function DI\{create, factory, value, autowire, object};

$definitions = [
    'config' => value(['debug' => true]),
    MyService::class => create(MyService::class),
    'factory_service' => factory(fn() => new Service()),
    DatabaseInterface::class => autowire(MySQLDatabase::class),
    'singleton_service' => object(SingletonService::class),
];
```

### Definition Helpers

```php
use function DebugSuite\Core\DI\Definitions\{factory, singleton, autowire, value, object};

$container->set('service', factory(fn() => new Service()));
$container->set('config', value(['key' => 'value']));
$container->set(MyClass::class, autowire(MyClass::class));
```

## PSR-11 Compliance

### Exceptions

```php
use DebugSuite\Core\DI\Exceptions\{ContainerException, NotFoundException};

try {
    $service = $container->get('non_existent_service');
} catch (NotFoundException $e) {
    // Service not found
} catch (ContainerException $e) {
    // General container error
}
```

### Interface Implementation

The container fully implements `Psr\Container\ContainerInterface`:

- `get(string $id): mixed` - Retrieve service by identifier
- `has(string $id): bool` - Check if service exists

## Service Providers with New DI System

### Updated Service Provider

```php
use DebugSuite\Core\AbstractServiceProvider;
use DebugSuite\Core\DI\Container;

class MyServiceProvider extends AbstractServiceProvider {
    protected $provides = [MyService::class];
    
    public function register(Container $container): void {
        $container->singleton(MyService::class, function(Container $c) {
            return new MyService($c->get('dependency'));
        });
        
        $this->mark_registered();
    }
    
    public function boot(Container $container): void {
        // Boot logic here
    }
}
```

## Advanced Features

### Autowiring with Parameter Injection

```php
$container->set(ComplexService::class, 
    $container->autowire(ComplexService::class)
        ->constructor_parameter('config', ['debug' => true])
        ->constructor_parameter('cache_enabled', false)
);
```

### Container Configuration

```php
$container->set_autowiring(true); // Enable/disable autowiring
$services = $container->get_services(); // Get all registered services
$enabled = $container->is_autowiring_enabled(); // Check autowiring status
```

### Magic Methods

```php
// Property-style access
$service = $container->MyService;
$exists = isset($container->MyService);
```

## Helper Functions

### Global DI Helpers

```php
// Get DI container
$container = debug_suite_di_container();

// Resolve from DI container  
$service = debug_suite_di_resolve(MyService::class);

// Create container builder
$builder = debug_suite_container_builder();

// Legacy compatibility
$container = debug_suite_container(); // Still works
$service = debug_suite_resolve(MyService::class); // Still works
```

## Migration Guide

### From Old Container

```php
// Old way
use DebugSuite\Core\Container;
$container = Container::get_instance();

// New way  
use DebugSuite\Core\DI\Container;
$container = Container::get_instance();
```

### Service Providers

```php
// Old import
use DebugSuite\Core\Container;

// New import
use DebugSuite\Core\DI\Container;
```

## Best Practices

1. **Use Dependency Injection**: Prefer constructor injection over service location
2. **Service Providers**: Register all services via providers
3. **Interface Binding**: Bind interfaces to implementations
4. **Singleton Pattern**: Use singletons for stateless services
5. **Definition System**: Use definitions for complex service creation
6. **PSR-11 Compliance**: Always use `get()` and `has()` methods when possible

## Performance Considerations

- Autowiring uses reflection - consider explicit bindings for performance-critical code
- Singleton services are cached automatically
- Container building is done once during application bootstrap
- Definition resolution is lazy - services are created only when needed

## Testing

The DI system enhances testability:

```php
// Test with mock dependencies
$container = new Container();
$container->bind(DatabaseInterface::class, $mockDatabase);
$service = $container->resolve(MyService::class);
```

This enhanced DI system provides enterprise-grade dependency injection capabilities while maintaining full backward compatibility and WordPress integration.
