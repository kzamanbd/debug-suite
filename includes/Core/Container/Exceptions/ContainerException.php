<?php
/**
 * Container exception for DI compliance.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core\Container\Exceptions;

use Exception;
use Psr\Container\ContainerExceptionInterface;

/**
 * Container exception for DI compliance.
 *
 * Base exception for all container-related errors. Implements the
 * ContainerExceptionInterface for standard compliance.
 *
 * @since DEBUG_SUITE_SINCE
 */
class ContainerException extends Exception implements ContainerExceptionInterface {
}
