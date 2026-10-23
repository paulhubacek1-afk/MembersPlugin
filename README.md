# Members Plugin – Profiles & Community

Members Plugin is a self-managed WordPress membership and community system with profile tools, theme-aware design controls, role-based avatar rings, automatic update checks, and email diagnostics.

## New in 1.0.4

- **Profile-only online status:** green online and grey offline indicators appear in profile headers. Member-directory lists no longer show status dots.
- **Single role-colored avatar ring:** a selected role ring replaces the default ring rather than drawing a second ring.
- **Birthday celebration:** on a profile owner's birthday, the profile shows a cake, birthday message and animated confetti. It uses the built-in `birth_date` form field and requires no shortcode.
- **Automatic update checks:** a WordPress scheduled task checks GitHub Releases every six hours and refreshes the available-update information. It does not install updates without authorization.
- **General hardening:** improved enqueue timing for birthday styles and documented automatic update behavior.

## Existing features

- **Mail recipient rule:** outgoing messages are not sent to recipients with the exact domain @buehne-total.eu. If a message has other recipients, those recipients can still receive it.
- **Mail diagnostics:** WordPress mail/SMTP failures and suppressed-recipient events are shown in **Members Plugin → E-Mail-Protokoll**, without saving message bodies.
- **Per-role avatar rings:** enable a profile-picture ring for any WordPress role and select its color individually in the dashboard.
- **ID-based profile photos:** avatar output checks the actual WordPress user-ID folder first.
- **HTML-enabled text fields:** safe h1–h6 heading tags are preserved without allowing arbitrary heading attributes.

## Installation

1. Upload the plugin folder as `wp-content/plugins/members-plugin/`, or install the release ZIP from **Plugins → Add New → Upload Plugin**.
2. If a separately installed legacy membership core is still active, deactivate it first. Both membership cores must not run at the same time while their old internal symbols overlap.
3. Activate **Members Plugin – Profiles & Community**.
4. Configure role ring colors and mail diagnostics from the **Members Plugin** menu in WordPress.

## Online and offline status

Green means online; grey means offline. Indicators appear beside names in profile headers only. Member-directory cards intentionally do not show online/offline status. A member is online for five minutes after their last activity. When a signed-in member has an active visible browser tab, a background request refreshes their activity and profile status indicators refresh about once per minute.

The optional compatibility shortcodes [users_online] and [mp_online_users] remain available for sites already using them, but they are not required for the automatic profile status dots.

## Birthday cake and confetti

The built-in membership form field `birth_date` is checked using the WordPress site's configured timezone. On the profile owner's birthday, Members Plugin shows a cake, birthday greeting and CSS confetti. It supports the common date formats stored by the built-in date field, including `Y/m/d` and `Y-m-d`. The celebration is automatically hidden on other days and while editing a profile.

## Profile-picture ring colors

Open **WordPress Dashboard → Members Plugin → Profilbild-Ringfarben**. Enable the ring for each role you want to style, then choose a separate color. Roles without an enabled ring keep the default avatar appearance. If a user has multiple roles, the first assigned role with an enabled ring color is used.

A selected role color replaces the default ring on that avatar. The styles avoid putting a second ring on the profile-photo wrapper.

## Email handling and SMTP diagnostics

Outgoing email recipients at the exact domain @buehne-total.eu are intentionally removed from the recipient list. If all recipients match that domain, no message is sent. If a message has additional recipients, delivery continues to those remaining recipients.

When WordPress reports a mail failure, Members Plugin records the failure code and error message in **Members Plugin → E-Mail-Protokoll**. The log does not store the message body. This confirms errors reported by WordPress/PHPMailer; it cannot guarantee delivery to the recipient's inbox.

## Automatic updates

Members Plugin schedules a background check every six hours using WordPress Cron. When a new GitHub Release exists, its version is returned to WordPress so it can appear in **Dashboard → Aktualisierungen**. The plugin checks for updates automatically, but does not silently install them. WordPress Cron runs when the site receives traffic, so a quiet site may run the check later than scheduled.

## Theme colors and developer tools

Theme colors, backgrounds, typography and button shapes are detected from global styles, theme palettes, builder settings and browser-computed styles. Values can be overridden in **Members Plugin → Design & CSS**.

Other tools include a custom CSS editor, searchable CSS class reference, front-end CSS inspector, security hardening, and dashboard updates through GitHub Releases.

Relevant filters include `mp_css_variables`, `mp_design_variables`, `mp_detected_theme_colors`, `mp_trusted_proxies`, `mp_send_security_headers`, and `mp_online_status_window`.

## Updates from GitHub

Updates are delivered through GitHub Releases. The public repository does not require a token to download public releases. If the repository becomes private, configure a narrowly scoped read-only token using `MP_GITHUB_TOKEN` in `wp-config.php`.

Changing the plugin header version and merging it to `main` triggers the release workflow, which creates the matching version tag and `members-plugin.zip`.

## Credits and license

Members Plugin is GPLv3-licensed. The bundled membership engine retains the required upstream license and attribution.

License: GPLv3. See `license.txt` and `SECURITY.md`.
