<?php
/**
 * Interface for debug providers.
 */

namespace DebugSuite\Interfaces;

/**
 * Debug Provider Interface
 */
interface DebugProviderInterface {

	/**
	 * Initialize the debug provider.
	 */
	public function init();

	/**
	 * Get the provider name.
	 */
	public function get_name();

	/**
	 * Get the provider description.
	 */
	public function get_description();

	/**
	 * Check if the provider is enabled.
	 */
	public function is_enabled();

	/**
	 * Get debug data from this provider.
	 */
	public function get_debug_data();

	/**
	 * Activate the debug provider.
	 */
	public function activate();

	/**
	 * Deactivate the debug provider.
	 */
	public function deactivate();
}
