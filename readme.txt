=== Members Plugin – Profiles & Community ===
Contributors: paulhubacek1-afk
Tags: membership, user-profile, community, user-registration, member-directory, online-users
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.0
Stable tag: 1.0.3
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A WordPress membership and profile system with role-based avatar ring colors, automatic online/offline markers and mail delivery diagnostics.

== Description ==

Members Plugin provides membership pages, login and registration forms, profile pages, member directories and account features, along with theme-aware design tools and configurable profile picture rings for each WordPress role.

Version 1.0.3 adds green online and grey offline indicators next to names in member directories and profile headers. Indicators refresh automatically and require no shortcode. It also suppresses outbound emails to recipients at @buehne-total.eu and records WordPress mail failures in the dashboard mail log.

Features:
* Front-end profiles, registration, login and account pages
* Member directories and configurable profile fields
* Automatic theme color detection and design variables
* CSS editor, class reference and front-end CSS inspector
* ID-based profile-photo resolution
* Configurable avatar ring colors for each user role
* Automatic online/offline indicators next to names
* Email recipient suppression rule and mail failure diagnostics
* Additional security hardening

== Installation ==

1. Upload the plugin ZIP through Plugins > Add New > Upload Plugin.
2. Deactivate any separately installed legacy membership core that conflicts with this bundled membership engine.
3. Activate Members Plugin – Profiles & Community.
4. Configure settings from the Members Plugin dashboard menu.

== Online and offline indicators ==

A green dot means online and a grey dot means offline. Dots are automatically shown beside names in member directories and profile headers; no shortcode is required. A member is considered online for five minutes after the last activity. An active, signed-in browser tab refreshes activity and visible status indicators about once per minute.

Optional compatibility shortcodes [users_online] and [mp_online_users] remain supported.

== Profile picture ring colors ==

Open Members Plugin > Profilbild-Ringfarben. Enable a ring and choose a color for each role. Roles without a ring enabled retain their normal avatar appearance. If a user has multiple roles, the first assigned role with an enabled ring color is used.

== Email handling ==

Recipients at the exact domain @buehne-total.eu are removed from outgoing email recipient lists. If every recipient matches that domain, the message is not sent. If other recipients remain, delivery continues to them.

Open Members Plugin > E-Mail-Protokoll to see WordPress mail errors and suppressed-recipient events. Message bodies are not stored in the log.

== Frequently Asked Questions ==

= Can I use status dots without a shortcode? =

Yes. Dots are automatically shown next to member names in member directories and profile headers.

= Can guests see the status dots? =

The indicators show only whether a member has activity inside the online window; they do not expose an exact last-seen time.

= What happens to emails for @buehne-total.eu? =

Those recipients are excluded by the plugin's mail rule. Other recipients on the same message can still receive it.

== Changelog ==

= 1.0.3 - 2026-10-23 =
* Added: automatic green online and grey offline indicators next to profile and directory names.
* Added: browser heartbeat and live presence refresh.
* Added: suppression of recipients at @buehne-total.eu.
* Added: dashboard log for WordPress mail failures and suppressed recipients.
* Improved: Members Plugin branding and usage documentation.

= 1.0.2 - 2026-10-23 =
* Added: configurable profile-picture ring colors per WordPress role.
* Added: [users_online] shortcode alias for the Users Online list.
* Improved: role ring styling across profile photos, directory cards and online list.
* Improved: plugin description, dashboard wording and documentation.

= 1.0.1 - 2026-10-23 =
* Fixed: resolve avatar pictures from the actual WordPress user-ID directory first.
* Fixed: preserve safe h1–h6 tags in HTML-enabled form text fields.
* Added: built-in online tracking and the [mp_online_users] shortcode.
* Added: responsive online-member list styling.
* Improved: plugin metadata and documentation.

= 1.0.0 =
* Initial Members Plugin build with theme-aware styles, CSS tools and additional security checks.

== License ==

GPLv3. The bundled membership engine retains required upstream licensing and attribution. See the included license.txt.
