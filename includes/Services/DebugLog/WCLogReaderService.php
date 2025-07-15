<?php
/**
 * WooCommerce Log Reader Service for Debug Suite.
 *
 * Handles WooCommerce-style logs with ISO 8601 timestamps and categories.
 * Format: YYYY-MM-DDTHH:MM:SS+00:00 LEVEL [CATEGORY] Message
 *
 * @package DebugSuite
 */

namespace DebugSuite\Services\DebugLog;

use DateTime;
use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WooCommerce log reader service for ISO 8601 log formats.
 *
 * @since 1.0.0
 */
class WCLogReaderService extends WPLogReaderService {

	/**
	 * Parse WooCommerce log lines (override parent method).
	 *
	 * @param array $lines Array of log lines.
	 * @return array
	 */
	protected function parse_log_entries( array $lines ): array {
		$entries = [];
		$entry = null;

		foreach ( $lines as $line_number => $line ) {
			// Check for WC log format: 2025-07-15T06:27:00+00:00 INFO [Category] Message
			if ( preg_match( '/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2})\s+(\w+)(?:\s+\[([^\]]+)\])?\s*:?\s*(.*)$/', $line, $matches ) ) {
				// Save previous entry
				if ( $entry ) {
					$entries[] = $entry;
				}

				$timestamp = $matches[1];
				$level = strtolower( $matches[2] );
				$category = $matches[3] ?? '';
				$message = trim( $matches[4] );

				// Handle special case: no category but message starts with [category]
				if ( empty( $category ) && preg_match( '/^\[([^\]]+)\]\s*(.*)$/', $message, $cat_matches ) ) {
					$category = $cat_matches[1];
					$message = trim( $cat_matches[2] );
				}

				$entry = [
					'timestamp'        => $this->parse_iso_timestamp( $timestamp ),
					'iso_timestamp'    => $timestamp,
					'type'             => ! empty( $category ) ? $category : ucfirst( $level ),
					'level'            => $level,
					'category'         => $category,
					'message'          => $message,
					'file_path'        => null,
					'line'             => null,
					'stack_trace'      => '',
					'has_stack_trace'  => false,
					'line_number'      => $line_number + 1,
					'raw_line'         => $line,
					'is_multiline'     => false,
				];
			} elseif ( $entry ) {
				// Continuation line - preserve multiline structure
				$trimmed_line = trim( $line );
				if ( ! empty( $trimmed_line ) ) {
					// Check for structured data continuation
					if ( $this->is_structured_continuation( $trimmed_line ) ) {
						$entry['message'] .= "\n" . $trimmed_line;
						$entry['is_multiline'] = true;
					} else {
						$entry['message'] .= ' ' . $trimmed_line;
					}
					$entry['raw_line'] .= "\n" . $line;
				}
			}
		}

		// Add last entry
		if ( $entry ) {
			$entries[] = $entry;
		}

		return array_reverse( $entries );
	}

	/**
	 * Parse ISO 8601 timestamp (override parent method).
	 *
	 * @param string $timestamp ISO 8601 timestamp.
	 * @return string
	 */
	protected function parse_timestamp( string $timestamp ): string {
		return $this->parse_iso_timestamp( $timestamp );
	}

	/**
	 * Parse ISO 8601 timestamp to standard format.
	 *
	 * @param string $timestamp ISO 8601 timestamp.
	 * @return string
	 */
	private function parse_iso_timestamp( string $timestamp ): string {
		try {
			$date = new DateTime( $timestamp );
			return $date->format( 'Y-m-d H:i:s' );
		} catch ( Exception $e ) {
			return $timestamp;
		}
	}

	/**
	 * Check if line is structured data continuation.
	 *
	 * @param string $line Line to check.
	 * @return bool
	 */
	private function is_structured_continuation( string $line ): bool {
		return str_starts_with( $line, '(' ) ||
			   str_starts_with( $line, ')' ) ||
			   str_starts_with( $line, '[' ) ||
			   str_starts_with( $line, ']' ) ||
			   str_contains( $line, '=>' ) ||
			   str_starts_with( $line, '{' ) ||
			   str_starts_with( $line, '}' );
	}

	/**
	 * Override filter entries to add category filtering.
	 *
	 * @param array $entries Log entries.
	 * @param array $options Filter options.
	 * @return array
	 */
	protected function filter_entries( array $entries, array $options ): array {
		// Use parent filtering first
		$filtered = parent::filter_entries( $entries, $options );

		// Add category filtering
		if ( ! empty( $options['category'] ) ) {
			$filtered = array_filter(
				$filtered,
				fn( $entry ) => stripos( $entry['category'] ?? '', $options['category'] ) !== false
			);
		}

		return $filtered;
	}

	/**
	 * Extract unique categories from entries.
	 *
	 * @param array $entries Log entries.
	 * @return array
	 */
	public function get_categories( array $entries ): array {
		$categories = array_unique(
			array_filter(
				array_column( $entries, 'category' )
			)
		);
		sort( $categories );
		return $categories;
	}
}
