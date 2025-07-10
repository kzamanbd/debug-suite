<?php
/**
 * Simple frontend template for Debug Suite.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Force asset registration if not already done
if ( ! wp_script_is( 'debug-suite-script', 'registered' ) || ! wp_style_is( 'debug-suite-style', 'registered' ) ) {
	$debug_suite_assets = debug_suite_resolve( 'DebugSuite\Core\Assets' );
	$debug_suite_assets->register_all_scripts();
}

$debug_suite_favicon = DEBUG_SUITE_PLUGIN_URL . 'assets/images/brand-logo.png';
$debug_suite_constants = [
	'wpDebug'        => WP_DEBUG,
	'wpDebugLog'     => WP_DEBUG_LOG,
	'wpDebugDisplay' => WP_DEBUG_DISPLAY,
	'publicRootPath' => ABSPATH,
	'filesUrl'       => content_url(),
	'favicon'        => $debug_suite_favicon,
	'wpVersion'      => get_bloginfo( 'version' ),
	'phpVersion'     => phpversion(),
	'isFrontend'     => true,
];

$debug_suite_settings = get_option( 'debug_suite_settings', [] );
$debug_suite_settings = array_merge( $debug_suite_constants, $debug_suite_settings );

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
