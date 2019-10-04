<?php
/**
 * Main plugin class.
 *
 * @package Sync_OPML_Blogroll
 */

namespace Sync_OPML_Blogroll;

/**
 * Main plugin class.
 *
 * Handles plugin activation and deactivation, and the actual syncing.
 */
class Sync_OPML_Blogroll {
	/**
	 * Registers hooks and settings.
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

		// Sync after settings are either first added or updated.
		add_action( 'add_option_sync_opml_blogroll_settings', array( $this, 'sync' ) );
		add_action( 'update_option_sync_opml_blogroll_settings', array( $this, 'sync' ) );
	}

	/**
	 * Runs on activation.
	 */
	public function activate() {
		// Schedule a daily cron job, starting 15 minutes after this plugin's
		// first activated.
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
			// Fallback settings if none exist, yet.
			array(
				'url'      => '',
				'username' => '',
				'password' => '',
			)
		);

		if ( empty( $options['url'] ) ) {
			// Nothing to do.
			return;
		}

		$args = array();

		if ( defined( 'SYNC_OPML_BLOGROLL_PASS' ) ) {
			// Use the `SYNC_OPML_BLOGROLL_PASS` constant instead.
			$options['password'] = SYNC_OPML_BLOGROLL_PASS;
		}

		if ( '' !== $options['username'] && '' !== $options['password'] ) {
			// We've been given some authentication details, so let's use them.
			$args = array(
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( (string) $options['username'] . ':' . (string) $options['password'] ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				),
			);
		}

		// Fetch the OPML file.
		$response = wp_remote_request( esc_url_raw( $options['url'] ), $args );

		if ( is_wp_error( $response ) ) {
			// Something went wrong.
			error_log( __( 'Fetching the OPML failed: ', 'sync-opml-blogroll' ) . $response->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return;
		}

		if ( empty( $response['body'] ) ) {
			// The response body is somehow empty.
			error_log( 'Something went wrong trying to fetch the OPML.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return;
		}

		$parser = new OPML_Parser();
		$feeds  = $parser->parse( $response['body'] );

		// `$feeds` should contain a multidimensional array.
		if ( empty( $feeds ) || ! is_array( $feeds ) ) {
			error_log( 'No feeds found.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return;
		}

		// WordPress's links.
		$bookmarks      = get_bookmarks();
		$bookmark_feeds = array();

		if ( ! function_exists( 'wp_insert_link' ) ) {
			require ABSPATH . 'wp-admin/includes/bookmark.php';
		}

		foreach ( $bookmarks as $bookmark ) {
			if ( ! empty( $bookmark->link_rss ) && ! in_array( $this->decode_ampersands( $bookmark->link_rss ), array_column( $feeds, 'feed' ), true ) ) {
				// Delete feeds not in the OPML (but leave bookmarks sans feed
				// link alone).
				wp_delete_link( $bookmark->link_id );
			} else {
				// Mark link present.
				$bookmark_feeds[] = $this->decode_ampersands( $bookmark->link_rss );
			}
		}

		foreach ( $feeds as $feed ) {
			if ( ! in_array( $feed['feed'], $bookmark_feeds, true ) && false !== filter_var( $feed['url'], FILTER_VALIDATE_URL ) && false !== filter_var( $feed['feed'], FILTER_VALIDATE_URL ) ) {
				// Add (valid) OPML links not already present in WordPress.
				wp_insert_link(
					array(
						'link_name'        => sanitize_text_field( $feed['name'] ),
						'link_url'         => $feed['url'], // Validated above.
						// Not sure if `target` is ever used. Skip for now.
						// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
						// 'link_target'   => $feed['target'],
						'link_rss'         => $feed['feed'], // Validated above.
						'link_description' => sanitize_textarea_field( $feed['description'] ),
					)
				);
			}
		}
	}

	/**
	 * Tiny helper function to decode only ampersands. (WordPress returns
	 * encoded feed links, while the OPML does not.)
	 *
	 * @param  string $str Possibly encoded string.
	 * @return string      Decoded string.
	 */
	private function decode_ampersands( $str ) {
		return str_replace( '&amp;', '&', $str );
	}
}
