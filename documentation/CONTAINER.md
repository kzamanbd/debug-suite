# Debug Suite - Container System (Legacy Documentation)

> **Note**: This documentation covers the legacy container system. For the current PSR-11 compliant DI system with PHP-DI compatibility, see [ENHANCED_DI_CONTAINER.md](ENHANCED_DI_CONTAINER.md).

This document explains the legacy dependency injection container system. **Please use the new PSR-11 compliant system for all new development.**

## Migration Notice

The container system has been completely replaced with a PSR-11 compliant implementation in the `DebugSuite\Core\DI` namespace. The new system provides:

- **PSR-11 Container Interface compliance**
- **PHP-DI style definitions and patterns**
- **Enhanced autowiring capabilities**
- **Better service lifecycle management**
- **Improved type safety and error handling**

See [ENHANCED_DI_CONTAINER.md](ENHANCED_DI_CONTAINER.md) for the current system documentation.

## Overview

The Debug Suite includes a comprehensive container system that provides:

- **Dependency Injection Container**: Manages class dependencies and resolves them automatically
- **Service Providers**: Register and configure services with the container
- **Service Manager**: Manages the lifecycle of service providers
- **Singleton Support**: Built-in singleton pattern for manager classes
- **Helper Functions**: Global functions for easy access to the container and services
- **Proper Initialization**: Services are initialized at the right time in the WordPress lifecycle

## Key Components

### 1. Container (`DebugSuite\Core\Container`)

The main dependency injection container that:

- Resolves class dependencies automatically (using reflection)
- Supports singleton and instance bindings
- Provides auto-resolution for class dependencies
- Manages service instances and bindings

#### Usage Example

```php
use DebugSuite\Core\Container;

$container = Container::get_instance();
$container->singleton(SomeClass::class, function(Container $c) {
    return new SomeClass();
});
$instance = $container->resolve(SomeClass::class);
```

### 2. Service Providers (`DebugSuite\Core\AbstractServiceProvider`)

Service providers register services with the container:

- Extend `AbstractServiceProvider` and implement `register()`
- List provided services in the `$provides` property
- Register services as singletons or instances
- Optionally implement `boot()` for post-registration logic

#### Example

```php
use DebugSuite\Core\AbstractServiceProvider;
use DebugSuite\Core\Container;

class ExampleServiceProvider extends AbstractServiceProvider {
    protected $provides = [ExampleService::class];
    public function register(Container $container): void {
        $container->singleton(ExampleService::class, fn($c) => new ExampleService());
        $this->mark_registered();
    }
}
```

### 3. Service Manager (`DebugSuite\Core\ServiceManager`)

Manages the registration and booting of service providers:

- Registers multiple providers
- Boots providers in correct order
- Prevents duplicate registrations
- Centrally registers hooks for all `Hookable` services

#### Example 1

```php
$service_manager = new ServiceManager($container);
$service_manager->register_providers([
    CoreServiceProvider::class,
    AdminServiceProvider::class,
]);
$service_manager->boot();
```

### 4. Singleton Trait (`DebugSuite\Core\Singleton`)

Provides singleton functionality for manager classes:

- Ensures a single instance
- Prevents cloning and serialization
- Provides `get_instance()` method

#### Example 2

```php
class MyManager {
    use \DebugSuite\Core\Singleton;
    // ...
}
$instance = MyManager::get_instance();
```

## Helper Functions

Located in `includes/helpers.php`:

- `debug_suite()`: Get the main Debug Suite instance
- `debug_suite_container()`: Get the container instance
- `debug_suite_resolve($service)`: Resolve a service from the container
- `debug_suite_service_manager()`: Get the service manager instance

## Service Initialization Lifecycle

1. **Container Creation**: Singleton `Container` instance is created
2. **Service Manager Setup**: `ServiceManager` is instantiated with the container
3. **Provider Registration**: All service providers are registered
4. **Provider Booting**: Providers are booted, services are resolved, and hooks are registered

## Best Practices

- Register all services via providers
- Use dependency injection for all dependencies
- Use the `Hookable` interface for classes that register WordPress hooks
- Use helper functions for easy access to services
