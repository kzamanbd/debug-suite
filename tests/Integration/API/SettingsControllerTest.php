<?php
/**
 * Integration tests for SettingsController REST API.
 *
 * @package DebugSuite\Tests\Integration\API
 * @group api
 * @group integration
 * @group rest-api
 */

namespace DebugSuite\Tests\Integration\API;

use DebugSuite\Tests\Helpers\DebugSuiteTestCase;
use DebugSuite\API\SettingsController;
use DebugSuite\Services\SettingsService;
use WP_REST_Request;
use ReflectionClass;
use WP_REST_Server;

/**
 * Test SettingsController REST API endpoints.
 *
 * @since 1.0.0
 */
class SettingsControllerTest extends DebugSuiteTestCase {

	/**
	 * Controller instance for testing.
	 *
	 * @var SettingsController
	 */
	private $controller;

	/**
	 * Service instance for testing.
	 *
	 * @var SettingsService
	 */
	private $service;

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	private $namespace = 'debug-suite/v1';
	
	/**
	 * Mock wp-config.php file path.
	 *
	 * @var string
	 */
	private $config_file;

	/**
	 * Set up test environment.
	 */
	public function set_up(): void {
		parent::set_up();
		
		// Set up REST API
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );
		
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
		$property->setAccessible(true);
		$property->setValue($this->service, $this->config_file);
		
		// Create controller
		$this->controller = new SettingsController( $this->service );
		
		// Register routes
		$this->controller->register_routes();
		
		// Create admin user
		$this->create_admin_user();
	}

	/**
	 * Test get settings endpoint without authentication.
	 */
	public function test_get_settings_without_auth() {
		// Set current user to 0 (not logged in)
		wp_set_current_user( 0 );
		
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/settings' );
		$response = rest_get_server()->dispatch( $request );
		
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test get settings endpoint with insufficient permissions.
	 */
	public function test_get_settings_insufficient_permissions() {
		// Create user without manage_options capability
		$user_id = $this->factory->user->create();
		wp_set_current_user( $user_id );
		
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/settings' );
		$response = rest_get_server()->dispatch( $request );
		
		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test get settings endpoint with proper authentication.
	 */
	public function test_get_settings_with_auth() {
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/settings' );
		$response = rest_get_server()->dispatch( $request );
		
		$this->assertEquals( 200, $response->get_status() );
		
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'settings', $data );
		
		// Check that settings were retrieved correctly
		$settings = $data['settings'];
		$this->assertIsArray( $settings );
		$this->assertArrayHasKey( 'WP_DEBUG', $settings );
		$this->assertArrayHasKey( 'WP_DEBUG_LOG', $settings );
		$this->assertArrayHasKey( 'WP_DEBUG_DISPLAY', $settings );
		$this->assertEquals( 'false', $settings['WP_DEBUG'] );
		$this->assertEquals( 'false', $settings['WP_DEBUG_LOG'] );
	}

	/**
	 * Test update settings endpoint.
	 */
	public function test_update_settings() {
		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/settings' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( json_encode( [
			'debug' => 'true',
			'debug_log' => 'true',
			'debug_display' => 'false',
		] ) );
		
		$response = rest_get_server()->dispatch( $request );
		
		$this->assertEquals( 200, $response->get_status() );
		
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
		
		// Verify that wp-config.php was updated
		$updated_content = file_get_contents( $this->config_file );
		$this->assertStringContainsString( "define('WP_DEBUG', true);", $updated_content );
		$this->assertStringContainsString( "define('WP_DEBUG_LOG', true);", $updated_content );
	}

	/**
	 * Test update settings endpoint with invalid parameters.
	 */
	public function test_update_settings_invalid_params() {
		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/settings' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( 'invalid_json' );
		
		$response = rest_get_server()->dispatch( $request );
		
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test update settings endpoint with invalid values.
	 */
	public function test_update_settings_invalid_values() {
		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/settings' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( json_encode( [
			'debug' => 'invalid_value',
		] ) );
		
		$response = rest_get_server()->dispatch( $request );
		
		$this->assertEquals( 500, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'invalid_value', $data['code'] );
	}

	/**
	 * Test reset settings endpoint.
	 */
	public function test_reset_settings() {
		// First update some settings
		$update_request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/settings' );
		$update_request->set_header( 'content-type', 'application/json' );
		$update_request->set_body( json_encode( [
			'debug' => 'true',
			'debug_log' => 'true',
			'debug_display' => 'true',
		] ) );
		rest_get_server()->dispatch( $update_request );
		
		// Then reset them
		$reset_request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/settings/reset' );
		$response = rest_get_server()->dispatch( $reset_request );
		
		$this->assertEquals( 200, $response->get_status() );
		
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
		
		// Verify settings were reset
		$get_request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/settings' );
		$get_response = rest_get_server()->dispatch( $get_request );
		$get_data = $get_response->get_data();
		
		$settings = $get_data['settings'];
		$this->assertEquals( 'false', $settings['WP_DEBUG'] );
		$this->assertEquals( 'false', $settings['WP_DEBUG_LOG'] );
		$this->assertEquals( 'false', $settings['WP_DEBUG_DISPLAY'] );
	}

	/**
	 * Test permissions check method directly.
	 */
	public function test_permissions_check() {
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/settings' );
		
		// Test with admin user
		$this->create_admin_user();
		$this->assertTrue( $this->controller->permissions_check( $request ) );
		
		// Test with regular user
		$user_id = $this->factory->user->create();
		wp_set_current_user( $user_id );
		$this->assertFalse( $this->controller->permissions_check( $request ) );
		
		// Test without authentication
		wp_set_current_user( 0 );
		$this->assertFalse( $this->controller->permissions_check( $request ) );
	}
}
