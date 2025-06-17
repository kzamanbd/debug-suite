# Service Layer Architecture Implementation

This document explains the Service Layer Pattern implementation for the Debug Suite WordPress plugin, which separates business logic from API controllers for better maintainability, testability, and reusability.

## Architecture Overview

The implementation follows this separation of concerns:

```md
REST Controller → Service Layer → Data Access Layer
     ↓               ↓               ↓
API Handling   Business Logic   File/DB Operations
```

### Key Components

1. **REST Controllers** (`/includes/API/`)
   - Handle HTTP requests and responses
   - Validate API parameters
   - Transform service results to proper REST responses
   - **No business logic**

2. **Service Layer** (`/includes/Services/`)
   - Contains all business logic
   - Returns consistent `ServiceResult` objects
   - Handles validation, error handling, and data processing
   - **Testable and reusable**

3. **ServiceResult Class** (`/includes/Core/ServiceResult.php`)
   - Consistent response format for all services
   - Success/failure states with context data
   - Easy conversion to API responses

## Implemented Services

### 1. FileLogsService

**Purpose**: Handles WordPress debug log operations

**Key Methods**:

- `get_log_entries(array $options)` - Parse and retrieve log entries
- `clear_log_file()` - Clear the debug log file
- `get_log_file_stats()` - Get file statistics and entry counts

**Features**:

- Configurable log file path
- Entry filtering by severity level
- Pagination support
- Statistics calculation

### 2. SettingsService

**Purpose**: Manages WordPress configuration constants

**Key Methods**:

- `update_debug_settings(array $settings)` - Update wp-config.php constants
- `get_current_debug_settings()` - Read current constant values
- `reset_debug_settings()` - Reset to default values

**Features**:

- Safe wp-config.php modification
- Input validation and sanitization
- Backup-friendly operations
- Support for WP_DEBUG, WP_DEBUG_LOG, WP_DEBUG_DISPLAY

### 3. FileManagerService

**Purpose**: Handles file system operations

**Key Methods**:

- `get_directory_tree(string $path, array $options)` - Browse directories
- `get_file_contents(string $path)` - Read file contents with metadata
- `save_file_contents(string $path, string $contents, array $options)` - Save files with backup

**Features**:

- Path security validation
- Automatic backup creation
- File metadata extraction
- WordPress root-relative paths

## Updated Controllers

### FileLogsController

**Before** (Monolithic):

```php
public function get_file_logs($request): WP_REST_Response {
    // 50+ lines of business logic mixed with API handling
    $log_file = WP_CONTENT_DIR . '/debug.log';
    // File reading, parsing, error handling all in controller
}
```

**After** (Service Layer):

```php
public function get_file_logs(WP_REST_Request $request): WP_REST_Response|WP_Error {
    $options = ['limit' => $request->get_param('limit')];
    $result = $this->file_logs_service->get_log_entries($options);
    
    return $result->is_failure() 
        ? new WP_Error($result->get_error_code(), $result->get_error_message())
        : rest_ensure_response($result->to_array());
}
```

### New Endpoints Added

1. **Log Management**:
   - `DELETE /logs/clear` - Clear log file
   - `GET /logs/stats` - Get log statistics

2. **Settings Management**:
   - `GET /settings` - Get current settings
   - `POST /settings/reset` - Reset to defaults

3. **File Management**:
   - `POST /files/content` - Save file contents with backup

## Dependency Injection Integration

### ServicesServiceProvider

Registers all services with the PSR-11 container:

```php
class ServicesServiceProvider extends AbstractServiceProvider {
    protected array $provides = [
        FileLogsService::class,
        FileManagerService::class,
        SettingsService::class,
    ];
    
    public function register(Container $container): void {
        $container->singleton(FileLogsService::class, function($container) {
            $log_file_path = $container->has('debug_suite.log_file_path') 
                ? $container->get('debug_suite.log_file_path') 
                : null;
            return new FileLogsService($log_file_path);
        });
        // ... other services
    }
}
```

### Configuration Support

Services support dependency injection of configuration:

```php
// Custom log file path
$container->bind('debug_suite.log_file_path', '/custom/path/debug.log');

// Custom base path for file operations  
$container->bind('debug_suite.base_path', '/custom/wordpress/root');

// Custom wp-config.php path
$container->bind('debug_suite.config_file_path', '/custom/wp-config.php');
```

## Benefits Achieved

### 1. **Separation of Concerns**

- API controllers only handle HTTP concerns
- Business logic isolated in services
- Data access separated from business rules

### 2. **Testability**

- Services can be unit tested independently
- No WordPress hooks or global state in business logic
- Mockable dependencies

### 3. **Reusability**

- Services can be used by multiple controllers
- Business logic available for CLI commands
- Easy integration with other systems

### 4. **Error Handling**

- Consistent error responses across all endpoints
- Detailed error context for debugging
- Proper HTTP status codes

### 5. **Maintainability**

- Single responsibility principle
- Clear boundaries between layers
- Easy to modify business logic without affecting API

## Usage Examples

### Using Services Directly

```php
// Get services from container
$file_logs_service = debug_suite_resolve(FileLogsService::class);
$settings_service = debug_suite_resolve(SettingsService::class);

// Use in other contexts
$result = $file_logs_service->get_log_entries(['limit' => 50]);
if ($result->is_success()) {
    $entries = $result->get_data()['entries'];
    // Process entries...
}
```

### Testing Services

```php
class FileLogsServiceTest extends TestCase {
    public function test_get_log_entries_with_empty_file() {
        $service = new FileLogsService('/path/to/empty/log');
        $result = $service->get_log_entries();
        
        $this->assertTrue($result->is_success());
        $this->assertEmpty($result->get_data()['entries']);
    }
}
```

### Extending Services

```php
class CustomFileLogsService extends FileLogsService {
    public function get_log_entries(array $options = []): ServiceResult {
        // Add custom filtering logic
        $result = parent::get_log_entries($options);
        
        if ($result->is_success()) {
            $data = $result->get_data();
            $data['custom_field'] = $this->add_custom_processing($data['entries']);
            return ServiceResult::success($data);
        }
        
        return $result;
    }
}
```

## Migration Guide

For future services, follow this pattern:

1. **Create Service Class**:
   - Implement `ServiceInterface`
   - Return `ServiceResult` objects
   - Handle all business logic

2. **Update Controller**:
   - Inject service via constructor
   - Delegate to service methods
   - Transform `ServiceResult` to REST response

3. **Register with DI Container**:
   - Add to `ServicesServiceProvider`
   - Configure dependencies
   - Use singleton pattern for stateful services

4. **Add Tests**:
   - Unit test service logic
   - Integration test API endpoints
   - Mock external dependencies

This architecture provides a solid foundation for scalable, maintainable WordPress plugin development while leveraging modern PHP patterns and the Debug Suite's PSR-11 container system.
