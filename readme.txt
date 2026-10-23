=== Members Plugin – Profiles & Community ===
Contributors: paulhubacek1-afk
Tags: membership, user-profile, community, user-registration, member-directory
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.0
Stable tag: 1.0.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A WordPress membership and profile system with theme-aware design tools, user-ID-based profile images, and a built-in online-member list.

== Description ==

Members Plugin provides membership pages, login and registration forms, profile pages, member directories and account features, along with theme-aware design tools and a built-in online-member list.

Version 1.0.1 includes a profile-image resolver that checks the correct WordPress user-ID folder first, helping avoid using a picture associated with a previously loaded profile. It also includes a responsive online-member list without requiring a separate online-user extension. HTML-enabled text fields preserve safe h1–h6 heading tags after sanitization.

Features:
* Front-end profiles, registration, login and account pages
* Member directories and configurable profile fields
* Automatic theme color detection and design variables
* CSS editor, class reference and front-end CSS inspector
* ID-based profile-photo resolution
* Built-in online tracking and responsive online-member list
* Additional security hardening

== Installation ==

1. Upload the plugin ZIP through Plugins > Add New > Upload Plugin.
2. Deactivate the original Ultimate Member plugin if it is installed and active.
3. Activate Members Plugin – Profiles & Community.
4. Configure the design under Members Plugin > Design & CSS.

Important: This plugin includes its own membership core and does not require a separate Ultimate Member installation. The bundled legacy core still shares some internal symbols with the upstream plugin, so both plugins cannot currently run at the same time. Do not remove the compatibility guard or activate both plugins together.

== Online member list ==

Add [mp_online_users] to a page or shortcode block. Use [mp_online_users limit="20"] to show up to 20 people. Only logged-in visitors can see the list. A member is considered online for five minutes after the last recorded activity; activity is written at most once per minute.

== Frequently Asked Questions ==

= Does Members Plugin require Ultimate Member? =

No. The membership core is bundled in this plugin. The original Ultimate Member plugin must be deactivated because the legacy internal symbols currently overlap.

= How does the profile-image fix work? =

The plugin resolves the requested WordPress user ID and checks wp-content/uploads/ultimatemember/{USER_ID}/profile_photo.{ext} before leaving the existing avatar unchanged.

= Can guests see the online-member list? =

No. The shortcode displays the list only to logged-in visitors.

== Changelog ==

= 1.0.1 - 2026-10-23 =
* Fixed: resolve avatar pictures from the actual WordPress user-ID directory first.
* Fixed: preserve safe h1–h6 tags in HTML-enabled form text fields.
* Added: built-in online tracking and the [mp_online_users] shortcode.
* Added: responsive online-member list styling.
* Improved: plugin metadata and documentation.

= 1.0.0 =
* Initial Members Plugin build with theme-aware styles, CSS tools and additional security checks.

== License ==

GPLv3. This derivative project retains the upstream membership core's licensing and attribution. See the included license.txt and project README.
