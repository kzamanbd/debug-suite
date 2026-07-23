<?php
/**
 * Tests for FeatureService defaults.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Tests\Unit\Services;

use DebugSuite\Services\FeatureService;
use DebugSuite\Tests\Helpers\TestCase;

/**
 * Test the feature ID list stays in sync with the frontend.
 *
 * @covers \DebugSuite\Services\FeatureService
 * @group services
 * @group features
 */
class FeatureServiceTest extends TestCase {

	/**
	 * Feature IDs declared by the settings page.
	 *
	 * FeatureService::update_feature() silently rejects any ID that is not in
	 * get_default_features(), so a toggle rendered by the frontend for an ID the
	 * backend does not know about can never be saved.
	 */
	public function test_default_features_cover_every_frontend_feature_id() {
		$settings_page = dirname( __DIR__, 3 ) . '/src/pages/settings/index.tsx';

		$this->assertFileExists( $settings_page, 'Settings page source not found.' );

		$source = file_get_contents( $settings_page );

		// Match the `id: 'foo',` line of each entry in the initialFeatures array.
		preg_match( '/const initialFeatures[^=]*=\s*\[(.*?)\n\];/s', $source, $block );
		$this->assertNotEmpty( $block, 'Could not locate the initialFeatures array.' );

		preg_match_all( "/id:\s*'([a-z0-9-]+)'/", $block[1], $matches );
		$frontend_ids = $matches[1];

		$this->assertNotEmpty( $frontend_ids, 'No feature IDs found in the settings page.' );

		$backend_ids = array_keys( FeatureService::get_default_features() );

		$this->assertSame(
			[],
			array_values( array_diff( $frontend_ids, $backend_ids ) ),
			'Feature IDs rendered by the settings page are missing from FeatureService::get_default_features().'
		);
	}

	/**
	 * The API docs page is gated on this ID, so it must be a known feature.
	 */
	public function test_api_docs_feature_is_registered_and_enabled_by_default() {
		$defaults = FeatureService::get_default_features();

		$this->assertArrayHasKey( 'api-docs', $defaults );
		$this->assertTrue( $defaults['api-docs'] );
	}
}
