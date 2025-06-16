# Debug Providers Guide

This document explains how to create and work with Debug Providers in the Debug Suite plugin.

## What are Debug Providers?

Debug Providers are components that collect and provide specific types of debug information. Each provider focuses on a particular aspect of WordPress, such as:

- PHP configuration
- WordPress constants
- Database queries
- Active plugins
- Theme information
- Server environment
- Custom functionality

## Provider Architecture

Debug providers follow a standardized structure:

1. **Interface**: All providers implement the `DebugProviderInterface`
2. **Base Class**: Most providers extend the `AbstractDebugProvider` base class
3. **Optional Interface**: Providers can also implement `Hookable` if they need to register WordPress hooks

## Creating a New Debug Provider

### Step 1: Create a Provider Class

Create a new class in the `includes/Providers` directory:

```php
<?php

namespace DebugSuite\Providers;

use DebugSuite\Interfaces\DebugProviderInterface;
use DebugSuite\Providers\AbstractDebugProvider;
use DebugSuite\Interfaces\Hookable;

class ExampleDebugProvider extends AbstractDebugProvider implements DebugProviderInterface, Hookable {
    protected readonly string $name = 'example';
    protected readonly string $description = 'Example debug provider.';

    public function init(): void {
        // Initialization logic
    }

    public function get_debug_data(): array {
        return [ 'example' => 'data' ];
    }

    public function register_hooks(): void {
        // Register WordPress hooks if needed
    }
}
```

### Step 2: Register the Provider

Register your provider with the Debug Provider Manager in your service provider's `register` method:

```php
use DebugSuite\Managers\DebugProviderManager;
use DebugSuite\Providers\ExampleDebugProvider;

public function register(Container $container): void {
    $container->singleton(ExampleDebugProvider::class, fn($c) => new ExampleDebugProvider());
    $manager = DebugProviderManager::get_instance();
    $manager->register_provider('example', $container->resolve(ExampleDebugProvider::class));
    $this->mark_registered();
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

## Key Methods to Implement

- `init()`: Initialize the provider
- `get_debug_data()`: Return an array of debug data
- `register_hooks()`: (optional, when implementing Hookable) Register any WordPress hooks needed by your provider

## Best Practices

1. **Performance**: Make sure your provider doesn't impact site performance, especially on production sites
2. **Data Structure**: Return structured data that can be easily displayed in the UI
3. **Security**: Be careful with sensitive data - don't expose passwords or private information
4. **Error Handling**: Use proper error handling to prevent your provider from breaking the site
