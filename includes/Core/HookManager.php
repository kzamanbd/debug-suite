<?php

namespace DebugSuite\Core;

use DebugSuite\Interfaces\Hookable;
use Exception;

class HookManager implements Hookable {

	public function register_hooks(): void {
		add_filter( 'plugin_action_links_' . plugin_basename( DEBUG_SUITE_FILE ), [ $this, 'plugin_action_links' ] );
		add_action( 'admin_notices', [ $this, 'show_upgrade_notice' ] );
		add_action( 'wp_ajax_debug_suite_upgrade_database', [ $this, 'handle_database_upgrade' ] );
	}

	public function plugin_action_links( array $links ): array {
		$links[] = '<a href="' . admin_url( 'admin.php?page=debug-suite' ) . '">' . __( 'Settings', 'debug-suite' ) . '</a>';

		return $links;
	}


	/**
	 * Show admin notice for database upgrade.
	 *
	 * @return void
	 */
	public function show_upgrade_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! DatabaseManager::needs_update() ) {
			return;
		}

		$current_version = DatabaseManager::get_db_version();
		$latest_version  = DEBUG_SUITE_VERSION;

		?>
		<div class="notice notice-warning is-dismissible" id="debug-suite-db-upgrade-notice">
			<p>
				<strong><?php esc_html_e( 'Debug Suite Database Update Required', 'debug-suite' ); ?></strong>
			</p>
			<p>
				<?php
				printf(
					/* translators: %1$s: current database version, %2$s: latest version */
					esc_html__( 'Debug Suite database needs to be updated from version %1$s to %2$s to ensure proper functionality.', 'debug-suite' ),
					'<code>' . esc_html( $current_version ) . '</code>',
					'<code>' . esc_html( $latest_version ) . '</code>'
				);
				?>
			</p>
			<p>
				<button type="button" class="button button-primary" id="debug-suite-upgrade-db-button">
					<?php esc_html_e( 'Update Database Now', 'debug-suite' ); ?>
				</button>
				<span class="spinner" id="debug-suite-upgrade-spinner" style="display: none; float: none; margin-left: 10px;"></span>
			</p>
			<div id="debug-suite-upgrade-message" style="margin-top: 10px; display: none;"></div>
		</div>
		<?php
	}

	/**
	 * Handle AJAX database upgrade request.
	 *
	 * @return void
	 */
	public function handle_database_upgrade(): void {
		// Verify nonce
		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, 'debug_suite_upgrade_database' ) ) {
			return;
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				[
					'message' => __( 'You do not have permission to perform this action.', 'debug-suite' ),
				]
			);
		}

		try {
			// Run database updates
			DatabaseManager::create_tables();

			wp_send_json_success(
				[
					'message' => __( 'Database updated successfully!', 'debug-suite' ),
				]
			);
		} catch ( Exception $e ) {
			wp_send_json_error(
				[
					'message' => sprintf(
						/* translators: %s: error message */
						__( 'Database update failed: %s', 'debug-suite' ),
						$e->getMessage()
					),
				]
			);
		}
	}
}
