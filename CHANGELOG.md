# Members Plugin – Changelog

## 1.0.1 – 2026-10-23

- Fixed: avatar output now resolves the WordPress user ID and checks that user's own `uploads/ultimatemember/{ID}/profile_photo.{ext}` file first.
- Fixed: a photo belonging to one user is not selected because another profile was previously loaded.
- Added: built-in online-member tracking with a five-minute online window and a member-only `[mp_online_users]` shortcode.
- Added: responsive, theme-aware styling for the online-member list.
- Fixed: HTML-enabled form text fields now preserve safe heading tags (`h1`–`h6`) instead of stripping them and rendering the heading text as ordinary text.
- Improved: plugin metadata and installation/update documentation.

Note: this is a replacement build with its own bundled core. The existing upstream core has not yet been fully renamed internally, so it must not be activated alongside the original Ultimate Member plugin; the activation guard remains in place deliberately.
