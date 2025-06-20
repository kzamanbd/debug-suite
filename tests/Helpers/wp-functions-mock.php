<?php
/**
 * Mock WordPress functions for unit testing without WordPress core.
 *
 * @package DebugSuite\Tests\Helpers
 */

if ( ! function_exists( '__' ) ) {
	/**
	 * Mock translation function.
	 *
	 * @param string $text    Text to translate.
	 * @param string $domain  Text domain.
	 * @return string
	 */
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( '_x' ) ) {
	/**
	 * Mock contextual translation function.
	 *
	 * @param string $text    Text to translate.
	 * @param string $context Context information for translators.
	 * @param string $domain  Text domain.
	 * @return string
	 */
	function _x( $text, $context, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * Mock escaped translation function.
	 *
	 * @param string $text    Text to translate.
	 * @param string $domain  Text domain.
	 * @return string
	 */
	function esc_html__( $text, $domain = 'default' ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * Mock escaping function.
	 *
	 * @param string $text Text to escape.
	 * @return string
	 */
	function esc_html( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * Mock attribute escaping function.
	 *
	 * @param string $text Text to escape.
	 * @return string
	 */
	function esc_attr( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Mock sanitization function.
	 *
	 * @param string $str String to sanitize.
	 * @return string
	 */
	function sanitize_text_field( $str ) {
		return trim( strip_tags( (string) $str ) );
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	/**
	 * Mock post content sanitization function.
	 *
	 * @param string $content Content to sanitize.
	 * @return string
	 */
	function wp_kses_post( $content ) {
		return strip_tags( (string) $content, '<p><a><strong><em><span><br><h1><h2><h3><h4><h5><h6><ul><ol><li>' );
	}
}

if ( ! function_exists( 'current_time' ) ) {
	/**
	 * Mock current time function.
	 *
	 * @param string $type Type of time to return.
	 * @param int    $gmt  Whether to use GMT timezone.
	 * @return string|int
	 */
	function current_time( $type, $gmt = 0 ) {
		switch ( $type ) {
			case 'mysql':
				return gmdate( 'Y-m-d H:i:s' );
			case 'timestamp':
				return time();
			default:
				return time();
		}
	}
}

if ( ! function_exists( 'add_action' ) ) {
	/**
	 * Mock add_action function.
	 *
	 * @param string   $tag             Action hook name.
	 * @param callable $function_to_add Function to be called.
	 * @param int      $priority        Priority.
	 * @param int      $accepted_args   Number of arguments.
	 * @return true
	 */
	function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		return true;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * Mock add_filter function.
	 *
	 * @param string   $tag             Filter hook name.
	 * @param callable $function_to_add Function to be called.
	 * @param int      $priority        Priority.
	 * @param int      $accepted_args   Number of arguments.
	 * @return true
	 */
	function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		return true;
	}
}

if ( ! function_exists( 'plugin_basename' ) ) {
	/**
	 * Mock plugin_basename function.
	 *
	 * @param string $file Full path to the plugin file.
	 * @return string
	 */
	function plugin_basename( $file ) {
		return basename( dirname( $file ) ) . '/' . basename( $file );
	}
}

if ( ! function_exists( 'wp_die' ) ) {
	/**
	 * Mock wp_die function.
	 *
	 * @param string $message Error message.
	 * @param string $title   Error title.
	 * @param array  $args    Additional arguments.
	 * @return void
	 */
	function wp_die( $message = '', $title = '', $args = array() ) {
		throw new \Exception( $message );
	}
}

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * Mock get_option function.
	 *
	 * @param string $option  Name of the option to retrieve.
	 * @param mixed  $default Default value to return if option doesn't exist.
	 * @return mixed
	 */
	function get_option( $option, $default = false ) {
		$options = [
			'date_format' => 'Y-m-d H:i:s',
			'time_format' => 'H:i:s',
		];
		
		return $options[$option] ?? $default;
	}
}

if ( ! function_exists( 'wp_date' ) ) {
	/**
	 * Mock wp_date function.
	 *
	 * @param string       $format    PHP date format.
	 * @param int|float    $timestamp Unix timestamp.
	 * @param \DateTimeZone $timezone  Optional. Timezone to use.
	 * @return string|false
	 */
	function wp_date( $format, $timestamp = null, $timezone = null ) {
		return date( $format, $timestamp );
	}
}

if ( ! function_exists( 'date_i18n' ) ) {
	/**
	 * Mock date_i18n function.
	 *
	 * @param string   $format    Format to display the date.
	 * @param int|bool $timestamp Optional timestamp.
	 * @param bool     $gmt       Optional. Whether to use GMT timezone.
	 * @return string
	 */
	function date_i18n( $format, $timestamp = false, $gmt = false ) {
		if ( ! $timestamp ) {
			$timestamp = time();
		}
		return date( $format, $timestamp );
	}
}

if ( ! function_exists( 'wp_list_sort' ) ) {
	/**
	 * Mock wp_list_sort function.
	 *
	 * @param array  $list    List to sort.
	 * @param string $orderby Property to sort by.
	 * @param string $order   Optional. Order direction. ASC or DESC.
	 * @param string $type    Optional. Type of data.
	 * @param bool   $preserve_keys Optional. Whether to preserve keys.
	 * @return array Sorted array.
	 */
	function wp_list_sort( $list, $orderby, $order = 'ASC', $type = 'OBJECT', $preserve_keys = false ) {
		usort( $list, function( $a, $b ) use ( $orderby ) {
			if ( is_object( $a ) ) {
				return strcasecmp( $a->{$orderby}, $b->{$orderby} );
			} else {
				return strcasecmp( $a[$orderby], $b[$orderby] );
			}
		});
		return $list;
	}
}

if ( ! function_exists( 'debug_suite_date' ) ) {
	/**
	 * Mock debug_suite_date function.
	 *
	 * @param int $timestamp Unix timestamp.
	 * @return string
	 */
	function debug_suite_date( $timestamp ) {
		return date( 'Y-m-d H:i:s', $timestamp );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * Mock wp_json_encode function.
	 *
	 * @param mixed $data Data to encode.
	 * @param int   $options JSON encoding options.
	 * @param int   $depth Maximum depth.
	 * @return string
	 */
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}
