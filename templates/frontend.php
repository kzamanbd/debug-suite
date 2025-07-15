<?php
/**
 * Simple frontend template for Debug Suite.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Force asset registration if not already done
if ( ! wp_script_is( 'debug-suite-script', 'registered' ) || ! wp_style_is( 'debug-suite-style', 'registered' ) ) {
	debug_suite()->resolve( 'DebugSuite\Assets' )->register_all_scripts();
}

$debug_suite_settings = debug_suite()->resolve( 'DebugSuite\Assets' )->get_localized_data();

// Ensure assets are enqueued
wp_enqueue_script( 'debug-suite-script' );
wp_enqueue_style( 'debug-suite-style' );

// Localize script data
wp_localize_script( 'debug-suite-script', 'debugSuite', $debug_suite_settings );

// Load our custom header template
include DEBUG_SUITE_PLUGIN_DIR . 'templates/header-debug-suite.php';
?>

<div id="debug-suite-root-app" class="debug-suite-root-app"></div>

<?php
// Load our custom footer template
include DEBUG_SUITE_PLUGIN_DIR . 'templates/footer-debug-suite.php';
?>
