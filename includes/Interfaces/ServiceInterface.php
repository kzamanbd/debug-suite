<?php
/**
 * Base service interface for Debug Suite services.
 *
 * @package DebugSuite
 */

namespace DebugSuite\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Service interface for implementing business logic services.
 *
 * Provides a contract for service classes that handle business logic
 * separated from API controllers and data access layers.
 *
 * @since 1.0.0
 */
interface ServiceInterface {
	// Marker interface for service identification
	// Individual services will define their own methods
}
