<?php
/**
 * Tests for EmailLogService class.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Tests\Unit\Services\EmailLog;

use DebugSuite\Core\ServiceResponse;
use DebugSuite\Services\EmailLog\EmailLogService;
use DebugSuite\Tests\Helpers\DebugSuiteTestCase;
use DebugSuite\Install;

/**
 * Test EmailLogService functionality.
 *
 * @covers \DebugSuite\Services\EmailLog\EmailLogService
 * @group services
 * @group email-log
 */
class EmailLogServiceTest extends DebugSuiteTestCase {

    /**
     * EmailLogService instance.
     *
     * @var EmailLogService
     */
    private EmailLogService $service;

    /**
     * Set up test environment.
     */
    public function set_up() {
        parent::set_up();
        
        // Create the email logs table for testing
        Install::create_email_logs_table();
        
        $this->service = new EmailLogService();
    }

    /**
     * Clean up test environment.
     */
    public function tear_down(): void {
        global $wpdb;
        $table_name = $wpdb->prefix . 'debug_suite_email_logs';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
        
        parent::tear_down();
    }

    /**
     * Test hook registration.
     */
    public function test_register_hooks(): void {
        $this->service->register_hooks();

        $this->assertNotFalse( has_action( 'wp_mail', [ $this->service, 'capture_email_data' ] ) );
        $this->assertNotFalse( has_action( 'wp_mail_succeeded', [ $this->service, 'log_email_success' ] ) );
        $this->assertNotFalse( has_action( 'wp_mail_failed', [ $this->service, 'log_email_failure' ] ) );
    }

    /**
     * Test capture email data.
     */
    public function test_capture_email_data(): void {
        $mail_data = [
            'to'      => 'test@example.com',
            'subject' => 'Test Subject',
            'message' => 'Test message content',
        ];

        $result = $this->service->capture_email_data( $mail_data );
        $this->assertEquals( $mail_data, $result );
    }

    /**
     * Test get email log entries.
     */
    public function test_get_email_log_entries(): void {
        $result = $this->service->get_email_log_entries();

        $this->assertInstanceOf( ServiceResponse::class, $result );
    }

    /**
     * Test basic functionality.
     */
    public function test_basic_get_entries(): void {
        $result = $this->service->get_email_log_entries( [ 'limit' => 50 ] );

        $this->assert_service_result_success( $result );
        $this->assertArrayHasKey( 'entries', $result->get_data() );
        $this->assertArrayHasKey( 'total_count', $result->get_data() );
        $this->assertArrayHasKey( 'current_page', $result->get_data() );
        $this->assertArrayHasKey( 'total_pages', $result->get_data() );
    }

    /**
     * Test bulk delete.
     */
    public function test_bulk_delete(): void {
        $result = $this->service->bulk_delete_emails( [] );

        $this->assert_service_result_failure( $result );
    }
}
