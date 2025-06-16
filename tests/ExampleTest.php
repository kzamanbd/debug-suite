<?php
/**
 * Example test case for Debug Suite.
 *
 * @package DebugSuite
 */

use PHPUnit\Framework\TestCase;

/**
 * Example test case for Debug Suite plugin functionality.
 *
 * Provides basic test cases to verify that core plugin functionality
 * is working correctly, including REST API endpoints.
 *
 * @since DEBUG_SUITE_SINCE
 */
class ExampleTest extends TestCase
{
    /**
     * Test that true equals true.
     *
     * Basic sanity test to verify that the testing framework is working.
     *
     * @since DEBUG_SUITE_SINCE
     *
     * @return void
     */
    public function test_true_is_true(): void
    {
        $this->assertTrue(true);
    }

    /**
     * Test that the settings REST API endpoint exists.
     *
     * Verifies that the Debug Suite settings REST API endpoint is properly
     * registered and responds with appropriate status codes.
     *
     * @since DEBUG_SUITE_SINCE
     *
     * @return void
     */
    public function test_settings_rest_api_endpoint_exists(): void
    {
        if ( ! function_exists( 'rest_get_server' ) ) {
            $this->markTestSkipped( 'REST API not available.' );
        }

        // Simulate a GET request to the settings endpoint.
        $request = new \WP_REST_Request( 'GET', '/debug-suite/v1/settings' );
        $response = rest_get_server()->dispatch( $request );
        $data = $response->get_data();
        $status = $response->get_status();

        // The endpoint should return 200 or 403 (if not authenticated).
        $this->assertContains($status, [200, 403], 'Settings endpoint should exist and return 200 or 403.');
    }
}
