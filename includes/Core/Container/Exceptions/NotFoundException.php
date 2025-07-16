<?php
/**
 * Not found exception for DI compliance.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core\Container\Exceptions;

use DebugSuite\Core\Container\NotFoundExceptionInterface;

/**
 * Not found exception for DI compliance.
 *
 * Thrown when a service identifier is not found in the container.
 * Implement the NotFoundExceptionInterface for standard compliance.
 *
 * @since 1.0.0
 */
class NotFoundException extends ContainerException implements NotFoundExceptionInterface {

	/**
	 * Create a not found exception for a service identifier.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id The service identifier that was not found.
	 *
	 * @return static
	 */
	public static function for_identifier( string $id ): static {
		return new static( "Service [{$id}] not found in container." );
	}

	/**
	 * Create a not found exception for a service identifier with custom message.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id      The service identifier that was not found.
	 * @param string $message Custom error message with additional context.
	 *
	 * @return static
	 */
	public static function for_identifier_with_message( string $id, string $message ): static {
		return new static( "Service [{$id}] not found in container: {$message}" );
	}
}
