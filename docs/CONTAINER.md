# Debug Suite - Container System

This document explains how to use the Debug Suite's dependency injection container system for managing classes and their dependencies.

## Overview

The Debug Suite now includes a comprehensive container system that provides:

- **Dependency Injection Container**: Manages class dependencies and resolves them automatically
- **Service Providers**: Register and configure services with the container
- **Service Manager**: Manages the lifecycle of service providers
- **Singleton Support**: Built-in singleton pattern for manager classes
- **Helper Functions**: Global functions for easy access to the container

## Key Components

### 1. Container (`DebugSuite\Core\Container`)

The main dependency injection container that:

- Resolves class dependencies automatically
- Supports singleton and instance bindings
- Provides auto-resolution using reflection
- Manages service instances

### 2. Service Providers (`DebugSuite\Core\AbstractServiceProvider`)

Service providers register services with the container:

- **CoreServiceProvider**: Registers core services (Assets, I18n)
- **AdminServiceProvider**: Registers admin services (Admin, Settings)
- **FrontendServiceProvider**: Registers frontend services
- **ManagerServiceProvider**: Registers manager classes

### 3. Service Manager (`DebugSuite\Core\ServiceManager`)

Manages the registration and booting of service providers:

- Registers multiple providers
- Boots providers in correct order
- Prevents duplicate registrations

### 4. Singleton Trait (`DebugSuite\Core\Singleton`)

Provides singleton functionality for manager classes:

- Ensures single instance
- Prevents cloning and serialization
- Provides `get_instance()` method

## Usage Examples

### Accessing the Container

```php
// Get the main plugin instance
$debug_suite = debug_suite();

// Get the container
$container = debug_suite_container();

// Resolve a service
$admin = debug_suite_resolve('admin');
$settings = debug_suite_resolve(DebugSuite\Admin\Settings::class);
```

### Creating a Manager Class

```php
<?php

namespace DebugSuite\Managers;

use DebugSuite\Core\Singleton;

class MyManager {
    use Singleton;
    
    protected function init(): void {
        // Initialize the manager
    }
    
    public function do_something() {
        // Manager functionality
    }
}

// Usage
$manager = MyManager::get_instance();
$manager->do_something();
```

### Creating a Service Provider

```php
<?php

namespace DebugSuite\Providers;

use DebugSuite\Core\AbstractServiceProvider;
use DebugSuite\Core\Container;

class MyServiceProvider extends AbstractServiceProvider {
    
    protected $provides = array(
        'my_service',
        MyService::class,
    );
    
    public function register(Container $container): void {
        $container->singleton('my_service', function($container) {
            return new MyService();
        });
        
        $this->mark_registered();
    }
    
    public function boot(Container $container): void {
        // Boot logic here
        $this->mark_booted();
    }
}
```

### Registering Dependencies

```php
// In a service provider's register method
$container->bind('service_name', function($container) {
    return new ServiceClass($container->resolve('dependency'));
});

// Singleton binding
$container->singleton('singleton_service', ServiceClass::class);

// Instance binding
$container->instance('existing_instance', $existing_object);
```

### Auto-Resolution

The container can automatically resolve dependencies:

```php
class AdminController {
    public function __construct(Settings $settings, Assets $assets) {
        // Dependencies will be automatically injected
    }
}

// The container will automatically resolve Settings and Assets
$controller = $container->resolve(AdminController::class);
```

## Manager Classes with Container

Manager classes can be easily integrated with the container:

### Example: Debug Provider Manager

```php
<?php

namespace DebugSuite\Managers;

use DebugSuite\Core\Singleton;

class DebugProviderManager {
    use Singleton;
    
    private $providers = array();
    
    protected function init(): void {
        $this->register_built_in_providers();
    }
    
    public function register_provider(string $name, DebugProviderInterface $provider): void {
        $this->providers[$name] = $provider;
    }
    
    // ... other methods
}

// Usage through container
$manager = debug_suite_resolve('debug_provider_manager');
$manager->register_provider('my_provider', $provider);
```

## Benefits

1. **Loose Coupling**: Classes depend on interfaces, not concrete implementations
2. **Easy Testing**: Dependencies can be easily mocked for testing
3. **Centralized Configuration**: All service bindings in one place
4. **Automatic Resolution**: Container resolves dependencies automatically
5. **Lifecycle Management**: Service providers manage initialization and booting
6. **Performance**: Singleton pattern ensures efficient resource usage

## Global Helper Functions

- `debug_suite()`: Get the main plugin instance
- `debug_suite_container()`: Get the container instance
- `debug_suite_resolve($service)`: Resolve a service from the container
- `debug_suite_service_manager()`: Get the service manager instance

## Best Practices

1. **Use Service Providers**: Group related services in providers
2. **Implement Interfaces**: Define contracts for your services
3. **Leverage Auto-Resolution**: Let the container resolve dependencies automatically
4. **Use Singletons Wisely**: Only for stateful services that should be shared
5. **Test Dependencies**: Mock dependencies in unit tests for better isolation
