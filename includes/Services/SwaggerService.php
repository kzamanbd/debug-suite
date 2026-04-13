<?php

/**
 * Swagger service for Debug Suite business logic.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Services;

use DebugSuite\Interfaces\ServiceInterface;

class SwaggerService implements ServiceInterface {

    /**
     * Cached schema documents for the current request.
     *
     * @var array<string, array>
     */
    private static array $schema_cache = [];

    /**
     * Cached namespace lists for the current request.
     *
     * @var array<string, array<string>>
     */
    private static array $namespace_cache = [];

    public static function rewrite_base_api() {
        return 'debug-api';
    }

    public static function get_namespaces() {
        $cache_key = self::get_namespace_cache_key();

        if ( isset( self::$namespace_cache[ $cache_key ] ) ) {
            return self::$namespace_cache[ $cache_key ];
        }

        $namespaces = rest_get_server()->get_namespaces();
        self::$namespace_cache[ $cache_key ] = $namespaces;

        return $namespaces;
    }

    /**
     * Build a human-readable title for the selected schema namespace.
     *
     * @since DEBUG_SUITE_SINCE
     *
     * @param string|null $namespace Optional namespace to resolve.
     * @return string
     */
    public static function get_info_title( ?string $namespace = null ): string {
        $namespace = $namespace ? trim( $namespace, '/' ) : self::get_clean_namespace();
        $title = get_option( 'blogname' ) . ' API';

        if ( '' === $namespace ) {
            return $title;
        }

        $segments = explode( '/', $namespace );
        $slug = strtolower( trim( $segments[0] ?? '', '/' ) );

        if ( '' === $slug ) {
            return $title;
        }

        if ( 'wp' === $slug ) {
            $title = 'WordPress REST API';
        } else {
            $title = trim( preg_replace( '/[-_]+/', ' ', $slug ) );
            $title = ucwords( $title ) . ' API';
        }

        return $title;
    }

    public function swagger() {
        $cache_key = $this->get_schema_cache_key();

        if ( isset( self::$schema_cache[ $cache_key ] ) ) {
            return self::$schema_cache[ $cache_key ];
        }

        $cached_schema = get_transient( $cache_key );
        if ( false !== $cached_schema && is_array( $cached_schema ) ) {
            self::$schema_cache[ $cache_key ] = $cached_schema;

            return $cached_schema;
        }

        $logo_url = get_site_icon_url();
        if ( ! $logo_url && function_exists( 'has_custom_logo' ) && has_custom_logo() ) {
            $logo_url = wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' );
        }

        $namespace = self::get_clean_namespace();
        $version   = '1.0.0';

        if ( preg_match( '/v\d+(?:\.\d+)?/i', $namespace, $matches ) ) {
            $version = strtolower( $matches[0] );
        }

        $info = [
            'title'       => self::get_info_title( $namespace ),
            'description' => get_option( 'blogdescription' ),
            'version'     => $version,
            'contact'     => [
                'email' => get_option( 'admin_email' ),
            ],
        ];

        $schema = [
            'openapi' => '3.0.0',
            'info' => $info,
            'host' => $this->get_host(),
            'tags' => [],
            'schemes' => $this->get_schemes(),
            'paths' => $this->get_paths(),
            'servers' => [
                [
                    'url' => $this->get_base_path(),
                ],
            ],
            'components' => [
                'securitySchemes' => $this->security_definitions(),
            ],
        ];

        self::$schema_cache[ $cache_key ] = $schema;
        set_transient( $cache_key, $schema, HOUR_IN_SECONDS );

        return $schema;
    }

    private static function get_namespace_cache_key(): string {
        return md5(
            implode(
                '|',
                [
                    self::get_namespace(),
                    get_option( 'blogname' ),
                    get_option( 'debug_suite_swagger_api_basepath', 'wp/v2' ),
                    home_url(),
                ]
            )
        );
    }

    private function get_schema_cache_key(): string {
        return 'debug_suite_swagger_schema_' . md5(
            implode(
                '|',
                [
                    self::get_namespace(),
                    DEBUG_SUITE_VERSION,
                    home_url(),
                    rest_get_url_prefix(),
                    get_option( 'blogname' ),
                    get_option( 'blogdescription' ),
                    get_option( 'admin_email' ),
                    (string) get_option( 'site_icon' ),
                    (string) get_theme_mod( 'custom_logo' ),
                ]
            )
        );
    }

    public function get_host() {
        $host = parse_url( home_url(), PHP_URL_HOST );
        $port = parse_url( home_url(), PHP_URL_PORT );

        if ( $port ) {
            if ( $port !== 80 && $port !== 443 ) {
                $host = $host . ':' . $port;
            }
        }

        return $host;
    }

    public function get_base_path() {
        $path = parse_url( home_url(), PHP_URL_PATH );
        return rtrim( $path ?? '', '/' ) . '/' . ltrim( rest_get_url_prefix(), '/' );
    }

    public function get_schemes() {
        $schemes = [];
        if ( is_ssl() ) {
            $schemes[] = 'https';
        }
        $schemes[] = 'http';
        return $schemes;
    }

    public static function get_namespace() {
        $namespace = isset( $_REQUEST['namespace'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['namespace'] ) ) : '';
        if ( ! empty( $namespace ) ) {
            return '/' . trim( $namespace, '/' );
        }
        return '/' . trim( get_option( 'debug_suite_swagger_api_basepath', 'wp/v2' ), '/' );
    }

    public static function get_clean_namespace() {
        return trim( self::get_namespace(), '/' );
    }

    public function get_raw_paths() {
        $routes = rest_get_server()->get_routes();
        $basepath = self::get_namespace();

        $raw_paths = [];
        foreach ( $routes as $route => $value ) {
            if ( mb_strpos( $route, $basepath ) === 0 && ( $basepath !== $route ) ) {
                $raw_paths[ $route ] = $value;
            }
        }

        return $raw_paths;
    }

    public function get_paths() {
        $raw = $this->get_raw_paths();

        $paths = [];

        foreach ( $raw as $endpoint => $args ) {
            $ep = $this->convert_endpoint( $endpoint );
            $paths[ $ep ] = $this->get_methods_from_args( $ep, $endpoint, $args );
        }

        return $paths;
    }

    public function convert_endpoint( $endpoint ) {

        if ( mb_strpos( $endpoint, '(?P<' ) !== false ) {
            $endpoint = preg_replace_callback(
                '/\(\?P\<(.*?)>(.*)\)+/',
                function ( $match ) {
					return '{' . $match[1] . '}';
				},
                $endpoint
            );
        }

        return $endpoint;
    }

    public function get_default_tags_from_endpoint( $endpoint ) {
        $namespace = self::get_namespace();
        $ep = preg_replace_callback(
            '/^' . preg_quote( $namespace, '/' ) . '/',
            function () {
				return '';
			},
            $endpoint
        );
        $parts = explode( '/', trim( $ep, '/' ) );
        return isset( $parts[0] ) ? [ $parts[0] ] : [];
    }

    public function get_methods_from_args( $ep, $endpoint, $args ) {

        $path_parameters = $this->get_parameters_from_endpoint( $endpoint );
        $methods = [];

        $tags = $this->get_default_tags_from_endpoint( $endpoint );

        foreach ( $args as $arg ) {

            $all_parameters = $this->get_parameters_from_args(
                $ep,
                isset( $arg['args'] ) ? $arg['args'] : [],
                isset( $arg['methods'] ) ? $arg['methods'] : []
            );

            foreach ( $arg['methods'] as $method => $bool ) {
                $mtd = mb_strtolower( $method );
                $method_endpoint = $mtd . str_replace( '/', '_', $ep );
                $parameters = isset( $all_parameters[ $mtd ] ) ? $all_parameters[ $mtd ] : [];

                // Building parameters.
                $existing_names = array_map(
                    function ( $param ) {
						return $param['name'];
					},
                    $parameters
                );
                foreach ( $path_parameters as $path_params ) {
                    if ( ! in_array( $path_params['name'], $existing_names, true ) ) {
                        $parameters[] = $path_params;
                    }
                }

                $produces = [ 'application/json' ];
                if ( isset( $arg['produces'] ) ) {
                    $produces = (array) $arg['produces'];
                }

                $consumes = [
                    'application/x-www-form-urlencoded',
                    'multipart/form-data',
                ];

                if ( isset( $arg['consumes'] ) ) {
                    $consumes = (array) $arg['consumes'];
                }

                if ( $arg['accept_json'] ) {
                    $consumes[] = [ 'application/json' ];
                }

                if ( isset( $args['tags'] ) && is_array( $args['tags'] ) ) {
                    $tags = $args['tags'];
                }

                $responses = $this->get_responses( $method_endpoint );
                if ( isset( $arg['responses'] ) ) {
                    $responses = $arg['responses'];
                }

                $is_public = isset( $arg['permission_callback'] ) && '__return_true' === $arg['permission_callback'];
                $security  = $is_public ? [] : $this->get_security();

                $conf = [
                    'tags' => $tags,
                    'summary' => isset( $arg['summary'] ) ? $arg['summary'] : '',
                    'description' => isset( $arg['description'] ) ? $arg['description'] : '',
                    'consumes' => $consumes,
                    'produces' => $produces,
                    'parameters' => $parameters,
                    'security' => $security,
                    'responses' => $responses,
                ];

                $methods[ $mtd ] = $conf;
            }
        }

        return $methods;
    }

    public function get_parameters_from_endpoint( $endpoint ) {
        $path_params = [];

        if ( mb_strpos( $endpoint, '(?P<' ) !== false && ( preg_match_all( '/\(\?P\<(.*?)>(.*)\)/', $endpoint, $matches ) ) ) {
            foreach ( $matches[1] as $order => $match ) {
                $type = strpos( mb_strtolower( $matches[2][ $order ] ), '\d' ) !== false ? 'integer' : 'string';
                $params = [
                    'name' => $match,
                    'in' => 'path',
                    'description' => '',
                    'required' => true,
                    'type' => $type,
                ];
                if ( $type === 'integer' ) {
                    $params['format'] = 'int64';
                }
                $path_params[ $match ] = $params;
            }
        }

        return $path_params;
    }

    public function detect_in( $param, $mtd, $endpoint, $detail ) {
        if ( isset( $detail['in'] ) ) {
            return $detail['in'];
        }

        switch ( $mtd ) {
            case strpos( $endpoint, '{' . $param . '}' ) !== false:
                $in = 'path';
                break;
            case 'post':
                $in = 'formData';
                break;
            default:
                $in = 'query';
                break;
        }

        return $in;
    }

    public function parse_type_object_to_string( $types ) {
        if ( is_array( $types ) ) {
            foreach ( $types as $type ) {
                return $this->parse_type_object_to_string( $type );
            }
        }
        return $types === 'object' ? 'string' : $types;
    }

    public function build_params( $param, $mtd, $endpoint, $detail ) {
        /**
         * When the type is object, SwaggerUI by default add empty `{}` to parameter value
         * It's annoying so need to convert to just `string`
         */
        $type = $this->parse_type_object_to_string( $detail['type'] );
        if ( is_array( $type ) && isset( $type[0] ) ) {
            $type = $type[0];
        }

        if ( empty( $type ) ) {

            if ( strpos( $param, '_id' ) !== false ) {
                $type = 'integer';
            } elseif ( strtolower( $param ) === 'id' ) {
                $type = 'integer';
            } else {
                $type = 'string';
            }
        }

        $in = $this->detect_in( $param, $mtd, $endpoint, $detail );
        $required = ! empty( $detail['required'] );

        if ( 'path' === $in ) {
            $required = true;
        }

        $params = [
            'name' => $param,
            'in' => $in,
            'description' => isset( $detail['description'] ) ? $detail['description'] : '',
            'required' => $required,
            'type' => $type,
        ];

        if ( isset( $detail['items'] ) ) {
            $params['items'] = [
                'type' => isset( $detail['items']['type'] ) ? $detail['items']['type'] : 'string',
            ];
        } elseif ( isset( $detail['enum'] ) ) {
            $params['type'] = 'array';
            $items = [
                'type' => $detail['type'],
                'enum' => $detail['enum'],
            ];
            if ( isset( $detail['default'] ) ) {
                $items['default'] = $detail['default'];
            }
            $params['items'] = $items;
            $params['collectionFormat'] = 'multi';
        }

        if ( isset( $detail['maximum'] ) ) {
            $params['maximum'] = $detail['maximum'];
        }

        if ( isset( $detail['minimum'] ) ) {
            $params['minimum'] = $detail['minimum'];
        }

        if ( isset( $detail['format'] ) ) {
            $params['format'] = $detail['format'];
        } elseif ( $detail['type'] === 'integer' ) {
            $params['format'] = 'int64';
        }

        if ( isset( $detail['schema'] ) ) {
            $params['schema'] = $detail['schema'];
        }

        return $params;
    }

    public function get_parameters_from_args( $endpoint = '', $args = [], $methods = [] ) {
        $parameters = [];

        foreach ( $args as $param => $detail ) {
            foreach ( $methods as $method => $bool ) {
                $mtd = mb_strtolower( $method );

                if ( ! isset( $parameters[ $mtd ] ) ) {
                    $parameters[ $mtd ] = [];
                }

                $parameters[ $mtd ][] = $this->build_params( $param, $mtd, $endpoint, $detail + [ 'type' => 'string' ] );
            }
        }

        return $parameters;
    }

    public function get_security() {
        $raw = $this->security_definitions();
        if ( ! is_array( $raw ) ) {
            $raw = [];
        }

        $securities = [];
        foreach ( $raw as $key => $name ) {
            $securities[] = [
                $key => [],
            ];
        }

        return $securities;
    }

    public function get_responses( $method_endpoint ) {
        return apply_filters(
            'debug_suite_swagger_api_responses_' . $method_endpoint,
            [
				'200' => [ 'description' => 'OK' ],
				'404' => [ 'description' => 'Not Found' ],
				'400' => [ 'description' => 'Bad Request' ],
            ]
        );
    }

    public function security_definitions() {
        $definitions = [
            'basicAuth' => [
                'type'        => 'http',
                'scheme'      => 'basic',
                'description' => 'Basic Authentication (e.g., using WordPress Application Passwords)',
            ],
        ];
        return apply_filters( 'debug_suite_swagger_api_security_definitions', $definitions );
    }
}
