# Sync OPML to Blogroll
Keep your WordPress blogroll in sync with your feed reader.

## Instructions
Install, activate, and head over to *Settings > Sync OPML to Blogroll* to tell WordPress about your OPML endpoint of choice. (E.g., Miniflux's endpoint usually looks like this: https://your.miniflux.site/v1/export. Other feed readers typically expose OPML documents in a similar manner.)

For a feed to be picked up, it requires both a valid site URL and a valid feed link, though most if not all feed readers will take care of that for you.

Syncs **once daily**. Importing categories is optional and in a somewhat experimental phase.

## Basic Authentication
Supports basic authentication as used by, e.g., Miniflux. Username and password fields may be left blank if not applicable. **Note:** unless you manually add a `SYNC_OPML_BLOGROLL_PASS` constant to `wp-config.php` (see below), your password is saved to WordPress's database in plaintext format. (That's basic authentication for you, unfortunately.)

Defining the password in `wp-config.php` rather than storing it in the database is done by adding the following line to `wp-config.php`, just before `/* That's all, stop editing! Happy publishing. */`:
```
define( 'SYNC_OPML_BLOGROLL_PASS', 'your-password-here' );
```
If you've previously filled out and saved the password field, and only recently added above constant, simply visit *Settings > Sync OPML to Blogroll* and hit Save Changes to wipe your password from the database.

## Remarks
### Link Manager
This plugin explicitly enables the WordPress Link Manager that's disabled by default since version 3.5. No need for other Link Manager plugins that do the same.

### Categories
Importing categories is optional and disabled by default. OPML 2.0 and hierarchical categories are currently **not** supported. If you find categories are wrongly assigned, you may want to simply disable this option (and, if necessary, define and set categories manually).

### Sync Rate
This plugin'll attempt to fetch and process your OPML once a day. It will also 'force sync' immediately after it is first configured or settings are changed. (Not merely saved, but *changed*.) This might take a while for really large feeds.

If you really need full control of cron actions, you'll probably want to look into something like [WP-Crontrol](https://wordpress.org/plugins/wp-crontrol/). (Note: not for novices!)

### On "Syncing"
While feeds that are deleted from your reader and thus OPML endpoint will also be deleted from WordPress, existing WordPress bookmarks *without a feed link* are left alone.

Names, categories and even site URLs can be edited after feeds are imported, and changes will not be overwritten by future sync actions. (That also means that name changes on your feed reader's end will not affect WordPress links. This plugin, in fact, only looks at feed URLs to determine what to do.)

"Syncing," by the way, only works in one direction: *from* your OPML endpoint *to* your WordPress blogroll.
