# Hookable Interface Pattern

The `Hookable` interface provides a standardized way to register WordPress hooks in the Debug Suite plugin. This pattern ensures consistent hook registration across all services and enables automatic hook registration through the dependency injection container.

## Interface Definition

```php
interface Hookable {
    /**
     * Register hooks for WordPress.
     * This method will be called automatically to register the hooks.
     *
     * @return void
     */
    public function register_hooks(): void;
}
```

## Benefits

1. **Consistency**: All services that need WordPress hooks implement the same interface
2. **Automatic Registration**: Service providers automatically call `register_hooks()` for any service implementing `Hookable`
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
        // No WordPress hooks registered here
    }

    /**
     * Register hooks for WordPress.
     */
    public function register_hooks(): void {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
    }

    // ... other methods
}
```

### Service Provider

```php
class AdminServiceProvider extends AbstractServiceProvider {
    protected $provides = array(
        Admin::class,
    );

    public function register( Container $container ): void {
        $container->singleton(
            Admin::class,
            function ( Container $container ) {
                return new Admin();
            }
        );
    }

    public function boot( Container $container ): void {
        // Automatically registers hooks for all Hookable services
        $this->register_hookable_services( $container );
    }
}
```

## Automatic Hook Registration

The `AbstractServiceProvider` class provides a helper method that automatically registers hooks:

```php
protected function register_hookable_services( Container $container ): void {
    foreach ( $this->provides as $service ) {
        $instance = $container->resolve( $service );
        
        if ( $instance instanceof Hookable ) {
            $instance->register_hooks();
        }
    }
}
```

## Migration from `init()` Pattern

### Before (using `init()` method)

```php
class Assets {
    public function init(): void {
        add_action( 'init', [ $this, 'register_all_scripts' ] );
    }
}

// In service provider:
public function boot( Container $container ): void {
    $assets = $container->resolve( Assets::class );
    $assets->init(); // Manual call required
}
```

### After (using `Hookable` interface)

```php
class Assets implements Hookable {
    public function register_hooks(): void {
        add_action( 'init', [ $this, 'register_all_scripts' ] );
    }
}

// In service provider:
public function boot( Container $container ): void {
    $this->register_hookable_services( $container ); // Automatic!
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
    // No hooks registered yet, safe for unit testing
    
    // Hooks only registered when explicitly called:
    $service->register_hooks();
}
```
