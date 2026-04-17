<?php

namespace DebugSuite\Pages;

use DebugSuite\Services\SwaggerService;

class SwaggerPage extends AbstractPage {

    public function register_hooks( $force = true ): void {
        parent::register_hooks( $force );
        add_action( 'init', [ $this, 'rewrite_routes' ] );
        add_action( 'template_redirect', [ $this, 'render_template' ] );
        add_filter( 'redirect_canonical', [ $this, 'disable_canonical_redirect' ] );
    }

    public function disable_canonical_redirect( $redirect_url ) {
        if ( get_query_var( 'debug-suite-swagger' ) === 'docs' ) {
            return false;
        }
        return $redirect_url;
    }

    public static function rewrite_routes() {
        $base = preg_quote( SwaggerService::rewrite_base_api(), '/' );
        add_rewrite_tag( '%debug-suite-swagger%', '([^&]+)' );
        add_rewrite_rule( '^' . $base . '/docs(?:/.*)?$', 'index.php?debug-suite-swagger=docs', 'top' );
        add_rewrite_rule( '^' . $base . '/schema/?$', 'index.php?debug-suite-swagger=schema', 'top' );
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

        if ( 'schema' === $view ) {
            $this->swagger_schema();
        }

        if ( 'docs' === $view ) {
            $base_api   = SwaggerService::rewrite_base_api();
            $schema_url = user_trailingslashit( home_url( $base_api . '/schema' ) );
            $docs_path  = user_trailingslashit( '/' . $base_api . '/docs' );
            $title      = SwaggerService::get_info_title( SwaggerService::get_clean_namespace() );
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
                'swagger',
                [
					'base_api'          => $base_api,
					'schema_url'        => $schema_url,
					'docs_path'         => $docs_path,
					'title'             => $title,
					'logo_url'          => $logo_url,
                    'namespaces'        => SwaggerService::get_namespaces(),
                    'current_namespace' => SwaggerService::get_clean_namespace(),
                    'debug_suite_favicon_url' => $debug_suite_favicon_url,
				]
            );
            exit;
        }
    }

    /**
     * Get the ID of the page.
     *
     * @since DEBUG_SUITE_SINCE
     *
     * @return string
     */
	public function get_id(): string {
		return 'swagger';
	}

    /**
     * @inheritDoc
     *
     * @since DEBUG_SUITE_SINCE
     */
	public function menu( string $capability, string $position ): array {
		return [
            'page_title' => __( 'Debug Suite Swagger Docs', 'debug-suite' ),
            'menu_title' => __( 'API Docs', 'debug-suite' ),
            'capability' => $capability,
            'position'   => $position ?? 30,
            'route'      => home_url( SwaggerService::rewrite_base_api() . '/docs' ),
        ];
	}

    /**
     * @inheritDoc
     *
     * @since DEBUG_SUITE_SINCE
     */
	public function settings(): array {
		return [
            'schema' => user_trailingslashit( home_url( SwaggerService::rewrite_base_api() . '/schema' ) ),
        ];
	}

    /**
     * @inheritDoc
     *
     * @since DEBUG_SUITE_SINCE
     */
	public function scripts(): array {
        return [ 'debug-suite-swagger' ];
	}

    /**
     * Get the styles.
     *
     * @since DEBUG_SUITE_SINCE
     *
     * @return array<string> An array of style handles.
     */
    public function styles(): array {
        return [];
    }

        /**
         * Register the page scripts and styles.
         *
         * @since DEBUG_SUITE_SINCE
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
