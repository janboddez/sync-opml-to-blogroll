=== Sync OPML to Blogroll ===
Contributors: janboddez
Tags: opml, blogroll, rss, feeds, links, link manager
Tested up to: 6.1
Stable tag: 1.6.0
License: GNU General Public License v3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Keep your WordPress blogroll in sync with your feed reader.

== Description ==
Keep your WordPress blogroll in sync with your feed reader.

Syncs **once daily**. RSS feeds are stored as Links, editable through WordPress's Link Manager. (**Note:** This plugin also restores the WordPress Link Manager, which since version 3.5 of WordPress is hidden by default.)

Supports basic authentication as used by, e.g., Miniflux, and offers experimental support for categories.

For a feed to be picked up, it requires both a valid site URL and a valid feed link, though most if not all feed readers will take care of that for you.

To display the resulting "blogroll" on your site's front end, a `[bookmarks]` shortcode is introduced.

More details can be found on [this plugin's GitHub page](https://github.com/janboddez/sync-opml-to-blogroll).

= Credits =
Icon, Copyright 2006 OPML Icon Project
Licensed under a Creative Commons [Attribution-ShareAlike 3.0 Unported License](https://creativecommons.org/licenses/by-sa/3.0/).
Source: [http://www.opmlicons.com](http://www.opmlicons.com/)

== Installation ==
Within WP Admin, visit *Plugins > Add New* and search for "sync OPML" to locate the plugin. (Alternatively, upload this plugin's ZIP file via the "Upload Plugin" button.)

After activation, head over to *Settings > Sync OPML to Blogroll* to tell WordPress about your OPML endpoint of choice.

More detailed instructions can be found on [this plugin's GitHub page](https://github.com/janboddez/sync-opml-blogroll).

== Changelog ==
= 1.6.0 =
Introduce OPML v2 support.

= 1.5.0 =
Added bookmarks shortcode.

= 1.4.0 =
Default category option.

= 1.3.0 =
Setting naming.

= 1.2.0 =
Added support for microformats (HTML) feeds.

= 1.1.0 =
Fixed sync, added ability to denylist feeds.

= 1.0.1 =
Updated readme.txt.

= 1.0.0 =
Initial release.
