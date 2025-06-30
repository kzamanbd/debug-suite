<?php
/**
 * PHPUnit Bootstrap file for Debug Suite plugin tests.
 *
 * This file is loaded before running any tests and sets up the WordPress
 * testing environment for both unit and integration tests.
 *
 * @package DebugSuite\Tests
 */


// Set test environment constants
if( ! defined( 'DEBUG_SUITE_TESTING' ) ) {
	define( 'DEBUG_SUITE_TESTING', true );
}
if( ! defined( 'DEBUG_SUITE_TEST_DIR' ) ) {
	define( 'DEBUG_SUITE_TEST_DIR', __DIR__ );
}
if ( ! defined( 'DEBUG_SUITE_PLUGIN_DIR' ) ) {
	define( 'DEBUG_SUITE_PLUGIN_DIR', dirname( __DIR__ ) );
}

// WordPress test configuration
$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Check if WordPress test library exists
if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run tests/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo "Please run: tests/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version]" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Load Debug Suite plugin for testing.
 *
 * @return void
 */
function _manually_load_debug_suite_plugin(): void {
	// Load Composer autoloader
	$autoloader = DEBUG_SUITE_PLUGIN_DIR . '/vendor/autoload.php';
	if ( file_exists( $autoloader ) ) {
		require_once $autoloader;
	} else {
		echo "Composer autoloader not found. Please run 'composer install' first." . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit( 1 );
	}

	// Load plugin helpers
	require_once DEBUG_SUITE_PLUGIN_DIR . '/includes/helpers.php';

	// Load the main plugin file
	require_once DEBUG_SUITE_PLUGIN_DIR . '/debug-suite.php';
}

// Load the plugin before WordPress initializes
tests_add_filter( 'muplugins_loaded', '_manually_load_debug_suite_plugin' );

/**
 * Setup WordPress testing environment customizations.
 *
 * @return void
 */
function _setup_debug_suite_test_environment(): void {
	// Set up any test-specific WordPress configurations
	if ( ! defined( 'WP_DEBUG' ) ) {
		define( 'WP_DEBUG', true );
	}
	
	if ( ! defined( 'WP_DEBUG_LOG' ) ) {
		define( 'WP_DEBUG_LOG', true );
	}

	// Create test directories if needed
	$test_dirs = [
		DEBUG_SUITE_TEST_DIR . '/temp',
		DEBUG_SUITE_TEST_DIR . '/coverage',
		DEBUG_SUITE_TEST_DIR . '/reports',
	];

	foreach ( $test_dirs as $dir ) {
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
	}
}

// Setup test environment
tests_add_filter( 'init', '_setup_debug_suite_test_environment' );

/**
 * Cleanup test environment after each test.
 *
 * @return void
 */
function _cleanup_debug_suite_test_environment(): void {
	// Clean up any test files
	$temp_dir = DEBUG_SUITE_TEST_DIR . '/temp';
	if ( is_dir( $temp_dir ) ) {
		array_map( 'unlink', glob( $temp_dir . '/*' ) );
	}
}

// Clean up after tests
tests_add_filter( 'wp_loaded', '_cleanup_debug_suite_test_environment' );

// Start the WordPress testing framework
require $_tests_dir . '/includes/bootstrap.php';

// Load our test helper classes
require_once __DIR__ . '/Helpers/DebugSuiteTestCase.php';
require_once __DIR__ . '/Helpers/MockFactory.php';
// Load WordPress function mocks for unit tests
require_once __DIR__ . '/Helpers/wp-functions-mock.php';

echo "Debug Suite Test Bootstrap Complete!" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped