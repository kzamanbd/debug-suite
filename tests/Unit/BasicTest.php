<?php
/**
 * Simple test to verify PHPUnit setup.
 *
 * @package DebugSuite\Tests\Unit
 * @group setup
 */

namespace DebugSuite\Tests\Unit;

use DebugSuite\Tests\Helpers\TestCase;

/**
 * Basic test to verify PHPUnit is working.
 */
class BasicTest extends TestCase {

	/**
	 * Test that PHPUnit is working.
	 *
	 * This is a simple assertion to verify the testing setup.
	 */
	public function test_phpunit_works() {
		$this->assertTrue( true, 'PHPUnit should be working properly' );
	}

	/**
	 * Test basic PHP functionality.
	 * 
	 * Verifies that PHP comparison and array functions work as expected.
	 */
	public function test_basic_php() {
		$this->assertEquals( 'hello', 'hello', 'String equality should work' );
		$this->assertCount( 3, [ 1, 2, 3 ], 'Array count should work' );
		$this->assertContains( 2, [ 1, 2, 3 ], 'Array contains should work' );
	}

	/**
	 * Test autoloader is working.
	 * 
	 * Verifies that Composer's autoloader can find the plugin classes.
	 */
	public function test_autoloader() {
		$this->assertTrue( 
			class_exists( 'DebugSuite\Core\ServiceResponse' ), 
			'ServiceResponse class should be autoloadable'
		);
		
		$this->assertTrue( 
			class_exists( 'DebugSuite\Core\Container\Container' ),
			'Container class should be autoloadable'
		);
		
		$this->assertTrue( 
			class_exists( 'DebugSuite\Core\Container\Definitions\AutowiredDefinition' ),
			'AutowiredDefinition class should be autoloadable'
		);
	}
}
