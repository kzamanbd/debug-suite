<?php

namespace DebugSuite\Pages;

use DebugSuite\Services\OpenApiService;

class OpenApiPage extends AbstractPage {

    /**
     * Rewrite-rules version. Bump whenever rewrite_routes() changes to trigger a
     * one-time flush on the next request.
     */
    private const REWRITE_VERSION = '1.0.0';

    /**
     * Option that stores the last-flushed rewrite version.
     */
    private const REWRITE_VERSION_OPTION = 'debug_suite_openapi_rewrite_version';

    /**
     * Feature ID that gates this page (must match the frontend feature list).
     *
     * @since PLUGIN_SINCE
     */
    public const FEATURE_ID = 'api-docs';

    /**
     * Register the hooks.
     *
     * The docs and schema routes only exist while the api-docs feature is
     * enabled. The rewrite-state listeners are registered either way so the
     * rules are flushed in and out as the feature is toggled.
     *
     * @param bool $force Whether to register outside the admin.
     *
     * @return void
     */
    public function register_hooks( $force = true ): void {
        add_action( 'wp_loaded', [ $this, 'maybe_flush_rewrite_rules' ] );
        add_action( 'debug_suite_' . self::FEATURE_ID . '_activated', [ __CLASS__, 'refresh_rewrite_rules' ], 10, 0 );

        if ( ! debug_suite_is_feature_enabled( self::FEATURE_ID ) ) {
            return;
        }

        parent::register_hooks( $force );
        add_action( 'init', [ $this, 'rewrite_routes' ] );
        add_action( 'template_redirect', [ $this, 'render_template' ] );
        add_filter( 'redirect_canonical', [ $this, 'disable_canonical_redirect' ] );
    }

    public function disable_canonical_redirect( $redirect_url ) {
        if ( get_query_var( 'debug-suite-openapi' ) === 'docs' ) {
            return false;
        }
        return $redirect_url;
    }

    public static function rewrite_routes(): void {
        $base = preg_quote( OpenApiService::rewrite_base_api(), '/' );
        add_rewrite_tag( '%debug-suite-openapi%', '([^&]+)' );
        add_rewrite_rule( '^' . $base . '/docs(?:/.*)?$', 'index.php?debug-suite-openapi=docs', 'top' );
        add_rewrite_rule( '^' . $base . '/schema/?$', 'index.php?debug-suite-openapi=schema', 'top' );
    }

    /**
     * The rewrite state the stored option is compared against.
     *
     * Combines the rule version with whether the feature is currently enabled,
     * so toggling api-docs off flushes the rules away and toggling it back on
     * restores them — same mechanism that handles a rule-version bump.
     *
     * @since PLUGIN_SINCE
     *
     * @return string
     */
    private static function get_rewrite_state(): string {
        return self::REWRITE_VERSION . ':' . ( debug_suite_is_feature_enabled( self::FEATURE_ID ) ? 'on' : 'off' );
    }

    /**
     * Flush rewrite rules once whenever our rules change.
     *
     * Runs on wp_loaded (after rewrite_routes() has registered the rules on
     * init) and flushes only when the stored state differs from the current
     * one. This makes the /docs and /schema URLs self-heal after an activation,
     * plugin update, rule change, or feature toggle — without the intermittent
     * 404 that otherwise needs a manual "Save Permalinks" — and without paying
     * for a flush on every request.
     *
     * @return void
     */
    public function maybe_flush_rewrite_rules(): void {
        if ( get_option( self::REWRITE_VERSION_OPTION ) === self::get_rewrite_state() ) {
            return;
        }

        self::refresh_rewrite_rules();
    }

    /**
     * Register the rewrite rules, flush, and record the current state.
     *
     * Safe to call outside the normal request lifecycle (e.g. on activation or
     * when the feature is switched on): it registers the rules before flushing.
     * With the feature disabled it flushes without registering, which drops the
     * docs and schema rules from the rewrite table.
     *
     * @return void
     */
    public static function refresh_rewrite_rules(): void {
        if ( debug_suite_is_feature_enabled( self::FEATURE_ID ) ) {
            self::rewrite_routes();
        }

        flush_rewrite_rules( false );
        update_option( self::REWRITE_VERSION_OPTION, self::get_rewrite_state() );
    }

    public function render_schema() {
        if ( get_query_var( 'debug-suite-openapi' ) === 'schema' ) {
            $service = new OpenApiService();
            $response = $service->get_schema();
            wp_send_json( $response );
        }
    }

    public function render_template() {
        $view = get_query_var( 'debug-suite-openapi' );

        if ( ! $view ) {
            return;
        }

        if ( 'schema' === $view ) {
            $this->render_schema();
        }

        if ( 'docs' === $view ) {
            $base_api   = OpenApiService::rewrite_base_api();
            $schema_url = user_trailingslashit( home_url( $base_api . '/schema' ) );
            $docs_path  = user_trailingslashit( '/' . $base_api . '/docs' );
            $title      = OpenApiService::get_info_title( OpenApiService::get_clean_namespace() );
            $logo_url = get_site_icon_url();
            if ( ! $logo_url && function_exists( 'has_custom_logo' ) && has_custom_logo() ) {
                $logo_url = wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' );
            }
            if ( empty( $logo_url ) ) {
				$logo_url = DEBUG_SUITE_PLUGIN_URL . 'assets/images/logo.png';
			}
            $debug_suite_favicon_url = get_site_icon_url( 32 );

			if ( ! $debug_suite_favicon_url && function_exists( 'has_custom_logo' ) && has_custom_logo() ) {
				$debug_suite_favicon_url = wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' );
			}

            debug_suite_template(
                'openapi',
                [
					'base_api'          => $base_api,
					'schema_url'        => $schema_url,
					'docs_path'         => $docs_path,
					'title'             => $title,
					'logo_url'          => $logo_url,
                    'namespaces'        => OpenApiService::get_namespaces(),
                    'current_namespace' => OpenApiService::get_clean_namespace(),
                    'debug_suite_favicon_url' => $debug_suite_favicon_url,
				]
            );
            exit;
        }
    }

    /**
     * Get the ID of the page.
     *
     * @since 1.1.3
     *
     * @return string
     */
	public function get_id(): string {
		return 'openapi';
	}

    /**
     * @inheritDoc
     *
     * @since 1.1.3
     */
	public function menu( string $capability, string $position ): array {
		return [
            'page_title' => __( 'Debug Suite OpenAPI Docs', 'debug-suite' ),
            'menu_title' => __( 'API Docs', 'debug-suite' ),
            'capability' => $capability,
            'position'   => $position ?? 30,
            'route'      => home_url( OpenApiService::rewrite_base_api() . '/docs' ),
        ];
	}

    /**
     * @inheritDoc
     *
     * @since 1.1.3
     */
	public function settings(): array {
		return [];
	}

    /**
     * @inheritDoc
     *
     * @since 1.1.3
     */
	public function scripts(): array {
        return [];
	}

    /**
     * Get the styles.
     *
     * @since 1.1.3
     *
     * @return array<string> An array of style handles.
     */
    public function styles(): array {
        return [];
    }

    /**
     * Register the page scripts and styles.
     *
     * @since 1.1.3
     *
     * @return void
     */
    public function register(): void {
		// do stuff
	}
}
