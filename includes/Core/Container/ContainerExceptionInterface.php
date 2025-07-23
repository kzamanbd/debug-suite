<?php
/**
 * Base container exception interface.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core\Container;

use Throwable;

/**
 * Base interface representing a generic exception in a container.
 *
 * This interface is equivalent to PSR-11 ContainerExceptionInterface but uses our own namespace
 * to avoid external dependencies while maintaining the same contract.
 *
 * @since 1.0.0
 */
interface ContainerExceptionInterface extends Throwable {
}
