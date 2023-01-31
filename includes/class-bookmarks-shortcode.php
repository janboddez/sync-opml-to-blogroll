<?php
/**
 * Essentially bundles Shea Bunge's wonderful Bookmarks Shortcode plugin,
 * released under the MIT license.
 *
 * @author    Shea Bunge
 * @copyright Copyright (c) 2011 - 2020, Shea Bunge
 * @link      https://github.com/sheabunge/bookmarks-shortcode
 * @license   https://opensource.org/licenses/MIT
 *
 * @package Sync_OPML_Blogroll
 */

namespace Sync_OPML_Blogroll;

/**
 * Introduces a `bookmarks` shortcode.
 */
class Bookmarks_Shortcode {
	/**
	 * Returns a formatted list of WordPress bookmarks.
	 *
	 * @param  array $atts Attributes to be passed to the `wp_list_bookmarks()` function.
	 * @return string      The formatted list of bookmarks
	 */
	public static function bookmarks_shortcode( $atts = array() ) {
		$atts         = wp_parse_args( $atts );
		$atts['echo'] = false;

		return wp_list_bookmarks( $atts );
	}

	/**
	 * Registers the shortcodes.
	 */
	public static function register_bookmarks_shortcode() {
		global $shortcode_tags;

		foreach ( array( 'bookmarks', 'blogroll', 'links' ) as $shortcode ) {
			if ( ! isset( $shortcode_tags[ $shortcode ] ) ) {
				add_shortcode( $shortcode, array( __CLASS__, 'bookmarks_shortcode' ) );
			}
		}
	}
}
