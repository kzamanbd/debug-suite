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
