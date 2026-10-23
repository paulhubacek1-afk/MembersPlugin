# Members Plugin – Profiles & Community

Members Plugin is a WordPress membership and profile system with automatic theme colors, profile customization tools, and built-in online-member status.

## New in 1.0.1

- **ID-based profile photos:** avatar output checks the actual WordPress user-ID folder first: wp-content/uploads/ultimatemember/{USER_ID}/profile_photo.{jpg|jpeg|png|webp|gif|avif}. A photo from a previously loaded profile should no longer be selected for another user.
- **Online members included:** online tracking is built into this plugin; no separate online-user extension is required.
- **Responsive online list:** a compact, theme-aware grid with avatars and an online indicator.
- **HTML-enabled text fields:** safe `h1`–`h6` heading tags are preserved during form sanitization, without permitting arbitrary heading attributes.
- **Improved plugin identity and documentation.**

## Installation

1. Upload the plugin folder as wp-content/plugins/members-plugin/, or install the release ZIP from **Plugins → Add New → Upload Plugin**.
2. If Ultimate Member is already installed and active, deactivate it first.
3. Activate **Members Plugin – Profiles & Community**.
4. Configure the design under **Members Plugin → Design & CSS**.

**Important architecture note:** Members Plugin bundles its own membership core and does not require a separate Ultimate Member installation. Some legacy internal class/function names are still shared with the upstream code, so the two plugins must not be active at the same time. The compatibility guard is intentional. A complete internal namespace migration needs a separate compatibility-tested release so existing profiles, roles and uploaded pictures are not accidentally lost.

## Online-member list

Add the shortcode [mp_online_users] to a page or a shortcode block.

Set the maximum number of people to show with [mp_online_users limit="20"].

The list is visible only to logged-in visitors. A user is treated as online for five minutes after their last activity. Activity is saved at most once per minute to avoid unnecessary database writes.

The built-in image resolver checks the user folder by WordPress user ID. It does not guess the ID based on the currently viewed profile. If no valid image file is found, normal WordPress/avatar fallback behavior remains in place.

## Automatic theme colors

Theme colors, backgrounds, typography and button shape are detected from global styles/theme.json, theme palettes, builder settings and browser-computed styles. Variables can be overridden in **Members Plugin → Design & CSS**.

The existing .um classes and established profile data are retained for compatibility with sites migrating from the previous build.

## Design and developer tools

- Color and shape variables with live preview.
- Custom CSS editor.
- Searchable CSS class reference with copy-to-clipboard.
- Front-end CSS class inspector in the admin bar.
- Additional security checks and response headers.
- GitHub release updater in the WordPress dashboard.

Relevant filters include mp_css_variables, mp_design_variables, mp_detected_theme_colors, mp_trusted_proxies, mp_send_security_headers, and mp_online_status_window.

## Updates from GitHub

Updates are delivered through GitHub Releases. The repository is currently public, so no token should be required for public release downloads. If the repository becomes private, set a narrowly-scoped read-only token using MP_GITHUB_TOKEN in wp-config.php.

To publish a release, update the plugin header version, create a matching Git tag such as v1.0.1, then publish the release. The GitHub Actions workflow checks the tag against the plugin header and creates members-plugin.zip.

## Credits and license

This is a GPLv3-licensed derivative project that retains attribution and licensing for the bundled upstream membership core. The Members Plugin-specific tools and additions are maintained in includes/members-plugin/.

License: GPLv3. See license.txt and SECURITY.md.
