<?php

namespace DebugSuite;

use DebugSuite\Interfaces\Hookable;

/**
 * Admin Service class.
 *
 * Handles general admin functionality like redirects and notice management.
 *
 * @since 1.0.0
 */
class Admin implements Hookable {

	/**
	 * Register hooks.
	 */
	public function register_hooks(): void {
		add_action( 'admin_init', [ $this, 'handle_activation_redirect' ] );
		add_action( 'admin_print_scripts', [ $this, 'hide_unrelated_notices' ] );
	}

	/**
	 * Handle activation redirect.
	 *
	 * Redirects to the onboarding page after plugin activation
	 * if onboarding hasn't been completed.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_activation_redirect(): void {
		// Check if we should redirect
		if ( ! get_transient( 'debug_suite_activation_redirect' ) ) {
			return;
		}

		// Delete the redirect transient
		delete_transient( 'debug_suite_activation_redirect' );

		// Only redirect to onboarding if it hasn't been completed
		if ( ! get_option( 'debug_suite_onboarding_completed', false ) ) {
			// Get the onboarding URL
			$onboarding_url = add_query_arg(
				[
					'page' => 'debug-suite',
					'path' => 'onboarding',
				],
				admin_url( 'admin.php' )
			);

			// Redirect and exit
			wp_safe_redirect( $onboarding_url );
			exit;
		}
	}

	/**
	 * Remove all non-Debug Suite Logging plugin notices from our plugin pages.
	 *
	 * @since 1.0.0
	 */
	public function hide_unrelated_notices(): void {

		if ( ! debug_suite_current_page() ) {
			return;
		}

		$this->remove_unrelated_actions( 'user_admin_notices' );
		$this->remove_unrelated_actions( 'admin_notices' );
		$this->remove_unrelated_actions( 'all_admin_notices' );
		$this->remove_unrelated_actions( 'network_admin_notices' );
	}

	/**
	 * Remove all non-Debug Suite Logging notices from the our plugin pages based on the provided action hook.
	 *
	 * @param string $action The name of the action.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function remove_unrelated_actions( string $action ): void {

		global $wp_filter;

		if ( empty( $wp_filter[ $action ]->callbacks ) || ! is_array( $wp_filter[ $action ]->callbacks ) ) {
			return;
		}

		foreach ( $wp_filter[ $action ]->callbacks as $priority => $hooks ) {
			foreach ( $hooks as $name => $arr ) {

				if ( str_contains( strtolower( $name ), 'no3x\wpml' ) ) {
					continue;
				}

				// Handle the case when the callback is an array.
				if (
					is_array( $arr ) && ! empty( $arr['function'] ) && is_array( $arr['function'] )
					&& ! empty( $arr['function'][0] ) && is_object( $arr['function'][0] )
					&& ( str_contains( strtolower( get_class( $arr['function'][0] ) ), 'no3x\wpml' ) )
				) {
					continue;
				}

				unset( $wp_filter[ $action ]->callbacks[ $priority ][ $name ] );
			}
		}
	}
}
