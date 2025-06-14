<?php
/**
 * Interface for debug providers.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Interfaces;

/**
 * Debug Provider Interface.
 *
 * Interface for all debug providers in the Debug Suite plugin.
 *
 * @since 1.0.0
 */
interface DebugProviderInterface {


	/**
	 * Initialize the debug provider.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void;

	/**
	 * Get the provider name.
	 *
	 * @since 1.0.0
	 *
	 * @return string The provider name.
	 */
	public function get_name(): string;

	/**
	 * Get the provider description.
	 *
	 * @since 1.0.0
	 *
	 * @return string The provider description.
	 */
	public function get_description(): string;

	/**
	 * Check if the provider is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the provider is enabled.
	 */
	public function is_enabled(): bool;

	/**
	 * Get debug data from this provider.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> The debug data.
	 */
	public function get_debug_data(): array;

	/**
	 * Activate the debug provider.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function activate(): void;

	/**
	 * Deactivate the debug provider.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function deactivate(): void;
}
