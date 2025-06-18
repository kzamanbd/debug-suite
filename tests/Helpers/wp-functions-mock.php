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
