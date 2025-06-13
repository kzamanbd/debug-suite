<?php
/**
 * Abstract base class for debug providers.
 *
 * @since      1.0.0
 * @package    DebugSuite
 * @author     Kamruzzaman <kzamanbn@gmail.com>
 */

namespace DebugSuite\Providers;

use DebugSuite\Interfaces\DebugProviderInterface;

/**
 * Abstract Debug Provider
 *
 * @since      1.0.0
 * @package    DebugSuite
 * @author     Kamruzzaman <kzamanbn@gmail.com>
 */
abstract class AbstractDebugProvider implements DebugProviderInterface {

	/**
	 * Provider name.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $name    The provider name.
	 */
	protected $name;

	/**
	 * Provider description.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $description    The provider description.
	 */
	protected $description;

	/**
	 * Whether the provider is enabled.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      bool      $enabled    Whether the provider is enabled.
	 */
	protected $enabled;

	/**
	 * Constructor.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->enabled = true;
	}

	/**
	 * Get the provider name.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Get the provider description.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Check if the provider is enabled.
	 *
	 * @since    1.0.0
	 * @return   bool
	 */
	public function is_enabled() {
		return $this->enabled;
	}

	/**
	 * Enable the provider.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function enable() {
		$this->enabled = true;
	}

	/**
	 * Disable the provider.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function disable() {
		$this->enabled = false;
	}

	/**
	 * Activate the debug provider.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function activate() {
		$this->enable();
		$this->init();
	}

	/**
	 * Deactivate the debug provider.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function deactivate() {
		$this->disable();
	}
}
