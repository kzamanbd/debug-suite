<?php
/**
 * Frontend Router Service for Debug Suite.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Services;

use DebugSuite\Interfaces\Hookable;
use DebugSuite\Interfaces\ServiceInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FrontendRouterService implements ServiceInterface, Hookable {

	/**
	 * Register WordPress hooks.
	 */
	public function register_hooks(): void {
		add_action( 'init', [ $this, 'add_rewrite_rules' ] );
		add_filter( 'query_vars', [ $this, 'add_query_var' ] );
		add_action( 'template_redirect', [ $this, 'handle_frontend_page' ] );
	}

	/**
	 * Add rewrite rules for frontend routing.
	 */
	public function add_rewrite_rules(): void {
		if ( ! is_admin() ) {
			add_rewrite_rule( '^debug-suite/?$', 'index.php?debug_suite_page=main', 'top' );
			add_rewrite_rule( '^debug-suite/([^/]+)/?$', 'index.php?debug_suite_page=$matches[1]', 'top' );
		}
	}

	/**
	 * Add custom query variable.
	 */
	public function add_query_var( array $vars ): array {
		$vars[] = 'debug_suite_page';
		return $vars;
	}

	/**
	 * Handle frontend routing and render appropriate template.
	 */
	public function handle_frontend_page(): void {
		$page = get_query_var( 'debug_suite_page' );

		if ( ! $page || ! $this->is_valid_page_parameter( $page ) ) {
			return;
		}

		// Hide admin bar for frontend view
		show_admin_bar( false );
		add_filter( 'show_admin_bar', '__return_false' );

		// Restrict access to authorized users
		if ( ! $this->can_access_debug_suite() ) {
			$this->load_template( 'access-denied' );
			exit;
		}

		// Load frontend template
		$this->load_template( 'frontend' );
		exit;
	}

	/**
	 * Validate page parameter (alphanumeric, dash, underscore).
	 */
	private function is_valid_page_parameter( string $page ): bool {
		return preg_match( '/^[a-zA-Z0-9_-]+$/', $page ) === 1;
	}

	/**
	 * Check if current user can access Debug Suite.
	 *
	 * @return bool True if user has access.
	 */
	private function can_access_debug_suite(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Load a template file if it exists.
	 */
	private function load_template( string $template ): void {
		$file = $this->resolve_template_path( $template );

		if ( $file ) {
			include $file;
		} else {
			wp_die(
				esc_html__( 'Template not found.', 'debug-suite' ),
				esc_html__( 'Error', 'debug-suite' ),
				[ 'response' => 404 ]
			);
		}
	}

	/**
	 * Resolve template path with in-memory cache.
	 */
	private function resolve_template_path( string $template ): string|false {
		if ( isset( $this->template_cache[ $template ] ) ) {
			return $this->template_cache[ $template ];
		}

		$path = DEBUG_SUITE_PLUGIN_DIR . "templates/{$template}.php";
		$this->template_cache[ $template ] = file_exists( $path ) ? $path : false;

		return $this->template_cache[ $template ];
	}

	/**
	 * Generate a frontend URL (with optional path).
	 */
	public function get_frontend_url( string $path = '' ): string {
		$base = home_url( 'debug-suite' );
		return $path ? trailingslashit( $base ) . ltrim( $path, '/' ) : $base;
	}
}
