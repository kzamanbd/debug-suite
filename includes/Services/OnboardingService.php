<?php
/**
 * Onboarding service for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Services;

use DebugSuite\Core\ServiceResponse;
use DebugSuite\Interfaces\ServiceInterface;
use DebugSuite\Interfaces\Hookable;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Service for handling onboarding functionality.
 *
 * @since DEBUG_SUITE_SINCE
 */
class OnboardingService implements ServiceInterface, Hookable {

	/**
	 * Constructor.
	 *
	 * @param SettingsService $service Settings service instance.
	 */
	public function __construct(
		private SettingsService $service
	) {}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_init', [ $this, 'maybe_redirect_to_onboarding' ] );
	}

	/**
	 * Check if we should redirect to onboarding.
	 *
	 * @return void
	 */
	public function maybe_redirect_to_onboarding(): void {
		// Only redirect if this is the first activation
		if ( get_option( 'debug_suite_onboarding_completed', false ) ) {
			return;
		}

		// Don't redirect if this is an AJAX request
		if ( wp_doing_ajax() ) {
			return;
		}

		// Don't redirect if this is a REST API request
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		// Don't redirect on plugin activation
		if ( isset( $_GET['activate'] ) || isset( $_GET['activate-multi'] ) ) {
			return;
		}

		// Get current page
		$current_page = sanitize_text_field( wp_unslash( $_GET['page'] ?? '' ) );

		// If we're not on any debug-suite page, don't redirect
		if ( $current_page !== 'debug-suite' ) {
			return;
		}

		// Get the current user ID
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		// Check if we've already tried to redirect this user
		$redirect_key = 'debug_suite_redirect_' . $user_id;
		if ( get_transient( $redirect_key ) ) {
			return;
		}

		// Set a transient to prevent redirect loops
		set_transient( $redirect_key, true, MINUTE_IN_SECONDS );

		// Redirect to onboarding
		wp_safe_redirect( admin_url( 'admin.php?page=debug-suite#/onboarding' ) );
		exit;
	}

	/**
	 * Save onboarding settings.
	 *
	 * @param array $settings The settings to save.
	 * @return ServiceResponse
	 */
	public function save_settings( array $settings ): ServiceResponse {
		// Validate settings
		if ( ! isset( $settings['debug_mode'] ) || ! isset( $settings['debug_log'] ) || ! isset( $settings['debug_display'] ) ) {
			return ServiceResponse::failure(
				__( 'Required settings are missing.', 'debug-suite' ),
				'validation_error'
			);
		}

		// Update wp-config.php settings
		$config_settings = [
			'WP_DEBUG' => $settings['debug_mode'] ? 'true' : 'false',
			'WP_DEBUG_LOG' => $settings['debug_log'] ? 'true' : 'false',
			'WP_DEBUG_DISPLAY' => $settings['debug_display'] ? 'true' : 'false',
		];

		$result = $this->service->update_settings( $config_settings );
		if ( $result->is_failure() ) {
			return $result;
		}

		// Mark onboarding as completed
		update_option( 'debug_suite_onboarding_completed', true );

		return ServiceResponse::success(
			[
				'message' => __( 'Onboarding settings saved successfully.', 'debug-suite' ),
				'settings' => $settings,
			]
		);
	}

	/**
	 * Get current onboarding status.
	 *
	 * @return ServiceResponse
	 */
	public function get_status(): ServiceResponse {
		$completed = get_option( 'debug_suite_onboarding_completed', false );
		$current_settings = $this->service->get_settings()->get_data();

		return ServiceResponse::success(
			[
				'completed' => $completed && WP_DEBUG,
				'settings' => [
					'debug_mode' => $current_settings['WP_DEBUG'],
					'debug_log' => $current_settings['WP_DEBUG_LOG'],
					'debug_display' => $current_settings['WP_DEBUG_DISPLAY'],
				],
			]
		);
	}
}
