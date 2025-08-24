<?php
/**
 * Unit tests for FileSystem utility class.
 *
 * @package DebugSuite\Tests\Unit\Supports
 * @group   utils
 * @group   filesystem
 */

namespace DebugSuite\Tests\Unit\Core;

use DebugSuite\Core\FileSystem;
use DebugSuite\Tests\Helpers\TestCase;
use ReflectionClass;

/**
 * Test FileSystem utility functionality.
 */
class FileSystemTest extends TestCase {

	/**
	 * Test directory for filesystem operations.
	 *
	 * @var string
	 */
	private string $test_dir;

	/**
	 * Test file path.
	 *
	 * @var string
	 */
	private string $test_file;

	/**
	 * Set up test environment.
	 */
	public function set_up(): void {
		parent::set_up();
		
		$this->test_dir = sys_get_temp_dir() . '/debug-suite-filesystem-test-' . wp_rand();
		$this->test_file = $this->test_dir . '/test-file.txt';
		
		// Create test directory
		wp_mkdir_p( $this->test_dir );
	}

	/**
	 * Tear down test environment.
	 */
	public function tear_down(): void {
		// Clean up test files
		if ( file_exists( $this->test_file ) ) {
			unlink( $this->test_file );
		}
		
		if ( is_dir( $this->test_dir ) ) {
			rmdir( $this->test_dir );
		}
		
		parent::tear_down();
	}

	/**
	 * Test FileSystem availability check.
	 * 
	 * @covers \DebugSuite\Core\FileSystem::is_available
	 */
	public function test_is_available(): void {
		$is_available = FileSystem::is_available();
		$this->assertTrue( $is_available );
	}

	/**
	 * Test file existence check.
	 * 
	 * @covers \DebugSuite\Core\FileSystem::exists
	 */
	public function test_file_exists(): void {
		// Test non-existent file
		$this->assertFalse( FileSystem::exists( $this->test_file ) );
		
		// Create file and test existence
		file_put_contents( $this->test_file, 'test content' );
		$this->assertTrue( FileSystem::exists( $this->test_file ) );
	}

	/**
	 * Test file readability check.
	 * 
	 * @covers \DebugSuite\Core\FileSystem::is_readable
	 */
	public function test_is_readable(): void {
		// Create test file
		file_put_contents( $this->test_file, 'test content' );
		
		$this->assertTrue( FileSystem::is_readable( $this->test_file ) );
		$this->assertFalse( FileSystem::is_readable( '/non/existent/file.txt' ) );
	}

	/**
	 * Test file writability check.
	 * 
	 * @covers \DebugSuite\Core\FileSystem::is_writable
	 */
	public function test_is_writable(): void {
		// Create test file
		file_put_contents( $this->test_file, 'test content' );
		
		$this->assertTrue( FileSystem::is_writable( $this->test_file ) );
		$this->assertTrue( FileSystem::is_writable( $this->test_dir ) );
	}

	/**
	 * Test reading file contents.
	 * 
	 * @covers \DebugSuite\Core\FileSystem::get_contents
	 */
	public function test_get_contents(): void {
		$test_content = 'This is test content for FileSystem class.';
		
		// Create test file
		file_put_contents( $this->test_file, $test_content );
		
		$content = FileSystem::get_contents( $this->test_file );
		$this->assertEquals( $test_content, $content );
		
		// Test non-existent file
		$this->assertFalse( FileSystem::get_contents( '/non/existent/file.txt' ) );
	}

	/**
	 * Test writing file contents.
	 * 
	 * @covers \DebugSuite\Core\FileSystem::put_contents
	 */
	public function test_put_contents(): void {
		$test_content = 'This is new test content.';
		
		$result = FileSystem::put_contents( $this->test_file, $test_content );
		$this->assertTrue( $result );
		
		// Verify content was written correctly
		$written_content = file_get_contents( $this->test_file );
		$this->assertEquals( $test_content, $written_content );
	}

	/**
	 * Test copying files.
	 * 
	 * @covers \DebugSuite\Core\FileSystem::copy
	 */
	public function test_copy(): void {
		$test_content = 'Content to copy';
		$copy_file = $this->test_dir . '/copy-test.txt';
		
		// Create source file
		file_put_contents( $this->test_file, $test_content );
		
		$result = FileSystem::copy( $this->test_file, $copy_file );
		$this->assertTrue( $result );
		
		// Verify copy was successful
		$this->assertTrue( file_exists( $copy_file ) );
		$this->assertEquals( $test_content, file_get_contents( $copy_file ) );
		
		// Clean up
		unlink( $copy_file );
	}

	/**
	 * Test getting file size.
	 * 
	 * @covers \DebugSuite\Core\FileSystem::size
	 */
	public function test_size(): void {
		$test_content = 'This content has a specific length.';
		
		// Create test file
		file_put_contents( $this->test_file, $test_content );
		
		$size = FileSystem::size( $this->test_file );
		$this->assertEquals( strlen( $test_content ), $size );
		
		// Test non-existent file
		$this->assertEquals( 0, FileSystem::size( '/non/existent/file.txt' ) );
	}

	/**
	 * Test getting file modification time.
	 * 
	 * @covers \DebugSuite\Core\FileSystem::mtime
	 */
	public function test_mtime(): void {
		// Create test file
		file_put_contents( $this->test_file, 'test content' );
		
		$mtime = FileSystem::mtime( $this->test_file );
		$this->assertIsInt( $mtime );
		$this->assertGreaterThan( 0, $mtime );
		
		// Test non-existent file
		$this->assertEquals( 0, FileSystem::mtime( '/non/existent/file.txt' ) );
	}

	/**
	 * Test directory checks.
	 * 
	 * @covers \DebugSuite\Core\FileSystem::is_dir
	 */
	public function test_is_dir(): void {
		$this->assertTrue( FileSystem::is_dir( $this->test_dir ) );
		
		// Create test file
		file_put_contents( $this->test_file, 'test content' );
		$this->assertFalse( FileSystem::is_dir( $this->test_file ) );
		
		$this->assertFalse( FileSystem::is_dir( '/non/existent/directory' ) );
	}

	/**
	 * Test file checks.
	 * 
	 * @covers \DebugSuite\Core\FileSystem::is_file
	 */
	public function test_is_file(): void {
		$this->assertFalse( FileSystem::is_file( $this->test_dir ) );
		
		// Create test file
		file_put_contents( $this->test_file, 'test content' );
		$this->assertTrue( FileSystem::is_file( $this->test_file ) );
		
		$this->assertFalse( FileSystem::is_file( '/non/existent/file.txt' ) );
	}

	/**
	 * Test reading file tail.
	 * 
	 * @covers \DebugSuite\Core\FileSystem::read_tail
	 */
	public function test_read_file_tail(): void {
		$test_content = str_repeat( 'This is a line of content.' . PHP_EOL, 10 );
		
		// Create test file
		file_put_contents( $this->test_file, $test_content );
		
		// Read last 50 bytes
		$tail_content = FileSystem::read_tail( $this->test_file, 50 );
		$this->assertIsString( $tail_content );
		$this->assertEquals( 50, strlen( $tail_content ) );
		
		// Test reading more bytes than file size
		$full_content = FileSystem::read_tail( $this->test_file, strlen( $test_content ) + 100 );
		$this->assertEquals( $test_content, $full_content );
		
		// Test non-existent file
		$this->assertFalse( FileSystem::read_tail( '/non/existent/file.txt', 100 ) );
	}

	/**
	 * Test getting file permissions.
	 * 
	 * @covers \DebugSuite\Core\FileSystem::get_permissions
	 */
	public function test_get_permissions(): void {
		// Create test file
		file_put_contents( $this->test_file, 'test content' );
		
		$permissions = FileSystem::get_permissions( $this->test_file );
		$this->assertIsString( $permissions );
	}

	/**
	 * Test formatting file size.
	 * 
	 * @covers \DebugSuite\Core\FileSystem::format_size
	 */
	public function test_format_size(): void {
		$this->assertEquals( 'N/A', FileSystem::format_size( 0 ) );
		$this->assertEquals( '1 byte', FileSystem::format_size( 1 ) );
		$this->assertEquals( '512 bytes', FileSystem::format_size( 512 ) );
		$this->assertEquals( '1.00 KB', FileSystem::format_size( 1024 ) );
		$this->assertEquals( '1.00 MB', FileSystem::format_size( 1024 * 1024 ) );
		$this->assertEquals( '1.00 GB', FileSystem::format_size( 1024 * 1024 * 1024 ) );
	}

	/**
	 * Test fallback to PHP functions when WordPress filesystem is not available.
	 * 
	 * @covers \DebugSuite\Core\FileSystem::exists
	 * @covers \DebugSuite\Core\FileSystem::is_readable
	 * @covers \DebugSuite\Core\FileSystem::get_contents
	 */
	public function test_fallback_to_php_functions(): void {
		// Create test file
		$test_content = 'Testing fallback functionality';
		file_put_contents( $this->test_file, $test_content );
		
		// Force filesystem to be null temporarily to test fallbacks
		$reflection = new ReflectionClass( FileSystem::class );
		$filesystem_property = $reflection->getProperty( 'filesystem' );
		$original_filesystem = $filesystem_property->getValue();
		$filesystem_property->setValue( null, null );
		
		// Test fallback methods
		$this->assertTrue( FileSystem::exists( $this->test_file ), 'exists() should fallback to file_exists()' );
		$this->assertTrue( FileSystem::is_readable( $this->test_file ), 'is_readable() should fallback to is_readable()' );
		$this->assertEquals( $test_content, FileSystem::get_contents( $this->test_file ), 'get_contents() should fallback to file_get_contents()' );
		
		// Restore original filesystem
		$filesystem_property->setValue( null, $original_filesystem );
	}
}
