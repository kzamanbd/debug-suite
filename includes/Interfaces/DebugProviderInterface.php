<?php
/**
 * Interface for debug providers.
 *
 * @since      1.0.0
 * @package    DebugSuite
 * @subpackage DebugSuite/Interfaces
 * @author     Kamruzzaman <kzamanbn@gmail.com>
 */

namespace DebugSuite\Interfaces;

/**
 * Debug Provider Interface
 *
 * @since      1.0.0
 * @package    DebugSuite
 * @subpackage DebugSuite/Interfaces
 * @author     Kamruzzaman <kzamanbn@gmail.com>
 */
interface DebugProviderInterface {

	/**
	 * Initialize the debug provider.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function init();

	/**
	 * Get the provider name.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	public function get_name();

	/**
	 * Get the provider description.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	public function get_description();

	/**
	 * Check if the provider is enabled.
	 *
	 * @since    1.0.0
	 * @return   bool
	 */
	public function is_enabled();

	/**
	 * Get debug data from this provider.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public function get_debug_data();

	/**
	 * Activate the debug provider.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function activate();

	/**
	 * Deactivate the debug provider.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function deactivate();
}
