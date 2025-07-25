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
        $this->service = new EmailLogService();
    }

    /**
     * Test hook registration.
     */
    public function test_register_hooks(): void {
        $this->service->register_hooks();

        $this->assertTrue( has_action( 'wp_mail', [ $this->service, 'capture_email_data' ] ) );
        $this->assertTrue( has_action( 'wp_mail_succeeded', [ $this->service, 'log_email_success' ] ) );
        $this->assertTrue( has_action( 'wp_mail_failed', [ $this->service, 'log_email_failure' ] ) );
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
     * Test validation.
     */
    public function test_validation(): void {
        $result = $this->service->get_email_log_entries( [ 'status' => 'invalid' ] );

        $this->assert_service_result_failure( $result );
        $this->assertEquals( 'invalid_status', $result->get_error_code() );
    }

    /**
     * Test bulk delete.
     */
    public function test_bulk_delete(): void {
        $result = $this->service->bulk_delete_emails( [] );

        $this->assert_service_result_failure( $result );
    }
}
