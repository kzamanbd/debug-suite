<?php
/**
 * Abstract base class for debug providers with PSR-11 DI integration.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Providers;

use DebugSuite\Interfaces\DebugProviderInterface;

/**
 * Abstract base class for debug providers with DI container integration.
 *
 * Provides common functionality and structure for all debug providers.
 *
 * @since DEBUG_SUITE_SINCE
 */
abstract class AbstractDebugProvider implements DebugProviderInterface {


	/**
	 * Provider name.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var string
	 */
	protected readonly string $name;

	/**
	 * Provider description.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var string
	 */
	protected readonly string $description;

	/**
	 * Whether the provider is enabled.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var bool
	 */
	protected bool $enabled;

	/**
	 * Constructor for AbstractDebugProvider.
	 *
	 * Initializes the debug provider with default enabled state.
	 * Concrete providers should call parent constructor and set
	 * name and description properties.
	 *
	 * @since DEBUG_SUITE_SINCE
	 */
	public function __construct() {
		$this->enabled = true;
	}

	/**
	 * Get the provider name.
	 *
	 * Returns the human-readable name of this debug provider.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return string The provider name.
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Get the provider description.
	 *
	 * Returns a detailed description of what this debug provider does
	 * and what type of information it provides.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return string The provider description.
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * Check if the provider is enabled.
	 *
	 * Returns the current enabled state of this debug provider.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return bool Whether the provider is enabled.
	 */
	public function is_enabled(): bool {
		return $this->enabled;
	}

	/**
	 * Enable the provider.
	 *
	 * Sets the provider's enabled state to true, allowing it to
	 * collect and provide debug information.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return void
	 */
	public function enable(): void {
		$this->enabled = true;
	}

	/**
	 * Disable the provider.
	 *
	 * Sets the provider's enabled state to false, preventing it from
	 * collecting and providing debug information.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return void
	 */
	public function disable(): void {
		$this->enabled = false;
	}

	/**
	 * Activate the debug provider.
	 *
	 * Enables the provider and initializes it. This is typically called
	 * when the provider is turned on through the admin interface.
	 *
	 * @since DEBUG_SUITE_SINCE
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
	 * Disables the provider, stopping it from collecting debug information.
	 * This is typically called when the provider is turned off through the admin interface.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return void
	 */
	public function deactivate(): void {
		$this->disable();
	}

	/**
	 * Get debug data from this provider.
	 *
	 * Abstract method that must be implemented by concrete providers
	 * to return their specific debug information.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return array<string, mixed> The debug data.
	 */
	abstract public function get_debug_data(): array;

	/**
	 * Initialize the debug provider.
	 *
	 * Abstract method that must be implemented by concrete providers
	 * to perform their specific initialization tasks.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return void
	 */
	abstract public function init(): void;
}
