<?php

namespace DebugSuite\Pages;

use DebugSuite\Services\SwaggerService;

class SwaggerPage extends AbstractPage {

    public function register_hooks( $force = true ): void {
        parent::register_hooks( $force );
        add_action( 'wp', [ $this, 'swagger_schema' ] );
        add_filter( 'init', [ $this, 'rewrite_routes' ] );
        add_action( 'template_redirect', [ $this, 'render_template' ] );
        add_filter( 'redirect_canonical', [ $this, 'disable_canonical_redirect' ], 10, 2 );
    }

    public function disable_canonical_redirect( $redirect_url, $requested_url ) {
        if ( get_query_var( 'debug-suite-swagger' ) === 'docs' ) {
            return false;
        }
        return $redirect_url;
    }

    public function rewrite_routes() {
        $base = SwaggerService::rewrite_base_api();
        add_rewrite_tag( '%debug-suite-swagger%', '([^&]+)' );
        add_rewrite_rule( '^' . $base . '/docs/?(.*)?', 'index.php?debug-suite-swagger=docs', 'top' );
        add_rewrite_rule( '^' . $base . '/schema/?', 'index.php?debug-suite-swagger=schema', 'top' );
    }

    public function swagger_schema() {
        if ( get_query_var( 'debug-suite-swagger' ) === 'schema' ) {
            $service = new SwaggerService();
            $response = $service->swagger();
            wp_send_json( $response );
        }
    }

    public function render_template() {
        $view = get_query_var( 'debug-suite-swagger' );

        if ( ! $view ) {
            return;
        }

        if ( 'docs' === $view ) {
            $base_api   = SwaggerService::rewrite_base_api();
            $schema_url = user_trailingslashit( home_url( $base_api . '/schema' ) );
            $docs_path  = user_trailingslashit( '/' . $base_api . '/docs' );
            $title      = get_bloginfo( 'name' );
            $logo_url = get_site_icon_url();
            if ( ! $logo_url && function_exists( 'has_custom_logo' ) && has_custom_logo() ) {
                $logo_url = wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' );
            }

            debug_suite_template(
                'swagger',
                [
					'base_api'          => $base_api,
					'schema_url'        => $schema_url,
					'docs_path'         => $docs_path,
					'title'             => $title,
					'logo_url'          => $logo_url,
                    'namespaces'        => SwaggerService::get_namespaces(),
                    'current_namespace' => SwaggerService::get_clean_namespace(),
				]
            );
            exit;
        }
    }

    /**
     * Get the ID of the page.
     *
     * @since 1.0.0
     *
     * @return string
     */
	public function get_id(): string {
		return 'swagger';
	}

	/**
	 * @inheritDoc
	 */
	public function menu( string $capability, string $position ): array {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function settings(): array {
		return [
            'schema' => user_trailingslashit( home_url( SwaggerService::rewrite_base_api() . '/schema' ) ),
        ];
	}

	/**
	 * @inheritDoc
	 */
	public function scripts(): array {
        return [ 'debug-suite-swagger' ];
	}

    /**
     * Get the styles.
     *
     * @since 1.0.0
     *
     * @return array<string> An array of style handles.
     */
    public function styles(): array {
        return [];
    }

    /**
     * Register the page scripts and styles.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register(): void {
		wp_register_script(
			'debug-suite-swagger',
			DEBUG_SUITE_PLUGIN_URL . 'assets/js/swagger-stoplight.js',
			[ 'debug-suite-script' ],
			DEBUG_SUITE_VERSION,
			true
		);
	}
}
