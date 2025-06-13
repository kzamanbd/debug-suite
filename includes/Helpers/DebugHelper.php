<?php
/**
 * Debug helper utilities.
 *
 * @since      1.0.0
 * @package    DebugSuite
 * @subpackage DebugSuite/Helpers
 * @author     Kamruzzaman <kzamanbn@gmail.com>
 */

namespace DebugSuite\Helpers;

/**
 * Debug Helper class providing debugging utilities.
 *
 * @since      1.0.0
 * @package    DebugSuite
 * @subpackage DebugSuite/Helpers
 * @author     Kamruzzaman <kzamanbn@gmail.com>
 */
class DebugHelper {

	/**
	 * Pretty print data for debugging.
	 *
	 * @since    1.0.0
	 * @param    mixed  $data     The data to print.
	 * @param    string $label    Optional label for the data.
	 * @param    bool   $die      Whether to die after printing. Default false.
	 */
	public static function dump( $data, $label = '', $die = false ) {
		if ( ! WP_DEBUG ) {
			return;
		}

		echo '<pre style="background: #f0f0f0; border: 1px solid #ccc; padding: 10px; margin: 10px; font-size: 12px;">';
		
		if ( ! empty( $label ) ) {
			echo '<strong>' . esc_html( $label ) . ':</strong><br>';
		}
		
		echo htmlspecialchars( print_r( $data, true ) );
		echo '</pre>';

		if ( $die ) {
			wp_die();
		}
	}

	/**
	 * Log data to the debug log file.
	 *
	 * @since    1.0.0
	 * @param    mixed  $data     The data to log.
	 * @param    string $label    Optional label for the data.
	 */
	public static function log( $data, $label = '' ) {
		if ( ! WP_DEBUG_LOG ) {
			return;
		}

		$message = '';
		
		if ( ! empty( $label ) ) {
			$message .= $label . ': ';
		}
		
		if ( is_array( $data ) || is_object( $data ) ) {
			$message .= print_r( $data, true );
		} else {
			$message .= $data;
		}

		error_log( '[DEBUG SUITE] ' . $message );
	}

	/**
	 * Get current memory usage.
	 *
	 * @since    1.0.0
	 * @param    bool $format Whether to format the result. Default true.
	 * @return   string|int Memory usage.
	 */
	public static function memory_usage( $format = true ) {
		$bytes = memory_get_usage( true );
		
		if ( ! $format ) {
			return $bytes;
		}

		$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );
		
		for ( $i = 0; $bytes > 1024; $i++ ) {
			$bytes /= 1024;
		}
		
		return round( $bytes, 2 ) . ' ' . $units[ $i ];
	}

	/**
	 * Get current memory peak usage.
	 *
	 * @since    1.0.0
	 * @param    bool $format Whether to format the result. Default true.
	 * @return   string|int Memory peak usage.
	 */
	public static function memory_peak_usage( $format = true ) {
		$bytes = memory_get_peak_usage( true );
		
		if ( ! $format ) {
			return $bytes;
		}

		$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );
		
		for ( $i = 0; $bytes > 1024; $i++ ) {
			$bytes /= 1024;
		}
		
		return round( $bytes, 2 ) . ' ' . $units[ $i ];
	}

	/**
	 * Get execution time since script start.
	 *
	 * @since    1.0.0
	 * @return   string Execution time in seconds.
	 */
	public static function execution_time() {
		return round( microtime( true ) - $_SERVER['REQUEST_TIME_FLOAT'], 4 ) . 's';
	}

	/**
	 * Get database query count if Query Monitor is available.
	 *
	 * @since    1.0.0
	 * @return   int|string Query count or 'N/A'.
	 */
	public static function query_count() {
		global $wpdb;
		
		if ( isset( $wpdb->num_queries ) ) {
			return $wpdb->num_queries;
		}
		
		return 'N/A';
	}
}
