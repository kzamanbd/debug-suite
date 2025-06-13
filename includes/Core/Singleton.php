<?php
/**
 * Singleton trait for ensuring only one instance of a class.
 */

namespace DebugSuite\Core;

/**
 * Singleton trait for ensuring single instance of classes.
 */
trait Singleton {

	/**
	 * Instance of the class.
	 *
	 * @var static|null
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
	 *
	 * @return void
	 */
	final protected function __clone() {
		// Prevent cloning
	}

	/**
	 * Prevent unserializing.
	 *
	 * @return void
	 * @throws \Exception
	 */
	final public function __wakeup(): void {
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
	protected function init(): void {
		// Override in child classes
	}
}
