<?php
/**
 * Simple frontend template for Debug Suite.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Force asset registration if not already done
if ( ! wp_script_is( 'debug-suite-script', 'registered' ) || ! wp_style_is( 'debug-suite-style', 'registered' ) ) {
	$assets = debug_suite_resolve( 'DebugSuite\Core\Assets' );
	$assets->register_all_scripts();
}

// Prepare settings for frontend (same as admin but with frontend flag)
global $wp_roles;
if ( ! isset( $wp_roles ) ) {
	$wp_roles = new WP_Roles();
}

$roles = array_map(
	function ( $role ) {
		return [
			'name' => $role['name'],
		];
	},
	$wp_roles->roles
);

$favicon = DEBUG_SUITE_PLUGIN_URL . 'assets/images/brand-logo.png';
$constants = [
	'wpDebug'        => WP_DEBUG,
	'wpDebugLog'     => WP_DEBUG_LOG,
	'wpDebugDisplay' => WP_DEBUG_DISPLAY,
	'publicRootPath' => ABSPATH,
	'filesUrl'       => content_url(),
	'roles'          => $roles,
	'favicon'        => $favicon,
	'wpVersion'      => get_bloginfo( 'version' ),
	'phpVersion'     => phpversion(),
	'isFrontend'     => true,
];

$settings = get_option( 'debug_suite_settings', [] );
$settings = array_merge( $constants, $settings );

// Ensure assets are enqueued
wp_enqueue_script( 'debug-suite-script' );
wp_enqueue_style( 'debug-suite-style' );

// Localize script data
wp_localize_script( 'debug-suite-script', 'debugSuite', $settings );

// Load our custom header template
include DEBUG_SUITE_PLUGIN_DIR . 'templates/header-debug-suite.php';
?>

<div id="debug-suite-admin-app" class="debug-suite-admin-app"></div>

<?php
// Load our custom footer template
include DEBUG_SUITE_PLUGIN_DIR . 'templates/footer-debug-suite.php';
?>
