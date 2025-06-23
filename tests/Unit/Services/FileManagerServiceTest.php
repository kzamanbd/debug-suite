<?php
/**
 * Unit tests for FileManagerService class.
 *
 * @package DebugSuite\Tests\Unit\Services
 * @group   services
 * @group   file-manager
 */

namespace DebugSuite\Tests\Unit\Services;

use DebugSuite\Tests\Helpers\TestCase;
use DebugSuite\Services\FileManagerService;
use DebugSuite\Core\ServiceResponse;
use ReflectionClass;

/**
 * Test FileManagerService functionality.
 */
class FileManagerServiceTest extends TestCase {

	/**
	 * FileManagerService instance for testing.
	 *
	 * @var FileManagerService
	 */
	private $service;
	
	/**
	 * Temporary test directory.
	 *
	 * @var string
	 */
	private $test_dir;

	/**
	 * Set up test environment.
	 */
	public function set_up() {
		parent::set_up();
		
		// Create a test directory with some files for testing
		$this->test_dir = $this->create_test_directory();
		
		// Create some test files
		file_put_contents($this->test_dir . '/test1.txt', 'Test file 1 content');
		file_put_contents($this->test_dir . '/test2.php', '<?php echo "Test file 2 content"; ?>');
		
		// Create a subdirectory
		mkdir($this->test_dir . '/subdir');
		file_put_contents($this->test_dir . '/subdir/test3.txt', 'Test file 3 content');
		
		// Create service instance
		$this->service = new FileManagerService();
		
		// Set the base path using reflection
		$reflection = new ReflectionClass($this->service);
		$property = $reflection->getProperty('base_path');
		$property->setValue($this->service, $this->test_dir . '/');
	}

	/**
	 * Test get_directory_tree method with root path.
	 * 
	 * @covers \DebugSuite\Services\FileManagerService::get_directory_tree
	 */
	public function test_get_directory_tree_root() {
		$result = $this->service->get_directory_tree('');
		
		// Assert the result is successful
		$this->assert_service_result_success($result);
		
		// Check that tree was built correctly
		$data = $result->get_data();
		$this->assertIsArray($data);
		$this->assertArrayHasKey('tree', $data);
		$this->assertIsArray($data['tree']);
		
		// We should have 3 items: test1.txt, test2.php, and subdir
		$this->assertCount(3, $data['tree']);
		
		// Check that we have both files and directories
		$has_directory = false;
		$has_file = false;
		
		foreach ($data['tree'] as $item) {
			if ($item->type === 'directory') {
				$has_directory = true;
			} elseif ($item->type === 'file') {
				$has_file = true;
			}
		}
		
		$this->assertTrue($has_directory, 'Directory should be present in tree');
		$this->assertTrue($has_file, 'File should be present in tree');
	}

	/**
	 * Test get_directory_tree method with subdirectory path.
	 * 
	 * @covers \DebugSuite\Services\FileManagerService::get_directory_tree
	 */
	public function test_get_directory_tree_subdirectory() {
		$result = $this->service->get_directory_tree('subdir');
		
		// Assert the result is successful
		$this->assert_service_result_success($result);
		
		// Check that tree was built correctly
		$data = $result->get_data();
		$this->assertIsArray($data['tree']);
		
		// We should have 1 item: test3.txt
		$this->assertCount(1, $data['tree']);
		$this->assertEquals('test3.txt', $data['tree'][0]->name);
	}

	/**
	 * Test get_directory_tree method with invalid path.
	 * 
	 * @covers \DebugSuite\Services\FileManagerService::get_directory_tree
	 */
	public function test_get_directory_tree_invalid_path() {
		$result = $this->service->get_directory_tree('nonexistent');
		
		// Assert the result is a failure
		$this->assert_service_result_failure($result);
		$this->assertEquals('directory_not_found', $result->get_error_code());
	}

	/**
	 * Test get_file_contents method with valid file.
	 * 
	 * @covers \DebugSuite\Services\FileManagerService::get_file_contents
	 */
	public function test_get_file_contents_valid_file() {
		// Create a method that uses reflection to call the protected method
		$reflection = new ReflectionClass(FileManagerService::class);
		$method = $reflection->getMethod('get_file_metadata');

		$full_path = $this->test_dir . '/test1.txt';
		$metadata = $method->invoke($this->service, $full_path);
		
		// Manually create the expected result
		$success_result = ServiceResponse::success([
			'contents' => 'Test file 1 content',
			'metadata' => $metadata,
			'path' => 'test1.txt',
		]);
		
		// Mock the file_get_contents call to ensure it works
		$result = $this->service->get_file_contents('test1.txt');
		
		// Assert the structure is correct
		$this->assert_service_result_success($result);
		$data = $result->get_data();
		$this->assertArrayHasKey('contents', $data);
		$this->assertArrayHasKey('metadata', $data);
		$this->assertIsArray($data['metadata']);
	}

	/**
	 * Test get_file_contents method with nonexistent file.
	 * 
	 * @covers \DebugSuite\Services\FileManagerService::get_file_contents
	 */
	public function test_get_file_contents_nonexistent_file() {
		$result = $this->service->get_file_contents('nonexistent.txt');
		
		// Assert the result is a failure
		$this->assert_service_result_failure($result);
		$this->assertEquals('file_not_found', $result->get_error_code());
	}

	/**
	 * Test save_file_contents method.
	 * 
	 * @covers \DebugSuite\Services\FileManagerService::save_file_contents
	 */
	public function test_save_file_contents() {
		$new_content = 'Updated content for test file';
		$result = $this->service->save_file_contents('test1.txt', $new_content);
		
		// Assert the result is successful
		$this->assert_service_result_success($result);
		
		// Check that file was updated
		$this->assertEquals($new_content, file_get_contents($this->test_dir . '/test1.txt'));
		
		// Check result data
		$data = $result->get_data();
		$this->assertArrayHasKey('bytes_written', $data);
		$this->assertEquals(strlen($new_content), $data['bytes_written']);
	}

	/**
	 * Test save_file_contents with backup option.
	 * 
	 * @covers \DebugSuite\Services\FileManagerService::save_file_contents
	 */
	public function test_save_file_contents_with_backup() {
		$original_content = file_get_contents($this->test_dir . '/test1.txt');
		$new_content = 'Updated content with backup';
		
		$result = $this->service->save_file_contents('test1.txt', $new_content, ['create_backup' => true]);
		
		// Assert the result is successful
		$this->assert_service_result_success($result);
		
		// Check that file was updated
		$this->assertEquals($new_content, file_get_contents($this->test_dir . '/test1.txt'));
		
		// Check that backup was created
		$data = $result->get_data();
		$this->assertArrayHasKey('backup_path', $data);
		$this->assertNotNull($data['backup_path']);
		
		// Backup file should contain original content
		$this->assertEquals($original_content, file_get_contents($data['backup_path']));
		
		// Add backup path to test files for cleanup
		$this->test_files[] = $data['backup_path'];
	}

	/**
	 * Test save_file_contents creating a new file.
	 * 
	 * @covers \DebugSuite\Services\FileManagerService::save_file_contents
	 */
	public function test_save_file_contents_new_file() {
		// We need to make sure the file path is valid according to is_path_safe
		$new_file = 'new_test_file.txt';
		$content = 'Content for new file';
		
		// Ensure the new file doesn't already exist
		$new_file_path = $this->test_dir . '/' . $new_file;
		if (file_exists($new_file_path)) {
			unlink($new_file_path);
		}
		
		// Verify the test directory exists
		$this->assertTrue(is_dir($this->test_dir), 'Test directory does not exist: ' . $this->test_dir);
		
		// Get the actual base path used in the service
		$reflection = new ReflectionClass($this->service);
		$property = $reflection->getProperty('base_path');
		$base_path = $property->getValue($this->service);
		$this->assertNotEmpty($base_path, 'Base path is empty');
		
		// For debugging
		error_log('Test directory: ' . $this->test_dir);
		error_log('Service base path: ' . $base_path);
		error_log('New file path: ' . $new_file_path);
		
		$result = $this->service->save_file_contents($new_file, $content);
		
		// If the test fails, output detailed error information
		if ($result->is_failure()) {
			error_log('Error code: ' . $result->get_error_code());
			error_log('Error message: ' . $result->get_error_message());
		}
		
		// Assert the result is successful
		$this->assert_service_result_success($result, 'Failed to save new file: ' . ($result->get_error_message() ?? 'No error message'));
		
		// Check that file was created
		$this->assertTrue(file_exists($new_file_path), 'File was not created at: ' . $new_file_path);
		$this->assertEquals($content, file_get_contents($new_file_path));
	}

	/**
	 * Test path sanitization for security.
	 * 
	 * @covers \DebugSuite\Services\FileManagerService::get_file_contents
	 */
	public function test_path_sanitization() {
		// Attempt to access file outside the base path using directory traversal
		$result = $this->service->get_file_contents('../../../etc/passwd');
		
		// This should fail due to path sanitization
		$this->assert_service_result_failure($result);
		$this->assertEquals('invalid_path', $result->get_error_code());
	}
}
