<?php
/**
 * Upgrader class for handling database and plugin updates.
 * Pattern follows Dokan-lite style reusable upgrade routine.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core;

use DebugSuite\Interfaces\Hookable;
use Exception;

/**
 * Upgrader Handler.
 *
 * @since 1.1.2
 */
class Upgrader implements Hookable {

	/**
	 * Map of version -> update class.
	 *
	 * @var array<string, string>
	 */
	private const UPDATES = [
		'1.1.3' => \DebugSuite\Upgrades\Upgrade_1_1_3::class,
	];

	/**
	 * Register hooks for the upgrader.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_notices', [ $this, 'show_upgrade_notice' ] );
		add_action( 'wp_ajax_debug_suite_do_upgrade', [ $this, 'ajax_do_upgrade' ] );
	}

	/**
	 * Check if an upgrade is required.
	 *
	 * @return bool
	 */
	public function is_upgrade_required(): bool {
		$installed_version = get_option( 'debug_suite_version' );

		if ( ! $installed_version ) {
			return false; // Handled by check_installation()
		}

		// Check if we have defined update routines that are greater than the installed version.
		foreach ( array_keys( self::UPDATES ) as $version ) {
			if ( version_compare( $installed_version, (string) $version, '<' ) ) {
				return true;
			}
		}

		// Also check if our overall plugin version differs (just needs a bump).
		return version_compare( $installed_version, DEBUG_SUITE_VERSION, '<' );
	}

	/**
	 * Show admin notice for pending updates and provide an upgrade button.
	 *
	 * @return void
	 */
	public function show_upgrade_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! $this->is_upgrade_required() ) {
			return;
		}

		$current_version = get_option( 'debug_suite_version', '1.0.0' );
		$latest_version  = DEBUG_SUITE_VERSION;

		debug_suite_template( 'upgrade-notice', compact( 'current_version', 'latest_version' ) );
	}

	/**
	 * Handle AJAX database upgrade request.
	 *
	 * @return void
	 */
	public function ajax_do_upgrade(): void {
		// Verify nonce
		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, 'debug_suite_do_upgrade' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security check failed.', 'debug-suite' ) ], 403 );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to perform this action.', 'debug-suite' ) ], 403 );
		}

		if ( ! $this->is_upgrade_required() ) {
			wp_send_json_error( [ 'message' => __( 'No update is required.', 'debug-suite' ) ], 400 );
		}

		try {
			$installed_version = get_option( 'debug_suite_version', '1.0.0' );

			// Run table updates just in case schema changed
			DatabaseManager::create_tables();

			foreach ( self::UPDATES as $version => $class_name ) {
				if ( version_compare( $installed_version, (string) $version, '<' ) ) {
					if ( class_exists( $class_name ) ) {
						$upgrader = new $class_name();
						if ( method_exists( $upgrader, 'run' ) ) {
							$upgrader->run();
						}
					}

					update_option( 'debug_suite_version', $version );
				}
			}

			// Ensure the final version matches the code.
			update_option( 'debug_suite_version', DEBUG_SUITE_VERSION );

			wp_send_json_success(
				[
					'message' => __( 'Data updated successfully!', 'debug-suite' ),
				]
			);
		} catch ( Exception $e ) {
			wp_send_json_error(
				[
					'message' => sprintf(
						/* translators: %s: error message */
						__( 'Update failed: %s', 'debug-suite' ),
						$e->getMessage()
					),
				],
				500
			);
		}
	}
}
