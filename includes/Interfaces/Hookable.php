<?php
/**
 * Interface for classes that register WordPress hooks.
 *
 * @package DebugSuite
 */

declare(strict_types=1);

namespace DebugSuite\Interfaces;

/**
 * Interface Hookable.
 *
 * This interface should be implemented by all classes that use WordPress hooks
 * with the Dependency Management Container. Implementing this interface ensures
 * that the hooks are registered automatically. If this interface is not implemented,
 * the hooks must be registered manually by resolving the container.
 *
 * @since 1.0.0
 */
interface Hookable {


	/**
	 * Register hooks for WordPress.
	 *
	 * This method will be called automatically to register the hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_hooks(): void;
}
