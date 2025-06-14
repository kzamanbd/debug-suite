# Debug Suite - Container System

This document explains how to use the Debug Suite's dependency injection container system for managing classes and their dependencies.

## Overview

The Debug Suite includes a comprehensive container system that provides:

- **Dependency Injection Container**: Manages class dependencies and resolves them automatically
- **Service Providers**: Register and configure services with the container
- **Service Manager**: Manages the lifecycle of service providers
- **Singleton Support**: Built-in singleton pattern for manager classes
- **Helper Functions**: Global functions for easy access to the container
- **Proper Initialization**: Services are initialized at the right time in the WordPress lifecycle

## Key Components

### 1. Container (`DebugSuite\Core\Container`)

The main dependency injection container that:

- Resolves class dependencies automatically
- Supports singleton and instance bindings
- Provides auto-resolution using reflection
- Manages service instances

### 2. Service Providers (`DebugSuite\Core\AbstractServiceProvider`)

Service providers register services with the container:

- **CoreServiceProvider**: Registers core services (Assets, I18n) and initializes them properly
- **AdminServiceProvider**: Registers admin services (Admin, Settings) with dependency injection
- **FrontendServiceProvider**: Registers frontend services
- **ManagerServiceProvider**: Registers manager classes using the Singleton pattern

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

## Architecture Details

### Service Initialization Lifecycle

The container system follows a specific initialization lifecycle:

1. **Container Creation**: A singleton Container instance is created
2. **Service Manager Setup**: ServiceManager is instantiated with the container
3. **Provider Registration**: All service providers are registered with their bindings
4. **Provider Booting**: Service providers are booted, which:
   - Resolves services that need immediate initialization
   - Calls `init()` methods on services that require WordPress hook registration
   - Sets up service dependencies and relationships

### Proper Service Initialization

Services like Assets require proper initialization to register WordPress hooks:

```php
// In CoreServiceProvider::boot()
$assets = $container->resolve('assets');
$assets->init(); // This registers WordPress hooks at the right time
```

This pattern separates:

- **Construction**: Creating the service instance (no side effects)
- **Initialization**: Adding WordPress hooks and setting up integrations

## Usage Examples

### Accessing the Container

```php
// Get the main plugin instance
$debug_suite = debug_suite();

// Get the container
$container = debug_suite_container();

// Resolve a service
$admin = debug_suite_resolve(\DebugSuite\Admin\Admin::class);
$assets = debug_suite_resolve(\DebugSuite\Core\Assets::class);
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

### Creating a Service with Proper Initialization

```php
<?php

namespace DebugSuite\Core;

class MyService {
    private $initialized = false;
    
    public function __construct() {
        // Constructor should not have side effects
        // Don't add WordPress hooks here
    }
    
    public function init(): void {
        if ($this->initialized) {
            return;
        }
        
        // Add WordPress hooks and perform setup here
        add_action('init', [$this, 'on_init']);
        add_filter('my_filter', [$this, 'my_filter_handler']);
        
        $this->initialized = true;
    }
    
    public function on_init(): void {
        // WordPress initialization logic
    }
}
```

**Note**: For singleton services managed by the container, the `$initialized` check is often unnecessary because:

- The container ensures only one instance exists
- The `init()` method is typically called only once from the service provider
- WordPress hooks naturally handle duplicate registrations

### Creating a Service Provider with Initialization

```php
<?php

namespace DebugSuite\Providers;

use DebugSuite\Core\AbstractServiceProvider;
use DebugSuite\Core\Container;

class MyServiceProvider extends AbstractServiceProvider {
    
    protected $provides = array(
        MyService::class,
    );
    
    public function register(Container $container): void {
        $container->singleton(MyService::class, function($container) {
            return new MyService();
        });
        
        $this->mark_registered();
    }
    
    public function boot(Container $container): void {
        // Initialize the service properly
        $service = $container->resolve(MyService::class);
        $service->init(); // This adds WordPress hooks at the right time
        
        $this->mark_booted();
    }
}
```

### Registering Dependencies

```php
// In a service provider's register method
// Binding with dependencies
$container->bind(ServiceClass::class, function($container) {
    return new ServiceClass($container->resolve(DependencyClass::class));
});

// Singleton binding
$container->singleton(ServiceClass::class, ServiceClass::class);

// Instance binding
$container->instance(ServiceClass::class, $existing_object);
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

Manager classes can be easily integrated with the container using the Singleton trait:

### Example: Debug Provider Manager

```php
<?php

namespace DebugSuite\Managers;

use DebugSuite\Core\Singleton;
use DebugSuite\Interfaces\DebugProviderInterface;

class DebugProviderManager {
    use Singleton;
    
    private $providers = array();
    private $active_providers = array();
    
    protected function init(): void {
        $this->register_built_in_providers();
    }
    
    public function register_provider(string $name, DebugProviderInterface $provider): void {
        $this->providers[$name] = $provider;
    }
    
    public function activate_provider(string $name): bool {
        if (!isset($this->providers[$name])) {
            return false;
        }
        
        if (!isset($this->active_providers[$name])) {
            $this->active_providers[$name] = $this->providers[$name];
            $this->providers[$name]->activate();
        }
        
        return true;
    }
    
    public function get_debug_data(): array {
        $debug_data = array();
        
        foreach ($this->active_providers as $name => $provider) {
            $debug_data[$name] = $provider->get_debug_data();
        }
        
        return $debug_data;
    }
}

// Usage through container
$manager = debug_suite_resolve(DebugProviderManager::class);
$manager->register_provider('my_provider', $provider);
```

### Registering Manager in Service Provider

```php
<?php

namespace DebugSuite\Providers;

use DebugSuite\Core\AbstractServiceProvider;
use DebugSuite\Core\Container;
use DebugSuite\Managers\DebugProviderManager;

class ManagerServiceProvider extends AbstractServiceProvider {
    
    public function register(Container $container): void {
        $container->singleton(DebugProviderManager::class, function() {
            return DebugProviderManager::get_instance();
        });
        
        $this->mark_registered();
    }
    
    public function boot(Container $container): void {
        // Managers are automatically initialized when resolved
        $this->mark_booted();
    }
}
```

## Benefits

1. **Loose Coupling**: Classes depend on interfaces, not concrete implementations
2. **Easy Testing**: Dependencies can be easily mocked for testing
3. **Centralized Configuration**: All service bindings in one place
4. **Automatic Resolution**: Container resolves dependencies automatically
5. **Lifecycle Management**: Service providers manage initialization and booting
6. **Performance**: Singleton pattern ensures efficient resource usage
7. **WordPress Integration**: Proper timing of WordPress hook registration
8. **Clean Architecture**: Separation of construction and initialization concerns

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
6. **Separate Construction from Initialization**: Keep constructors side-effect free
7. **Initialize in Service Providers**: Use the `boot()` method to call `init()` on services
8. **Trust the Container**: Don't add unnecessary initialization checks for singleton services
9. **Use Class Names for Registration**: Register services using class names instead of string aliases for better type safety and IDE support
10. **Avoid Dual Registration**: Register each service only once using its class name to eliminate redundancy

### Simplified Service Registration Pattern

The Debug Suite uses a simplified approach where each service is registered once using its class name:

```php
// ✅ Good - Single registration per service
protected $provides = array(
    Assets::class,
    I18n::class,
);

public function register(Container $container): void {
    $container->singleton(Assets::class, function() {
        return new Assets();
    });
    
    $container->singleton(I18n::class, function() {
        return new I18n();
    });
}

// ✅ Resolve using class names
$assets = $container->resolve(Assets::class);
$i18n = $container->resolve(I18n::class);
```

**Avoid dual registration patterns:**

```php
// ❌ Avoid - Redundant dual registration
protected $provides = array(
    'assets',        // String alias
    Assets::class,   // Class name
);

public function register(Container $container): void {
    // Unnecessary - creates two bindings for same service
    $container->singleton('assets', function() {
        return new Assets();
    });
    
    $container->singleton(Assets::class, function($container) {
        return $container->resolve('assets'); // Redundant indirection
    });
}
```

### Benefits of Simplified Registration

The simplified service registration pattern provides several advantages:

1. **Reduced Code Duplication**: Eliminates 50% of container registrations by removing redundant string aliases
2. **Better Type Safety**: Using class names provides IDE autocomplete, type checking, and refactoring support
3. **Cleaner Architecture**: One canonical way to register and resolve each service
4. **Easier Maintenance**: Only one binding to maintain per service, reducing complexity
5. **Consistent Patterns**: All services follow the same registration and resolution pattern
6. **Performance**: Slight performance improvement by eliminating unnecessary resolver indirection
7. **Less Error-Prone**: Reduces risk of typos in string identifiers and inconsistent aliases

### Migration from Dual Registration

If you have existing service providers using the dual registration pattern, here's how to migrate:

**Before (Dual Registration):**

```php
class OldServiceProvider extends AbstractServiceProvider {
    protected $provides = array(
        'my_service',
        MyService::class,
    );
    
    public function register(Container $container): void {
        $container->singleton('my_service', function() {
            return new MyService();
        });
        
        $container->singleton(MyService::class, function($container) {
            return $container->resolve('my_service');
        });
    }
    
    public function boot(Container $container): void {
        $service = $container->resolve('my_service');
        $service->init();
    }
}
```

**After (Simplified Registration):**

```php
class NewServiceProvider extends AbstractServiceProvider {
    protected $provides = array(
        MyService::class,
    );
    
    public function register(Container $container): void {
        $container->singleton(MyService::class, function() {
            return new MyService();
        });
    }
    
    public function boot(Container $container): void {
        $service = $container->resolve(MyService::class);
        $service->init();
    }
}
```

**Update your resolution calls:**

```php
// Before
$service = debug_suite_resolve('my_service');

// After  
$service = debug_suite_resolve(MyService::class);
```
