<?php

/**
 * OpenAPI service for Debug Suite business logic.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Services;

use DebugSuite\Interfaces\ServiceInterface;
use Closure;

class OpenApiService implements ServiceInterface {

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
        return 'debug-suite/api';
    }

    public static function get_namespaces() {
        $cache_key = self::get_namespace_cache_key();

        if ( isset( self::$namespace_cache[ $cache_key ] ) ) {
            return self::$namespace_cache[ $cache_key ];
        }

        $namespaces = rest_get_server()->get_namespaces();
        usort(
            $namespaces,
            function ( $a, $b ) {
				$a_prefix = explode( '/', $a )[0];
				$b_prefix = explode( '/', $b )[0];

				return [ $a_prefix, $a ] <=> [ $b_prefix, $b ];
			}
        );
        self::$namespace_cache[ $cache_key ] = $namespaces;

        return $namespaces;
    }

    /**
     * Build a human-readable title for the selected schema namespace.
     *
     * @since 1.1.3
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

    public function get_schema() {
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
                    get_option( 'debug_suite_openapi_api_basepath', 'wp/v2' ),
                    home_url(),
                ]
            )
        );
    }

    private function get_schema_cache_key(): string {
        return 'debug_suite_openapi_schema_' . md5(
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
        return '/' . trim( get_option( 'debug_suite_openapi_api_basepath', 'wp/v2' ), '/' );
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
        $raw    = $this->get_raw_paths();
        $server = rest_get_server();

        $paths = [];

        foreach ( $raw as $endpoint => $args ) {
            $ep      = $this->convert_endpoint( $endpoint );
            $options = method_exists( $server, 'get_route_options' ) ? $server->get_route_options( $endpoint ) : [];
            if ( ! is_array( $options ) ) {
                $options = [];
            }
            $paths[ $ep ] = $this->get_methods_from_args( $ep, $endpoint, $args, $options );
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

    public function get_methods_from_args( $ep, $endpoint, $args, array $route_options = [] ) {

        $path_parameters = $this->get_parameters_from_endpoint( $endpoint );
        $methods = [];

        $tags = $this->get_default_tags_from_endpoint( $endpoint );

        $resource_schema = $this->resolve_resource_schema( $route_options );
        $common_args     = isset( $route_options['args'] ) && is_array( $route_options['args'] ) ? $route_options['args'] : [];

        foreach ( $args as $index => $arg ) {
            if ( ! is_int( $index ) || ! is_array( $arg ) || ! isset( $arg['methods'] ) ) {
                continue;
            }

            $handler_args   = isset( $arg['args'] ) && is_array( $arg['args'] ) ? $arg['args'] : [];
            $merged_args    = array_merge( $common_args, $handler_args );
            $normalized_methods = $this->normalize_handler_methods( $arg['methods'] );

            $all_parameters = $this->get_parameters_from_args(
                $ep,
                $merged_args,
                $normalized_methods
            );

            foreach ( $normalized_methods as $method => $bool ) {
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

                if ( ! empty( $arg['accept_json'] ) ) {
                    $consumes[] = 'application/json';
                }

                if ( isset( $route_options['tags'] ) && is_array( $route_options['tags'] ) ) {
                    $tags = $route_options['tags'];
                } elseif ( isset( $arg['tags'] ) && is_array( $arg['tags'] ) ) {
                    $tags = $arg['tags'];
                }

                $is_public = isset( $arg['permission_callback'] ) && '__return_true' === $arg['permission_callback'];
                $security  = $is_public ? [] : $this->get_security();

                $request_body = null;
                if ( in_array( $mtd, [ 'post', 'put', 'patch', 'delete' ], true ) ) {
                    $request_body = $this->build_request_body(
                        $merged_args,
                        $endpoint
                    );

                    // Body fields now live under requestBody; drop formData entries so they don't double-render.
                    $parameters = array_values(
                        array_filter(
                            $parameters,
                            function ( $param ) {
                                return ! isset( $param['in'] ) || 'formData' !== $param['in'];
                            }
                        )
                    );
                }

                $responses = $this->get_responses(
                    $method_endpoint,
                    [
                        'method'     => $mtd,
                        'endpoint'   => $ep,
                        'schema'     => $resource_schema,
                        'args'       => $merged_args,
                        'is_public'  => $is_public,
                    ]
                );
                if ( isset( $arg['responses'] ) ) {
                    $responses = $arg['responses'];
                }

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

                if ( $request_body ) {
                    $conf['requestBody'] = $request_body;
                }

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
         * When the type is object, the renderer by default adds empty `{}` to parameter value
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

        if ( isset( $detail['schema'] ) && is_array( $detail['schema'] ) ) {
            $params['schema'] = $this->sanitize_schema_for_output( $detail['schema'] );
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

    public function get_responses( $method_endpoint, array $context = [] ) {
        $responses = [
            '200' => [ 'description' => 'OK' ],
            '404' => [ 'description' => 'Not Found' ],
            '400' => [ 'description' => 'Bad Request' ],
        ];

        $method    = isset( $context['method'] ) ? $context['method'] : '';
        $endpoint  = isset( $context['endpoint'] ) ? $context['endpoint'] : '';
        $schema    = isset( $context['schema'] ) && is_array( $context['schema'] ) ? $context['schema'] : [];
        $args      = isset( $context['args'] ) && is_array( $context['args'] ) ? $context['args'] : [];
        $is_public = ! empty( $context['is_public'] );

        $response_schema = null;

        if ( ! empty( $schema ) ) {
            $response_schema = $schema;

            // GET on a collection endpoint (path does not end with an item identifier) returns a list.
            if ( 'get' === $method && ! $this->endpoint_targets_single_item( $endpoint ) ) {
                $response_schema = [
                    'type'  => 'array',
                    'items' => $schema,
                ];
            }
        } else {
            // Fallback: synthesize a minimal response shape from the request args
            // so the spotlight isn't an empty "OK" for routes that never declared a schema.
            $synthesized = $this->synthesize_response_schema_from_args( $args, $endpoint );
            if ( null !== $synthesized ) {
                $response_schema = $synthesized;
            }
        }

        if ( null !== $response_schema ) {
            $responses['200'] = [
                'description' => 'OK',
                'content'     => [
                    'application/json' => [
                        'schema'  => $response_schema,
                        'example' => $this->generate_example_from_schema( $response_schema ),
                    ],
                ],
            ];
        }

        if ( 'post' === $method ) {
            $responses['201'] = [ 'description' => 'Created' ];
        }

        if ( 'delete' === $method ) {
            $responses['204'] = [ 'description' => 'No Content' ];
        }

        if ( in_array( $method, [ 'post', 'put', 'patch' ], true ) ) {
            $responses['422'] = [ 'description' => 'Unprocessable Entity' ];
        }

        if ( ! $is_public ) {
            $responses['401'] = [ 'description' => 'Unauthorized' ];
            $responses['403'] = [ 'description' => 'Forbidden' ];
        }

        return apply_filters(
            'debug_suite_openapi_responses_' . $method_endpoint,
            $responses,
            $context
        );
    }

    /**
     * Build a placeholder JSON Schema for a 200 response by reflecting the request args.
     *
     * Most plugin routes never register a `schema` callback, so the spotlight ends up
     * showing a bare `200 OK` with no body shape. This is a best-effort fallback: it
     * echoes args (minus path/query/header params) as the response object so consumers
     * at least see plausible field names. Returns null when there's nothing useful to show.
     *
     * @param array<string, mixed> $args
     * @param string               $endpoint
     * @return array<string, mixed>|null
     */
    private function synthesize_response_schema_from_args( array $args, string $endpoint ) {
        if ( empty( $args ) ) {
            return null;
        }

        $properties = [];
        foreach ( $args as $name => $detail ) {
            if ( ! is_array( $detail ) ) {
                continue;
            }
            if ( strpos( $endpoint, '(?P<' . $name . '>' ) !== false ) {
                continue;
            }
            if ( isset( $detail['in'] ) && in_array( $detail['in'], [ 'path', 'query', 'header' ], true ) ) {
                continue;
            }

            $prop = $this->schema_from_arg( $detail );
            // File-upload fields don't echo back in a JSON response — drop the binary marker.
            if ( isset( $prop['format'] ) && 'binary' === $prop['format'] ) {
                $prop['type']   = 'string';
                $prop['format'] = 'uri';
            }
            $properties[ $name ] = $prop;
        }

        if ( empty( $properties ) ) {
            return null;
        }

        return [
            'type'       => 'object',
            'properties' => $properties,
        ];
    }

    /**
     * Build an OpenAPI 3 requestBody from the endpoint args.
     *
     * Args destined for the path, query string, or headers are left in `parameters`;
     * everything else becomes a JSON Schema property under `application/json`.
     *
     * @param array  $args     Raw `args` from the endpoint definition.
     * @param string $endpoint Raw route pattern (still contains `(?P<id>...)` placeholders).
     * @return array<string, mixed>|null
     */
    public function build_request_body( array $args, string $endpoint ) {
        if ( empty( $args ) ) {
            return null;
        }

        $properties = [];
        $required   = [];
        $has_binary = false;

        foreach ( $args as $name => $detail ) {
            if ( ! is_array( $detail ) ) {
                continue;
            }

            if ( strpos( $endpoint, '(?P<' . $name . '>' ) !== false ) {
                continue;
            }

            if ( isset( $detail['in'] ) && in_array( $detail['in'], [ 'path', 'query', 'header' ], true ) ) {
                continue;
            }

            $property = $this->schema_from_arg( $detail );
            if ( isset( $property['format'] ) && 'binary' === $property['format'] ) {
                $has_binary = true;
            }
            $properties[ $name ] = $property;

            if ( ! empty( $detail['required'] ) ) {
                $required[] = $name;
            }
        }

        if ( empty( $properties ) ) {
            return null;
        }

        $schema = [
            'type'       => 'object',
            'properties' => $properties,
        ];

        if ( ! empty( $required ) ) {
            $schema['required'] = $required;
        }

        $content_type = $has_binary ? 'multipart/form-data' : 'application/json';
        $body_payload = [ 'schema' => $schema ];
        if ( ! $has_binary ) {
            $body_payload['example'] = $this->generate_example_from_schema( $schema );
        }

        return [
            'required' => ! empty( $required ),
            'content'  => [
                $content_type => $body_payload,
            ],
        ];
    }

    /**
     * Convert a single WP REST arg definition into a JSON Schema property.
     *
     * @param array<string, mixed> $detail
     * @return array<string, mixed>
     */
    private function schema_from_arg( array $detail ): array {
        $type = $detail['type'] ?? 'string';
        if ( is_array( $type ) && isset( $type[0] ) ) {
            $type = $type[0];
        }
        if ( empty( $type ) ) {
            $type = 'string';
        }

        $prop = [ 'type' => $type ];

        $scalar_keys = [
            'description',
            'format',
            'enum',
            'minimum',
            'maximum',
            'exclusiveMinimum',
            'exclusiveMaximum',
            'multipleOf',
            'minLength',
            'maxLength',
            'pattern',
            'minItems',
            'maxItems',
            'uniqueItems',
            'minProperties',
            'maxProperties',
            'nullable',
            'readOnly',
            'writeOnly',
            'example',
            'title',
        ];
        foreach ( $scalar_keys as $key ) {
            if ( ! isset( $detail[ $key ] ) ) {
                continue;
            }
            $value = $detail[ $key ];
            if ( is_array( $value ) ) {
                $prop[ $key ] = $this->sanitize_schema_for_output( $value );
            } elseif ( is_scalar( $value ) ) {
                $prop[ $key ] = $value;
            }
        }

        if ( array_key_exists( 'default', $detail ) ) {
            $default = $detail['default'];
            if ( is_array( $default ) ) {
                $prop['default'] = $this->sanitize_schema_for_output( $default );
            } elseif ( null === $default || is_scalar( $default ) ) {
                $prop['default'] = $default;
            }
        }

        if ( isset( $detail['items'] ) && is_array( $detail['items'] ) ) {
            $prop['items'] = $this->sanitize_schema_for_output( $detail['items'] );
        }

        if ( isset( $detail['properties'] ) && is_array( $detail['properties'] ) ) {
            $prop['properties'] = $this->sanitize_schema_for_output( $detail['properties'] );
        }

        if ( isset( $detail['additionalProperties'] ) ) {
            $additional = $detail['additionalProperties'];
            if ( is_array( $additional ) ) {
                $prop['additionalProperties'] = $this->sanitize_schema_for_output( $additional );
            } elseif ( is_bool( $additional ) ) {
                $prop['additionalProperties'] = $additional;
            }
        }

        foreach ( [ 'oneOf', 'anyOf', 'allOf' ] as $combinator ) {
            if ( isset( $detail[ $combinator ] ) && is_array( $detail[ $combinator ] ) ) {
                $prop[ $combinator ] = $this->sanitize_schema_for_output( $detail[ $combinator ] );
            }
        }

        return $prop;
    }

    /**
     * Resolve the route-level resource schema into a plain array.
     *
     * Route-level options (schema, namespace, allow_batch, common args) are moved
     * out of the handler list by WP_REST_Server::get_routes() and stored on
     * WP_REST_Server::$route_options — fetch via get_route_options() upstream.
     *
     * @param array $route_options Options returned by WP_REST_Server::get_route_options().
     * @return array<string, mixed>
     */
    public function resolve_resource_schema( array $route_options ): array {
        if ( ! isset( $route_options['schema'] ) ) {
            return [];
        }

        $schema = $route_options['schema'];
        if ( is_callable( $schema ) ) {
            try {
                $schema = call_user_func( $schema );
            } catch ( \Throwable $e ) {
                return [];
            }
        }

        if ( ! is_array( $schema ) ) {
            return [];
        }

        return $this->sanitize_schema_for_output( $schema );
    }

    /**
     * Strip Closures / objects / callback keys out of a schema tree so it can be JSON-encoded and cached via transient.
     *
     * WP REST args frequently carry `validate_callback`/`sanitize_callback` closures and
     * resource schemas can embed closures in nested properties. `set_transient()` goes
     * through `maybe_serialize()`, which throws on Closures, so anything we put into the
     * OpenAPI document must be closure-free.
     *
     * @param mixed $data
     * @return mixed
     */
    private function sanitize_schema_for_output( $data ) {
        if ( $data instanceof Closure || is_object( $data ) || is_resource( $data ) ) {
            return null;
        }

        if ( ! is_array( $data ) ) {
            return $data;
        }

        $blocked_keys = [ 'validate_callback', 'sanitize_callback', 'arg_options' ];
        $out = [];
        foreach ( $data as $key => $value ) {
            if ( in_array( $key, $blocked_keys, true ) ) {
                continue;
            }
            if ( $value instanceof Closure || is_object( $value ) || is_resource( $value ) ) {
                continue;
            }
            $out[ $key ] = is_array( $value ) ? $this->sanitize_schema_for_output( $value ) : $value;
        }
        return $out;
    }

    /**
     * Normalize an endpoint's `methods` field to the canonical ['GET' => true] form.
     *
     * Accepts the WP-normalized shape as well as list and comma-string forms that
     * may arrive through the rest_endpoints filter or custom REST servers.
     *
     * @param mixed $methods
     * @return array<string, bool>
     */
    private function normalize_handler_methods( $methods ): array {
        if ( is_string( $methods ) ) {
            $methods = array_filter( array_map( 'trim', explode( ',', $methods ) ) );
        }

        if ( ! is_array( $methods ) ) {
            return [];
        }

        $normalized = [];
        foreach ( $methods as $key => $value ) {
            if ( is_string( $key ) ) {
                if ( $value ) {
                    $normalized[ strtoupper( $key ) ] = true;
                }
                continue;
            }

            if ( is_string( $value ) && '' !== $value ) {
                $normalized[ strtoupper( $value ) ] = true;
            }
        }

        return $normalized;
    }

    /**
     * Whether an endpoint targets a single resource (path ends with a placeholder like {id}).
     */
    public function endpoint_targets_single_item( string $endpoint ): bool {
        return (bool) preg_match( '/\{[^}]+\}\/?$/', $endpoint );
    }

    /**
     * Build a representative example value from a JSON Schema definition.
     *
     * @param array<string, mixed> $schema JSON Schema fragment.
     * @param int                  $depth  Recursion guard.
     * @return mixed
     */
    public function generate_example_from_schema( array $schema, int $depth = 0 ) {
        if ( $depth > 6 ) {
            return null;
        }

        if ( array_key_exists( 'example', $schema ) ) {
            return $schema['example'];
        }

        if ( array_key_exists( 'default', $schema ) ) {
            return $schema['default'];
        }

        if ( isset( $schema['enum'] ) && is_array( $schema['enum'] ) && ! empty( $schema['enum'] ) ) {
            return reset( $schema['enum'] );
        }

        $type = $schema['type'] ?? 'string';
        if ( is_array( $type ) ) {
            $type = reset( $type );
        }

        switch ( $type ) {
            case 'object':
                $example = [];
                if ( isset( $schema['properties'] ) && is_array( $schema['properties'] ) ) {
                    foreach ( $schema['properties'] as $name => $property ) {
                        if ( ! is_array( $property ) ) {
                            continue;
                        }
                        $example[ $name ] = $this->generate_example_from_schema( $property, $depth + 1 );
                    }
                }
                return $example;

            case 'array':
                $items = isset( $schema['items'] ) && is_array( $schema['items'] ) ? $schema['items'] : [ 'type' => 'string' ];
                return [ $this->generate_example_from_schema( $items, $depth + 1 ) ];

            case 'integer':
                return isset( $schema['minimum'] ) ? (int) $schema['minimum'] : 0;

            case 'number':
                return isset( $schema['minimum'] ) ? (float) $schema['minimum'] : 0.0;

            case 'boolean':
                return false;

            case 'null':
                return null;

            case 'string':
            default:
                $format = $schema['format'] ?? '';
                switch ( $format ) {
                    case 'date-time':
                        return gmdate( 'Y-m-d\TH:i:s' );
                    case 'date':
                        return gmdate( 'Y-m-d' );
                    case 'time':
                        return gmdate( 'H:i:s' );
                    case 'email':
                        return 'user@example.com';
                    case 'uri':
                    case 'url':
                        return home_url( '/' );
                    case 'uuid':
                        return '00000000-0000-0000-0000-000000000000';
                    case 'ip':
                    case 'ipv4':
                        return '0.0.0.0';
                    case 'hex-color':
                        return '#000000';
                    default:
                        return '';
                }
        }
    }

    public function security_definitions() {
        $definitions = [
            'basicAuth' => [
                'type'        => 'http',
                'scheme'      => 'basic',
                'description' => 'Basic Authentication (e.g., using WordPress Application Passwords)',
            ],
            'bearerAuth' => [
                'type'         => 'http',
                'scheme'       => 'bearer',
                'bearerFormat' => 'JWT',
                'description'  => 'Bearer token authentication (e.g., JWT or OAuth access token). Provide the token without the "Bearer " prefix.',
            ],
        ];
        return apply_filters( 'debug_suite_openapi_security_definitions', $definitions );
    }
}
