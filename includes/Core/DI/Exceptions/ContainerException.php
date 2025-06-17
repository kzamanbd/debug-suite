<?php
/**
 * Container exception for PSR-11 compliance.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core\DI\Exceptions;

use Exception;
use Psr\Container\ContainerExceptionInterface;

/**
 * Container exception for PSR-11 compliance.
 *
 * Base exception for all container-related errors. Implements the PSR-11
 * ContainerExceptionInterface for standard compliance.
 *
 * @since DEBUG_SUITE_SINCE
 */
class ContainerException extends Exception implements ContainerExceptionInterface {
}
