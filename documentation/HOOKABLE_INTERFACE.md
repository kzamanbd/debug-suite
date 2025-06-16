# Hookable Interface Pattern

The `Hookable` interface provides a standardized way to register WordPress hooks in the Debug Suite plugin. This pattern ensures consistent hook registration across all services and enables automatic hook registration through the dependency injection container.

## Interface Definition

```php
namespace DebugSuite\Interfaces;

/**
 * Interface Hookable.
 *
 * @since 1.0.0
 */
interface Hookable {
    /**
     * Register hooks for WordPress.
     *
     * @since 1.0.0
     * @return void
     */
    public function register_hooks(): void;
}
```

## Benefits

1. **Consistency**: All services that need WordPress hooks implement the same interface
2. **Automatic Registration**: Service providers and the ServiceManager automatically call `register_hooks()` for any service implementing `Hookable`
3. **Separation of Concerns**: Hook registration is separated from object construction
4. **Better Testing**: Services can be instantiated without registering hooks for unit testing
5. **Predictable Initialization**: Two-phase initialization (construct → register hooks)

## Implementation Example

### Service Class

```php
namespace DebugSuite\Admin;

use DebugSuite\Interfaces\Hookable;

class Admin implements Hookable {
    public function __construct() {
        // Constructor only sets up the instance
    }

    public function register_hooks(): void {
        add_action('admin_menu', [ $this, 'add_admin_menu' ]);
        // ...
    }
}
```

### Service Provider

```php
use DebugSuite\Core\AbstractServiceProvider;
use DebugSuite\Core\Container;
use DebugSuite\Admin\Admin;

class AdminServiceProvider extends AbstractServiceProvider {
    protected $provides = [ Admin::class ];
    public function register(Container $container): void {
        $container->singleton(Admin::class, fn($c) => new Admin());
        $this->mark_registered();
    }
}
```

## Automatic Hook Registration

The `ServiceManager` centrally registers hooks for all services implementing `Hookable` after all providers are booted.

## Migration from `init()` Pattern

**Before (using `init()` method):**

```php
class Assets {
    public function init(): void { /* ... */ }
}

// In service provider:
public function boot(Container $container): void {
    $assets = $container->resolve(Assets::class);
    $assets->init(); // Manual call required
}
```

**After (using `Hookable` interface):**

```php
class Assets implements Hookable {
    public function register_hooks(): void { /* ... */ }
}

// In service provider:
public function boot(Container $container): void {
    // No manual call needed; ServiceManager handles it
}
```

## Services Using Hookable Interface

Currently implemented:

- `DebugSuite\Core\Assets`
- `DebugSuite\Core\I18n`
- `DebugSuite\Admin\Admin`

## Testing Benefits

Services can be tested without WordPress hooks being registered:

```php
public function test_service_creation() {
    $service = new Admin();
    $service->register_hooks();
}
```
