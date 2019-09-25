<?php
/**
 * Plugin Name: Sync OPML to Blogroll
 * Description: Keep your blogroll in sync with your feed reader.
 * Author: Jan Boddez
 * Author URI: https://janboddez.tech/
 * License: GNU General Public License v3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: sync-opml-blogroll
 * Version: 0.1
 *
 * @author  Jan Boddez <jan@janboddez.be>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 *
 * @package Sync_OPML_Blogroll
 */

namespace Sync_OPML_Blogroll;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

require_once dirname( __FILE__ ) . '/includes/class-options-handler.php';
require_once dirname( __FILE__ ) . '/includes/class-opml-parser.php';

/**
 * Main plugin class.
 */
class Sync_OPML_Blogroll {
	/**
	 * Register hooks and settings.
	 */
	public function __construct() {
		// Schedule a recurring cron job.
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		// Enable WordPress's link manager.
		add_filter( 'pre_option_link_manager_enabled', '__return_true' );

		// Register settings page.
		new Options_Handler();

		// Inform our cron job about its callback function.
		add_action( 'sync_opml_blogroll', array( $this, 'sync' ) );
	}

	/**
	 * Runs on activation.
	 */
	public function activate() {
		// Schedule a daily cron job, starting 15 minutes from plugin activation.
		if ( false === wp_next_scheduled( 'sync_opml_blogroll' ) ) {
			wp_schedule_event( time() + 900, 'daily', 'sync_opml_blogroll' );
		}
	}

	/**
	 * Runs on deactivation.
	 */
	public function deactivate() {
		wp_clear_scheduled_hook( 'sync_opml_blogroll' );
	}

	/**
	 * Syncs bookmarks to an online OPML feed list.
	 */
	public function sync() {
		// Fetch settings.
		$options = get_option(
			'sync_opml_blogroll_settings',
			array(
				'url'      => '',
				'username' => '',
				'password' => '',
			)
		);

		if ( empty( $options['url'] ) ) {
			// Nothing to do.
			error_log( 'No OPML URL given.' );
			return;
		}

		$args = array();

		if ( defined( 'SYNC_OPML_BLOGROLL_PASS' ) ) {
			$options['password'] = SYNC_OPML_BLOGROLL_PASS;
		}

		if ( '' !== $options['username'] && '' !== $options['password'] ) {
			error_log( 'Using Basic Authentication.' );

			$args = array(
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( (string) $options['username'] . ':' . (string) $options['password'] ),
				),
			);
		}

		// Grab the OPML file.
		$response = wp_remote_request( esc_url_raw( $options['url'] ), $args );

		if ( is_wp_error( $response ) ) {
			error_log( $response->get_error_message() );
			return;
		}

		if ( empty( $response['body'] ) ) {
			error_log( 'Something went wrong.' );
			return;
		}

		$parser = new OPML_Parser();
		$feeds  = $parser->parse( $response['body'] );

		if ( empty( $feeds ) || ! is_array( $feeds ) ) {
			error_log( 'No feeds found.' );
			return;
		}

		$bookmarks      = get_bookmarks();
		$bookmark_feeds = array();

		if ( ! function_exists( 'wp_insert_link' ) ) {
			require ABSPATH . 'wp-admin/includes/bookmark.php';
		}

		foreach ( $bookmarks as $bookmark ) {
			if ( ! empty( $bookmark->link_rss ) && ! in_array( $this->decode_ampersands( $bookmark->link_rss ), array_column( $feeds, 'feed' ), true ) ) {
				// Delete feeds not in the OPML file (but leave bookmarks sans
				// feed link alone).
				error_log( "Deleting bookmark '" . $bookmark->link_rss . "'" );
				wp_delete_link( $bookmark->link_id );
			} else {
				// Mark link present.
				$bookmark_feeds[] = $this->decode_ampersands( $bookmark->link_rss );
			}
		}

		foreach ( $feeds as $feed ) {
			if ( ! in_array( $feed['feed'], $bookmark_feeds, true ) && false !== filter_var( $feed['url'], FILTER_VALIDATE_URL ) && false !== filter_var( $feed['feed'], FILTER_VALIDATE_URL ) ) {
				// Add (valid) links not already present.
				error_log( "Adding bookmark '" . $feed['feed'] . "'" );
				wp_insert_link(
					array(
						'link_name'        => $feed['name'],
						'link_url'         => $feed['url'],
						// 'link_target'   => $feed['target'],
						'link_rss'         => $feed['feed'],
						'link_description' => sanitize_text_field( $feed['description'] ),
					)
				);
			} else {
				// Update?
			}
		}
	}

	/**
	 * Decode only ampersands.
	 *
	 * @param string $str Possibly encoded string.
	 */
	private function decode_ampersands( $str ) {
		return str_replace( '&amp;', '&', $str );
	}
}

// Instantiate main plugin class.
new Sync_OPML_Blogroll();
