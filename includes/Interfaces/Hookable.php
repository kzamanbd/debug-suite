<?php
/**
 * Interface for classes that register WordPress hooks with PSR-11 DI integration.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Interfaces;

/**
 * Interface for classes that register WordPress hooks with DI Container integration.
 *
 * Ensures automatic hook registration when services are resolved from the container.
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
