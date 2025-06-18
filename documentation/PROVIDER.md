# Service Provider Patterns and Lifecycle Management

This document explains the service provider system in Debug Suite, which is built on the PSR-11 compliant dependency injection container architecture.

## Overview

Service Providers in Debug Suite are responsible for:

- **Service Registration**: Binding services to the DI container
- **Dependency Configuration**: Setting up service dependencies
- **Lifecycle Management**: Managing service initialization and bootstrapping
- **Hook Registration**: Automatically registering WordPress hooks for services

## Core Service Provider Architecture

### AbstractServiceProvider

All service providers extend `DebugSuite\Core\Container\AbstractServiceProvider`:

```php
<?php

namespace DebugSuite\Providers;

use DebugSuite\Core\Container\AbstractServiceProvider;
use DebugSuite\Core\Container\Container;

class ExampleServiceProvider extends AbstractServiceProvider
{
    /**
     * Services provided by this provider.
     *
     * @var array<class-string>
     */
    protected array $provides = [
        ExampleService::class,
        ExampleController::class,
    ];

    /**
     * Register services with the container.
     *
     * @param Container $container The DI container.
     * @return void
     */
    public function register(Container $container): void
    {
        // Service registration logic
    }

    /**
     * Boot the service provider.
     *
     * @param Container $container The DI container.
     * @return void
     */
    public function boot(Container $container): void
    {
        // Optional: Additional setup after all providers are registered
    }
}
```

## Service Registration Patterns

### Simple Service Registration

```php
public function register(Container $container): void
{
    // Basic singleton registration
    $container->singleton(LoggerService::class, fn() => new LoggerService());
    
    // Service with dependencies
    $container->singleton(EmailService::class, fn($c) => 
        new EmailService($c->get(LoggerService::class))
    );
}
```

### Advanced Registration with Definitions

```php
public function register(Container $container): void
{
    // Using definition array approach
    $container->add_definitions([
        // Autowired services
        DatabaseService::class => $container->autowire(DatabaseService::class),
        CacheService::class    => $container->autowire(CacheService::class),
        
        // Factory-based services
        LoggerInterface::class => $container->factory(function($c) {
            return new FileLogger($c->get('config.log_path'));
        }),
        
        // Configuration values
        'config.log_path' => $container->value('/var/log/debug-suite.log'),
        'config.debug'    => $container->value(WP_DEBUG),
    ]);
}
```

### Environment-Aware Registration

```php
public function register(Container $container): void
{
    // Environment-specific configuration
    $container->set(ApiService::class,
        debug_suite_autowire_env(ApiService::class, [
            'development' => [
                'base_url' => 'https://dev-api.example.com',
                'debug' => true,
                'timeout' => 30
            ],
            'production' => [
                'base_url' => 'https://api.example.com',
                'debug' => false,
                'timeout' => 10
            ]
        ])
    );
}
```

## Current Service Providers

### CoreServiceProvider

Registers core WordPress integration services:

```php
protected array $provides = [
    I18n::class,
    Assets::class,
    // Core services
];
```

### AdminServiceProvider

Registers admin-specific services:

```php
protected array $provides = [
    Admin::class,
    // Admin services
];
```

### AppServiceProvider

Registers application services and API controllers:

```php
protected array $provides = [
    FileLogsService::class,
    FileManagerService::class,
    SettingsService::class,
    FileLogsController::class,
    FileManagerController::class,
    SettingsController::class,
];
```

### FrontendServiceProvider

Registers frontend services:

```php
protected array $provides = [
    Frontend::class,
    // Frontend services
];
```

## Service Manager Lifecycle

The `ServiceManager` handles the provider lifecycle:

### 1. Registration Phase

```php
$service_manager = debug_suite_service_manager();
$service_manager->register_providers([
    CoreServiceProvider::class,
    AdminServiceProvider::class,
    FrontendServiceProvider::class,
    AppServiceProvider::class,
]);
```

### 2. Boot Phase

```php
// This automatically:
// 1. Calls register() on all providers
// 2. Calls boot() on all providers  
// 3. Registers hooks for services implementing Hookable
$service_manager->boot();
```

### 3. Hook Registration

Services implementing `Hookable` get their hooks registered automatically:

```php
class ExampleService implements Hookable
{
    public function register_hooks(): void
    {
        add_action('init', [$this, 'initialize']);
        add_filter('example_filter', [$this, 'filter_data']);
    }
}
```

## Best Practices

1. **Provider Organization**: Group related services in the same provider
2. **Dependency Declaration**: Always list provided services in `$provides` array
3. **Registration Only**: Use `register()` for service registration, not initialization
4. **Boot for Setup**: Use optional `boot()` method for post-registration setup
5. **Hookable Services**: Implement `Hookable` for services needing WordPress hooks
6. **Environment Awareness**: Use environment-specific configuration for different deployment stages

## Testing Service Providers

```php
class ExampleServiceProviderTest extends DebugSuiteTestCase
{
    public function test_provides_services(): void
    {
        $provider = new ExampleServiceProvider();
        $this->assertContains(ExampleService::class, $provider->provides());
    }

    public function test_registers_services(): void
    {
        $container = Container::get_instance();
        $provider = new ExampleServiceProvider();
        $provider->register($container);
        
        $this->assertTrue($container->has(ExampleService::class));
    }
}
```
