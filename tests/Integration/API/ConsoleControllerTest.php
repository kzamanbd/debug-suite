<?php
/**
 * Integration tests for ConsoleController REST API.
 *
 * @package DebugSuite\Tests\Integration\API
 * @group api
 * @group integration
 * @group rest-api
 */

namespace DebugSuite\Tests\Integration\API;

use DebugSuite\API\ConsoleController;
use DebugSuite\Services\Console\ConsoleService;
use DebugSuite\Services\Console\ConsoleSettingsService;
use DebugSuite\Tests\Helpers\DebugSuiteTestCase;
use WP_REST_Request;
use WP_REST_Server;

class ConsoleControllerTest extends DebugSuiteTestCase {

	protected $namespace = 'debug-suite/v1';
	private $controller;

	public function set_up(): void {
		parent::set_up();
		if ( ! $this->is_wordpress_available() ) {
			$this->markTestSkipped( 'WordPress test environment not available' );
		}

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->controller = new ConsoleController( new ConsoleService(), new ConsoleSettingsService() );
		$this->controller->register_routes();
		do_action( 'rest_api_init' );

		$this->create_admin_user();
	}

	public function test_execute_returns_output_shape(): void {
		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/console/execute' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( json_encode( [ 'input' => 'return 40 + 2;' ] ) );

		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'output', $data );
		$this->assertArrayHasKey( 'dump', $data );
		$this->assertArrayHasKey( 'execution_time', $data );
		$this->assertStringContainsString( '42', $data['output'] );
	}

	public function test_execute_rejects_empty_input(): void {
		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/console/execute' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( json_encode( [ 'input' => '   ' ] ) );

		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 400, $response->get_status() );
	}

	public function test_execute_forbidden_for_non_admin(): void {
		wp_set_current_user( $this->factory()->user->create() );
		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/console/execute' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( json_encode( [ 'input' => 'return 1;' ] ) );

		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 403, $response->get_status() );
	}

	public function test_settings_roundtrip(): void {
		$post = new WP_REST_Request( 'POST', '/' . $this->namespace . '/console/settings' );
		$post->set_header( 'content-type', 'application/json' );
		$post->set_body( json_encode( [ 'window_split' => 'horizontal' ] ) );
		rest_get_server()->dispatch( $post );

		$get      = new WP_REST_Request( 'GET', '/' . $this->namespace . '/console/settings' );
		$response = rest_get_server()->dispatch( $get );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'horizontal', $response->get_data()['window_split'] );
	}
}
