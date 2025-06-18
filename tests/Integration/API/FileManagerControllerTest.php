<?php
/**
 * Integration tests for FileManagerController REST API.
 *
 * @package DebugSuite\Tests\Integration\API
 * @group api
 * @group integration
 * @group rest-api
 */

namespace DebugSuite\Tests\Integration\API;

use DebugSuite\Tests\Helpers\DebugSuiteTestCase;
use DebugSuite\API\FileManagerController;
use DebugSuite\Services\FileManagerService;
use ReflectionClass;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Test FileManagerController REST API endpoints.
 *
 * @since 1.0.0
 */
class FileManagerControllerTest extends DebugSuiteTestCase {

	/**
	 * Controller instance for testing.
	 *
	 * @var FileManagerController
	 */
	private $controller;

	/**
	 * Service instance for testing.
	 *
	 * @var FileManagerService
	 */
	private $service;

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'debug-suite/v1';

	/**
	 * Test directory.
	 *
	 * @var string
	 */
	private $test_dir;

	/**
	 * Set up test environment.
	 */
	public function set_up(): void {
		parent::set_up();
		
		// Set up REST API
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );
		
		// Create test directory with sample files
		$this->test_dir = $this->create_test_directory();
		file_put_contents( $this->test_dir . '/test1.txt', 'Test file 1 content' );
		file_put_contents( $this->test_dir . '/test2.php', '<?php echo "Test file 2"; ?>' );
		mkdir( $this->test_dir . '/subdir' );
		file_put_contents( $this->test_dir . '/subdir/test3.txt', 'Test file 3 content' );
		
		// Create service and controller
		$this->service = new FileManagerService();
		
		// Set the base path using reflection
		$reflection = new ReflectionClass($this->service);
		$property = $reflection->getProperty('base_path');
		$property->setAccessible(true);
		$property->setValue($this->service, $this->test_dir . '/');
		
		$this->controller = new FileManagerController( $this->service );
		
		// Register routes
		$this->controller->register_routes();
		
		// Create admin user
		$this->create_admin_user();
	}

	/**
	 * Test get files endpoint without authentication.
	 */
	public function test_get_files_without_auth() {
		// Set current user to 0 (not logged in)
		wp_set_current_user( 0 );
		
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/files' );
		$response = rest_get_server()->dispatch( $request );
		
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test get files endpoint with insufficient permissions.
	 */
	public function test_get_files_insufficient_permissions() {
		// Create user without manage_options capability
		$user_id = $this->factory->user->create();
		wp_set_current_user( $user_id );
		
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/files' );
		$response = rest_get_server()->dispatch( $request );
		
		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test get files endpoint with proper authentication.
	 */
	public function test_get_files_with_auth() {
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/files' );
		$response = rest_get_server()->dispatch( $request );
		
		$this->assertEquals( 200, $response->get_status() );
		
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'tree', $data );
		$this->assertIsArray( $data['tree'] );
		
		// Should have 3 items (2 files and 1 directory)
		$this->assertCount( 3, $data['tree'] );
	}

	/**
	 * Test get files endpoint with path parameter.
	 */
	public function test_get_files_with_path() {
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/files' );
		$request->set_param( 'path', 'subdir' );
		
		$response = rest_get_server()->dispatch( $request );
		
		$this->assertEquals( 200, $response->get_status() );
		
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'tree', $data );
		$this->assertIsArray( $data['tree'] );
		
		// Should have 1 item (test3.txt)
		$this->assertCount( 1, $data['tree'] );
		$this->assertEquals( 'test3.txt', $data['tree'][0]->name );
	}

	/**
	 * Test get file contents endpoint.
	 */
	public function test_get_file_contents() {
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/files/content' );
		$request->set_param( 'path', 'test1.txt' );
		
		$response = rest_get_server()->dispatch( $request );
		
		$this->assertEquals( 200, $response->get_status() );
		
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'contents', $data );
		$this->assertEquals( 'Test file 1 content', $data['contents'] );
		$this->assertArrayHasKey( 'metadata', $data );
	}

	/**
	 * Test get file contents with invalid path.
	 */
	public function test_get_file_contents_invalid_path() {
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/files/content' );
		$request->set_param( 'path', 'nonexistent.txt' );
		
		$response = rest_get_server()->dispatch( $request );
		
		$this->assertEquals( 500, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'file_not_found', $data['code'] );
	}

	/**
	 * Test save file contents endpoint.
	 */
	public function test_save_file_contents() {
		$new_content = 'Updated content via API';
		
		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/files/content' );
		$request->set_param( 'path', 'test1.txt' );
		$request->set_param( 'contents', $new_content );
		$request->set_param( 'create_backup', true );
		
		$response = rest_get_server()->dispatch( $request );
		
		$this->assertEquals( 200, $response->get_status() );
		
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
		
		// Verify file was updated
		$this->assertEquals( $new_content, file_get_contents( $this->test_dir . '/test1.txt' ) );
	}

	/**
	 * Test save file contents creating a new file.
	 */
	public function test_save_file_contents_new_file() {
		$new_file = 'new_file.txt';
		$content = 'New file created via API';
		
		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/files/content' );
		$request->set_param( 'path', $new_file );
		$request->set_param( 'contents', $content );
		
		$response = rest_get_server()->dispatch( $request );
		
		$this->assertEquals( 200, $response->get_status() );
		
		// Verify file was created
		$this->assertTrue( file_exists( $this->test_dir . '/' . $new_file ) );
		$this->assertEquals( $content, file_get_contents( $this->test_dir . '/' . $new_file ) );
	}

	/**
	 * Test save file contents with invalid path.
	 */
	public function test_save_file_contents_invalid_path() {
		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/files/content' );
		$request->set_param( 'path', '../../../etc/passwd' );
		$request->set_param( 'contents', 'This should fail' );
		
		$response = rest_get_server()->dispatch( $request );
		
		// Should fail due to path validation
		$this->assertEquals( 500, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'invalid_path', $data['code'] );
	}
}
