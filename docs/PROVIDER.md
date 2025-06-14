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

declare(strict_types=1);

/**
 * Example Debug Provider.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Providers;

use DebugSuite\Interfaces\Hookable;

/**
 * Example Debug Provider.
 *
 * This is an example implementation of a Debug Provider that shows
 * how to properly extend the AbstractDebugProvider class and implement
 * the Hookable interface.
 *
 * @since 1.0.0
 */
class ExampleDebugProvider extends AbstractDebugProvider implements Hookable {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        parent::__construct();
        
        $this->name        = 'Example Provider';
        $this->description = 'An example debug provider implementation.';
    }

    /**
     * Initialize the debug provider.
     *
     * This method is called when the provider is activated.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function init(): void {
        // Initialize your provider
        // Set up any necessary configuration
    }

    /**
     * Register hooks for WordPress.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_hooks(): void {
        // Register any WordPress hooks needed by this provider
        \add_action('init', [$this, 'example_hook_callback']);
    }

    /**
     * Example hook callback.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function example_hook_callback(): void {
        // Hook callback implementation
        if (!$this->is_enabled()) {
            return;
        }
        
        // Perform provider functionality
    }

    /**
     * Get debug data from this provider.
     *
     * @since 1.0.0
     *
     * @return array<string, mixed> The debug data.
     */
    public function get_debug_data(): array {
        if (!$this->is_enabled()) {
            return [];
        }
        
        // Collect and return debug data
        return [
            'example_key' => 'example_value',
            'timestamp'   => time(),
            'nested_data' => [
                'key1' => 'value1',
                'key2' => 'value2',
            ],
        ];
    }
}
```

### Step 2: Register the Provider

Register your provider with the Debug Provider Manager in your service provider's `register` method:

```php
<?php

declare(strict_types=1);

/**
 * Example Service Provider.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Providers;

use DebugSuite\Core\AbstractServiceProvider;
use DebugSuite\Core\Container;
use DebugSuite\Managers\DebugProviderManager;

/**
 * Example Service Provider.
 *
 * @since 1.0.0
 */
class ExampleServiceProvider extends AbstractServiceProvider {

    /**
     * Register services with the container.
     *
     * @since 1.0.0
     *
     * @param Container $container The container instance.
     *
     * @return void
     */
    public function register(Container $container): void {
        // Register the debug provider
        $container->singleton(ExampleDebugProvider::class);
        
        // Add the provider to the manager
        $container->extend(DebugProviderManager::class, function (DebugProviderManager $manager, Container $container) {
            $manager->add_provider($container->get(ExampleDebugProvider::class));
            return $manager;
        });
    }

    /**
     * Boot services after all providers have been registered.
     *
     * @since 1.0.0
     *
     * @param Container $container The container instance.
     *
     * @return void
     */
    public function boot(Container $container): void {
        // Any boot logic for the service provider
    }
}
```

### Step 3: Add Your Service Provider to the Plugin

In your plugin's main file (`debug-suite.php`), add your service provider to the list:

```php
// Register service providers
$service_manager = new ServiceManager($container);
$service_manager->register_providers([
    CoreServiceProvider::class,
    AdminServiceProvider::class,
    FrontendServiceProvider::class,
    ManagerServiceProvider::class,
    ExampleServiceProvider::class, // Add your service provider here
]);
```

## Key Methods to Implement

### `init()`

Initialize the provider. This is called when the provider is activated.

### `get_debug_data()`

Return an array of debug data. This is the main method that provides the debugging information to the UI.

### `register_hooks()` (optional, when implementing Hookable)

Register any WordPress hooks needed by your provider.

## Best Practices

1. **Performance**: Make sure your provider doesn't impact site performance, especially on production sites
2. **Data Structure**: Return structured data that can be easily displayed in the UI
3. **Security**: Be careful with sensitive data - don't expose passwords or private information
4. **Error Handling**: Use proper error handling to prevent your provider from breaking the site
5. **Settings**: Allow users to enable/disable your provider through the UI
6. **Documentation**: Document what your provider does and how to use it

## Common Provider Categories

- **System Info**: PHP version, PHP extensions, server software, etc.
- **WordPress Info**: WP version, active plugins, theme info, etc.
- **Database Info**: DB version, table sizes, slow queries, etc.
- **Request Info**: Current request details, headers, cookies, etc.
- **User Info**: Current user, roles, capabilities, etc.
- **Custom Functionality**: Debug info specific to your application
