<?php
/**
 * Feature service for Debug Suite – manages enabled/disabled state in options.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Services;

use DebugSuite\Interfaces\ServiceInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service for reading and updating feature toggles stored in WordPress options.
 *
 * @since 1.0.0
 */
class FeatureService implements ServiceInterface {

	/**
	 * Option name for storing feature states.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'debug_suite_features';

	/**
	 * Default enabled state per feature ID (must match frontend feature IDs).
	 * Static so it can be used before the container is fully built (e.g. when loading service providers).
	 *
	 * @return array<string, bool>
	 */
	public static function get_default_features(): array {
		return [
			'email-log'       => false,
			'system-info'     => true,
			'file-manager'    => false,
			'query-monitor'   => false,
			'api-logger'      => false,
			'asset-manager'   => false,
		];
	}

	/**
	 * Get all feature states (saved option merged with defaults).
	 *
	 * @return array<string, bool> Map of feature_id => enabled.
	 */
	public function get_features(): array {
		$saved    = get_option( self::OPTION_NAME, [] );
		$defaults = self::get_default_features();

		if ( ! is_array( $saved ) ) {
			$saved = [];
		}

		$merged = [];
		foreach ( array_keys( $defaults ) as $id ) {
			$merged[ $id ] = array_key_exists( $id, $saved ) ? (bool) $saved[ $id ] : $defaults[ $id ];
		}

		return $merged;
	}

	/**
	 * Update a single feature's enabled state and persist to options.
	 *
	 * @param string $feature_id Feature ID.
	 * @param bool   $enabled    Whether the feature is enabled.
	 * @return bool True if option was updated, false otherwise.
	 */
	public function update_feature( string $feature_id, bool $enabled ): bool {
		$defaults = self::get_default_features();
		if ( ! array_key_exists( $feature_id, $defaults ) ) {
			return false;
		}

		$current = get_option( self::OPTION_NAME, [] );
		if ( ! is_array( $current ) ) {
			$current = [];
		}

		$current[ $feature_id ] = $enabled;
		return update_option( self::OPTION_NAME, $current );
	}

	/**
	 * Update multiple feature states at once.
	 *
	 * @param array<string, bool> $features Map of feature_id => enabled.
	 * @return bool True if option was updated.
	 */
	public function update_features( array $features ): bool {
		$defaults = self::get_default_features();
		$current  = get_option( self::OPTION_NAME, [] );
		if ( ! is_array( $current ) ) {
			$current = [];
		}

		foreach ( $features as $id => $enabled ) {
			if ( array_key_exists( $id, $defaults ) ) {
				$current[ $id ] = (bool) $enabled;
			}
		}

		return update_option( self::OPTION_NAME, $current );
	}
}
