<?php
/**
 * Unit tests for ServiceResponse class.
 *
 * @package DebugSuite\Tests\Unit\Core
 * @group core
 * @group service-response
 */

namespace DebugSuite\Tests\Unit\Core;

use DebugSuite\Tests\Helpers\TestCase;
use DebugSuite\Core\ServiceResponse;

/**
 * Test ServiceResponse functionality.
 */
class ServiceResponseTest extends TestCase {

	/**
	 * Test successful ServiceResponse creation.
	 *
	 * @covers \DebugSuite\Core\ServiceResponse::success
	 * @covers \DebugSuite\Core\ServiceResponse::is_success
	 * @covers \DebugSuite\Core\ServiceResponse::is_failure
	 * @covers \DebugSuite\Core\ServiceResponse::get_data
	 */
	public function test_successful_service_result() {
		$data = [ 'key' => 'value' ];
		$result = ServiceResponse::success( $data );
		
		$this->assertTrue( $result->is_success(), 'ServiceResponse should be successful' );
		$this->assertFalse( $result->is_failure(), 'ServiceResponse should not be a failure' );
		$this->assertEquals( $data, $result->get_data(), 'Data should match what was passed in' );
		$this->assertNull( $result->get_error_message(), 'Error message should be null for success' );
		$this->assertNull( $result->get_error_code(), 'Error code should be null for success' );
	}

	/**
	 * Test failed ServiceResponse creation.
	 *
	 * @covers \DebugSuite\Core\ServiceResponse::failure
	 * @covers \DebugSuite\Core\ServiceResponse::is_success
	 * @covers \DebugSuite\Core\ServiceResponse::is_failure
	 * @covers \DebugSuite\Core\ServiceResponse::get_error_message
	 * @covers \DebugSuite\Core\ServiceResponse::get_error_code
	 * @covers \DebugSuite\Core\ServiceResponse::get_error_context
	 */
	public function test_failed_service_result() {
		$message = 'Test error message';
		$code = 'test_error';
		$context = [ 'debug' => 'info' ];
		
		$result = ServiceResponse::failure( $message, $code, $context );
		
		$this->assertFalse( $result->is_success(), 'ServiceResponse should not be successful' );
		$this->assertTrue( $result->is_failure(), 'ServiceResponse should be a failure' );
		$this->assertEquals( $message, $result->get_error_message(), 'Error message should match' );
		$this->assertEquals( $code, $result->get_error_code(), 'Error code should match' );
		$this->assertEquals( $context, $result->get_error_context(), 'Error context should match' );
		$this->assertNull( $result->get_data(), 'Data should be null for failure' );
	}
	
	/**
	 * Test converting ServiceResponse to array.
	 *
	 * @covers \DebugSuite\Core\ServiceResponse::to_array
	 */
	public function test_to_array_success() {
		$data = [ 'key' => 'value', 'nested' => ['foo' => 'bar'] ];
		$result = ServiceResponse::success( $data );
		
		$expected = [
			'success' => true,
			'data' => $data,
		];
		
		$this->assertEquals( $expected, $result->to_array(), 'to_array() should return correct structure for success' );
	}
	
	/**
	 * Test converting failed ServiceResponse to array.
	 *
	 * @covers \DebugSuite\Core\ServiceResponse::to_array
	 */
	public function test_to_array_failure() {
		$message = 'Test error message';
		$code = 'test_error';
		$context = [ 'debug' => 'info' ];
		
		$result = ServiceResponse::failure( $message, $code, $context );
		
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
	 * @covers \DebugSuite\Core\ServiceResponse::get_data_or
	 */
	public function test_get_data_or_with_success() {
		$data = ['status' => 'complete'];
		$result = ServiceResponse::success( $data );
		
		$this->assertEquals($data, $result->get_data_or('default'), 'Should return data for success');
	}
	
	/**
	 * Test getting data with default when failure.
	 *
	 * @covers \DebugSuite\Core\ServiceResponse::get_data_or
	 */
	public function test_get_data_or_with_failure() {
		$default = 'default value';
		$result = ServiceResponse::failure( 'Error occurred', 'error_code' );
		
		$this->assertEquals($default, $result->get_data_or($default), 'Should return default for failure');
	}
	
	/**
	 * Test ServiceResponse with empty data.
	 *
	 * @covers \DebugSuite\Core\ServiceResponse::success
	 */
	public function test_success_with_empty_data() {
		$result = ServiceResponse::success();
		
		$this->assertTrue( $result->is_success(), 'Should be successful with empty data' );
		$this->assertNull( $result->get_data(), 'Data should be null when not provided' );
	}

	/**
	 * Test ServiceResponse with minimal failure data.
	 *
	 * @covers \DebugSuite\Core\ServiceResponse::failure
	 */
	public function test_failure_with_minimal_data() {
		$result = ServiceResponse::failure( 'Error' );
		
		$this->assertTrue( $result->is_failure(), 'Should be a failure' );
		$this->assertEquals( 'Error', $result->get_error_message(), 'Error message should match' );
		$this->assertNull( $result->get_error_code(), 'Error code should be null when not provided' );
		$this->assertEmpty( $result->get_error_context(), 'Context should be empty when not provided' );
	}
}
