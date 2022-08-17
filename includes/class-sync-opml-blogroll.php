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
	 * This plugin's single instance.
	 *
	 * @var Sync_OPML_Blogroll $instance Plugin instance.
	 */
	private static $instance;

	/**
	 * This plugin's `Options_handler` instance.
	 *
	 * @var Options_Handler $options_handler `Options Handler` instance.
	 */
	private $options_handler;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Sync_OPML_Blogroll Single class instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * (Private) constructor.
	 */
	private function __construct() {
		// Register settings page.
		$this->options_handler = new Options_Handler();
	}

	/**
	 * Registers hooks and settings.
	 */
	public function register() {
		// Enable WordPress's link manager.
		add_filter( 'pre_option_link_manager_enabled', '__return_true' );

		// Schedule a recurring cron job.
		add_action( 'init', array( $this, 'schedule_event' ) );
		register_deactivation_hook( dirname( dirname( __FILE__ ) ) . '/sync-opml-blogroll.php', array( $this, 'deactivate' ) );

		// Inform our cron job about its callback function.
		add_action( 'sync_opml_blogroll', array( $this, 'sync' ) );

		// Sync after settings are either first added or updated.
		add_action( 'add_option_sync_opml_blogroll_settings', array( $this, 'trigger_sync' ) );
		add_action( 'update_option_sync_opml_blogroll_settings', array( $this, 'trigger_sync' ) );

		// Minor CSS tweaks.
		add_action( 'admin_head', array( $this, 'link_manager_css' ) );
	}

	/**
	 * Schedules cron job.
	 */
	public function schedule_event() {
		// Schedule a daily cron job, starting up to 15 minutes after this
		// plugin's first activated.
		if ( false === wp_next_scheduled( 'sync_opml_blogroll' ) ) {
			wp_schedule_event( time() + 900, 'daily', 'sync_opml_blogroll' );
		}
	}

	/**
	 * Schedules a single sync job.
	 */
	public function trigger_sync() {
		wp_schedule_single_event( time() + 60, 'sync_opml_blogroll' );
	}

	/**
	 * Runs on deactivation.
	 */
	public function deactivate() {
		wp_clear_scheduled_hook( 'sync_opml_blogroll' );
	}

	/**
	 * Syncs bookmarks to an online OPML feed list.
	 *
	 * @param array $options Custom options array.
	 */
	public function sync( $options = array() ) {
		if ( empty( $options ) || ! is_array( $options ) ) {
			// Fetch saved settings.
			$options = get_option(
				'sync_opml_blogroll_settings',
				// Fallback settings if none exist, yet.
				array(
					'url'                => '',
					'username'           => '',
					'password'           => '',
					'denylist'           => '',
					'categories_enabled' => false,
				)
			);
		}

		if ( empty( $options['url'] ) ) {
			// Nothing to do.
			return;
		}

		$args = array();

		if ( defined( 'SYNC_OPML_BLOGROLL_PASS' ) ) {
			// Use the `SYNC_OPML_BLOGROLL_PASS` constant instead.
			$options['password'] = SYNC_OPML_BLOGROLL_PASS;
		}

		if ( isset( $options['username'] ) && '' !== $options['username'] && isset( $options['password'] ) && '' !== $options['password'] ) {
			// We've been given some authentication details, so let's use them.
			$args = array(
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( (string) $options['username'] . ':' . (string) $options['password'] ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				),
			);
		}

		// Fetch the OPML file.
		$response = wp_remote_get( esc_url_raw( $options['url'] ), $args );

		if ( is_wp_error( $response ) ) {
			// Something went wrong.
			/* translators: %s: error message */
			error_log( sprintf( __( 'Fetching the OPML failed: %s', 'sync-opml-blogroll' ), $response->get_error_message() ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return;
		}

		if ( empty( $response['body'] ) ) {
			// The response body is somehow empty.
			error_log( __( 'No valid OPML found.', 'sync-opml-blogroll' ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return;
		}

		// List of feeds to import.
		$parser = new OPML_Parser();
		$feeds  = $parser->parse( $response['body'], (bool) $options['categories_enabled'] );
		$feeds  = apply_filters(
			'sync_opml_blogroll_feeds',
			(array) $feeds,
			(bool) $options['categories_enabled']
		);

		// `$feeds` should now represent a multidimensional array.
		if ( empty( $feeds ) || ! is_array( $feeds ) ) {
			error_log( __( 'No feeds found.', 'sync-opml-blogroll' ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return;
		}

		// List of just the feed URLs.
		$feed_list = array_map(
			array( $this, 'decode_ampersands' ),
			array_column( $feeds, 'feed' )
		);

		// WordPress's existing links.
		$bookmarks      = get_bookmarks();
		$bookmark_feeds = array();

		if ( ! function_exists( 'wp_insert_link' ) ) {
			require ABSPATH . 'wp-admin/includes/bookmark.php';
		}

		foreach ( $bookmarks as $bookmark ) {
			if ( ! empty( $bookmark->link_rss ) && ! in_array( $this->decode_ampersands( $bookmark->link_rss ), $feed_list, true ) ) {
				// Delete feeds not in the OPML (but leave bookmarks sans feed
				// link alone).
				wp_delete_link( $bookmark->link_id );
			} else {
				// Mark link present.
				$bookmark_feeds[] = $this->decode_ampersands( $bookmark->link_rss );
			}
		}

		$denylist = array();

		if ( ! empty( $options['denylist'] ) ) {
			$denylist = explode( "\n", (string) $options['denylist'] );
			$denylist = array_map( 'trim', $denylist );
		} elseif ( ! empty( $options['blacklist'] ) ) {
			// Legacy setting.
			$denylist = explode( "\n", (string) $options['blacklist'] );
			$denylist = array_map( 'trim', $denylist );
		}

		foreach ( $feeds as $feed ) {
			if ( ! empty( $denylist ) ) {
				if ( str_replace( $denylist, '', $feed['feed'] ) !== $feed['feed'] ) {
					// Denylisted.
					/* translators: %s: ignored feed URL */
					error_log( sprintf( __( 'Skipping %s (denylisted).', 'sync-opml-blogroll' ), $feed['feed'] ) );  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					continue;
				}

				if ( str_replace( $denylist, '', $feed['url'] ) !== $feed['url'] ) {
					// Denylisted.
					/* translators: %s: ignored site URL */
					error_log( sprintf( __( 'Skipping %s (denylisted).', 'sync-opml-blogroll' ), $feed['url'] ) );  // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					continue;
				}
			}

			if ( in_array( $feed['feed'], $bookmark_feeds, true ) ) {
				// Already exists.
				continue;
			}

			if ( false === filter_var( $feed['url'], FILTER_VALIDATE_URL ) ) {
				// Invalid site URL. We need at least a site URL to display in
				// our blogroll.
				continue;
			}

			if ( '' === $feed['feed'] && 'microformats' === $feed['type'] ) {
				// For HTML feeds, the feed URL is the site URL.
				$feed['feed'] = $feed['url'];
			}

			if ( false === filter_var( $feed['feed'], FILTER_VALIDATE_URL ) ) {
				// Invalid feed URL.
				continue;
			}

			$term_id = null;

			if ( '' !== $feed['category'] ) {
				// A link category was set. Let's try to find it.
				$name = sanitize_text_field( $feed['category'] );
				$slug = sanitize_title( $feed['category'] );
				$term = term_exists( $slug, 'link_category' );

				if ( ! isset( $term['term_id'] ) ) {
					// Create it.
					$term = wp_insert_term( $name, 'link_category', array( 'slug' => $slug ) );
				}

				if ( isset( $term['term_id'] ) ) {
					// Success!
					$term_id = $term['term_id'];
				}
			} elseif ( isset( $options['default_category'] ) ) {
				$term = term_exists( intval( $options['default_category'] ), 'link_category' );

				if ( isset( $term['term_id'] ) ) {
					// Default category (still) exists. That's good.
					$term_id = $term['term_id'];
				}
			}

			// Add OPML links not already present in WordPress.
			$args = array(
				'link_name'        => sanitize_text_field( $feed['name'] ),
				'link_rss'         => $feed['feed'], // Validated above.
				'link_url'         => $feed['url'], // Validated above.
				'link_description' => sanitize_textarea_field( $feed['description'] ),
				// Not sure if `target` is ever used. Skip for now.
				// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
				// 'link_target'   => $feed['target'],
			);

			if ( isset( $term_id ) ) {
				// If applicable, add the category ID.
				$args['link_category'] = $term_id;
			}

			wp_insert_link( $args );
		}
	}

	/**
	 * Cleans up the Link Manager a bit, visually.
	 */
	public function link_manager_css() {
		?>
		<style type="text/css">
		#post-body-content .stuffbox .inside {
			padding-left: 10px;
			padding-right: 10px;
		}
		</style>
		<?php
	}

	/**
	 * Tiny helper function to decode only ampersands. (WordPress returns
	 * encoded feed links, while OPML does not.)
	 *
	 * @param string $str Possibly encoded string.
	 *
	 * @return string Decoded string.
	 */
	private function decode_ampersands( $str ) {
		return str_replace( '&amp;', '&', $str );
	}
}
