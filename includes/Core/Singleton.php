<?php
/**
 * Singleton trait for ensuring only one instance of a class.
 *
 * @since      1.0.0
 * @package    DebugSuite
 * @author     Kamruzzaman <kzamanbn@gmail.com>
 */

namespace DebugSuite\Core;

/**
 * Singleton trait for ensuring single instance of classes.
 *
 * @since      1.0.0
 * @package    DebugSuite
 * @author     Kamruzzaman <kzamanbn@gmail.com>
 */
trait Singleton {

	/**
	 * Instance of the class.
	 *
	 * @var static
	 */
	private static $instance;

	/**
	 * Prevent direct object creation.
	 */
	final private function __construct() {
		$this->init();
	}

	/**
	 * Prevent object cloning.
	 */
	final protected function __clone() {
		// Prevent cloning
	}

	/**
	 * Prevent unserializing.
	 */
	final public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}

	/**
	 * Initialize the singleton instance.
	 *
	 * @return static
	 */
	final public static function get_instance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Initialize the class.
	 * Override this method in child classes to perform initialization.
	 *
	 * @return void
	 */
	protected function init() {
		// Override in child classes
	}
}
