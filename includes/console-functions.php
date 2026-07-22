<?php

/**
 * Bootstraps the scoped PsySH global helper functions.
 *
 * The scoped PsySH classes (DebugSuite\Packages\Psy\Shell, etc.) call global
 * helper functions defined in PsySH's own functions.php, which mozart scopes
 * into override/Psy/functions.php. That file is generated build output — it
 * does not exist until `vendor/bin/mozart compose` has run, which happens
 * *after* this project's own Composer autoloader is first assembled.
 *
 * Referencing override/Psy/functions.php directly from composer.json's
 * `autoload.files` creates a chicken-and-egg fatal error: mozart's own CLI
 * bootstraps via this project's vendor/autoload.php, which would eagerly
 * `require` a file mozart itself hasn't created yet. Requiring this stable,
 * always-present wrapper instead — and guarding the include with
 * file_exists() — breaks that cycle: on the very first `composer install`
 * this file no-ops safely, and by the time the plugin actually runs (after
 * the post-install-cmd hook has run mozart), the scoped file exists and
 * gets loaded normally.
 *
 * @since      1.0.0
 *
 * @package    DebugSuite
 */

$debug_suite_psysh_functions = __DIR__ . '/../override/Psy/functions.php';

if ( file_exists( $debug_suite_psysh_functions ) ) {
	require_once $debug_suite_psysh_functions;
}

unset( $debug_suite_psysh_functions );
