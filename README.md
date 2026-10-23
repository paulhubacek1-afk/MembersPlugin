# Members Plugin

Mitgliederbereich für WordPress – Registrierung, Login, Profile, Konto, Mitgliederverzeichnis.
Fork von **Ultimate Member 2.14.1** (GPLv3) mit drei Schwerpunkten:

1. **Passt sich automatisch an das Farbspektrum deines Themes an**
2. **Alles anpassbar – und alle CSS-Klassen sichtbar**, damit du gezielt nachbessern kannst
3. **Zusätzliche Sicherheitsfixes** (siehe [SECURITY.md](SECURITY.md))

Alle Original-Klassen (`.um`, `.um-button`, `.um-profile-nav` …), Templates, Shortcodes und
Hooks bleiben unverändert erhalten – bestehende Seiten, Formulare und eigenes CSS funktionieren weiter.

## Installation

1. Ordner als `wp-content/plugins/members-plugin/` hochladen (oder die ZIP unter *Plugins › Installieren › Plugin hochladen*).
2. **Ultimate Member deaktivieren**, falls installiert (Formulare, Rollen und Einstellungen bleiben erhalten – der Fork nutzt dieselben Daten).
3. *Members Plugin* aktivieren.
4. *Members Plugin › Design & CSS* öffnen.

## Automatische Theme-Farben

Alle Stylesheets nutzen CSS-Variablen mit dem Originalwert als Fallback:

```css
.um a.um-button { background: var(--um-primary, #3ba1da); }
```

Die Werte werden automatisch ermittelt aus:

| Quelle | Beispiele |
|--------|-----------|
| `theme.json` / globale Stile (inkl. Website-Editor-Anpassungen und Stilvarianten) | Button-, Text-, Hintergrund-, Überschriftenfarbe, Button-Radius, Schrift |
| Theme-Palette | `primary`, `accent`, `contrast`, `base` …, Astra, Kadence, Blocksy, GeneratePress |
| Theme-/Builder-Optionen | Elementor Kit, OceanWP, Divi, Customizer-Hintergrund |
| Live-Erkennung im Browser | für klassische Themes ohne `theme.json` (liest echte Button-/Text-/Hintergrundfarbe) |

Daraus werden alle Abstufungen berechnet (Hover, Rahmen, gedämpfter Text, Flächen, lesbare
Schrift auf Buttons nach WCAG-Kontrast) – **auch für dunkle Themes**. Getestet mit Twenty
Seventeen, Twenty Twenty-One, Twenty Twenty-Three (inkl. dunkler Variante „Pitch“), Twenty
Twenty-Four, Twenty Twenty-Five.

## Anpassen

*Members Plugin › Design & CSS*

- **Farben & Form** – jede Variable einzeln überschreiben, mit Live-Vorschau aus echten Plugin-Klassen
- **Eigenes CSS** – mit Code-Editor, wird nach den Plugin-Styles geladen
- **CSS-Klassen** – durchsuchbare Liste aller ~480 Klassen mit „gestylt in“ / „verwendet in“, Klick kopiert den Selektor, **Starter-CSS mit allen Klassen** zum Download
- **Variablen & Anleitung** – alle Variablen mit aktuellem Wert und Beispiele

**Klassen-Inspector:** Im Frontend in der Admin-Leiste auf **„CSS-Klassen“** klicken, mit der Maus
auf ein Element zeigen → Klassen werden angezeigt. Klick hält das Element fest und zeigt den
Klassenpfad, einen empfohlenen Selektor (kopierbar) und die berechneten Werte. `Esc` beendet.

### Variablen

`--um-primary`, `--um-primary-hover`, `--um-primary-soft`, `--um-on-primary`, `--um-secondary`,
`--um-secondary-hover`, `--um-on-secondary`, `--um-text`, `--um-text-strong`, `--um-text-muted`,
`--um-text-light`, `--um-bg`, `--um-bg-alt`, `--um-border`, `--um-border-strong`, `--um-tooltip-bg`,
`--um-nav-bg`, `--um-nav-hover`, `--um-on-nav`, `--um-error`, `--um-success`, `--um-info`,
`--um-warning-bg`, `--um-warning-border`, `--um-radius`, `--um-button-radius`, `--um-font-family`

```css
/* Beispiel: runde Buttons, aktiver Profil-Tab unterstrichen */
.um .um-button { border-radius: 999px !important; }
.um .um-profile-nav-item.active a {
	background: transparent;
	box-shadow: inset 0 -3px 0 var(--um-primary);
}
```

Body-Klassen: `um-mp-design`, `um-theme-dark` (bei dunklem Hintergrund).

### Für Entwickler

| Filter | Zweck |
|--------|-------|
| `mp_css_variables` | Endgültige Variablenwerte ändern |
| `mp_design_variables` | Variablen registrieren/ändern |
| `mp_detected_theme_colors` | Theme-Erkennung anpassen |
| `mp_trusted_proxies` | IPs vertrauenswürdiger Reverse-Proxys (für korrekte Besucher-IP) |
| `mp_send_security_headers` | Sicherheits-Header steuern |

Templates überschreiben wie gewohnt: `wp-content/themes/DEIN-THEME/ultimate-member/…`

## Entwicklung

- `tools/css-vars.py` – wandelt hartkodierte Farben in Stylesheets in Variablen um (idempotent, auch für `.min.css`)
- `tools/minify-css.py` – einfacher CSS-Minifier ohne Abhängigkeiten
- Code des Forks: `includes/members-plugin/`; Änderungen am UM-Kern sind mit `// Members Plugin:` kommentiert

## Hinweise

- Text-Domain bleibt `ultimate-member`, damit vorhandene Übersetzungen weiter funktionieren.
- `Update URI: false` verhindert, dass WordPress.org den Fork mit dem Original überschreibt.
- Die im Entwicklungsstand fehlenden Bibliotheken (Action Scheduler 3.9.3, wp-config-transformer 1.4.7) sind enthalten.
- Behobener Original-Fehler: Mitgliederverzeichnisse ohne Rollenauswahl zeigten keine Mitglieder.

Lizenz: GPLv3 – basiert auf [Ultimate Member](https://wordpress.org/plugins/ultimate-member/).
