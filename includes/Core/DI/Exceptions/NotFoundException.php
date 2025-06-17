<?php
/**
 * Not found exception for PSR-11 compliance.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core\DI\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Not found exception for PSR-11 compliance.
 *
 * Thrown when a service identifier is not found in the container.
 * Implements the PSR-11 NotFoundExceptionInterface for standard compliance.
 *
 * @since DEBUG_SUITE_SINCE
 */
class NotFoundException extends ContainerException implements NotFoundExceptionInterface {

	/**
	 * Create a not found exception for a service identifier.
	 *
	 * @since DEBUG_SUITE_SINCE
	 *
	 * @param string $id The service identifier that was not found.
	 *
	 * @return static
	 */
	public static function for_identifier( string $id ): static {
		return new static( "Service [{$id}] not found in container." );
	}
}
