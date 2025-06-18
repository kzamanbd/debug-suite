<?php
/**
 * PHPUnit bootstrap file for Debug Suite plugin tests.
 *
 * @package DebugSuite
 */

// Load Composer autoloader
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Detect if we're running the tests from the command line
$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// If WP_TESTS_DIR is not set, this is a basic test run without WP core
if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	// Only show warning if we're running integration tests that need WordPress
	if ( getenv( 'TEST_SUITE' ) === 'integration' ) {
		echo "Warning: WP_TESTS_DIR environment variable not set or not pointing to a valid WordPress test installation.\n";
		echo "Integration tests require WordPress core. Some tests may fail.\n";
	} else {
		echo "Debug Suite test bootstrap loaded.\n";
	}

	// Define basic constants for standalone testing
	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', dirname( __DIR__, 4 ) . '/' );
	}

	if ( ! defined( 'WP_CONTENT_DIR' ) ) {
		define( 'WP_CONTENT_DIR', dirname( __DIR__, 4 ) . '/wp-content' );
	}

	// Load mock functions if WP functions don't exist
	require_once __DIR__ . '/Helpers/wp-functions-mock.php';
} else {
	// This is a WordPress integrated test environment
	echo "WordPress test environment detected at: $_tests_dir\n";
	
	// Give access to tests_add_filter() function
	require_once $_tests_dir . '/includes/functions.php';

	/**
	 * Manually load the plugin being tested.
	 */
	function _manually_load_plugin() {
		require dirname( __DIR__ ) . '/debug-suite.php';
	}
	tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

	// Start up the WP testing environment
	require $_tests_dir . '/includes/bootstrap.php';
}

// Plugin constants (these should be defined in the plugin file, but we define them here for tests)
if ( ! defined( 'DEBUG_SUITE_PLUGIN_DIR' ) ) {
	define( 'DEBUG_SUITE_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'DEBUG_SUITE_PLUGIN_URL' ) ) {
	define( 'DEBUG_SUITE_PLUGIN_URL', 'http://example.org/wp-content/plugins/debug-suite/' );
}

if ( ! defined( 'DEBUG_SUITE_VERSION' ) ) {
	define( 'DEBUG_SUITE_VERSION', '1.0.0' );
}

echo "Debug Suite test bootstrap loaded.\n";
