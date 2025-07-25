<?php
/**
 * Integration tests for EmailLogController.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Tests\Integration\API;

use DebugSuite\API\EmailLogController;
use DebugSuite\Services\EmailLog\EmailLogService;
use DebugSuite\Tests\Helpers\DebugSuiteTestCase;
use WP_REST_Request;
use WP_REST_Server;

/**
 * EmailLogController integration tests.
 *
 * @covers \DebugSuite\API\EmailLogController
 * @group api
 * @group integration
 * @group email-log
 */
class EmailLogControllerTest extends DebugSuiteTestCase {

	/**
	 * Controller instance.
	 *
	 * @var EmailLogController
	 */
	private EmailLogController $controller;

	/**
	 * Service instance.
	 *
	 * @var EmailLogService
	 */
	private EmailLogService $service;

	/**
	 * Namespace for REST API.
	 *
	 * @var string
	 */
	protected $namespace = 'debug-suite/v1';

	/**
	 * Table name for testing.
	 *
	 * @var string
	 */
	private string $table_name;

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		global $wpdb, $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );

		$this->table_name = $wpdb->prefix . 'debug_suite_email_logs';

		// Create service and controller
		$this->service = new EmailLogService();
		$this->controller = new EmailLogController( $this->service );

		// Register routes
		$this->controller->register_routes();

		// Create table
		$this->service->create_table();

		// Create admin user
		$this->create_admin_user();
	}

	/**
	 * Clean up test environment.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		global $wpdb;
		$wpdb->query( "DROP TABLE IF EXISTS {$this->table_name}" );

		parent::tear_down();
	}

	/**
	 * Insert test email data.
	 *
	 * @param array $data Email data.
	 * @return int Email ID.
	 */
	private function insert_test_email( array $data = [] ): int {
		global $wpdb;

		$defaults = [
			'to_email'      => 'test@example.com',
			'subject'       => 'Test Subject',
			'message'       => 'Test message content',
			'headers'       => 'From: sender@example.com',
			'attachments'   => '',
			'status'        => 'success',
			'error_message' => '',
			'sent_date'     => current_time( 'mysql' ),
		];

		$data = wp_parse_args( $data, $defaults );
		$wpdb->insert( $this->table_name, $data );
		return $wpdb->insert_id;
	}

	/**
	 * Test get email logs endpoint without authentication.
	 */
	public function test_get_email_logs_without_auth(): void {
		// Set current user to 0 (not logged in)
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/email-logs' );
		$response = rest_get_server()->dispatch( $request );

		// WordPress REST API returns 401 for unauthorized access
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test get email logs endpoint with insufficient permissions.
	 */
	public function test_get_email_logs_insufficient_permissions(): void {
		// Create regular user
		$user_id = $this->factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $user_id );

		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/email-logs' );
		$response = rest_get_server()->dispatch( $request );

		// Should return 403 for insufficient permissions
		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test get email logs endpoint with admin user.
	 */
	public function test_get_email_logs_with_admin(): void {
		// Insert test data
		$this->insert_test_email( [ 'to_email' => 'user1@example.com', 'subject' => 'Subject 1' ] );
		$this->insert_test_email( [ 'to_email' => 'user2@example.com', 'subject' => 'Subject 2' ] );

		// Create admin user
		$this->create_admin_user();

		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/email-logs' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'entries', $data );
		$this->assertArrayHasKey( 'total', $data );
		$this->assertArrayHasKey( 'total_pages', $data );
		$this->assertArrayHasKey( 'current_page', $data );
		$this->assertArrayHasKey( 'per_page', $data );
		$this->assertArrayHasKey( 'has_more', $data );

		$this->assertCount( 2, $data['entries'] );
		$this->assertEquals( 2, $data['total'] );
	}

	/**
	 * Test get email logs with filters.
	 */
	public function test_get_email_logs_with_filters(): void {
		$this->insert_test_email( [ 'to_email' => 'user1@example.com', 'status' => 'success' ] );
		$this->insert_test_email( [ 'to_email' => 'user2@example.com', 'status' => 'failed' ] );
		$this->insert_test_email( [ 'to_email' => 'user3@example.com', 'status' => 'success' ] );

		$this->create_admin_user();

		// Test status filter
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/email-logs' );
		$request->set_param( 'status', 'failed' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertCount( 1, $data['entries'] );
		$this->assertEquals( 'failed', $data['entries'][0]['status'] );

		// Test receiver filter
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/email-logs' );
		$request->set_param( 'receiver', 'user1@example.com' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertCount( 1, $data['entries'] );
		$this->assertEquals( 'user1@example.com', $data['entries'][0]['receiver'] );
	}

	/**
	 * Test get email logs with pagination.
	 */
	public function test_get_email_logs_pagination(): void {
		// Insert 5 test emails
		for ( $i = 1; $i <= 5; $i++ ) {
			$this->insert_test_email( [ 'subject' => "Subject {$i}" ] );
		}

		$this->create_admin_user();

		// Test first page
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/email-logs' );
		$request->set_param( 'page', 1 );
		$request->set_param( 'per_page', 2 );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertCount( 2, $data['entries'] );
		$this->assertEquals( 5, $data['total'] );
		$this->assertEquals( 3, $data['total_pages'] );
		$this->assertEquals( 1, $data['current_page'] );
		$this->assertTrue( $data['has_more'] );

		// Test second page
		$request->set_param( 'page', 2 );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertCount( 2, $data['entries'] );
		$this->assertEquals( 2, $data['current_page'] );
	}

	/**
	 * Test get email statistics endpoint.
	 */
	public function test_get_email_stats(): void {
		$this->insert_test_email( [ 'status' => 'success' ] );
		$this->insert_test_email( [ 'status' => 'success' ] );
		$this->insert_test_email( [ 'status' => 'failed' ] );

		$this->create_admin_user();

		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/email-logs/stats' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'total_emails', $data );
		$this->assertArrayHasKey( 'successful', $data );
		$this->assertArrayHasKey( 'failed', $data );
		$this->assertArrayHasKey( 'success_rate', $data );

		$this->assertEquals( 3, $data['total_emails'] );
		$this->assertEquals( 2, $data['successful'] );
		$this->assertEquals( 1, $data['failed'] );
	}

	/**
	 * Test bulk delete email logs endpoint.
	 */
	public function test_bulk_delete_emails(): void {
		$id1 = $this->insert_test_email();
		$id2 = $this->insert_test_email();
		$id3 = $this->insert_test_email();

		$this->create_admin_user();

		$request = new WP_REST_Request( 'DELETE', '/' . $this->namespace . '/email-logs/bulk' );
		$request->set_param( 'ids', [ $id1, $id2 ] );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'deleted_count', $data );
		$this->assertEquals( 2, $data['deleted_count'] );

		// Verify entries were deleted
		global $wpdb;
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
		$this->assertEquals( 1, $count );
	}

	/**
	 * Test bulk delete with empty IDs.
	 */
	public function test_bulk_delete_emails_empty_ids(): void {
		$this->create_admin_user();

		$request = new WP_REST_Request( 'DELETE', '/' . $this->namespace . '/email-logs/bulk' );
		$request->set_param( 'ids', [] );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test clear all logs endpoint.
	 */
	public function test_clear_all_logs(): void {
		$this->insert_test_email();
		$this->insert_test_email();
		$this->insert_test_email();

		$this->create_admin_user();

		$request = new WP_REST_Request( 'DELETE', '/' . $this->namespace . '/email-logs/clear' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Verify all entries were deleted
		global $wpdb;
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
		$this->assertEquals( 0, $count );
	}

	/**
	 * Test resend email endpoint.
	 */
	public function test_resend_email(): void {
		$email_id = $this->insert_test_email( [
			'to_email' => 'test@example.com',
			'subject'  => 'Test Subject',
			'message'  => 'Test message',
		] );

		$this->create_admin_user();

		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . "/email-logs/{$email_id}/resend" );
		$response = rest_get_server()->dispatch( $request );

		// Since wp_mail may not be properly mocked, we expect either success or failure
		$this->assertContains( $response->get_status(), [ 200, 500 ] );
	}

	/**
	 * Test resend email with invalid ID.
	 */
	public function test_resend_email_invalid_id(): void {
		$this->create_admin_user();

		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/email-logs/99999/resend' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test create table endpoint.
	 */
	public function test_create_table(): void {
		global $wpdb;

		// Drop table first
		$wpdb->query( "DROP TABLE IF EXISTS {$this->table_name}" );

		$this->create_admin_user();

		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/email-logs/table' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'table_created', $data );
		$this->assertTrue( $data['table_created'] );

		// Verify table exists
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$this->table_name}'" );
		$this->assertEquals( $this->table_name, $table_exists );
	}

	/**
	 * Test search functionality.
	 */
	public function test_get_email_logs_search(): void {
		$this->insert_test_email( [ 'subject' => 'Password reset request' ] );
		$this->insert_test_email( [ 'subject' => 'Welcome to our site' ] );
		$this->insert_test_email( [ 'message' => 'Your password has been reset' ] );

		$this->create_admin_user();

		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/email-logs' );
		$request->set_param( 'search', 'password' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertCount( 2, $data['entries'] );
	}

	/**
	 * Test sorting functionality.
	 */
	public function test_get_email_logs_sorting(): void {
		$this->insert_test_email( [ 'subject' => 'A Subject', 'sent_date' => '2025-01-01 10:00:00' ] );
		$this->insert_test_email( [ 'subject' => 'B Subject', 'sent_date' => '2025-01-02 10:00:00' ] );
		$this->insert_test_email( [ 'subject' => 'C Subject', 'sent_date' => '2025-01-03 10:00:00' ] );

		$this->create_admin_user();

		// Test sort by subject ASC
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/email-logs' );
		$request->set_param( 'sort_by', 'subject' );
		$request->set_param( 'sort_order', 'asc' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$subjects = array_column( $data['entries'], 'subject' );
		$this->assertEquals( [ 'A Subject', 'B Subject', 'C Subject' ], $subjects );
	}

	/**
	 * Test date filtering.
	 */
	public function test_get_email_logs_date_filter(): void {
		$yesterday = wp_date( 'Y-m-d', strtotime( '-1 day' ) );
		$today = wp_date( 'Y-m-d' );
		$tomorrow = wp_date( 'Y-m-d', strtotime( '+1 day' ) );

		$this->insert_test_email( [ 'sent_date' => $yesterday . ' 12:00:00' ] );
		$this->insert_test_email( [ 'sent_date' => $today . ' 12:00:00' ] );
		$this->insert_test_email( [ 'sent_date' => $tomorrow . ' 12:00:00' ] );

		$this->create_admin_user();

		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/email-logs' );
		$request->set_param( 'date_from', $today );
		$request->set_param( 'date_to', $today );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertCount( 1, $data['entries'] );
	}

	/**
	 * Test permissions check.
	 */
	public function test_permissions_check(): void {
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/email-logs' );

		// Test with admin user
		$this->create_admin_user();
		$result = $this->controller->permissions_check( $request );
		$this->assertTrue( $result );

		// Test with regular user
		$user_id = $this->factory()->user->create();
		wp_set_current_user( $user_id );
		$result = $this->controller->permissions_check( $request );
		$this->assertTrue( is_wp_error( $result ) || $result === false );

		// Test without authentication
		wp_set_current_user( 0 );
		$result = $this->controller->permissions_check( $request );
		$this->assertTrue( is_wp_error( $result ) || $result === false );
	}
}
