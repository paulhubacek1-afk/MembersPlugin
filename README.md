# Members Plugin – Profiles & Community

Members Plugin is a self-managed WordPress membership and community system with profile tools, theme-aware design controls, role-based avatar rings, and automatic online/offline status markers.

## New in 1.0.3

- **Automatic presence indicators:** a green dot means online and a grey dot means offline. Dots appear next to names in member directories and profile headers; no shortcode is required.
- **Live status updates:** active signed-in browser tabs refresh their activity heartbeat, and visible dots refresh automatically.
- **Mail recipient rule:** outgoing messages are not sent to recipients with the exact domain @buehne-total.eu. If a message has other recipients, those recipients can still receive it.
- **Mail diagnostics:** WordPress mail/SMTP failures and suppressed recipient events are shown in **Members Plugin → E-Mail-Protokoll**, without saving message bodies.
- **Per-role avatar rings:** enable a profile-picture ring for any WordPress role and select its color individually in the dashboard.

## Installation

1. Upload the plugin folder as wp-content/plugins/members-plugin/, or install the release ZIP from **Plugins → Add New → Upload Plugin**.
2. If a separately installed legacy membership core is still active, deactivate it first. Both membership cores must not run at the same time while their old internal symbols overlap.
3. Activate **Members Plugin – Profiles & Community**.
4. Configure role ring colors and mail diagnostics from the **Members Plugin** menu in WordPress.

## Online and offline status

Green means online; grey means offline. Status indicators appear automatically after names in member directories and next to names on profile headers, without requiring a shortcode. A member is online for five minutes after their last activity. When a signed-in member has an active visible browser tab, a background request refreshes their activity and status indicators refresh about once per minute.

The optional compatibility shortcodes [users_online] and [mp_online_users] remain available for sites already using them, but they are not required for the automatic status dots.

## Profile-picture ring colors

Open **WordPress Dashboard → Members Plugin → Profilbild-Ringfarben**. Enable the ring for each role you want to style, then choose a separate color. Roles without an enabled ring keep the default avatar appearance. If a user has multiple roles, the first assigned role with an enabled ring color is used.

Colors apply to WordPress avatar output, Members Plugin profile pictures and member-directory cards, and the optional online-member shortcode.

## Email handling and SMTP diagnostics

Outgoing email recipients at the exact domain @buehne-total.eu are intentionally removed from the recipient list. If all recipients match that domain, no message is sent. If a message has additional recipients, delivery continues to those remaining recipients.

When WordPress reports a mail failure, Members Plugin records the failure code and error message in **Members Plugin → E-Mail-Protokoll**. The log does not store the message body. This confirms errors reported by WordPress/PHPMailer; it cannot guarantee delivery to the recipient's inbox.

## Profile photos

The built-in image resolver checks the user folder by WordPress ID, rather than guessing which profile is currently open. It checks uploads/ultimatemember/{USER_ID}/profile_photo.{ext} first. If no valid file is found, the normal avatar fallback remains in place.

## Theme colors and developer tools

Theme colors, backgrounds, typography and button shapes are detected from global styles, theme palettes, builder settings and browser-computed styles. Values can be overridden in **Members Plugin → Design & CSS**.

Other tools include a custom CSS editor, searchable CSS class reference, front-end CSS inspector, security hardening, and dashboard updates through GitHub Releases.

Relevant filters include mp_css_variables, mp_design_variables, mp_detected_theme_colors, mp_trusted_proxies, mp_send_security_headers, and mp_online_status_window.

## Updates from GitHub

Updates are delivered through GitHub Releases. The public repository does not require a token to download public releases. If the repository becomes private, configure a narrowly scoped read-only token using MP_GITHUB_TOKEN in wp-config.php.

Changing the plugin header version and merging it to main triggers the release workflow, which creates the matching version tag and members-plugin.zip.

## Credits and license

Members Plugin is GPLv3-licensed. The bundled membership engine retains the required upstream license and attribution.

License: GPLv3. See license.txt and SECURITY.md.
