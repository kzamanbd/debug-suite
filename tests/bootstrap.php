<?php
/**
 * PHPUnit bootstrap file for Debug Suite.
 *
 * @package DebugSuite
 */

define( 'DS_TESTS_DIR', __DIR__ );

autoload_if_possible();

function autoload_if_possible(): void {
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if ( file_exists( $autoload ) ) {
        require_once $autoload;
    }
}

// Set up WordPress test environment if needed.
// You may need to adjust this path depending on your setup.
// require_once getenv( 'WP_TESTS_DIR' ) . '/includes/bootstrap.php';
