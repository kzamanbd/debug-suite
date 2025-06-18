# Feature Implementation Quick Guide

This document provides a step-by-step guide for implementing new features in the Debug Suite plugin using the Service Layer Pattern architecture.

## Quick Implementation Checklist

### ✅ Creating a New API Endpoint

1. **Create Service Class** (`/includes/Services/`)
   - Implement `ServiceInterface`
   - Return `ServiceResponse` objects
   - Accept configuration via constructor
   - Add comprehensive PHPDoc

2. **Register Service** (`/includes/Providers/ServicesServiceProvider.php`)
   - Add to `$provides` array
   - Register in `register()` method with singleton pattern
   - Support configurable dependencies

3. **Create/Update Controller** (`/includes/API/`)
   - Extend `RestController` (which already implements `Hookable`)
   - Inject service via constructor
   - Handle only HTTP concerns
   - Transform `ServiceResponse` to REST responses

4. **Register Controller** (if new)
   - Add to `ServicesServiceProvider`'s `$provides` array
   - Register in `register()` method with singleton pattern

## Code Templates

### Service Template

```php
<?php

declare(strict_types=1);

namespace DebugSuite\Services;

use DebugSuite\Core\ServiceResponse;
use DebugSuite\Interfaces\ServiceInterface;

/**
 * Service for handling [domain] operations.
 *
 * @since 1.0.0
 */
class ExampleService implements ServiceInterface
{
    /**
     * Constructor with configurable dependencies.
     *
     * @param string|null $config_path Optional configuration path.
     */
    public function __construct(
        private readonly ?string $config_path = null
    ) {
        $this->config_path = $config_path ?? '/default/path';
    }

    /**
     * Process data according to business rules.
     *
     * @param array $input Input data to process.
     * @return ServiceResponse Success with processed data or failure with error.
     */
    public function process_data(array $input): ServiceResponse
    {
        // Input validation
        if (empty($input['required_field'])) {
            return ServiceResponse::failure(
                __('Required field is missing.', 'debug-suite'),
                'validation_error',
                ['field' => 'required_field']
            );
        }

        try {
            // Business logic
            $result = $this->perform_business_logic($input);
            
            return ServiceResponse::success([
                'data' => $result,
                'timestamp' => current_time('mysql'),
                'config_used' => $this->config_path,
            ]);
        } catch (\Exception $e) {
            return ServiceResponse::failure(
                sprintf(__('Processing failed: %s', 'debug-suite'), $e->getMessage()),
                'processing_error',
                ['exception' => $e->getMessage(), 'input' => $input]
            );
        }
    }

    /**
     * Private method for business logic.
     *
     * @param array $input Input data.
     * @return array Processed data.
     */
    private function perform_business_logic(array $input): array
    {
        // Implementation here
        return ['processed' => true, 'original' => $input];
    }
}
```

### Controller Template

```php
<?php

declare(strict_types=1);

namespace DebugSuite\API;

use DebugSuite\Services\ExampleService;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * REST controller for [domain] endpoints.
 *
 * @since 1.0.0
 */
class ExampleController extends RestController
{
    /**
     * The service instance.
     */
    private ExampleService $service;

    /**
     * Route base for endpoints.
     *
     * @var string
     */
    protected $rest_base = 'example';

    /**
     * Constructor.
     *
     * @param ExampleService $service Service instance.
     */
    public function __construct(ExampleService $service)
    {
        $this->service = $service;
    }

    /**
     * Register the routes for the controller.
     */
    public function register_routes(): void
    {
        register_rest_route(
            $this->namespace,
            '/example',
            [
                'methods' => 'POST',
                'callback' => [$this, 'process_request'],
                'permission_callback' => [$this, 'permissions_check'],
                'args' => [
                    'required_field' => [
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'validate_callback' => function($param) {
                            return !empty($param);
                        },
                    ],
                    'optional_field' => [
                        'required' => false,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ]
        );
    }

    /**
     * Handle the request.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function process_request(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        // Extract parameters
        $input = [
            'required_field' => $request->get_param('required_field'),
            'optional_field' => $request->get_param('optional_field'),
        ];

        // Delegate to service
        $result = $this->service->process_data($input);

        // Transform result to HTTP response
        return $this->transform_service_result($result);
    }

    /**
     * Transform service result to HTTP response.
     *
     * @param ServiceResponse $result The service result.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    private function transform_service_result(ServiceResponse $result): WP_REST_Response|WP_Error
    {
        if ($result->is_failure()) {
            $status_code = match($result->get_error_code()) {
                'validation_error' => 400,
                'not_found' => 404,
                'permission_denied' => 403,
                'file_system_error' => 500,
                default => 500
            };

            return new WP_Error(
                $result->get_error_code(),
                $result->get_error_message(),
                ['status' => $status_code]
            );
        }

        return rest_ensure_response($result->to_array());
    }
}
```

### Service Registration Template

```php
// In AppServiceProvider::register()

// Add to $provides array
protected array $provides = [
    FileLogsService::class,
    FileManagerService::class,
    SettingsService::class,
    ExampleService::class, // Add new service
    ExampleController::class, // Add new controller
];

// Register service in register() method
$container->singleton(ExampleService::class, function($container) {
    // Support configurable dependencies
    $config_path = $container->has('debug_suite.example_config_path') 
        ? $container->get('debug_suite.example_config_path') 
        : null;
    
    return new ExampleService($config_path);
});

// Register controller with required service dependency
$container->singleton(ExampleController::class, function($container) {
    // Resolve the required service dependency
    $service = $container->get(ExampleService::class);
    
    // Pass the required dependency to the controller
    return new ExampleController($service);
});
```

## Testing Templates

### Service Unit Test

```php
<?php

declare(strict_types=1);

namespace DebugSuite\Tests\Services;

use DebugSuite\Services\ExampleService;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ExampleService.
 */
class ExampleServiceTest extends TestCase
{
    private ExampleService $service;

    protected function setUp(): void
    {
        $this->service = new ExampleService('/test/config/path');
    }

    public function test_process_data_success(): void
    {
        $input = ['required_field' => 'test_value'];
        $result = $this->service->process_data($input);

        $this->assertTrue($result->is_success());
        $this->assertArrayHasKey('data', $result->get_data());
        $this->assertEquals('/test/config/path', $result->get_data()['config_used']);
    }

    public function test_process_data_validation_failure(): void
    {
        $input = []; // Missing required field
        $result = $this->service->process_data($input);

        $this->assertTrue($result->is_failure());
        $this->assertEquals('validation_error', $result->get_error_code());
        $this->assertStringContains('Required field is missing', $result->get_error_message());
    }

    public function test_process_data_with_custom_config(): void
    {
        $service = new ExampleService('/custom/path');
        $input = ['required_field' => 'test'];
        $result = $service->process_data($input);

        $this->assertTrue($result->is_success());
        $this->assertEquals('/custom/path', $result->get_data()['config_used']);
    }
}
```

### Controller Integration Test

```php
<?php

declare(strict_types=1);

namespace DebugSuite\Tests\API;

use DebugSuite\API\ExampleController;
use DebugSuite\Core\ServiceResponse;
use DebugSuite\Services\ExampleService;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Tests for ExampleController.
 */
class ExampleControllerTest extends WP_UnitTestCase
{
    private ExampleController $controller;
    private ExampleService $mock_service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mock_service = $this->createMock(ExampleService::class);
        $this->controller = new ExampleController($this->mock_service);
    }

    public function test_process_request_success(): void
    {
        // Mock service response
        $this->mock_service
            ->expects($this->once())
            ->method('process_data')
            ->with(['required_field' => 'test', 'optional_field' => null])
            ->willReturn(ServiceResponse::success(['processed' => true]));

        // Create request
        $request = new WP_REST_Request('POST');
        $request->set_param('required_field', 'test');

        // Execute
        $response = $this->controller->process_request($request);

        // Assert
        $this->assertInstanceOf('WP_REST_Response', $response);
        $this->assertEquals(200, $response->get_status());
    }

    public function test_process_request_validation_error(): void
    {
        // Mock service response
        $this->mock_service
            ->expects($this->once())
            ->method('process_data')
            ->willReturn(ServiceResponse::failure('Validation failed', 'validation_error'));

        // Create request with missing required field
        $request = new WP_REST_Request('POST');

        // Execute
        $response = $this->controller->process_request($request);

        // Assert
        $this->assertInstanceOf('WP_Error', $response);
        $this->assertEquals('validation_error', $response->get_error_code());
    }
}
```

## Common Patterns

### Error Handling in Services

```php
// Validation errors
if (!$this->is_valid_input($input)) {
    return ServiceResponse::failure(
        __('Invalid input provided.', 'debug-suite'),
        'validation_error',
        ['input' => $input, 'requirements' => $this->get_requirements()]
    );
}

// File system errors
if (!file_exists($file_path)) {
    return ServiceResponse::failure(
        sprintf(__('File not found: %s', 'debug-suite'), $file_path),
        'file_not_found',
        ['path' => $file_path, 'checked_at' => current_time('mysql')]
    );
}

// Exception handling
try {
    $result = $this->dangerous_operation();
} catch (\Exception $e) {
    return ServiceResponse::failure(
        sprintf(__('Operation failed: %s', 'debug-suite'), $e->getMessage()),
        'operation_failed',
        ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
    );
}
```

### Common HTTP Status Code Mapping

```php
private function get_status_code(string $error_code): int
{
    return match($error_code) {
        'validation_error' => 400,
        'unauthorized' => 401,
        'permission_denied' => 403,
        'not_found', 'file_not_found' => 404,
        'conflict' => 409,
        'file_system_error', 'operation_failed' => 500,
        default => 500
    };
}
```

## Best Practices Summary

1. **Services are stateless** - No instance variables that change between method calls
2. **Services return ServiceResponse** - Never throw exceptions to controllers
3. **Controllers are thin** - Only HTTP request/response handling
4. **Validate early** - Input validation at service entry points
5. **Use dependency injection** - Accept dependencies via constructor
6. **Provide context** - Include helpful data in error responses
7. **Follow naming conventions** - Clear, descriptive method and class names
8. **Write tests** - Unit tests for services, integration tests for controllers
9. **Document thoroughly** - PHPDoc for all public methods
10. **Handle errors gracefully** - Proper error codes and user-friendly messages

This guide ensures consistent implementation of new features while maintaining the high code quality and architectural integrity of the Debug Suite plugin.
