=== Members Plugin – Profiles & Community ===
Contributors: paulhubacek1-afk
Tags: membership, user-profile, community, user-registration, member-directory, online-users
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.0
Stable tag: 1.0.4
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A WordPress membership and profile system with per-role avatar rings, automatic profile presence dots, birthday celebrations and scheduled update checks.

== Description ==

Version 1.0.4 removes online/offline dots from member-directory lists, makes a role-colored ring replace the default avatar ring, adds a cake and confetti celebration on the profile owner's birthday, and schedules automatic GitHub release checks every six hours.

It also retains the recipient suppression rule for @buehne-total.eu and the dashboard email diagnostics log.

== Online and offline indicators ==

A green dot means online and a grey dot means offline. Dots appear beside the profile owner name in profile headers only; member-directory lists do not show online status. Status updates use a five-minute online window and a signed-in user's browser heartbeat.

The optional compatibility shortcodes [users_online] and [mp_online_users] remain available, but are not required for the profile indicators.

== Birthday celebration ==

If the built-in birth_date field contains a valid birthday matching the current day and month in the site's timezone, the profile page displays a birthday cake, greeting and animated confetti. It is automatically hidden on other days and while editing the profile.

== Profile picture ring colors ==

Open Members Plugin > Profilbild-Ringfarben. Enable a ring and choose a color for each role. A selected role color replaces the default ring instead of stacking a second ring. Roles without an enabled ring retain their default appearance.

== Automatic updates ==

Members Plugin checks GitHub Releases every six hours through WordPress Cron and refreshes available-update information so updates can appear in Dashboard > Aktualisierungen. It does not install updates without authorization. WordPress Cron depends on site traffic.

== Email handling ==

Recipients at the exact domain @buehne-total.eu are removed from outgoing email recipient lists. If every recipient matches that domain, the message is not sent. If other recipients remain, delivery continues to them.

Open Members Plugin > E-Mail-Protokoll to see WordPress mail errors and suppressed-recipient events. Message bodies are not stored in the log.

== Frequently Asked Questions ==

= Do the online status dots appear in member directories? =

No. Status dots appear only in profile headers.

= Does birthday confetti need a shortcode? =

No. The profile checks the built-in birth_date field automatically.

= Does the automatic updater install updates by itself? =

No. It searches for releases automatically and makes available updates visible in WordPress.

== Changelog ==

= 1.0.4 - 2026-10-23 =
* Changed: online/offline status dots no longer appear in member-directory lists.
* Fixed: a role-colored avatar ring replaces the default ring instead of stacking on the photo wrapper.
* Added: birthday cake and animated confetti on the profile owner's birthday.
* Added: automatic GitHub release checks every six hours through WordPress Cron.
* Improved: birthday stylesheet loading and updater documentation.

= 1.0.3 - 2026-10-23 =
* Added: automatic green online and grey offline indicators beside names in member-directory cards and profile headers.
* Added: browser heartbeat for signed-in users and periodic presence refresh.
* Added: suppression of recipients at @buehne-total.eu.
* Added: dashboard log for WordPress mail failures and suppressed recipients.
* Improved: Members Plugin branding and usage documentation.

= 1.0.2 - 2026-10-23 =
* Added: configurable profile-picture ring colors per WordPress role.
* Added: [users_online] shortcode alias for the Users Online list.
* Improved: role ring styling across profile photos, directory cards and online list.
* Improved: product description and dashboard wording.

= 1.0.1 - 2026-10-23 =
* Fixed: resolve avatar pictures from the actual WordPress user-ID directory first.
* Fixed: preserve safe h1–h6 tags in HTML-enabled form text fields.
* Added: built-in online tracking and the [mp_online_users] shortcode.
* Added: responsive online-member list styling.
* Improved: plugin metadata and installation/update documentation.

= 1.0.0 =
* Initial Members Plugin build with theme-aware styles, CSS tools and additional security checks.

== License ==

GPLv3. The bundled membership engine retains required upstream licensing and attribution. See the included license.txt.
