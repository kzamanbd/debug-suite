# Debug Providers Guide

> **DEPRECATED**: The Debug Provider system described in this document has been deprecated in favor of the PSR-11 DI Container system. Use the service provider pattern with the PSR-11 Container instead. This document is kept for historical purposes.

This document explains the legacy Debug Providers system in the Debug Suite plugin, which has been replaced by the PSR-11 compliant Dependency Injection container system.

## What are Debug Providers?

In the legacy architecture, Debug Providers were components that collected and provided specific types of debug information. Each provider focused on a particular aspect of WordPress, such as:

- PHP configuration
- WordPress constants
- Database queries
- Active plugins
- Theme information
- Server environment
- Custom functionality

## Current Architecture

The PSR-11 DI Container now handles all dependency management:

1. **PSR-11 Container**: Use `DebugSuite\Core\Container\Container` for dependency injection
2. **Service Providers**: Extend `DebugSuite\Core\Container\AbstractServiceProvider` for registering services
3. **Hookable Interface**: Services can implement `Hookable` if they need to register WordPress hooks

## Creating a Debug Service (Modern Approach)

### Step 1: Create a Service Class

Create a new class in the `includes/Services` directory:

```php
<?php

namespace DebugSuite\Services;

use DebugSuite\Core\ServiceResult;
use DebugSuite\Interfaces\ServiceInterface;
use DebugSuite\Interfaces\Hookable;

class ExampleDebugService implements ServiceInterface, Hookable {
    private string $name = 'example';
    private string $description = 'Example debug service.';

    public function get_debug_data(): ServiceResult {
        return ServiceResult::success([
            'example' => 'data'
        ]);
    }

    public function register_hooks(): void {
        // Register WordPress hooks if needed
    }
}
```

### Step 2: Register the Service with the DI Container

Register your service in a service provider's `register` method:

```php
use DebugSuite\Services\ExampleDebugService;

public function register(Container $container): void {
    $container->singleton(ExampleDebugService::class, fn() => new ExampleDebugService());
    
    // Register controller using the service
    $container->singleton(ExampleController::class, fn($c) => 
        new ExampleController($c->get(ExampleDebugService::class))
    );
}
```

### Step 3: Add Your Service Provider to the Plugin

In your plugin's main file (`debug-suite.php`), add your service provider to the list:

```php
$service_manager = new ServiceManager($container);
$service_manager->register_providers([
    CoreServiceProvider::class,
    AdminServiceProvider::class,
    FrontendServiceProvider::class,
    ExampleServiceProvider::class, // Add your service provider here
]);
```

## Best Practices with the New Architecture

1. **Service Layer Pattern**: Follow the service layer pattern for separation of concerns
2. **Return ServiceResult**: Services should return `ServiceResult` objects for consistent error handling
3. **PSR-11 Container**: Use the PSR-11 container for dependency injection
4. **Autowiring**: Take advantage of autowiring for automatic dependency resolution
5. **Configuration**: Accept configuration through constructor parameters for testability
6. **Documentation**: Fully document all public methods with PHPDoc
