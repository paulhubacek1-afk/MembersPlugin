# Members Plugin – Changelog

## 2.14.1 – 2026-10-23

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
