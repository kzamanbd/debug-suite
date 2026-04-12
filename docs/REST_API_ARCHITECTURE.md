# REST API Architecture

## API Controllers Structure

- **Base Controller**: `DebugSuite\API\RestController` - Provides common functionality and implements `Hookable`
- **Settings Controller**: `DebugSuite\API\SettingsController` - Delegates to `SettingsService` for wp-config.php management
- **File Manager Controller**: `DebugSuite\API\FileManagerController` - Delegates to `FileManagerService` for file operations
- **File Logs Controller**: `DebugSuite\API\FileLogsController` - Delegates to `FileLogsService` for debug.log processing

## Controller Lifecycle

- **Hookable Implementation**: All controllers extend `RestController` which implements `Hookable`
- **Automatic Registration**: Controllers automatically register their routes via the `register_hooks()` method
- **ServiceManager Integration**: The service manager handles registration and boot lifecycle
- **Hook Registration**: When a controller is resolved, its hooks are automatically registered
- **No Manual Registration**: No need to manually register controllers in `rest_api_init`

## Service Layer Integration

- **Controllers are thin**: Handle only HTTP request/response concerns
- **Business logic in services**: All domain logic implemented in dedicated service classes
- **Consistent error handling**: Services return `ServiceResponse` objects, controllers transform to HTTP responses
- **Required dependency injection**: Controllers receive service instances via required constructor parameters
- **Strong typing**: All service dependencies are strongly typed and non-nullable

## API Endpoints

```php
// Settings endpoints
GET /wp-json/debug-suite/v1/settings          # Get current debug settings
POST /wp-json/debug-suite/v1/settings         # Update debug settings
POST /wp-json/debug-suite/v1/settings/reset   # Reset settings to defaults

// File manager endpoints  
GET /wp-json/debug-suite/v1/files?path={path}        # Browse directory structure
GET /wp-json/debug-suite/v1/files/content?path={path} # Get file contents
POST /wp-json/debug-suite/v1/files/content           # Save file contents with backup

// File logs endpoints
GET /wp-json/debug-suite/v1/logs              # Get parsed log entries
GET /wp-json/debug-suite/v1/logs/stats        # Get log file statistics
DELETE /wp-json/debug-suite/v1/logs/clear     # Clear log file
```

## Permission System

- All endpoints require `manage_options` capability
- Consistent permission checking via `permissions_check()` method
- Proper error handling with WP_Error responses

## Testing REST API Controllers

The plugin includes a comprehensive testing framework for REST API controllers. Controllers are tested using integration tests that verify:

1. **Route Registration**: Ensure routes are properly registered with WordPress
2. **Permission Checks**: Verify that only authorized users can access endpoints
3. **Request Parameters**: Validate parameter handling and sanitization
4. **Service Integration**: Test controller interactions with services
5. **Response Formatting**: Verify proper HTTP response formats

### Controller Test Example

```php
/**
 * @covers \DebugSuite\API\LogsController
 * @group api
 * @group integration
 */
class FileLogsControllerTest extends DebugSuiteTestCase {
    private $controller;
    private $service;
    
    protected function set_up() {
        parent::set_up();
        $this->service = $this->createMock(FileLogsService::class);
        $this->controller = new FileLogsController($this->service);
        $this->controller->register_routes();
    }
    
    public function test_get_logs_endpoint() {
        // Mock service response
        $this->service->method('get_log_entries')
            ->willReturn(ServiceResponse::success(['logs' => []]));
        
        // Create and execute request
        $request = new WP_REST_Request('GET', '/debug-suite/v1/logs');
        $response = rest_get_server()->dispatch($request);
        
        // Verify response
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertArrayHasKey('logs', $data);
    }
    
    public function test_permissions() {
        // Test unauthorized access
        wp_set_current_user(0);
        $request = new WP_REST_Request('GET', '/debug-suite/v1/logs');
        $response = rest_get_server()->dispatch($request);
        $this->assertEquals(401, $response->get_status());
        
        // Test authorized access
        $user_id = $this->factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($user_id);
        $response = rest_get_server()->dispatch($request);
        $this->assertNotEquals(401, $response->get_status());
    }
}
```

### Mocking Service Layer

When testing controllers, the service layer is typically mocked to isolate controller logic:

```php
// Mock service response for success case
$this->service->method('get_log_entries')
    ->willReturn(ServiceResponse::success(['data' => 'value']));

// Mock service response for error case
$this->service->method('clear_logs')
    ->willReturn(ServiceResponse::failure('Error message', 'error_code'));
```

### Testing Response Transformation

Controllers should transform `ServiceResponse` objects into appropriate WordPress responses:

```php
public function test_error_transformation() {
    // Mock service error
    $this->service->method('get_log_entries')
        ->willReturn(ServiceResponse::failure('Not found', 'not_found'));
    
    // Execute request
    $request = new WP_REST_Request('GET', '/debug-suite/v1/logs');
    $response = $this->controller->get_logs($request);
    
    // Verify WP_Error response
    $this->assertInstanceOf(WP_Error::class, $response);
    $this->assertEquals('not_found', $response->get_error_code());
    $this->assertEquals(404, $response->get_error_data()['status']);
}
```

For more detailed testing guidelines, see the [TESTING.md](./TESTING.md) documentation.
POST /wp-json/debug-suite/v1/settings         # Update debug settings
POST /wp-json/debug-suite/v1/settings/reset   # Reset settings to defaults

// File manager endpoints  
GET /wp-json/debug-suite/v1/files?path={path}        # Browse directory structure
GET /wp-json/debug-suite/v1/files/content?path={path} # Get file contents
POST /wp-json/debug-suite/v1/files/content           # Save file contents with backup

// File logs endpoints
GET /wp-json/debug-suite/v1/logs              # Get parsed log entries
GET /wp-json/debug-suite/v1/logs/stats        # Get log file statistics
DELETE /wp-json/debug-suite/v1/logs/clear     # Clear log file

```md

## Permission System

- All endpoints require `manage_options` capability
- Consistent permission checking via `permissions_check()` method
- Proper error handling with WP_Error responses

## Adding a New API Controller

1. **Create Controller Class**: Extend `RestController` which already implements `Hookable`
2. **Define REST Base**: Set the `$rest_base` property for your endpoints
3. **Implement Route Registration**: Define endpoints in `register_routes()` method
4. **Implement Request Handling**: Process requests and transform service results
5. **Register in Container**: Register controller in `ServicesServiceProvider`

**Example Controller Class**:

```php
class ExampleController extends RestController {
    private ExampleService $service;
    protected $rest_base = 'example';

    public function __construct( ExampleService $service ) {
        $this->service = $service;
    }

    // Implementation of register_routes() and other methods...
}
```

**Example Service and Controller Registration**:

```php
// In AppServiceProvider - for business services
protected array $provides = [
    // ...existing services
    ExampleService::class,
];

public function register(Container $container): void {
    // ...existing registrations
    
    // Register service
    $container->add([
        ExampleService::class => $container->object(ExampleService::class),
    ]);
}

// In RestControllerProvider - for REST controllers
protected array $provides = [
    // ...existing controllers
    ExampleController::class,
];

public function register(Container $container): void {
    // ...existing registrations
    
    // Register controller with dependency injection
    $container->add([
        ExampleController::class => $container->autowire(ExampleController::class),
    ]);
}
```
