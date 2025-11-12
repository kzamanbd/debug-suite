# Testing in Debug Suite Plugin

This document provides comprehensive guidelines for testing the Debug Suite WordPress plugin, covering unit tests, integration tests, and best practices for ensuring code quality.

## Testing Architecture

Debug Suite implements a robust testing architecture that follows industry best practices for WordPress plugin development. The testing infrastructure is designed to support both:

1. **Unit Tests**: Testing individual components in isolation without WordPress dependencies
2. **Integration Tests**: Testing components with WordPress core functions and database interactions

## Testing Directory Structure

```text
tests/
├── bootstrap.php               # Main test bootstrap file
├── README.md                   # Brief testing overview
├── Helpers/                    # Test helpers and utilities
│   ├── TestCase.php            # Base test case for unit tests
│   ├── DebugSuiteTestCase.php  # Base test case for WordPress integration tests
│   ├── MockFactory.php         # Factory for creating test mocks
│   └── wp-functions-mock.php   # Mock WordPress functions
├── Unit/                       # Unit tests (no WordPress dependencies)
│   ├── BasicTest.php           # Basic tests to verify setup
│   ├── Core/                   # Tests for core functionality
│   │   └── Container/          # Tests for DI container
│   └── Services/               # Tests for service classes
└── Integration/                # Integration tests (with WordPress)
    └── API/                    # Tests for API controllers
```

## Test Base Classes

### Unit Tests Base Class

For unit tests that don't require WordPress core functionality, extend the `TestCase` class:

```php
namespace DebugSuite\Tests\Unit\Services;

use DebugSuite\Tests\Helpers\TestCase;
use DebugSuite\Services\ExampleService;

class ExampleServiceTest extends TestCase {
    // Test methods here
}
```

The `TestCase` class provides:

- Container setup for dependency injection testing
- Mock factory for creating test doubles
- WordPress function mocks
- PHPUnit polyfills for cross-version compatibility via Yoast's polyfills

### Integration Tests Base Class

For tests that require WordPress core functionality, extend the `DebugSuiteTestCase` class:

```php
namespace DebugSuite\Tests\Integration\API;

use DebugSuite\Tests\Helpers\DebugSuiteTestCase;
use DebugSuite\API\ExampleController;

class ExampleControllerTest extends DebugSuiteTestCase {
    // Test methods here
}
```

The `DebugSuiteTestCase` class provides:

- WordPress test environment setup
- Factory for creating WordPress entities (posts, users, etc.)
- REST API testing helpers
- Debug Suite plugin initialization and bootstrap

## Setting Up the Test Environment

### Prerequisites

- PHP 8.1 or higher
- Composer
- Local MySQL/MariaDB database for WordPress tests

### WordPress Test Setup

The plugin includes a script to set up the WordPress test environment:

```bash
bin/install-wp-tests.sh wordpress_test root password localhost latest
```

This script will:

1. Download WordPress core
2. Create a test database
3. Configure WordPress for testing
4. Install necessary tables

### Installing Test Dependencies

```bash
composer install
```

## Running Tests

The plugin provides several composer commands for running tests:

```bash
# Run all tests
composer run test

# Run unit tests only
composer run test:unit

# Run integration tests only
composer run test:integration

# Run tests with coverage report
composer run test:coverage

# Run a specific test file
composer run test -- --filter=ExampleServiceTest

# Run tests with a specific group tag
composer run test -- --group=api
```

## Test Groups

Tests are organized into groups using PHPUnit's `@group` annotation:

- `@group unit` - Unit tests
- `@group integration` - Integration tests
- `@group api` - API controller tests
- `@group services` - Service class tests
- `@group container` - DI container tests
- `@group setup` - Basic setup tests

## Testing Best Practices

### 1. Service Testing

When testing service classes:

```php
/**
 * @covers \DebugSuite\Services\ExampleService
 * @group services
 */
class ExampleServiceTest extends TestCase {
    private ExampleService $service;
    
    protected function set_up() {
        parent::set_up();
        $this->service = new ExampleService();
    }
    
    /**
     * Test successful processing with valid input
     */
    public function test_process_data_with_valid_input() {
        $result = $this->service->process_data([
            'required_field' => 'test value'
        ]);
        
        $this->assertTrue($result->is_success());
        $this->assertArrayHasKey('processed_data', $result->get_data());
    }
    
    /**
     * Test validation failure with missing required field
     */
    public function test_process_data_with_missing_required_field() {
        $result = $this->service->process_data([]);
        
        $this->assertTrue($result->is_failure());
        $this->assertEquals('validation_error', $result->get_error_code());
    }
}
```

### 2. API Controller Testing

For testing REST API controllers:

```php
/**
 * @covers \DebugSuite\API\ExampleController
 * @group api
 * @group integration
 */
class ExampleControllerTest extends DebugSuiteTestCase {
    private $controller;
    private $service;
    
    protected function set_up() {
        parent::set_up();
        $this->service = $this->createMock(ExampleService::class);
        $this->controller = new ExampleController($this->service);
        $this->controller->register_routes();
    }
    
    /**
     * Test endpoint permission checks
     */
    public function test_permission_check() {
        // Test without admin privileges
        wp_set_current_user(0);
        $request = new WP_REST_Request('GET', '/debug-suite/v1/example');
        $response = $this->controller->permissions_check($request);
        $this->assertInstanceOf(WP_Error::class, $response);
        
        // Test with admin privileges
        $user_id = $this->factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($user_id);
        $response = $this->controller->permissions_check($request);
        $this->assertTrue($response);
    }
    
    /**
     * Test successful request processing
     */
    public function test_process_request() {
        // Mock service response
        $this->service->method('process_data')
            ->willReturn(ServiceResponse::success(['key' => 'value']));
            
        // Create request
        $request = new WP_REST_Request('POST', '/debug-suite/v1/example');
        $request->set_param('required_field', 'test value');
        
        // Execute controller method
        $response = $this->controller->process_request($request);
        
        // Assert response
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $data = $response->get_data();
        $this->assertArrayHasKey('key', $data);
        $this->assertEquals('value', $data['key']);
    }
}
```

### 3. Mocking Dependencies

For unit testing with dependencies:

```php
public function test_service_with_dependencies() {
    // Create mock dependency
    $mock_dependency = $this->createMock(DependencyInterface::class);
    $mock_dependency->method('get_data')
        ->willReturn(['key' => 'value']);
    
    // Create service with mock dependency
    $service = new TestedService($mock_dependency);
    
    // Test service
    $result = $service->process();
    $this->assertTrue($result->is_success());
}
```

### 4. Testing Container Bindings

For testing DI container bindings:

```php
public function test_container_binding() {
    $container = $this->get_test_container();
    $container->bind('config', ['debug' => true]);
    
    $this->assertTrue($container->has('config'));
    $this->assertEquals(['debug' => true], $container->get('config'));
}
```

### 5. Testing Hookable Services

For testing WordPress hook registration:

```php
public function test_hook_registration() {
    $service = new ExampleService();
    
    // Test before hooks are registered
    $this->assertFalse(has_action('init', [$service, 'initialize']));
    
    // Register hooks
    $service->register_hooks();
    
    // Test after hooks are registered
    $this->assertEquals(10, has_action('init', [$service, 'initialize']));
}
```

## Coverage Reports

Generate code coverage reports to measure test completeness:

```bash
composer run test:coverage
```

This will generate a coverage report in the `coverage` directory, which you can open in a browser to view detailed reports on which parts of the code are covered by tests.

## Continuous Integration

For CI/CD integration, the plugin includes GitHub Actions workflows that automatically run tests on push and pull requests. See the `.github/workflows` directory for configuration details.

## Troubleshooting

### Common Issues

1. **WordPress Test Environment Not Found**
   - Make sure the `WP_TESTS_DIR` environment variable is set correctly
   - Run the `bin/install-wp-tests.sh` script again

2. **Database Connection Issues**
   - Verify your database credentials in the WordPress test config
   - Ensure the test database exists and is accessible

3. **Memory Limits**
   - If you encounter memory limit errors, increase PHP memory limit:

     ```bash
     php -d memory_limit=256M vendor/bin/phpunit
     ```

## Adding New Tests

### 1. Adding a New Unit Test

Create a new test file in the appropriate subdirectory of `tests/Unit/`:

```php
<?php
/**
 * Tests for NewService.
 *
 * @package DebugSuite\Tests\Unit\Services
 * @group services
 */

namespace DebugSuite\Tests\Unit\Services;

use DebugSuite\Tests\Helpers\TestCase;
use DebugSuite\Services\NewService;

/**
 * @covers \DebugSuite\Services\NewService
 */
class NewServiceTest extends TestCase {
    private NewService $service;
    
    protected function set_up() {
        parent::set_up();
        $this->service = new NewService();
    }
    
    public function test_example_method() {
        $result = $this->service->example_method();
        $this->assertTrue($result->is_success());
    }
}
```

### 2. Adding a New Integration Test

Create a new test file in the appropriate subdirectory of `tests/Integration/`:

```php
<?php
/**
 * Integration tests for NewController.
 *
 * @package DebugSuite\Tests\Integration\API
 * @group api
 * @group integration
 */

namespace DebugSuite\Tests\Integration\API;

use DebugSuite\Tests\Helpers\DebugSuiteTestCase;
use DebugSuite\API\NewController;

/**
 * @covers \DebugSuite\API\NewController
 */
class NewControllerTest extends DebugSuiteTestCase {
    private $controller;
    
    protected function set_up() {
        parent::set_up();
        $this->controller = new NewController();
        $this->controller->register_routes();
    }
    
    public function test_example_endpoint() {
        // Test implementation
    }
}
```

## Conclusion

A comprehensive testing strategy is essential for maintaining code quality and preventing regressions. By following these guidelines, you can ensure that the Debug Suite plugin remains robust and reliable through future development and updates.
