<?php
/**
 * Abstract base class for debug providers.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Providers;

use DebugSuite\Interfaces\DebugProviderInterface;

/**
 * Abstract Debug Provider.
 *
 * Provides base functionality for all debug providers.
 *
 * @since 1.0.0
 */
abstract class AbstractDebugProvider implements DebugProviderInterface {


	/**
	 * Provider name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected readonly string $name;

	/**
	 * Provider description.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected readonly string $description;

	/**
	 * Whether the provider is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	protected bool $enabled;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->enabled = true;
	}

	/**
	 * Get the provider name.
	 *
	 * @since 1.0.0
	 *
	 * @return string The provider name.
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Get the provider description.
	 *
	 * @since 1.0.0
	 *
	 * @return string The provider description.
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * Check if the provider is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the provider is enabled.
	 */
	public function is_enabled(): bool {
		return $this->enabled;
	}

	/**
	 * Enable the provider.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enable(): void {
		$this->enabled = true;
	}

	/**
	 * Disable the provider.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function disable(): void {
		$this->enabled = false;
	}

	/**
	 * Activate the debug provider.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function activate(): void {
		$this->enable();
		$this->init();
	}

	/**
	 * Deactivate the debug provider.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function deactivate(): void {
		$this->disable();
	}

	/**
	 * Get debug data from this provider.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> The debug data.
	 */
	abstract public function get_debug_data(): array;

	/**
	 * Initialize the debug provider.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	abstract public function init(): void;
}
