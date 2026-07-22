<?php
/**
 * Unit tests for SettingsService class.
 *
 * @package DebugSuite\Tests\Unit\Services
 * @group   services
 * @group   settings
 */

namespace DebugSuite\Tests\Unit\Services;

use DebugSuite\Tests\Helpers\TestCase;
use DebugSuite\Services\SettingsService;
use ReflectionClass;

/**
 * Test SettingsService functionality.
 */
class SettingsServiceTest extends TestCase {

	/**
	 * SettingsService instance for testing.
	 *
	 * @var SettingsService
	 */
	private $service;
	
	/**
	 * Mock wp-config.php file path.
	 *
	 * @var string
	 */
	private $config_file;

	/**
	 * Set up test environment.
	 */
	public function set_up() {
		parent::set_up();
		
		// Create a mock wp-config.php file for testing
		$config_content = <<<EOT
<?php
/**
 * The base configuration for WordPress
 */

// ** Database settings ** //
define('DB_NAME', 'wordpress');
define('DB_USER', 'wordpress');
define('DB_PASSWORD', 'wordpress');
define('DB_HOST', 'localhost');

// ** Other settings ** //
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);

/* That's all, stop editing! Happy publishing. */
EOT;
		
		$this->config_file = $this->create_test_file($config_content, '.php');
		
		// Create service instance
		$this->service = new SettingsService();
		
		// Set the config file path using reflection
		$reflection = new ReflectionClass($this->service);
		$property = $reflection->getProperty('config_file_path');
		$property->setValue($this->service, $this->config_file);
	}

	/**
	 * Test get_settings method.
	 * 
	 * @covers \DebugSuite\Services\SettingsService::get_settings
	 */
	public function test_get_settings() {
		$result = $this->service->get_settings();
		
		// Assert the result is successful
		$this->assert_service_result_success($result);
		
		// Check the extracted settings
		$settings = $result;
		$this->assertIsArray($settings);
		$this->assertArrayHasKey('wp_debug', $settings);
		$this->assertArrayHasKey('wp_debug_log', $settings);
		$this->assertArrayHasKey('wp_debug_display', $settings);
		
		// Verify values match what's in our mock config file
		$this->assertFalse($settings['wp_debug']);
		$this->assertFalse($settings['wp_debug_log']);
		// WP_DEBUG_DISPLAY is not in the mock file, so should default to false
		$this->assertFalse($settings['wp_debug_display']);
	}

	/**
	 * Test update_settings method.
	 * 
	 * @covers \DebugSuite\Services\SettingsService::update_settings
	 */
	public function test_update_settings() {
		$new_settings = [
			'WP_DEBUG' => 'true',
			'WP_DEBUG_LOG' => 'true',
		];
		
		$result = $this->service->update_settings($new_settings);
		
		// Assert the result is successful
		$this->assert_service_result_success($result);
		
		// Check that the file was updated correctly
		$updated_content = file_get_contents($this->config_file);
		$this->assertStringContainsString("define('WP_DEBUG', true);", $updated_content);
		$this->assertStringContainsString("define('WP_DEBUG_LOG', true);", $updated_content);
		
		// WP_DEBUG_DISPLAY should not have been changed
		$this->assertStringNotContainsString("define('WP_DEBUG_DISPLAY'", $updated_content);
	}

	/**
	 * Test update_settings method with all constants.
	 * 
	 * @covers \DebugSuite\Services\SettingsService::update_settings
	 */
	public function test_update_all_debug_settings() {
		$new_settings = [
			'WP_DEBUG' => 'true',
			'WP_DEBUG_LOG' => 'true',
			'WP_DEBUG_DISPLAY' => 'true',
		];
		
		$result = $this->service->update_settings($new_settings);
		
		// Assert the result is successful
		$this->assert_service_result_success($result);
		
		// Check that the file was updated correctly
		$updated_content = file_get_contents($this->config_file);
		$this->assertStringContainsString("define('WP_DEBUG', true);", $updated_content);
		$this->assertStringContainsString("define('WP_DEBUG_LOG', true);", $updated_content);
		
		// WP_DEBUG_DISPLAY should have been added
		$this->assertStringContainsString("define('WP_DEBUG_DISPLAY', true);", $updated_content);
	}

	/**
	 * Test update_settings method with invalid settings.
	 * 
	 * @covers \DebugSuite\Services\SettingsService::update_settings
	 */
	public function test_update_settings_invalid_key() {
		$invalid_settings = [
			'WP_DEBUG' => 'true',
			'INVALID_CONSTANT' => 'true',
		];
		
		$result = $this->service->update_settings($invalid_settings);
		
		// Assert the result is a failure
		$this->assert_service_result_failure($result);
		$this->assertEquals('invalid_setting', $result->get_error_code());
	}

	/**
	 * Test update_settings method with invalid values.
	 * 
	 * @covers \DebugSuite\Services\SettingsService::update_settings
	 */
	public function test_update_settings_invalid_value() {
		$invalid_settings = [
			'WP_DEBUG' => 'invalid_value',
		];
		
		$result = $this->service->update_settings($invalid_settings);
		
		// Assert the result is a failure
		$this->assert_service_result_failure($result);
		$this->assertEquals('invalid_value', $result->get_error_code());
	}

	/**
	 * Test update_settings method with empty settings.
	 * 
	 * @covers \DebugSuite\Services\SettingsService::update_settings
	 */
	public function test_update_settings_empty() {
		$result = $this->service->update_settings([]);
		
		// Assert the result is a failure
		$this->assert_service_result_failure($result);
		$this->assertEquals('empty_settings', $result->get_error_code());
	}

	/**
	 * Test reset_debug_settings method.
	 * 
	 * @covers \DebugSuite\Services\SettingsService::reset_debug_settings
	 */
	public function test_reset_debug_settings() {
		// First update some settings
		$this->service->update_settings([
			'WP_DEBUG' => 'true',
			'WP_DEBUG_LOG' => 'true',
			'WP_DEBUG_DISPLAY' => 'true',
		]);
		
		// Then reset them
		$result = $this->service->reset_debug_settings();
		
		// Assert the result is successful
		$this->assert_service_result_success($result);
		
		// Get the current settings after reset
		$current_settings = $this->service->get_settings();
		
		// All settings should be set to 'false'
		$this->assertFalse($current_settings['wp_debug']);
		$this->assertFalse($current_settings['wp_debug_log']);
		$this->assertFalse($current_settings['wp_debug_display']);
	}

	/**
	 * Test handling non-existent config file.
	 * 
	 * @covers \DebugSuite\Services\SettingsService::get_settings
	 */
	public function test_nonexistent_config_file() {
		// Set the config file path to a non-existent file
		$reflection = new ReflectionClass($this->service);
		$property = $reflection->getProperty('config_file_path');
		$property->setValue($this->service, '/path/to/nonexistent/wp-config.php');
		
		$result = $this->service->get_settings();
		
		// Assert the result is a failure
		$this->assert_service_result_failure($result);
		$this->assertEquals('config_file_not_found', $result->get_error_code());
	}
}
