# Sicherheit – Members Plugin

Basis: **Ultimate Member 2.14.1** (22.10.2026). Diese Version enthält bereits alle bis dahin
veröffentlichten CVE-Fixes von Ultimate Member (u.a. CVE-2026-107625, CVE-2026-96270,
CVE-2026-93428, PHP Object Injection, Stored SQL Injection in Verzeichnis-Suchfeldern,
Privilege Escalation bei der Kontoübermittlung).

Darüber hinaus wurde der Code gezielt auf typische Schwachstellen-Muster geprüft
(öffentliche AJAX-Endpunkte, fehlende Berechtigungsprüfungen, SQL ohne `prepare`,
Path Traversal, Open Redirect, Host-Header, IP-Spoofing, XSS in Ausgaben/Shortcodes,
Meta-Key-Injection). Folgende Probleme wurden behoben:

| # | Bereich | Problem | Fix | Datei |
|---|---------|---------|-----|-------|
| 1 | Datei-/Bildfelder | Feldwerte wurden nur mit `sanitize_text_field` bereinigt; `../` blieb erhalten. `um_is_temp_file()` prüfte nur `file_exists(temp/ . $wert)` → Existenz-Orakel für beliebige Server-Dateien und Umbenennen von Dateien außerhalb des Upload-Ordners. | Nur reine Dateinamen, `realpath`-Prüfung, dass die Datei im Temp-Ordner liegt (auch für `um_is_temp_upload`, `um_is_temp_image`, `um_is_file_owner`, `move_temporary_files`). | `includes/um-short-functions.php`, `includes/core/class-uploader.php` |
| 2 | Datei-Download | Pfad aus User-Meta ohne Containment-Prüfung, MIME-Typ aus Metadaten, Dateiname ungefiltert im Header, Bilder `inline`. | Basename + `realpath`-Containment, MIME aus echter Datei, `nosniff`, nur echte Bilder `inline` (SVG nie), bereinigter Dateiname mit RFC-5987-Kodierung. | `includes/core/class-files.php` |
| 3 | AJAX `um_select_options` (öffentlich) | Antwort spiegelte komplettes `$_POST` und lieferte die **vollständige Feldkonfiguration** beliebiger Formulare an anonyme Besucher; Meta-Abfrage für beliebige Meta-Keys. | Antwort auf benötigte Werte reduziert, Meta-Key muss ein öffentliches UM-Feld und darf nicht gesperrt sein. | `includes/core/class-form.php` |
| 4 | Admin-AJAX | `um_get_users` (Liste aller Benutzernamen), `um_run_package`, `um_get_packages`, `um_same_page_update`, `um_member_directory_default_filter_settings`, `um_dismiss_notice`, `um_get_icons` prüften nur die Nonce. | Zusätzlich `current_user_can( 'manage_options' )`. | diverse in `includes/admin`, `includes/ajax`, `includes/core` |
| 5 | Frontend-Aktionen | Freigeben/Ablehnen/Deaktivieren/Reaktivieren/Aktivierungsmail: nur Nonce-Prüfung (Berechtigungsprüfung im Original auskommentiert). | `edit_users` erforderlich. | `includes/frontend/class-actions-listener.php` |
| 6 | IP-Sperren | `um_user_ip()` vertraute `X-Forwarded-For`/`Client-IP` → IP-Sperren per Header umgehbar. | Nur `REMOTE_ADDR`; Proxy-Header nur von vertrauenswürdigen Proxys (Filter `mp_trusted_proxies`). | `includes/um-short-functions.php` |
| 7 | Host-Header | `get_current_url()` und leere JS-Weiterleitungen bauten URLs aus dem Host-Header → Host-Header-Injection / Cache-Poisoning. | Host aus `home_url()`. | `includes/core/class-permalinks.php`, `includes/um-short-functions.php` |
| 8 | Weiterleitungen | `um_js_redirect()` und Sperr-Redirects nutzten `wp_redirect`. | `wp_validate_redirect` / `wp_safe_redirect`. | `includes/um-short-functions.php`, `includes/core/um-actions-form.php` |
| 9 | Meta-Keys | Whitelist für Formular-Übermittlungen griff nur bei `update_user_metadata`, nicht beim Anlegen neuer Meta-Einträge. | Zusätzlich `add_user_metadata` abgesichert. | `includes/members-plugin/class-mp-security.php` |
| 10 | oEmbed-Feld | Lokale URLs wurden als `<iframe>` eingebettet – auch `/wp-admin` oder `wp-login.php` (Clickjacking auf gleicher Domain). | Admin-/Login-/REST-URLs werden nur verlinkt. | `includes/core/um-filters-fields.php` |
| 11 | Profil-Ausgaben | E-Mail-Werte unescaped im Link; externe Links ohne `noopener`. | Escaping, `rel="noopener noreferrer"`. | `includes/core/um-filters-fields.php` |
| 12 | Verzeichnis-Templates | Meta-Keys, „keine Mitglieder“-Text und Sortier-Label unescaped. | `esc_attr` / `wp_kses_post` / `esc_html`. | `templates/members*.php` |
| 13 | Sensible Seiten | Login, Registrierung, Konto, Passwort ohne Clickjacking-Schutz. | `X-Frame-Options: SAMEORIGIN`, `nosniff`, `Referrer-Policy` (Filter `mp_send_security_headers`). | `includes/members-plugin/class-mp-security.php` |
| 14 | Sicherungs-Import | Spaltennamen aus der ZIP wurden ungeprüft an `$wpdb->insert/update` übergeben (SQL-Injection über präparierte Spaltennamen); Post-Meta beliebiger Beiträge überschreibbar; auf Multisite konnten Website-Admins netzwerkweite Benutzerkonten überschreiben. | Spalten-Whitelist per `SHOW COLUMNS`, nur skalare Werte, Post-Meta nur für Mitglieder-Beiträge, Symlink-Ziele übersprungen, Größenprüfung vor dem Entpacken, Multisite nur für Netzwerk-Admins. | `includes/class-mp-migration-backup.php` |
| 15 | Online-Mitglieder-Shortcode | Zeigte private Profile und als Fallback den Login-Namen (hilft bei Brute-Force). | Profil-Sichtbarkeit wird geprüft, kein Login-Name mehr. | `includes/members-plugin/class-mp-online-status.php` |

Neue Funktionen des Forks sind von Anfang an abgesichert: Capability-Checks
(`manage_options`, eigenes CSS nur mit `edit_css`), Nonces für alle Formulare,
Farbwerte nur als Hex, CSS-Werte per Whitelist, eigenes CSS ohne `<style>`/`<script>`,
`expression()`, `javascript:`, `behavior`, `-moz-binding`.

## Ehrlicher Hinweis

Ultimate Member umfasst rund 270 PHP-Dateien. Das Audit war gründlich, aber kein
formaler Penetrationstest. Eine Garantie, dass *keine* Lücken mehr existieren, kann
niemand geben. Empfehlung:

- Original-Changelog von Ultimate Member verfolgen und neue Fixes übernehmen
  (der Fork hält die UM-Struktur bewusst gleich, damit Patches leicht übertragbar sind).
- Einen WordPress-Sicherheitsscanner (z.B. Wordfence/Patchstack) einsetzen.
- Sicherheitsprobleme bitte nicht öffentlich als Issue melden, sondern direkt an die Maintainer.
