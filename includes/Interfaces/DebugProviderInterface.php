<?php
/**
 * Interface for debug providers with PSR-11 DI Container integration.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Interfaces;

/**
 * Debug Provider Interface for Debug Suite with DI Container integration.
 *
 * Defines the contract for debug providers that collect and manage debug information.
 *
 * @since DEBUG_SUITE_SINCE
 */
interface DebugProviderInterface {


	/**
	 * Initialize the debug provider.
	 *
	 * Performs initial setup and configuration for the debug provider.
	 * Called during the provider registration process.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return void
	 */
	public function init(): void;

	/**
	 * Get the provider name.
	 *
	 * Returns a human-readable name for the debug provider.
	 * Used for identification in the admin interface.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return string The provider name.
	 */
	public function get_name(): string;

	/**
	 * Get the provider description.
	 *
	 * Returns a detailed description of what this debug provider does
	 * and what type of debug information it provides.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return string The provider description.
	 */
	public function get_description(): string;

	/**
	 * Check if the provider is enabled.
	 *
	 * Determines whether this debug provider is currently active
	 * and should collect debug information.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return bool Whether the provider is enabled.
	 */
	public function is_enabled(): bool;

	/**
	 * Get debug data from this provider.
	 *
	 * Collects and returns the debug information managed by this provider.
	 * The returned array should contain structured debug data.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return array<string, mixed> The debug data.
	 */
	public function get_debug_data(): array;

	/**
	 * Activate the debug provider.
	 *
	 * Enables the debug provider and starts collecting debug information.
	 * Called when the provider is turned on through the admin interface.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return void
	 */
	public function activate(): void;

	/**
	 * Deactivate the debug provider.
	 *
	 * Disables the debug provider and stops collecting debug information.
	 * Called when the provider is turned off through the admin interface.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return void
	 */
	public function deactivate(): void;
}
