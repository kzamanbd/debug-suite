<?php
/**
 * Container builder for configuring and creating DI Containers.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core\Container;

use DebugSuite\Core\Container\Definitions\DefinitionInterface;

/**
 * Container builder for configuring and creating DI Containers.
 *
 * Provides a fluent interface for configuring dependency injection containers
 * with compatible features and configuration options.
 *
 * @since 1.0.0
 */
class ContainerBuilder {

	/**
	 * Whether autowiring is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	private bool $autowiring_enabled = true;

	/**
	 * Definition sources for the container.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, DefinitionInterface>
	 */
	private array $definitions = [];

	/**
	 * Enable or disable autowiring.
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
	 *
	 * @param array<string, DefinitionInterface> $definitions Array of definitions.
	 *
	 * @return static
	 */
	public function add( array $definitions ): static {
		$this->definitions = array_merge( $this->definitions, $definitions );
		return $this;
	}

	/**
	 * Build the container with the configured options.
	 *
	 * @since 1.0.0
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
