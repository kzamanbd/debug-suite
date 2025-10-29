<?php

namespace DebugSuite\Core;

use Exception;

trait Singleton {


	/**
	 * Object container.
	 *
	 * @var self
	 */
	private static $object;

	/**
	 * Create object of this class.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( static::$object ) {
			return static::$object;
		}

		static::$object = new static();

		return static::$object;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @throws Exception
	 */
	public function __clone() {
		wp_die( 'Cloning is forbidden.' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @throws Exception
	 */
	public function __wakeup() {
		wp_die( 'Unserializing instances of this class is forbidden.' );
	}
}
