<?php
/**
 * Members Plugin – Design-System: CSS-Variablen, Theme-Anpassung, eigenes CSS.
 *
 * Alle Stylesheets des Plugins verwenden CSS-Variablen der Form
 * `var(--um-primary, #3ba1da)`. Diese Klasse berechnet die Werte (automatisch
 * aus dem Theme und/oder manuell) und gibt sie als :root-Block aus.
 *
 * @package MembersPlugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class MP_Design
 */
final class MP_Design {

	const OPTION = 'mp_design';

	/**
	 * Im <head> bereits ausgegebene Stylesheets.
	 *
	 * @var string[]
	 */
	private static $head_styles = array();

	/**
	 * Registrierte Variablen.
	 *
	 * @return array<string,array{label:string,desc:string,default:string,group:string,type:string}>
	 */
	public static function variables() {
		$vars = array(
			'primary'         => array( __( 'Primärfarbe', 'ultimate-member' ), __( 'Buttons, aktive Tabs, Links, Kalender, Modal-Kopf.', 'ultimate-member' ), '#3ba1da', 'colors' ),
			'primary-hover'   => array( __( 'Primärfarbe (Hover)', 'ultimate-member' ), __( 'Hover-Zustand von Buttons und Links.', 'ultimate-member' ), '#44b0ec', 'colors' ),
			'primary-soft'    => array( __( 'Primärfarbe (hell)', 'ultimate-member' ), __( 'Markierungen, z.B. im Datumswähler.', 'ultimate-member' ), '#e1f4ff', 'colors' ),
			'on-primary'      => array( __( 'Text auf Primärfarbe', 'ultimate-member' ), __( 'Schrift auf Buttons und farbigen Flächen.', 'ultimate-member' ), '#ffffff', 'colors' ),
			'secondary'       => array( __( 'Sekundär-Button', 'ultimate-member' ), __( 'Hintergrund von .um-button.um-alt.', 'ultimate-member' ), '#eeeeee', 'colors' ),
			'secondary-hover' => array( __( 'Sekundär-Button (Hover)', 'ultimate-member' ), '', '#e5e5e5', 'colors' ),
			'on-secondary'    => array( __( 'Text auf Sekundär-Button', 'ultimate-member' ), '', '#666666', 'colors' ),
			'text'            => array( __( 'Text', 'ultimate-member' ), __( 'Fließtext, Labels, Eingabefelder.', 'ultimate-member' ), '#666666', 'colors' ),
			'text-strong'     => array( __( 'Text (stark)', 'ultimate-member' ), __( 'Namen, Überschriften.', 'ultimate-member' ), '#333333', 'colors' ),
			'text-muted'      => array( __( 'Text (gedämpft)', 'ultimate-member' ), __( 'Meta-Infos, Hinweise.', 'ultimate-member' ), '#999999', 'colors' ),
			'text-light'      => array( __( 'Text (hell)', 'ultimate-member' ), __( 'Platzhalter, Icons in Feldern.', 'ultimate-member' ), '#aaaaaa', 'colors' ),
			'bg'              => array( __( 'Hintergrund', 'ultimate-member' ), __( 'Eingabefelder, Karten, Dropdowns.', 'ultimate-member' ), '#ffffff', 'colors' ),
			'bg-alt'          => array( __( 'Hintergrund (alternativ)', 'ultimate-member' ), __( 'Zebra-Zeilen, Bereiche, Hover.', 'ultimate-member' ), '#f4f4f4', 'colors' ),
			'border'          => array( __( 'Rahmen', 'ultimate-member' ), __( 'Rahmen von Feldern, Karten, Trennlinien.', 'ultimate-member' ), '#dddddd', 'colors' ),
			'border-strong'   => array( __( 'Rahmen (Fokus)', 'ultimate-member' ), __( 'Rahmen bei Fokus/Hover.', 'ultimate-member' ), '#bbbbbb', 'colors' ),
			'tooltip-bg'      => array( __( 'Tooltip-Hintergrund', 'ultimate-member' ), '', '#333333', 'colors' ),
			'nav-bg'          => array( __( 'Profil-Navigation', 'ultimate-member' ), __( 'Hintergrund der Tab-Leiste im Profil (.um-profile-nav).', 'ultimate-member' ), '#444444', 'colors' ),
			'nav-hover'       => array( __( 'Profil-Navigation (Hover)', 'ultimate-member' ), '', '#555555', 'colors' ),
			'on-nav'          => array( __( 'Text in Profil-Navigation', 'ultimate-member' ), '', '#ffffff', 'colors' ),
			'error'           => array( __( 'Fehler', 'ultimate-member' ), __( 'Fehlermeldungen, Status "abgelehnt".', 'ultimate-member' ), '#c74a4a', 'status' ),
			'success'         => array( __( 'Erfolg', 'ultimate-member' ), '', '#7acf58', 'status' ),
			'info'            => array( __( 'Info', 'ultimate-member' ), '', '#497bc7', 'status' ),
			'warning-bg'      => array( __( 'Warnung (Hintergrund)', 'ultimate-member' ), '', '#f9f9d1', 'status' ),
			'warning-border'  => array( __( 'Warnung (Rahmen)', 'ultimate-member' ), '', '#efe4a2', 'status' ),
			'radius'          => array( __( 'Eckenradius', 'ultimate-member' ), __( 'Felder, Karten, Tags. Leer = Original (2–8px).', 'ultimate-member' ), '', 'shape' ),
			'button-radius'   => array( __( 'Button-Eckenradius', 'ultimate-member' ), __( 'Leer = vom Theme bzw. Original.', 'ultimate-member' ), '', 'shape' ),
			'font-family'     => array( __( 'Schriftart', 'ultimate-member' ), __( 'Leer = Schrift des Themes (inherit).', 'ultimate-member' ), 'inherit', 'shape' ),
		);

		$out = array();
		foreach ( $vars as $key => $v ) {
			$out[ $key ] = array(
				'label'   => $v[0],
				'desc'    => $v[1],
				'default' => $v[2],
				'group'   => $v[3],
				'type'    => 'shape' === $v[3] ? ( 'font-family' === $key ? 'font' : 'size' ) : 'color',
			);
		}

		/**
		 * Filtert die registrierten Design-Variablen.
		 *
		 * @param array $out Variablen.
		 */
		return apply_filters( 'mp_design_variables', $out );
	}

	/**
	 * Standardoptionen.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'auto_colors'   => 1,
			'overrides'     => array(),
			'custom_css'    => '',
			'inspector'     => 1,
			'editor_styles' => 1,
		);
	}

	/**
	 * Optionen laden.
	 *
	 * @return array
	 */
	public static function get_options() {
		$opts = get_option( self::OPTION, array() );
		if ( ! is_array( $opts ) ) {
			$opts = array();
		}
		$opts = wp_parse_args( $opts, self::defaults() );
		if ( ! is_array( $opts['overrides'] ) ) {
			$opts['overrides'] = array();
		}
		return $opts;
	}

	/**
	 * Werte, die aus dem Theme abgeleitet werden (ohne manuelle Overrides).
	 *
	 * @return array<string,string>
	 */
	public static function auto_values() {
		$t      = MP_Theme_Colors::detect();
		$vars   = self::variables();
		$values = array();

		$bg   = $t['bg'] ? $t['bg'] : $vars['bg']['default'];
		$text = $t['text'] ? $t['text'] : null;

		if ( $t['primary'] ) {
			$values['primary']      = $t['primary'];
			$values['primary-soft'] = MP_Color::mix( $t['primary'], $bg, 0.15 );
			$on                     = $t['on_primary'];
			if ( ! $on || MP_Color::contrast( $on, $t['primary'] ) < 3 ) {
				$on = MP_Color::readable_on( $t['primary'], array( '#ffffff', $text ? $text : '#111111', '#111111' ) );
			}
			$values['on-primary'] = $on;

			// Hover: Theme-Wert nur verwenden, wenn er sich sichtbar unterscheidet und lesbar bleibt.
			$hover = $t['primary_hover'];
			if ( ! $hover || MP_Color::to_hex( $hover ) === MP_Color::to_hex( $t['primary'] ) || MP_Color::contrast( $hover, $on ) < 3 ) {
				$hover = MP_Color::hover( $t['primary'] );
				if ( MP_Color::contrast( $hover, $on ) < 3 ) {
					$hover = MP_Color::hover( $t['primary'], 0.08 );
				}
			}
			$values['primary-hover'] = $hover;
		}

		if ( $t['bg'] || $t['text'] ) {
			// Fehlende Seite ergänzen, damit die Mischungen sinnvoll sind.
			if ( ! $text ) {
				$text = MP_Color::is_dark( $bg ) ? '#eeeeee' : '#333333';
			}
			$values['bg']              = $bg;
			$values['text-strong']     = $t['heading'] ? $t['heading'] : $text;
			$values['text']            = MP_Color::mix( $text, $bg, 0.85 );
			$values['text-muted']      = MP_Color::mix( $text, $bg, 0.55 );
			$values['text-light']      = MP_Color::mix( $text, $bg, 0.42 );
			$values['bg-alt']          = MP_Color::mix( $text, $bg, 0.05 );
			$values['border']          = MP_Color::mix( $text, $bg, 0.15 );
			$values['border-strong']   = MP_Color::mix( $text, $bg, 0.3 );
			$values['secondary']       = MP_Color::mix( $text, $bg, 0.08 );
			$values['secondary-hover'] = MP_Color::mix( $text, $bg, 0.14 );
			$values['on-secondary']    = MP_Color::mix( $text, $bg, 0.85 );
			$values['tooltip-bg']      = MP_Color::is_dark( $bg ) ? MP_Color::mix( $text, $bg, 0.2 ) : $values['text-strong'];
			// Profil-Navigation: dunkle Leiste aus der Theme-Textfarbe (bei dunklen Themes eine hellere Fläche).
			$nav                       = MP_Color::is_dark( $bg ) ? MP_Color::mix( $text, $bg, 0.18 ) : MP_Color::mix( $values['text-strong'], $bg, 0.88 );
			$values['nav-bg']          = $nav;
			$values['nav-hover']       = MP_Color::hover( $nav, 0.1 );
			$values['on-nav']          = MP_Color::readable_on( $nav, array( '#ffffff', '#111111' ) );
		}

		if ( $t['button_radius'] ) {
			$values['button-radius'] = $t['button_radius'];
		}
		if ( $t['font_family'] ) {
			$values['font-family'] = $t['font_family'];
		}

		return $values;
	}

	/**
	 * Endgültige Werte: Theme-Werte (falls aktiviert) + manuelle Overrides.
	 *
	 * @return array<string,string>
	 */
	public static function resolved_values() {
		// Members Plugin: used by enqueue_frontend() and body_class() on every page; compute once per request.
		static $cache = null;
		static $cache_opts = null;
		$opts = self::get_options();
		if ( null !== $cache && $cache_opts === $opts ) {
			return $cache;
		}
		$cache_opts = $opts;
		$cache      = self::compute_resolved_values( $opts );
		return $cache;
	}

	/**
	 * Theme values (if enabled) plus manual overrides.
	 *
	 * @param array $opts Options.
	 * @return array<string,string>
	 */
	private static function compute_resolved_values( $opts ) {
		$values = ! empty( $opts['auto_colors'] ) ? self::auto_values() : array();
		$vars   = self::variables();

		foreach ( $opts['overrides'] as $key => $value ) {
			if ( isset( $vars[ $key ] ) && '' !== $value && null !== $value ) {
				$values[ $key ] = $value;
			}
		}

		// Wenn nur die Primärfarbe manuell gesetzt wurde, Abhängige nachziehen.
		if ( ! empty( $opts['overrides']['primary'] ) ) {
			$p = $opts['overrides']['primary'];
			if ( empty( $opts['overrides']['primary-hover'] ) ) {
				$values['primary-hover'] = MP_Color::hover( $p );
			}
			if ( empty( $opts['overrides']['on-primary'] ) ) {
				$values['on-primary'] = MP_Color::readable_on( $p );
			}
			if ( empty( $opts['overrides']['primary-soft'] ) ) {
				$values['primary-soft'] = MP_Color::mix( $p, isset( $values['bg'] ) ? $values['bg'] : '#ffffff', 0.15 );
			}
		}

		/**
		 * Filtert die endgültigen CSS-Variablenwerte.
		 *
		 * @param array $values Werte (Schlüssel ohne "--um-").
		 */
		return apply_filters( 'mp_css_variables', $values );
	}

	/**
	 * Erzeugt den :root-Block.
	 *
	 * @param array|null $values Werte.
	 * @return string
	 */
	public static function variables_css( $values = null ) {
		if ( null === $values ) {
			$values = self::resolved_values();
		}
		if ( empty( $values ) ) {
			return '';
		}
		$lines = array();
		foreach ( $values as $key => $value ) {
			$key   = preg_replace( '/[^a-z0-9-]/', '', strtolower( $key ) );
			$value = self::sanitize_css_value( $value );
			if ( '' === $key || '' === $value ) {
				continue;
			}
			$lines[] = '--um-' . $key . ':' . $value . ';';
		}
		return ':root{' . implode( '', $lines ) . '}';
	}

	/**
	 * Basis-CSS, das zusätzlich zu den umgestellten UM-Stylesheets geladen wird.
	 *
	 * @return string
	 */
	public static function base_css() {
		return '.um,.um-modal,.um-account,.um-directory{font-family:var(--um-font-family,inherit)}';
	}

	/**
	 * Nur sichere Zeichen in CSS-Werten zulassen.
	 *
	 * @param string $value Wert.
	 * @return string
	 */
	public static function sanitize_css_value( $value ) {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';
		// Erlaubt: Hex, Zahlen+Einheiten, Funktionsnamen (var, rgb, color-mix…), Schriftnamen.
		if ( ! preg_match( '/^[#a-zA-Z0-9\s,.\'"()%_-]{1,300}$/', $value ) ) {
			return '';
		}
		// Keine Möglichkeit, aus dem Block auszubrechen oder URLs/Expressions einzuschleusen.
		if ( preg_match( '/url\s*\(|expression\s*\(|javascript:|@import/i', $value ) ) {
			return '';
		}
		return $value;
	}

	/**
	 * Eigenes CSS bereinigen.
	 *
	 * @param string $css CSS.
	 * @return string
	 */
	public static function sanitize_custom_css( $css ) {
		$css = is_string( $css ) ? $css : '';
		$css = wp_strip_all_tags( $css );
		// Schließt das <style>-Tag nie (auch nicht kodiert).
		$css = str_ireplace( array( '</style', '<style', '<script', '</script' ), '', $css );
		$css = preg_replace( '/expression\s*\(|javascript\s*:|-moz-binding|behavior\s*:/i', '', $css );
		return trim( $css );
	}

	/**
	 * Hooks registrieren.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend' ), 9999 );
		add_action( 'wp_print_footer_scripts', array( __CLASS__, 'late_custom_css' ), 11 );
		add_action(
			'wp_head',
			static function () {
				self::$head_styles = (array) wp_styles()->done;
			},
			9999
		);
		add_action( 'enqueue_block_assets', array( __CLASS__, 'enqueue_editor' ), 20 );
		MP_Theme_Colors::register_cache_invalidation();
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
	}

	/**
	 * Frontend: Variablen + eigenes CSS ausgeben.
	 */
	public static function enqueue_frontend() {
		$opts = self::get_options();

		wp_register_style( 'mp-design', false, array(), MP_VERSION );
		wp_enqueue_style( 'mp-design' );

		$values = self::resolved_values();
		$css    = self::variables_css( $values ) . self::base_css();
		if ( '' !== $opts['custom_css'] ) {
			$css .= "\n/* Members Plugin – eigenes CSS */\n" . self::sanitize_custom_css( $opts['custom_css'] );
		}
		wp_add_inline_style( 'mp-design', $css );

		// Live-Erkennung im Browser für Werte, die serverseitig nicht ermittelt werden konnten.
		if ( ! empty( $opts['auto_colors'] ) ) {
			$have = array_keys( $values );
			$all  = array( 'primary', 'bg', 'text' );
			if ( array_diff( $all, $have ) ) {
				wp_enqueue_script( 'mp-theme-detect', MP_URL . 'assets/theme-detect.js', array(), MP_VERSION, true );
				wp_add_inline_script( 'mp-theme-detect', 'window.mpThemeDetect=' . wp_json_encode( array( 'have' => array_values( $have ) ) ) . ';', 'before' );
			}
		}
	}

	/**
	 * Werden UM-Stylesheets erst im Footer geladen (z.B. Mitgliederverzeichnis),
	 * wird das eigene CSS danach erneut ausgegeben, damit es Vorrang behält.
	 */
	public static function late_custom_css() {
		$opts = self::get_options();
		if ( '' === $opts['custom_css'] ) {
			return;
		}
		$late = false;
		foreach ( (array) wp_styles()->done as $handle ) {
			if ( 0 === strpos( $handle, 'um_' ) && ! in_array( $handle, self::$head_styles, true ) ) {
				$late = true;
				break;
			}
		}
		if ( ! $late ) {
			return;
		}
		echo "<style id=\"mp-design-late-css\">\n" . self::sanitize_custom_css( $opts['custom_css'] ) . "\n</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bereinigtes CSS.
	}

	/**
	 * Block-Editor: dieselben Variablen, damit UM-Blöcke wie im Frontend aussehen.
	 */
	public static function enqueue_editor() {
		if ( ! is_admin() ) {
			return;
		}
		$opts = self::get_options();
		if ( empty( $opts['editor_styles'] ) ) {
			return;
		}
		wp_register_style( 'mp-design-editor', false, array(), MP_VERSION );
		wp_enqueue_style( 'mp-design-editor' );
		wp_add_inline_style( 'mp-design-editor', self::variables_css() . self::base_css() );
	}

	/**
	 * Body-Klasse zur Theme-Erkennung (z.B. für eigenes CSS bei dunklen Themes).
	 *
	 * @param string[] $classes Klassen.
	 * @return string[]
	 */
	public static function body_class( $classes ) {
		$values = self::resolved_values();
		if ( ! empty( $values['bg'] ) && MP_Color::to_hex( $values['bg'] ) && MP_Color::is_dark( $values['bg'] ) ) {
			$classes[] = 'um-theme-dark';
		}
		$classes[] = 'um-mp-design';
		return $classes;
	}
}
