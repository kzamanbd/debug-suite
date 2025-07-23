<?php
/**
 * Container exception for DI compliance.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core\Container\Exceptions;

use Exception;
use DebugSuite\Core\Container\ContainerExceptionInterface;

/**
 * Container exception for DI compliance.
 *
 * Base exception for all container-related errors. Implement the
 * ContainerExceptionInterface for standard compliance.
 *
 * @since 1.0.0
 */
class ContainerException extends Exception implements ContainerExceptionInterface {
}
