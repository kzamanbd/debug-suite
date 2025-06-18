# Enhanced AutowiredDefinition - Advanced Parameter Injection

The `AutowiredDefinition` class provides comprehensive autowiring capabilities with multiple parameter injection strategies, making it one of the most flexible components in the Debug Suite dependency injection system.

## Key Features

### 1. **Environment-Aware Parameter Injection**

Services can be configured differently based on WordPress environment:

```php
$definition = $container->autowire(DatabaseService::class)
    ->environment_parameters('development', [
        'host' => 'localhost',
        'debug' => true,
        'pool_size' => 5
    ])
    ->environment_parameters('production', [
        'host' => 'prod-db.example.com',
        'debug' => false,
        'pool_size' => 20
    ]);
```

### 2. **Dynamic Parameter Callbacks**

Parameters can be resolved using callbacks with access to the container resolver:

```php
$definition = $container->autowire(EmailService::class)
    ->constructor_parameter_callback('api_key', function($resolver) {
        $config = $resolver(ConfigService::class);
        return $config->get('email.api_key');
    })
    ->constructor_parameter_callback('timestamp', fn() => time());
```

### 3. **Static Parameter Overrides**

Simple static values can be injected directly:

```php
// Single parameter
$definition = $container->autowire(LoggerService::class)
    ->constructor_parameter('log_level', 'debug')
    ->constructor_parameter('log_file', '/var/log/app.log');

// Multiple parameters at once
$definition = $container->autowire(LoggerService::class)
    ->constructor_parameters([
        'log_level' => 'debug',
        'log_file' => '/var/log/app.log',
        'max_size' => 1024 * 1024
    ]);
```

### 4. **Parameter Resolution Priority**

Parameters are resolved in the following priority order:

1. **Environment-specific parameters** (highest priority)
2. **Dynamic parameter callbacks**
3. **Static parameter overrides**
4. **Type-based dependency injection**
5. **Default parameter values**
6. **Container exception with helpful suggestions** (if none above match)

## Advanced Usage Examples

### Complex Service Configuration

```php
// Complex service with multiple injection strategies
$definition = $container->autowire(PaymentService::class)
    // Environment-specific configuration
    ->environment_parameters('development', [
        'api_endpoint' => 'https://sandbox.payment.com',
        'timeout' => 30,
        'debug' => true
    ])
    ->environment_parameters('production', [
        'api_endpoint' => 'https://api.payment.com',
        'timeout' => 10,
        'debug' => false
    ])
    // Dynamic callback for sensitive data
    ->constructor_parameter_callback('api_key', function($resolver) {
        $vault = $resolver(SecretVault::class);
        return $vault->get('payment.api_key');
    })
    // Static configuration
    ->constructor_parameter('retry_attempts', 3);
```

### Service Factory with Parameters

```php
// Factory pattern with parameter injection
$container->singleton(CacheService::class, function($resolver) {
    return $container->autowire(CacheService::class)
        ->constructor_parameters([
            'prefix' => 'app_cache_',
            'ttl' => 3600,
            'driver' => 'redis'
        ])
        ->resolve($resolver);
});
```

## Convenience Helper Functions

The enhanced system includes several convenience helper functions:

### `debug_suite_autowire_with_params()`

```php
// Quick autowiring with static parameters
$definition = debug_suite_autowire_with_params(LoggerService::class, [
    'log_level' => 'debug',
    'log_file' => '/var/log/app.log'
], true); // singleton
```

### `debug_suite_autowire_env()`

```php
// Environment-aware autowiring
$definition = debug_suite_autowire_env(DatabaseService::class, [
    'development' => ['host' => 'localhost', 'debug' => true],
    'production' => ['host' => 'prod-db.com', 'debug' => false]
], true); // singleton
```

## Error Handling and Debugging

### Enhanced Error Messages

When parameter resolution fails, the system provides comprehensive error messages with actionable suggestions:

```text
Cannot resolve parameter [api_key] for class [PaymentService]. 
No type hint, default value, or explicit binding provided. 
Suggestions: Add a type hint to parameter $api_key, 
Use ->constructor_parameter('api_key', $value), 
Use ->constructor_parameter_callback('api_key', $callback), 
Add a default value to the parameter in the constructor.
```

### Parameter Introspection

```php
// Get current parameter overrides for debugging
$overrides = $definition->get_parameter_overrides();
var_dump($overrides); // ['log_level' => 'debug', 'log_file' => '/var/log/app.log']
```

## WordPress Environment Detection

The system automatically detects the WordPress environment using:

1. **WP_ENVIRONMENT_TYPE** constant (WordPress 5.5+)
2. **WP_DEBUG** constant (fallback for older WordPress)
3. **'production'** (default if none detected)

Supported environments:

- `development`
- `staging`
- `production`
- `testing`

## Implementation Best Practices

### 1. **Environment-Specific Configuration**

```php
// ✅ Good - Environment-specific config
$definition->environment_parameters('development', ['debug' => true]);
$definition->environment_parameters('production', ['debug' => false]);

// ❌ Avoid - Hardcoded environment checks
$definition->constructor_parameter_callback('debug', function() {
    return defined('WP_DEBUG') && WP_DEBUG;
});
```

### 2. **Use Callbacks for Dynamic/Computed Values**

```php
// ✅ Good - Dynamic computation
$definition->constructor_parameter_callback('timestamp', fn() => time());

// ❌ Avoid - Static timestamp
$definition->constructor_parameter('timestamp', time());
```

### 3. **Prefer Static Parameters for Simple Values**

```php
// ✅ Good - Static configuration
$definition->constructor_parameters(['timeout' => 30, 'retries' => 3]);

// ❌ Avoid - Unnecessary callbacks for static values
$definition->constructor_parameter_callback('timeout', fn() => 30);
```

### 4. **Group Related Parameters**

```php
// ✅ Good - Bulk parameter setting
$definition->constructor_parameters([
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'app_db'
]);

// ❌ Avoid - Individual calls for related params
$definition->constructor_parameter('host', 'localhost')
          ->constructor_parameter('port', 3306)
          ->constructor_parameter('database', 'app_db');
```

## Integration with Container

### Service Provider Registration

```php
class DatabaseServiceProvider extends AbstractServiceProvider {
    
    protected array $provides = [DatabaseService::class];
    
    public function register(Container $container): void {
        $container->singleton(DatabaseService::class, 
            debug_suite_autowire_env(DatabaseService::class, [
                'development' => [
                    'host' => 'localhost',
                    'debug' => true,
                    'pool_size' => 5
                ],
                'production' => [
                    'host' => $_ENV['DB_HOST'],
                    'debug' => false,
                    'pool_size' => 20
                ]
            ], true)
        );
    }
}
```

### Container Builder Integration

```php
$builder = debug_suite_container_builder()
    ->enable_autowiring()
    ->add_definitions([
        PaymentService::class => debug_suite_autowire_with_params(
            PaymentService::class,
            ['timeout' => 30, 'retries' => 3]
        )
    ]);

$container = $builder->build();
```

1. **Environment-specific parameters** (highest priority)
2. **Dynamic callbacks**
3. **Static parameter overrides**
4. **Type-based dependency injection**
5. **Default parameter values**
6. **Error** (if none of the above can resolve the parameter)

## Usage Examples

### Basic Autowiring

```php
// Simple autowiring without parameters
$container->set(SimpleService::class, $container->autowire(SimpleService::class));

// Singleton autowiring
$container->set(CacheService::class, $container->autowire(CacheService::class, true));
```

### Complex Parameter Injection

```php
$definition = $container->autowire(ComplexService::class)
    // Environment-specific configuration
    ->environment_parameters('development', [
        'cache_ttl' => 60,
        'debug_mode' => true
    ])
    ->environment_parameters('production', [
        'cache_ttl' => 3600,
        'debug_mode' => false
    ])
    // Dynamic parameter resolution
    ->constructor_parameter_callback('api_endpoint', function($resolver) {
        $config = $resolver(ConfigurationService::class);
        return $config->get_api_endpoint();
    })
    // Static parameters
    ->constructor_parameter('service_name', 'complex-service')
    ->constructor_parameter('version', '1.0.0');

$container->set(ComplexService::class, $definition);
```

### Service with Mixed Dependencies

```php
class NotificationService {
    public function __construct(
        private EmailService $email_service,     // Type-injected
        private string $from_address,            // Parameter override
        private int $retry_count = 3,            // Default value
        private bool $debug_mode = false         // Environment-specific
    ) {}
}

$container->set(NotificationService::class, 
    $container->autowire(NotificationService::class)
        ->constructor_parameter('from_address', 'noreply@example.com')
        ->environment_parameters('development', ['debug_mode' => true])
        ->environment_parameters('production', ['debug_mode' => false])
);
```

## Environment Detection

The class automatically detects the WordPress environment using:

1. `WP_ENVIRONMENT_TYPE` constant (WordPress 5.5+)
2. `WP_DEBUG` constant as fallback
3. Defaults to 'production'

## Error Handling

Clear error messages are provided when parameters cannot be resolved:

```php
// This will throw a detailed ContainerException
$definition = $container->autowire(ServiceWithUnresolvableParam::class);
// Error: "Cannot resolve parameter [api_key] for class [ServiceWithUnresolvableParam]. 
//         No type hint, default value, or explicit binding provided."
```

## Best Practices

### 1. **Use Environment Parameters for Configuration**

```php
$container->autowire(DatabaseService::class)
    ->environment_parameters('development', [
        'host' => 'localhost',
        'pool_size' => 5
    ])
    ->environment_parameters('production', [
        'host' => env('DB_HOST'),
        'pool_size' => 50
    ]);
```

### 2. **Prefer Callbacks for Dynamic Values**

```php
$container->autowire(LoggerService::class)
    ->constructor_parameter_callback('log_file', function() {
        return WP_CONTENT_DIR . '/logs/app-' . date('Y-m-d') . '.log';
    });
```

### 3. **Use Static Parameters for Constants**

```php
$container->autowire(ApiService::class)
    ->constructor_parameter('api_version', 'v1')
    ->constructor_parameter('timeout', 30);
```

### 4. **Combine with Container Builder**

```php
$container = (new ContainerBuilder())
    ->enable_autowiring(true)
    ->add_definitions([
        EmailService::class => $container->autowire(EmailService::class)
            ->environment_parameters('development', ['debug' => true])
            ->constructor_parameter('from_address', 'dev@example.com'),
            
        DatabaseService::class => $container->autowire(DatabaseService::class, true)
            ->environment_parameters('production', ['pool_size' => 20])
    ])
    ->build();
```

## Integration with Helper Functions

```php
// Using helper functions for cleaner syntax
$definition = debug_suite_autowire(MyService::class)
    ->constructor_parameter('config_path', '/etc/myapp/config.ini')
    ->environment_parameters('development', ['debug' => true]);

$container->set(MyService::class, $definition);
```

This enhanced autowiring system provides the flexibility of modern PHP-DI containers while maintaining WordPress compatibility and following the plugin's architectural guidelines.
