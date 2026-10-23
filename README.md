# Members Plugin – Profiles & Community

Members Plugin is a self-managed WordPress membership and community system with profile tools, theme-aware design controls, role-based avatar rings, and a built-in Users Online list.

## New in 1.0.2

- **Ring color per role:** enable a profile-picture ring for any WordPress role and select its color individually in the dashboard.
- **Users Online:** [users_online] and [mp_online_users] display a responsive list of people active within the last five minutes.
- **ID-based profile photos:** avatar output checks the actual WordPress user-ID folder first: uploads/ultimatemember/{USER_ID}/profile_photo.{jpg|jpeg|png|webp|gif|avif}.
- **HTML-enabled text fields:** safe h1–h6 heading tags are preserved without allowing arbitrary heading attributes.

## Installation

1. Upload the plugin folder as wp-content/plugins/members-plugin/, or install the release ZIP from Plugins → Add New → Upload Plugin.
2. If the separately installed legacy membership plugin is still active, deactivate it first. Both membership cores must not run at the same time while their old internal symbols overlap.
3. Activate Members Plugin – Profiles & Community.
4. Configure design options and role ring colors from the Members Plugin menu in WordPress.

## Users Online

Add [users_online] or [mp_online_users] to a page or shortcode block. To show at most 20 people, use [users_online limit="20"].

Only logged-in visitors can view the list. A member is considered online for five minutes after the last recorded activity. Activity is stored at most once per minute to avoid unnecessary database writes.

## Profile-picture ring colors

Open WordPress Dashboard → Members Plugin → Profilbild-Ringfarben. Enable the ring for each role you want to style, then choose a separate color. Roles without an enabled ring keep the default avatar appearance. If a user has multiple roles, the first role assigned to that account with an enabled ring color is used.

Colors apply to WordPress avatar output, Members Plugin profiles and member-directory cards, and the Users Online list.

## Profile photos

The built-in image resolver checks the user folder by WordPress ID, rather than guessing which profile is currently open. It checks uploads/ultimatemember/{USER_ID}/profile_photo.{ext} first. If no valid file is found, the normal avatar fallback remains in place.

## Theme colors and developer tools

Theme colors, backgrounds, typography and button shapes are detected from global styles, theme palettes, builder settings and browser-computed styles. Values can be overridden in Members Plugin → Design & CSS.

Other tools include a custom CSS editor, searchable CSS class reference, front-end CSS inspector, security hardening, and dashboard updates through GitHub Releases.

Relevant filters include mp_css_variables, mp_design_variables, mp_detected_theme_colors, mp_trusted_proxies, mp_send_security_headers, and mp_online_status_window.

## Updates from GitHub

Updates are delivered through GitHub Releases. The public repository does not require a token to download public releases. If the repository becomes private, configure a narrowly scoped read-only token using MP_GITHUB_TOKEN in wp-config.php.

Changing the plugin header version and merging it to main triggers the release workflow, which creates the matching version tag and members-plugin.zip.

## Credits and license

Members Plugin is GPLv3-licensed. The bundled membership engine retains the required upstream license and attribution.

License: GPLv3. See license.txt and SECURITY.md.
