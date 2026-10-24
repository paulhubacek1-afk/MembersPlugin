# Members Plugin – Profiles & Community

## Updated in 1.1

- **Bühnen-Layout for member cards:** four columns, large round profile photo overlapping the card, bold names, statistics chips and hover effects. Fonts, sizes and colours are adjustable under **Design & CSS › Mitgliederkarten**.
- **Font Awesome 5** for icons instead of Font Awesome 6, matching the theme and add-ons.
- **Role ring** recolours the frame directly around the profile photo.

## Updated in 1.0.9.3

- **Faster member directories:** profile and cover photos are now browser-cacheable (stable, file-based URL versions instead of a new timestamp on every request), lists load small avatar thumbnails, per-member cache lookups are bundled into one query, and already loaded directory pages reappear instantly.
- **Fixes:** dependent dropdown fields in forms work again, the updater only offers Members Plugin 1.x releases, missing photo files fall back to the default avatar, and several smaller birthday/uninstall issues are resolved.
- **Security:** hardened backup import and the public online-status endpoint.

## Updated in 1.0.9.2

- Cover-photo uploads are saved at maximum supported encoder quality (100), and the selected crop keeps its full resolution.
- Member directories are optimized by priming current-page user and user-meta caches, querying profile-form IDs once per request, and lazy-loading off-screen cover photos.

## Updated in 1.0.9.1

- Fixed the WordPress Media Library picker for **Default Profile Photo** and **Default Cover Photo** under the Appearance settings sections. The media scripts are now loaded on the profile-photo and cover-photo subtabs too.

## Updated in 1.0.9

- Fixed localization bootstrap for both `members-plugin` and the bundled core's `ultimate-member` text domain. Translation loading occurs on WordPress `init`, avoiding early-loading warnings.
- Release ZIP includes the official German translation for the bundled core. The active language is controlled by **WordPress → Settings → General → Site Language**.
- Existing legacy CSS classes and extension-facing compatibility identifiers are unchanged.

## Updated in 1.0.8.1

- Fixed WordPress update discovery: the plugin now registers a standard update transient filter, fetches published GitHub releases, and supplies the ZIP to the WordPress updater.
- The updater considers only the Members Plugin `1.0.x` release line, so unrelated bundled-core tags such as `2.14.1` are ignored.


Members Plugin is evolving toward an independent WordPress membership system. It currently includes profile tools, theme-aware design controls, role-based avatar rings, automatic update checks, email diagnostics, and a standalone migration backup tool.

## Updated in 1.0.8

- Fixed the backup page tab link so it opens the correct **Einstellungen → Sicherung** page.
- Improved release ZIP creation: existing assets are replaced and the archive is validated against the plugin version before upload.

## Updated in 1.0.7

- **Migration backup:** open **Tools → MP Migration & Sicherung** to create a private ZIP snapshot before removing a separately installed legacy membership engine plugin. The tool is loaded before the legacy-core compatibility guard.
- **MP-owned database index:** backup records use the WordPress table prefix plus `mp_backup_index`.
- **Version display:** the WordPress plugin header reports Members Plugin version 1.0.9.
- **Important limitation:** this snapshot is a safety net, not a one-click restore or a completed conversion to a fully independent membership engine. Keep a separate full-site/database backup too.

## Updated in 1.0.6

- **Confetti only:** on the profile owner's birthday, colorful confetti falls across the full visible profile page. No cake, greeting or other text is shown.

## Fixed in 1.0.5

- **Profile-page role ring:** the selected role color is now applied to the outer circular profile-photo link, so the ring remains visible instead of being clipped by the image container.
- **No inner double ring:** the photo image's own shadow is suppressed while the role-colored ring remains on the outside.

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

## Birthday confetti

The built-in membership form field `birth_date` is checked using the WordPress site's configured timezone. On the profile owner's birthday, colorful CSS confetti falls across the full visible profile page. No cake, greeting or other text is shown. It supports common date formats stored by the built-in date field, including `Y/m/d` and `Y-m-d`. The effect is automatically hidden on other days and while editing a profile.

## Profile-picture ring colors

Open **WordPress Dashboard → Members Plugin → Profilbild-Ringfarben**. Enable the ring for each role you want to style, then choose a separate color. Roles without an enabled ring keep the default avatar appearance. If a user has multiple roles, the first assigned role with an enabled ring color is used.

A selected role color replaces the default ring on that avatar. The ring is drawn on the outer circular profile-photo wrapper. The inner image shadow is disabled so it cannot be clipped or create a second ring.

## Email handling and SMTP diagnostics

Outgoing email recipients at the exact domain @buehne-total.eu are intentionally removed from the recipient list. If all recipients match that domain, no message is sent. If a message has additional recipients, delivery continues to those remaining recipients.

When WordPress reports a mail failure, Members Plugin records the failure code and error message in **Members Plugin → E-Mail-Protokoll**. The log does not store the message body. This confirms errors reported by WordPress/PHPMailer; it cannot guarantee delivery to the recipient's inbox.

## Migration backup before removing legacy membership engine

The page **Tools → MP Migration & Sicherung** is intentionally independent from the bundled legacy core, so it can be used while a separately installed legacy membership engine plugin is detected. It creates a ZIP containing a JSON database snapshot (WordPress users and usermeta, legacy-engine-related options and custom tables, UM form/directory posts and metadata) and copies files from the common `uploads/ultimatemember` and `uploads/ultimate-member` directories when present. Backup files are placed in a directory adjacent to the WordPress web root, with restrictive permissions, and are served only through an administrator-only, nonce-protected download action. The index of backups is stored in an MP-owned table using the site's actual WordPress table prefix.

The archive contains sensitive personal data and password hashes. Store it securely. The snapshot is not an automatic SQL restore and does not yet make the whole bundled membership engine independent of legacy membership engine naming or internals. Do not uninstall UM until you have downloaded and checked the archive and have a separate full-site backup.

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
