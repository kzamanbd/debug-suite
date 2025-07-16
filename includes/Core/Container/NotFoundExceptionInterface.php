<?php
/**
 * Not found exception interface.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Core\Container;

/**
 * No entry was found in the container.
 *
 * This interface is equivalent to PSR-11 NotFoundExceptionInterface but uses our own namespace
 * to avoid external dependencies while maintaining the same contract.
 *
 * @since 1.0.0
 */
interface NotFoundExceptionInterface extends ContainerExceptionInterface {
}
