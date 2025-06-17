# DI Container Migration Guide

## Summary of Changes

The Debug Suite dependency injection system has been refactored to use the `DebugSuite\Core\Container` namespace for better organization and enhanced PSR-11/PHP-DI compatibility.

## Namespace Changes

### Old Namespace → New Namespace

| Old Class | New Class |
|-----------|-----------|
| `DebugSuite\Core\Container` | `DebugSuite\Core\Container\Container` |
| `DebugSuite\Core\ServiceManager` | `DebugSuite\Core\Container\ServiceManager` |
| `DebugSuite\Core\Exceptions\ContainerException` | `DebugSuite\Core\Container\Exceptions\ContainerException` |
| `DebugSuite\Core\Exceptions\NotFoundException` | `DebugSuite\Core\Container\Exceptions\NotFoundException` |
| `DebugSuite\Core\Definitions\*` | `DebugSuite\Core\Container\Definitions\*` |

## Required Updates

### 1. Service Providers

**Before:**
```php
use DebugSuite\Core\Container;

class MyServiceProvider extends AbstractServiceProvider {
    public function register(Container $container): void {
        // ...
    }
}
```

**After:**

```php
use DebugSuite\Core\Container\Container;

class MyServiceProvider extends AbstractServiceProvider {
    public function register(Container $container): void {
        // ...
    }
}
```

### 2. Main Plugin File

**Before:**
```php
use DebugSuite\Core\Container;
use DebugSuite\Core\ServiceManager;
```

**After:**

```php
use DebugSuite\Core\Container\Container;
use DebugSuite\Core\Container\ServiceManager;
```

### 3. Helper Functions Usage

**Before:**
```php
$container = debug_suite_container();
$service = debug_suite_resolve(MyService::class);
```

**After (still works, but new options available):**
```php
// Legacy helpers still work
$container = debug_suite_container();
$service = debug_suite_resolve(MyService::class);

// New DI-specific helpers
$container = debug_suite_di_container();
$service = debug_suite_di_resolve(MyService::class);
```

## New Features Available

### 1. Container Builder

```php
use DebugSuite\Core\Container\ContainerBuilder;

$container = (new ContainerBuilder())
    ->enable_autowiring(true)
    ->add_definitions([
        'config' => value(['debug' => true]),
        MyService::class => autowire(MyService::class),
    ])
    ->build();
```

### 2. PHP-DI Style Definitions

```php
use function Container\{create, factory, value, autowire, object};

$definitions = [
    'config' => value(['debug' => true]),
    MyService::class => create(MyService::class),
    'logger' => factory(fn() => new Logger()),
];
```

### 3. Enhanced Autowiring

```php
$container->set(ComplexService::class, 
    $container->autowire(ComplexService::class)
        ->constructor_parameter('config', ['debug' => true])
);
```

## Backward Compatibility

All existing helper functions remain functional:

- `debug_suite_container()` ✅ Still works
- `debug_suite_resolve()` ✅ Still works  
- `debug_suite_service_manager()` ✅ Still works

## Benefits of the Migration

### PSR-11 Compliance
- Implements `Psr\Container\ContainerInterface`
- Proper PSR-11 exceptions (`ContainerException`, `NotFoundException`)
- Standard container behavior

### PHP-DI Compatibility
- Definition system with Factory, Autowired, and Value definitions
- PHP-DI style helper functions
- Advanced autowiring capabilities

### Better Organization
- Clear namespace separation for Container components
- Modular design with focused responsibilities
- Enhanced documentation and type safety

### Performance Improvements
- Optimized service resolution
- Better caching mechanisms
- Lazy loading of services

## Testing the Migration

After updating your imports, test that everything works:

```php
// Test container functionality
$container = debug_suite_di_container();
$exists = $container->has(MyService::class);
$service = $container->get(MyService::class);

// Test service manager
$serviceManager = debug_suite()->get_service_manager();
$isBooted = $serviceManager->is_booted();
```

## Troubleshooting

### Common Issues

1. **Import Errors**: Update all `use` statements to new namespace
2. **Type Hints**: Update method parameter types to use new Container class
3. **Service Providers**: Ensure all providers import the new Container class

### Error Examples

**Error:**
```
Class 'DebugSuite\Core\Container' not found
```

**Solution:**

```php
// Change this
use DebugSuite\Core\Container;

// To this  
use DebugSuite\Core\Container\Container;
```

The migration maintains full backward compatibility while providing enhanced dependency injection capabilities following industry standards.
