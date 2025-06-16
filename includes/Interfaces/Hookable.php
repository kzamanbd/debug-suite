<?php
/**
 * Interface for classes that register WordPress hooks.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Interfaces;

/**
 * Interface for classes that register WordPress hooks.
 *
 * This interface should be implemented by all classes that use WordPress hooks
 * with the Dependency Management Container. Implementing this interface ensures
 * that the hooks are registered automatically when the service is resolved.
 *
 * @since DEBUG_SUITE_SINCE
 */
interface Hookable {


	/**
	 * Register hooks for WordPress.
	 *
	 * This method will be called automatically when the service is resolved
	 * from the container to register the necessary WordPress hooks.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return void
	 */
	public function register_hooks(): void;
}
