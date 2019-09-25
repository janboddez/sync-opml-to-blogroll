<?php
/**
 * Handles WP Admin settings pages and the like.
 *
 * @package Sync_OPML_Blogroll
 */

namespace Sync_OPML_Blogroll;

/**
 * Options handler class.
 */
class Options_Handler {
	/**
	 * Plugin options.
	 *
	 * @var   array $options
	 */
	private $options = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->options = get_option(
			'sync_opml_blogroll_settings',
			array(
				'url'      => '',
				'username' => '',
				'password' => '',
			)
		);

		add_action( 'admin_menu', array( $this, 'create_menu' ) );
	}

	/**
	 * Registers the plugin settings page.
	 */
	public function create_menu() {
		add_options_page(
			__( 'Sync OPML to Blogroll', 'sync-opml-blogroll' ),
			__( 'Sync OPML to Blogroll', 'sync-opml-blogroll' ),
			'manage_options',
			'sync-opml-blogroll',
			array( $this, 'settings_page' )
		);
		add_action( 'admin_init', array( $this, 'add_settings' ) );
	}

	/**
	 * Registers the actual options.
	 */
	public function add_settings() {
		register_setting(
			'sync-opml-blogroll-settings-group',
			'sync_opml_blogroll_settings',
			array( 'sanitize_callback' => array( $this, 'sanitize_settings' ) )
		);
	}

	/**
	 * Handles submitted options.
	 *
	 * @param  array $settings Settings as submitted through WP Admin.
	 * @return array           Options to be stored.
	 */
	public function sanitize_settings( $settings ) {
		if ( isset( $settings['url'] ) && wp_http_validate_url( $settings['url'] ) ) {
			$this->options['url'] = esc_url_raw( $settings['url'] );
		}

		if ( isset( $settings['username'] ) ) {
			$this->options['username'] = $settings['username'];
		}

		if ( ! defined( 'SYNC_OPML_BLOGROLL_PASS' ) ) {
			if ( isset( $settings['password'] ) ) {
				$this->options['password'] = $settings['password'];
			}
		} else {
			$this->options['password'] = '';
		}

		// Updated settings.
		return $this->options;
	}

	/**
	 * Echoes the plugin options form. Handles the OAuth flow, too, for now.
	 */
	public function settings_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Sync OPML to Blogroll', 'sync-opml-blogroll' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				// Print nonces and such.
				settings_fields( 'sync-opml-blogroll-settings-group' );
				?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><label for="sync_opml_blogroll_settings[url]"><?php esc_html_e( 'OPML URL', 'sync-opml-blogroll' ); ?></label></th>
						<td><input type="text" id="sync_opml_blogroll_settings[url]" name="sync_opml_blogroll_settings[url]" style="min-width: 33%;" value="<?php echo esc_attr( $this->options['url'] ); ?>" />
						<p class="description"><?php esc_html_e( 'The URL to your feed reader&rsquo;s OPML endpoint.', 'sync-opml-blogroll' ); ?></p></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="sync_opml_blogroll_settings[username]"><?php esc_html_e( 'Username', 'sync-opml-blogroll' ); ?></label></th>
						<td><input type="text" id="sync_opml_blogroll_settings[username]" name="sync_opml_blogroll_settings[username]" style="min-width: 33%;" value="<?php echo esc_attr( $this->options['username'] ); ?>" />
						<p class="description"><?php esc_html_e( 'Your feed reader&rsquo;s username, should it require Basic Authentication. Leave blank if not applicable.', 'sync-opml-blogroll' ); ?></p></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="sync_opml_blogroll_settings[password]"><?php esc_html_e( 'Password', 'sync-opml-blogroll' ); ?></label></th>
						<td><input type="text" id="sync_opml_blogroll_settings[password]" name="sync_opml_blogroll_settings[password]" style="min-width: 33%;" value="<?php echo esc_attr( ( ! defined( 'SYNC_OPML_BLOGROLL_PASS' ) ? $this->options['password'] : '' ) ); ?>" <?php echo ( defined( 'SYNC_OPML_BLOGROLL_PASS' ) ? 'disabled="disabled" ' : '' ); ?>/>
						<p class="description"><?php esc_html_e( 'Your feed reader&rsquo;s password, should it require Basic Authentication. Leave blank if not applicable.', 'sync-opml-blogroll' ); ?></p></td>
					</tr>
				</table>
				<p class="submit"><?php submit_button( __( 'Save Changes' ), 'primary', 'submit', false ); ?></p>
			</form>
		</div>
		<?php
	}
}
