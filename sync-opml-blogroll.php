<?php
/**
 * Plugin Name: Sync OPML to Blogroll
 * Plugin URI:  https://jan.boddez.net/wordpress/sync-opml-to-blogroll
 * Description: Keep your blogroll in sync with your feed reader.
 * Author:      Jan Boddez
 * Author URI:  https://jan.boddez.net/
 * License:     GNU General Public License v3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: sync-opml-blogroll
 * Version:     1.5.0
 *
 * @author  Jan Boddez <jan@janboddez.be>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 *
 * @package Sync_OPML_Blogroll
 */

namespace Sync_OPML_Blogroll;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

// Include required classes.
require_once dirname( __FILE__ ) . '/includes/class-opml-parser.php';
require_once dirname( __FILE__ ) . '/includes/class-options-handler.php';
require_once dirname( __FILE__ ) . '/includes/class-sync-opml-blogroll.php';

// Instantiate main plugin class.
$sync_opml_blogroll = Sync_OPML_Blogroll::get_instance();
$sync_opml_blogroll->register();
