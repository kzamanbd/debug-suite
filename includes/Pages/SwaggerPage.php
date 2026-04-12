<?php

namespace DebugSuite\Pages;

use DebugSuite\Services\SwaggerService;

class SwaggerPage extends AbstractPage {

    public function register_hooks( $force = true ): void {
        parent::register_hooks( $force );
        add_filter( 'init', [ $this, 'routes' ] );
        add_action( 'template_redirect', [ $this, 'render_template' ] );
        add_action( 'wp', [ $this, 'swagger_schema' ] );
        add_filter( 'redirect_canonical', [ $this, 'disable_canonical_redirect' ], 10, 2 );
    }

    public function disable_canonical_redirect( $redirect_url, $requested_url ) {
        if ( get_query_var( 'debug-suite-swagger' ) === 'docs' ) {
            return false;
        }
        return $redirect_url;
    }

    public function routes() {
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

            $service = new SwaggerService();

            // Allow extending schemas via filter
            $schemas = apply_filters(
                'debug_suite_swagger_schemas',
                [
					'default' => [
						'name' => __( 'WP REST API', 'debug-suite' ),
						'data' => $service->swagger(),
					],
				]
            );

            $schemas_json = wp_json_encode( $schemas );

            include dirname( __DIR__, 2 ) . '/templates/swagger.php';
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
