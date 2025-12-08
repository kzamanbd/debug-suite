<?php

namespace DebugSuite\Modules\EmailLog;

use DebugSuite\Interfaces\Hookable;

class Assets implements Hookable {
	public function register_hooks(): void {
		add_filter( 'debug_suite_assets_scripts', [ $this, 'register_scripts' ] );
	}

	public function register_scripts( array $scripts ): array {
		$file = DEBUG_SUITE_PLUGIN_DIR . 'assets/js/email-log.asset.php';
		if ( file_exists( $file ) ) {
			$assets = require $file;
			$scripts['debug-suite-email-log'] = [
				'src'     => DEBUG_SUITE_PLUGIN_URL . 'assets/js/email-log.js',
				'version' => DEBUG_SUITE_VERSION,
				'deps'    => $assets['dependencies'],
			];
		}

		return $scripts;
	}
}
