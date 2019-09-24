# Sync OPML to Blogroll
Keep your WordPress blogroll in sync with your feed reader.

Head over to the settings page to tell the plugin about your OPML endpoint.

Supports basic authentication as used by, e.g., Miniflux. Username and password fields may be left blank if not applicable. (Note: your username and password are saved to WordPress's database in plaintext format.)

For a feed to be picked up, it requires both a valid site URL and a valid feed link, though most, if not all, feed readers will handle all of this for you.

Syncs once per hour.

While feeds that are deleted from your reader and thus OPML endpoint will be deleted from WordPress, too, regular WordPress bookmarks, i.e., those without a feed link, are left alone.

Names and even site URLs can be edited after feeds are imported, and changes will not be overwritten. This also means that name changes on your feed reader's end will not affect WordPress links. The plugin, in fact, only looks at feed URLs to determine what to do.
