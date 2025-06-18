<?php
/**
 * Unit tests for ServiceResult class.
 *
 * @package DebugSuite\Tests\Unit\Core
 * @group core
 * @group service-result
 */

namespace DebugSuite\Tests\Unit\Core;

use DebugSuite\Tests\Helpers\TestCase;
use DebugSuite\Core\ServiceResult;

/**
 * Test ServiceResult functionality.
 */
class ServiceResultTest extends TestCase {

	/**
	 * Test successful ServiceResult creation.
	 *
	 * @covers \DebugSuite\Core\ServiceResult::success
	 * @covers \DebugSuite\Core\ServiceResult::is_success
	 * @covers \DebugSuite\Core\ServiceResult::is_failure
	 * @covers \DebugSuite\Core\ServiceResult::get_data
	 */
	public function test_successful_service_result() {
		$data = [ 'key' => 'value' ];
		$result = ServiceResult::success( $data );
		
		$this->assertTrue( $result->is_success(), 'ServiceResult should be successful' );
		$this->assertFalse( $result->is_failure(), 'ServiceResult should not be a failure' );
		$this->assertEquals( $data, $result->get_data(), 'Data should match what was passed in' );
		$this->assertNull( $result->get_error_message(), 'Error message should be null for success' );
		$this->assertNull( $result->get_error_code(), 'Error code should be null for success' );
	}

	/**
	 * Test failed ServiceResult creation.
	 *
	 * @covers \DebugSuite\Core\ServiceResult::failure
	 * @covers \DebugSuite\Core\ServiceResult::is_success
	 * @covers \DebugSuite\Core\ServiceResult::is_failure
	 * @covers \DebugSuite\Core\ServiceResult::get_error_message
	 * @covers \DebugSuite\Core\ServiceResult::get_error_code
	 * @covers \DebugSuite\Core\ServiceResult::get_error_context
	 */
	public function test_failed_service_result() {
		$message = 'Test error message';
		$code = 'test_error';
		$context = [ 'debug' => 'info' ];
		
		$result = ServiceResult::failure( $message, $code, $context );
		
		$this->assertFalse( $result->is_success(), 'ServiceResult should not be successful' );
		$this->assertTrue( $result->is_failure(), 'ServiceResult should be a failure' );
		$this->assertEquals( $message, $result->get_error_message(), 'Error message should match' );
		$this->assertEquals( $code, $result->get_error_code(), 'Error code should match' );
		$this->assertEquals( $context, $result->get_error_context(), 'Error context should match' );
		$this->assertNull( $result->get_data(), 'Data should be null for failure' );
	}
	
	/**
	 * Test converting ServiceResult to array.
	 *
	 * @covers \DebugSuite\Core\ServiceResult::to_array
	 */
	public function test_to_array_success() {
		$data = [ 'key' => 'value', 'nested' => ['foo' => 'bar'] ];
		$result = ServiceResult::success( $data );
		
		$expected = [
			'success' => true,
			'data' => $data,
		];
		
		$this->assertEquals( $expected, $result->to_array(), 'to_array() should return correct structure for success' );
	}
	
	/**
	 * Test converting failed ServiceResult to array.
	 *
	 * @covers \DebugSuite\Core\ServiceResult::to_array
	 */
	public function test_to_array_failure() {
		$message = 'Test error message';
		$code = 'test_error';
		$context = [ 'debug' => 'info' ];
		
		$result = ServiceResult::failure( $message, $code, $context );
		
		$expected = [
			'success' => false,
			'error' => [
				'message' => $message,
				'code' => $code,
				'context' => $context,
			],
		];
		
		$this->assertEquals( $expected, $result->to_array(), 'to_array() should return correct structure for failure' );
	}
	
	/**
	 * Test getting data with default.
	 *
	 * @covers \DebugSuite\Core\ServiceResult::get_data_or
	 */
	public function test_get_data_or_with_success() {
		$data = ['status' => 'complete'];
		$result = ServiceResult::success( $data );
		
		$this->assertEquals($data, $result->get_data_or('default'), 'Should return data for success');
	}
	
	/**
	 * Test getting data with default when failure.
	 *
	 * @covers \DebugSuite\Core\ServiceResult::get_data_or
	 */
	public function test_get_data_or_with_failure() {
		$default = 'default value';
		$result = ServiceResult::failure( 'Error occurred', 'error_code' );
		
		$this->assertEquals($default, $result->get_data_or($default), 'Should return default for failure');
	}
	
	/**
	 * Test ServiceResult with empty data.
	 *
	 * @covers \DebugSuite\Core\ServiceResult::success
	 */
	public function test_success_with_empty_data() {
		$result = ServiceResult::success();
		
		$this->assertTrue( $result->is_success(), 'Should be successful with empty data' );
		$this->assertNull( $result->get_data(), 'Data should be null when not provided' );
	}

	/**
	 * Test ServiceResult with minimal failure data.
	 *
	 * @covers \DebugSuite\Core\ServiceResult::failure
	 */
	public function test_failure_with_minimal_data() {
		$result = ServiceResult::failure( 'Error' );
		
		$this->assertTrue( $result->is_failure(), 'Should be a failure' );
		$this->assertEquals( 'Error', $result->get_error_message(), 'Error message should match' );
		$this->assertNull( $result->get_error_code(), 'Error code should be null when not provided' );
		$this->assertEmpty( $result->get_error_context(), 'Context should be empty when not provided' );
	}
}
