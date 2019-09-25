# Sync OPML to Blogroll
Keep your WordPress blogroll in sync with your feed reader.

## Instructions
Install, activate, and head over to *Settings > Sync OPML to Blogroll* to tell WordPress about your OPML endpoint of choice.

Supports basic authentication as used by, e.g., Miniflux. Username and password fields may be left blank if not applicable. (Note: currently, your username and password are saved to WordPress's database in plaintext format.)

For a feed to be picked up, it requires both a valid site URL and a valid feed link, though most if not all feed readers will take care of that for you.

Syncs once per hour. Importing categories is not supported (yet).

## Remarks
While feeds that are deleted from your reader and thus OPML endpoint will be deleted from WordPress, too, regular WordPress bookmarks, i.e., those without a feed link, are left alone.

Names, categories and even site URLs can be edited after feeds are imported, and changes will not be overwritten by future sync actions. (That also means that name changes on your feed reader's end will not affect WordPress links. This plugin, in fact, only looks at feed URLs to determine what to do.)
