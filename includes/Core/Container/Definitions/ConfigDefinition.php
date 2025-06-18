<?php
/**
 * Configuration-based definition for dependency injection.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core\Container\Definitions;

/**
 * Configuration-based definition for dependency injection.
 *
 * Allows services to be configured differently based on environment,
 * WordPress constants, or other runtime conditions.
 *
 * @since DEBUG_SUITE_SINCE
 */
class ConfigDefinition implements DefinitionInterface {

	/**
	 * The definition name.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * Configuration mapping for different environments.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var array<string, mixed>
	 */
	private array $configurations;

	/**
	 * Default configuration fallback.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var mixed
	 */
	private $default_config;

	/**
	 * Whether this is a singleton.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var bool
	 */
	private bool $singleton;

	/**
	 * Constructor.
	 *
	 * @param array $configurations Configuration mapping.
	 * @param mixed|null $default_config Default configuration.
	 * @param bool  $singleton      Whether this is a singleton.
	 *
	 *@since DEBUG_SUITE_SINCE
	 */
	public function __construct( array $configurations = [], mixed $default_config = null, bool $singleton = false ) {
		$this->configurations = $configurations;
		$this->default_config = $default_config;
		$this->singleton      = $singleton;
		$this->name           = '';
	}

	/**
	 * Get the name of this definition.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Set the name of this definition.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $name The definition name.
	 *
	 * @return static
	 */
	public function set_name( string $name ): static {
		$this->name = $name;
		return $this;
	}

	/**
	 * Resolve this definition to a configuration value.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param callable $resolver Function to resolve dependencies.
	 *
	 * @return mixed
	 */
	public function resolve( callable $resolver ) {
		$environment = $this->detect_environment();

		// Check for environment-specific config
		if ( isset( $this->configurations[ $environment ] ) ) {
			return $this->configurations[ $environment ];
		}

		// Check for WordPress debug mode config
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && isset( $this->configurations['debug'] ) ) {
			return $this->configurations['debug'];
		}

		// Check for production config
		if ( isset( $this->configurations['production'] ) ) {
			return $this->configurations['production'];
		}

		// Return default configuration
		return $this->default_config;
	}

	/**
	 * Check if this definition is cacheable (singleton).
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return bool
	 */
	public function is_singleton(): bool {
		return $this->singleton;
	}

	/**
	 * Set configuration for a specific environment.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $environment Environment name.
	 * @param mixed  $config      Configuration value.
	 *
	 * @return static
	 */
	public function for_environment( string $environment, $config ): static {
		$this->configurations[ $environment ] = $config;
		return $this;
	}

	/**
	 * Set configuration for debug mode.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param mixed $config Configuration value.
	 *
	 * @return static
	 */
	public function for_debug( $config ): static {
		$this->configurations['debug'] = $config;
		return $this;
	}

	/**
	 * Set configuration for production mode.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param mixed $config Configuration value.
	 *
	 * @return static
	 */
	public function for_production( $config ): static {
		$this->configurations['production'] = $config;
		return $this;
	}

	/**
	 * Detect current environment.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return string
	 */
	private function detect_environment(): string {
		// Check for explicit environment constant
		if ( defined( 'WP_ENVIRONMENT_TYPE' ) ) {
			return constant( 'WP_ENVIRONMENT_TYPE' );
		}

		// Check for debug mode
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return 'development';
		}

		// Default to production
		return 'production';
	}
}
