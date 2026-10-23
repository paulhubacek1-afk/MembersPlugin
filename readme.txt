=== Members Plugin – Profiles & Community ===
Contributors: paulhubacek1-afk
Tags: membership, user-profile, community, user-registration, member-directory, online-users
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.0
Stable tag: 1.0.9.3
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A WordPress membership and profile system with per-role avatar rings, birthday confetti, scheduled update checks and a standalone migration backup tool.

== Description ==

Version 1.0.8 fixes the Sicherung navigation and strengthens release ZIP validation. Version 1.0.7 added an administrator-only Sicherung page with ZIP export and import. Import is limited to the same site URL and database prefix; it updates or inserts captured WordPress users, metadata, options, posts and post metadata by ID, and restores archived profile media. It does not delete records absent from the archive. Create a full site backup before importing. The membership engine is still undergoing separation from legacy internals; internal compatibility names are retained where required.

Version 1.0.6 displays only colorful confetti across the full visible profile page on a member's birthday, without a cake or text. Version 1.0.5 fixes profile-page role rings by applying the selected ring to the outer circular photo link and suppressing the clipped inner image shadow.

It also retains the recipient suppression rule for @buehne-total.eu and the dashboard email diagnostics log.

== Online and offline indicators ==

A green dot means online and a grey dot means offline. Dots appear beside the profile owner name in profile headers only; member-directory lists do not show online status. Status updates use a five-minute online window and a signed-in user's browser heartbeat.

The optional compatibility shortcodes [users_online] and [mp_online_users] remain available, but are not required for the profile indicators.

== Birthday celebration ==

If the built-in birth_date field contains a valid birthday matching the current day and month in the site's timezone, colorful confetti falls across the full visible profile page. No cake, greeting or other text is displayed. The effect is hidden on other days and while editing the profile.

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

= 1.0.9.3 =
* Fixed: the Default Profile Photo / Default Cover Photo buttons can open the WordPress Media Library again (settings scripts were not loaded after the admin menu was renamed).
* Faster: member directory: cover photos and profile photos now use the file's modification time as URL version instead of the current time. Browsers can finally cache them, so returning to the directory or paging no longer re-downloads every image.
* Faster: member directory: profile photos in lists load the matching small thumbnail (the original is offered via srcset for high-density screens) instead of the full-size original.
* Faster: member directory: the per-member profile cache options are loaded with one query per page (WordPress 6.4+), the field list and allowed-HTML lists are built once per request, and profile forms are only queried when tagline/reveal fields are shown.
* Faster: member directory script: pages that were already loaded are shown instantly from a 60-second client cache, slow older responses can no longer overwrite newer results, and the grid no longer re-initialises once per previously opened page on every window resize.
* Faster: theme color detection is cached (flushed on theme/style changes), and the birthday stylesheet is loaded only on a member's birthday.
* Faster: online status: no extra AJAX heartbeat on every page load; presence polling loads all users with two queries.
* Fixed: dependent dropdown fields (child options) in profile/registration forms threw a JavaScript error because the AJAX response no longer contained the field's default/editable values.
* Fixed: the updater could offer the bundled core's upstream tags (e.g. 2.14.1) as an update; only Members Plugin 1.x releases are considered now. The duplicate second updater was removed (it also caused a fatal error while a separate legacy core was active).
* Fixed: a missing profile-photo file now shows the default avatar instead of a broken image URL.
* Fixed: birthday: well-known birthday fields are checked first; members born on 29 February celebrate on 28 February in non-leap years.
* Security: backup import only restores the option and post types the export captures and never writes executable file types into uploads.
* Security: the public online-status endpoint only reports members whose profile the visitor may view.
* Improved: uninstall removes the updater cron event and cached data; Members Plugin options are deleted when "delete data on uninstall" is enabled.

= 1.0.9.2 =
* Fixed: save uploaded cover photos and responsive thumbnails at quality 100 and preserve full resolution of the selected crop.
* Improved: prime member/user-meta caches to reduce repeated database lookups.
* Improved: query profile-form IDs once per request and lazy-load off-screen directory cover photos.

= 1.0.9.1 =
* Fixed: enqueue the WordPress Media Library on all Appearance settings subsections.
* Fixed: Default Profile Photo and Default Cover Photo media pickers can now open.

= 1.0.9 =
* Fixed: register both translation domains at WordPress init so the plugin uses the site's language setting.
* Fixed: release ZIP includes the official German translation file for the bundled membership core.
* Kept: legacy CSS classes and compatibility identifiers unchanged.

= 1.0.8.1 =
* Fixed automatic update discovery in the WordPress dashboard using published GitHub Releases.
* Ignore unrelated internal core version tags when checking for Members Plugin updates.


= 1.0.6 - 2026-10-23 =
* Changed: birthday effect now shows only full-page confetti.
* Removed: birthday cake and all celebration text.

= 1.0.5 - 2026-10-23 =
* Fixed: role-colored ring is now visible on profile pages.
* Fixed: inner image shadow no longer causes a clipped or doubled profile ring.

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
