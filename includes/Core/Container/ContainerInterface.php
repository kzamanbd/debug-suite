<?php
/**
 * Container interface for dependency injection.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core\Container;

/**
 * Describes the interface of a container that exposes methods to read its entries.
 *
 * This interface is equivalent to PSR-11 ContainerInterface but uses our own namespace
 * to avoid external dependencies while maintaining the same contract.
 *
 * @since 1.0.0
 */
interface ContainerInterface {

	/**
	 * Finds an entry of the container by its identifier and returns it.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id Identifier of the entry to look for.
	 *
	 * @throws NotFoundExceptionInterface  No entry was found for this identifier.
	 * @throws ContainerExceptionInterface Error while retrieving the entry.
	 *
	 * @return mixed Entry.
	 */
	public function get( string $id );

	/**
	 * Returns true if the container can return an entry for the given identifier.
	 * Returns false otherwise.
	 *
	 * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
	 * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id Identifier of the entry to look for.
	 *
	 * @return bool
	 */
	public function has( string $id ): bool;
}
