<?php
/**
 * Abstract base class for debug providers.
 */

namespace DebugSuite\Providers;

use DebugSuite\Interfaces\DebugProviderInterface;

/**
 * Abstract Debug Provider
 */
abstract class AbstractDebugProvider implements DebugProviderInterface {

	/**
	 * Provider name.
	 */
	protected $name;

	/**
	 * Provider description.
	 */
	protected $description;

	/**
	 * Whether the provider is enabled.
	 */
	protected $enabled;

	public function __construct() {
		$this->enabled = true;
	}

	/**
	 * Get the provider name.
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Get the provider description.
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Check if the provider is enabled.
	 */
	public function is_enabled() {
		return $this->enabled;
	}

	/**
	 * Enable the provider.
	 */
	public function enable() {
		$this->enabled = true;
	}

	/**
	 * Disable the provider.
	 */
	public function disable() {
		$this->enabled = false;
	}

	/**
	 * Activate the debug provider.
	 */
	public function activate() {
		$this->enable();
		$this->init();
	}

	/**
	 * Deactivate the debug provider.
	 */
	public function deactivate() {
		$this->disable();
	}
}
