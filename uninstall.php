<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * @link       https://kzaman.me/plugins/debug-suite
 * @since      1.0.0
 *
 * @package    Debug_Suite
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load the Install class for database cleanup
require_once plugin_dir_path( __FILE__ ) . 'includes/Install.php';

// Delete options
delete_option( 'debug_suite_version' );
delete_option( 'debug_suite_needs_onboarding' );
delete_transient( 'debug_suite_activation_redirect' );

// Drop database tables
\DebugSuite\Install::drop_tables();

// Clean up email logs table
global $wpdb;
$table_name = $wpdb->prefix . 'debug_suite_email_logs';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
