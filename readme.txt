=== Members Plugin – Profiles & Community ===
Contributors: paulhubacek1-afk
Tags: membership, user-profile, community, user-registration, member-directory, online-users
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.0
Stable tag: 1.0.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A WordPress membership and profile system with role-based avatar ring colors and a built-in Users Online list.

== Description ==

Members Plugin provides membership pages, login and registration forms, profile pages, member directories and account features, along with theme-aware design tools, role-specific profile picture rings and a built-in online-member list.

Version 1.0.2 lets administrators choose a separate avatar ring color for each WordPress role. The online-member list is available through [users_online] or [mp_online_users]. Safe h1–h6 heading tags remain supported in HTML-enabled form text fields.

Features:
* Front-end profiles, registration, login and account pages
* Member directories and configurable profile fields
* Automatic theme color detection and design variables
* CSS editor, class reference and front-end CSS inspector
* ID-based profile-photo resolution
* Configurable avatar ring colors for each user role
* Built-in online tracking and responsive Users Online list
* Additional security hardening

== Installation ==

1. Upload the plugin ZIP through Plugins > Add New > Upload Plugin.
2. Deactivate any separately installed legacy membership core that conflicts with this bundled membership engine.
3. Activate Members Plugin – Profiles & Community.
4. Configure settings from the Members Plugin dashboard menu.

== Users Online ==

Add [users_online] or [mp_online_users] to a page or shortcode block. Use [users_online limit="20"] to show up to 20 people. Only logged-in visitors can see the list. A member is considered online for five minutes after the last activity timestamp; activity is saved at most once per minute.

== Profile picture ring colors ==

Open Members Plugin > Profilbild-Ringfarben. Enable a ring and choose a color for each role. Roles without a ring enabled retain their normal avatar appearance. If a user has multiple roles, the first assigned role with an enabled ring color is used.

== Frequently Asked Questions ==

= Can guests see Users Online? =

No. The shortcode displays the list only to logged-in visitors.

= How do profile-picture ring colors work? =

Each WordPress role can have its own enabled/disabled ring and hex color. The ring is applied to avatar output, profile photos, member-directory cards and the Users Online list.

== Changelog ==

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
