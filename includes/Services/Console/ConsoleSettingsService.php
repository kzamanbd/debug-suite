<?php
/**
 * Per-user console preferences stored in user meta.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Services\Console;

use DebugSuite\Interfaces\Hookable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads and writes the current user's console settings.
 *
 * @since 1.0.0
 */
class ConsoleSettingsService implements Hookable {

	private const META_KEY = 'debug_suite_console';

	/**
	 * No hooks needed.
	 *
	 * @return void
	 */
	public function register_hooks(): void {}

	/**
	 * Default settings.
	 *
	 * @return array{window_split: string, snippets: array}
	 */
	private function defaults(): array {
		return [
			'window_split' => 'vertical',
			'snippets'     => [],
		];
	}

	/**
	 * Get a user's console settings, merged over defaults.
	 *
	 * @param int $user_id User ID.
	 * @return array{window_split: string, snippets: array}
	 */
	public function get( int $user_id ): array {
		$saved = get_user_meta( $user_id, self::META_KEY, true );
		$saved = is_array( $saved ) ? $saved : [];

		return array_merge( $this->defaults(), $saved );
	}

	/**
	 * Persist a user's console settings.
	 *
	 * @param int   $user_id  User ID.
	 * @param array $settings Partial settings to merge and save.
	 * @return array{window_split: string, snippets: array}
	 */
	public function save( int $user_id, array $settings ): array {
		$current = $this->get( $user_id );

		if ( isset( $settings['window_split'] ) && in_array( $settings['window_split'], [ 'horizontal', 'vertical' ], true ) ) {
			$current['window_split'] = $settings['window_split'];
		}

		if ( isset( $settings['snippets'] ) && is_array( $settings['snippets'] ) ) {
			$current['snippets'] = array_values(
				array_map(
					static function ( $snippet ) {
						return [
							'id'    => (string) ( $snippet['id'] ?? wp_generate_uuid4() ),
							'title' => sanitize_text_field( (string) ( $snippet['title'] ?? '' ) ),
							'code'  => (string) ( $snippet['code'] ?? '' ),
						];
					},
					$settings['snippets']
				)
			);
		}

		update_user_meta( $user_id, self::META_KEY, $current );

		return $current;
	}
}
