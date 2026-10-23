## 1.0.9.3 – 2026-10-23

- Fixed: the Default Profile Photo / Default Cover Photo buttons can open the WordPress Media Library again. Root cause: renaming the admin menu to "Members Plugin" changed WordPress' internal page names (`members-plugin_page_um_options`), so the settings scripts and the media library were never loaded on the settings pages.
- Faster: member directory: cover photos and profile photos now use the file's modification time as URL version instead of the current time. Browsers can finally cache them, so returning to the directory or paging no longer re-downloads every image.
- Faster: member directory: profile photos in lists load the matching small thumbnail (the original is offered via srcset for high-density screens) instead of the full-size original.
- Faster: member directory: the per-member profile cache options are loaded with one query per page (WordPress 6.4+), the field list and allowed-HTML lists are built once per request, and profile forms are only queried when tagline/reveal fields are shown.
- Faster: member directory script: pages that were already loaded are shown instantly from a 60-second client cache, slow older responses can no longer overwrite newer results, and the grid no longer re-initialises once per previously opened page on every window resize.
- Faster: theme color detection is cached (flushed on theme/style changes), and the birthday stylesheet is loaded only on a member's birthday.
- Faster: online status: no extra AJAX heartbeat on every page load; presence polling loads all users with two queries.
- Fixed: dependent dropdown fields (child options) in profile/registration forms threw a JavaScript error because the AJAX response no longer contained the field's `default`/`editable` values.
- Fixed: the updater could offer the bundled core's upstream tags (e.g. 2.14.1) as an update; only Members Plugin 1.x releases are considered now. The duplicate second updater was removed (it also caused a fatal error while a separate legacy core was active).
- Fixed: a missing profile-photo file now shows the default avatar instead of a broken image URL.
- Fixed: birthday: well-known birthday fields are checked first; members born on 29 February celebrate on 28 February in non-leap years.
- Security: backup import only restores the option and post types the export captures and never writes executable file types into uploads.
- Security: the public online-status endpoint only reports members whose profile the visitor may view.
- Improved: uninstall removes the updater cron event and cached data; Members Plugin options are deleted when "delete data on uninstall" is enabled.

## 1.0.9.2 – 2026-10-23

- Fixed: user-uploaded cover photos are saved at encoder quality 100, including the master image and generated thumbnails.
- Fixed: the saved cover image keeps the full resolution of the user's selected crop instead of being downscaled by the generic image width limit.
- Improved: member directories prime user and user-meta caches for the current result page, reducing repeated database lookups while rendering cards.
- Improved: profile-form IDs are queried once per request instead of once per member card.
- Improved: directory cover photos use native lazy loading and asynchronous decoding to reduce competition from off-screen images.
- Kept: configured cover thumbnail sizes and directory extension hooks remain supported.

## 1.0.9.1 – 2026-10-23

- Fixed: WordPress Media Library assets are now enqueued on every Appearance settings subsection, including Default Profile Photo and Default Cover Photo.
- Fixed: the media picker can open from both profile and cover image settings.

## 1.0.9 – 2026-10-23

- Fixed: register both `members-plugin` and legacy `ultimate-member` text domains on WordPress `init`, so translations follow the site's selected language without loading too early.
- Fixed: release ZIP includes the official German `ultimate-member-de_DE.mo` translation for the bundled membership core.
- Kept: legacy `.um` CSS classes and compatibility identifiers unchanged.

## 1.0.8.1 – 2026-10-23

- Fixed: register a WordPress-compatible update transient filter and plugin information response using the published GitHub release ZIP.
- Fixed: updater checks published `1.0.x` product releases only and ignores unrelated core-version tags.
- Improved: release workflow accepts four-part patch versions such as `1.0.8.1`.

## 1.0.8 – 2026-10-23

- Fixed: Sicherung page navigation and export/import redirects consistently open **Einstellungen → Sicherung**.
- Changed: plugin text domain to `members-plugin`.
- Improved: release workflow rebuilds and replaces an existing ZIP, checks the packaged version, tests ZIP integrity, and verifies the published asset.
- Note: legacy database table names and upload folder names remain recognized only for safe migration/backup compatibility; they are not used as visible product branding.

# Members Plugin – Changelog

## 1.0.7 – 2026-10-23

- Added: administrator-only Sicherung page with ZIP export and import.
- Added: same-site and database-prefix validation before import, plus ID-based upsert for captured WordPress users, user metadata, options, posts and post metadata.
- Added: restore of archived profile media into expected upload folders.
- Changed: visible backup page wording to Members Plugin terminology; legacy internal compatibility identifiers remain where required.

## 1.0.7 – 2026-10-23

- Added: standalone migration backup screen under **Members Plugin → Sicherung**, available even when the separately installed separate legacy membership plugin is detected.
- Added: MP-owned database index table `mp_backup_index` and private ZIP backups containing user data, user metadata, legacy-engine-related options/tables, UM form/directory records, and common profile upload folders.
- Changed: WordPress plugin overview version is now **1.0.7**.
- Safety: backups are stored outside the WordPress web root and can be downloaded only by an administrator through a nonce-protected action.
- Note: this is a backup foundation, not yet a complete migration to a fully independent membership engine.



## 1.0.6 – 2026-10-23

- Changed: the birthday effect is now confetti only, spread across the full profile viewport.
- Removed: birthday cake, greeting and all celebration text.

## 1.0.5 – 2026-10-23

- Fixed: role-colored avatar rings now appear on profile pages by applying the ring to the outer circular photo link, not the inner image whose shadow is clipped.
- Fixed: inner profile images no longer add a second shadow ring.

## 1.0.4 – 2026-10-23

- Changed: removed automatic online/offline dots from member-directory cards. Profile-header indicators remain.
- Fixed: role-colored avatar rings now override the default ring, with the photo wrapper's shadow reset to prevent stacked rings.
- Added: a birthday cake, greeting and animated CSS confetti on the profile owner's birthday using the built-in `birth_date` field.
- Added: automatic GitHub release checks through WordPress Cron every six hours. Updates are advertised to WordPress, not silently installed.
- Fixed: birthday CSS is enqueued before the page head is rendered.
- Improved: updater and product documentation.

## 1.0.3 – 2026-10-23

- Added: automatic green online and grey offline indicators beside names in member-directory cards and profile headers; no shortcode needed.
- Added: browser heartbeat for signed-in users and periodic presence refresh.
- Added: suppression of outgoing email recipients at the exact domain @buehne-total.eu; other recipients remain eligible to receive the message.
- Added: **Members Plugin → E-Mail-Protokoll** to show suppressed-recipient events and WordPress/PHPMailer mail failures without storing message bodies.
- Improved: documentation and product wording focused on Members Plugin.

## 1.0.2 – 2026-10-23

- Added: administrators can enable and choose a profile-picture ring color for each WordPress role under Members Plugin → Profilbild-Ringfarben.
- Added: the [users_online] shortcode alias alongside [mp_online_users].
- Improved: role ring colors for WordPress avatars, member profiles, directory cards, and the Users Online list.
- Improved: product wording and documentation to present the plugin as Members Plugin.

## 1.0.1 – 2026-10-23

- Fixed: avatar output now resolves the WordPress user ID and checks that user's own uploads/ultimatemember/{ID}/profile_photo.{ext} file first.
- Fixed: a photo belonging to one user is not selected because another profile was previously loaded.
- Added: built-in online-member tracking with a five-minute online window and a member-only [mp_online_users] shortcode.
- Added: responsive, theme-aware styling for the online-member list.
- Fixed: HTML-enabled form text fields now preserve safe heading tags (h1–h6) instead of stripping them and rendering the heading text as ordinary text.
- Improved: plugin metadata and installation/update documentation.

Note: the bundled membership engine retains a small compatibility guard for overlapping legacy internal symbols. Do not activate a second copy of that membership core alongside Members Plugin until the internal names have been fully separated and tested.
