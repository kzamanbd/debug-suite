<?php
/**
 * Container builder for configuring and creating DI containers.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core\DI;

use DebugSuite\Core\DI\Definitions\DefinitionInterface;

/**
 * Container builder for configuring and creating DI containers.
 *
 * Provides a fluent interface for configuring dependency injection containers
 * with PHP-DI compatible features and configuration options.
 *
 * @since DEBUG_SUITE_SINCE
 */
class ContainerBuilder {

	/**
	 * Whether autowiring is enabled.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var bool
	 */
	private bool $autowiring_enabled = true;

	/**
	 * Definition sources for the container.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @var array<string, DefinitionInterface>
	 */
	private array $definitions = [];

	/**
	 * Enable or disable autowiring.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param bool $enabled Whether autowiring should be enabled.
	 *
	 * @return static
	 */
	public function enable_autowiring( bool $enabled = true ): static {
		$this->autowiring_enabled = $enabled;
		return $this;
	}

	/**
	 * Add definitions to the container.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param array<string, DefinitionInterface> $definitions Array of definitions.
	 *
	 * @return static
	 */
	public function add_definitions( array $definitions ): static {
		$this->definitions = array_merge( $this->definitions, $definitions );
		return $this;
	}

	/**
	 * Build the container with the configured options.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @return Container
	 */
	public function build(): Container {
		$container = Container::get_instance();
		$container->set_autowiring( $this->autowiring_enabled );

		// Add definitions to the container
		foreach ( $this->definitions as $id => $definition ) {
			$container->set( $id, $definition );
		}

		return $container;
	}
}
