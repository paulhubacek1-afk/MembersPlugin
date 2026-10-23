<?php
/**
 * Members Plugin – CSS-Klassen-Referenz.
 *
 * Durchsucht die Stylesheets, Templates, PHP- und JS-Dateien des Plugins nach
 * allen verwendeten CSS-Klassen und liefert eine gruppierte Liste. Damit sieht
 * man auf einen Blick, welche Klassen es gibt, wo sie gestylt und wo sie im
 * Markup verwendet werden – die Grundlage für eigenes CSS.
 *
 * @package MembersPlugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class MP_Class_Reference
 */
final class MP_Class_Reference {

	const TRANSIENT = 'mp_class_reference';

	/**
	 * Gruppen (Reihenfolge = Priorität beim Zuordnen).
	 *
	 * @return array<string,array{label:string,pattern:string}>
	 */
	public static function groups() {
		return array(
			'buttons'   => array( 'label' => __( 'Buttons & Links', 'ultimate-member' ), 'pattern' => '/button|btn|um-link|um-alt\b/' ),
			'notices'   => array( 'label' => __( 'Hinweise, Fehler & Tooltips', 'ultimate-member' ), 'pattern' => '/notice|error|success|warning|alert|um-tip|tipsy|um-req|locked/' ),
			'account'   => array( 'label' => __( 'Konto (Account)', 'ultimate-member' ), 'pattern' => '/account/' ),
			'directory' => array( 'label' => __( 'Mitgliederverzeichnis', 'ultimate-member' ), 'pattern' => '/member|directory|search|filter|pagi|um-new-dropdown|um-slider/' ),
			'profile'   => array( 'label' => __( 'Profil', 'ultimate-member' ), 'pattern' => '/profile|cover|um-name|um-meta|um-header|um-photo|um-tab|um-item|um-social|um-gdpr/' ),
			'modal'     => array( 'label' => __( 'Modal, Upload & Zuschneiden', 'ultimate-member' ), 'pattern' => '/modal|upload|crop|fileupload|um-single-|um-finish|um-progress|um-confirm/' ),
			'forms'     => array( 'label' => __( 'Formulare & Felder', 'ultimate-member' ), 'pattern' => '/field|form|um-row|um-col|um-half|input|checkbox|radio|um-s\d|um-center|um-left|um-right|um-clear|um-password|um-register|um-login|um-rating|um-raty/' ),
			'picker'    => array( 'label' => __( 'Datums- & Zeitwähler', 'ultimate-member' ), 'pattern' => '/^picker/' ),
			'select2'   => array( 'label' => __( 'Auswahllisten (Select2)', 'ultimate-member' ), 'pattern' => '/^select2/' ),
			'nav'       => array( 'label' => __( 'Navigation & Dropdowns', 'ultimate-member' ), 'pattern' => '/dropdown|nav|menu|um-misc|um-dir/' ),
			'responsive' => array( 'label' => __( 'Responsive (Breakpoints)', 'ultimate-member' ), 'pattern' => '/^uimob|^uimob|^um-responsive/' ),
			'other'     => array( 'label' => __( 'Allgemein & Sonstiges', 'ultimate-member' ), 'pattern' => '/.*/' ),
		);
	}

	/**
	 * Liefert die Referenz (gecacht).
	 *
	 * @param bool $force Neu scannen.
	 * @return array{classes:array,icons:int,generated:int}
	 */
	public static function get( $force = false ) {
		$key    = self::TRANSIENT . '_' . md5( UM_VERSION . MP_VERSION . self::fingerprint() );
		$cached = $force ? false : get_transient( $key );
		if ( is_array( $cached ) && isset( $cached['classes'] ) ) {
			return $cached;
		}
		$data = self::scan();
		set_transient( $key, $data, WEEK_IN_SECONDS );
		return $data;
	}

	/**
	 * Fingerabdruck über Änderungszeiten der Stylesheets (Cache wird bei Änderungen ungültig).
	 *
	 * @return string
	 */
	private static function fingerprint() {
		$sum = '';
		foreach ( self::css_files() as $file ) {
			$sum .= filemtime( $file );
		}
		return $sum;
	}

	/**
	 * Zu scannende Stylesheets.
	 *
	 * @return string[]
	 */
	private static function css_files() {
		$files = array_merge(
			glob( UM_PATH . 'assets/css/*.css' ),
			glob( UM_PATH . 'assets/libs/*/*.css' )
		);
		$out   = array();
		foreach ( (array) $files as $f ) {
			$base = basename( $f );
			if ( false !== strpos( $base, '.min.' ) || 'jquery-ui.css' === $base || 'um-fontawesome.css' === $base ) {
				continue;
			}
			$out[] = $f;
		}
		return $out;
	}

	/**
	 * Ist das eine Icon-Klasse?
	 *
	 * @param string $class Klasse.
	 * @return bool
	 */
	private static function is_icon( $class ) {
		return (bool) preg_match( '/^(um-faicon-|um-icon-|fa-|ion-|fas$|far$|fab$|fa$)/', $class );
	}

	/**
	 * Gehört die Klasse zum Plugin (und nicht zu jQuery UI o.ä.)?
	 *
	 * @param string $class Klasse.
	 * @return bool
	 */
	private static function is_plugin_class( $class ) {
		return (bool) preg_match( '/^(um|uimob|select2|picker|tipsy|cropper|upload|raty)/', $class ) || 'um' === $class;
	}

	/**
	 * Scan durchführen.
	 *
	 * @return array
	 */
	public static function scan() {
		$classes = array();
		$icons   = array();
		$root    = wp_normalize_path( UM_PATH );

		// 1. Stylesheets: Selektoren auswerten.
		foreach ( self::css_files() as $file ) {
			$css = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$css = preg_replace( '#/\*.*?\*/#s', '', $css );
			// Deklarationen entfernen, nur Selektoren behalten.
			$css = preg_replace( '/\{[^{}]*\}/', '{}', $css );
			if ( ! preg_match_all( '/\.(-?[_a-zA-Z][\w-]*)/', $css, $m ) ) {
				continue;
			}
			$rel = str_replace( $root, '', wp_normalize_path( $file ) );
			foreach ( array_unique( $m[1] ) as $class ) {
				if ( self::is_icon( $class ) ) {
					$icons[ $class ] = true;
					continue;
				}
				if ( ! self::is_plugin_class( $class ) ) {
					continue;
				}
				$classes[ $class ]['css'][ $rel ] = true;
			}
		}

		// 2. Markup: Templates, PHP und JS – Klassen aus class-Attributen und jQuery-Aufrufen.
		$sources = array_merge(
			self::rglob( UM_PATH . 'templates', 'php' ),
			self::rglob( UM_PATH . 'includes/core', 'php' ),
			self::rglob( UM_PATH . 'includes/frontend', 'php' ),
			self::rglob( UM_PATH . 'includes/common', 'php' ),
			self::rglob( UM_PATH . 'includes/ajax', 'php' ),
			array_filter(
				(array) glob( UM_PATH . 'assets/js/*.js' ),
				static function ( $f ) {
					return false === strpos( $f, '.min.js' );
				}
			)
		);

		$patterns = array(
			'/class\s*=\s*\\\\?["\']([^"\'\\\\]*)/i',             // class="..." / class=\"...\"
			'/[\'"]class[\'"]\s*=>\s*[\'"]([^\'"]*)/i',          // 'class' => '...'
			'/(?:add|remove|has|toggle)Class\(\s*[\'"]([^\'"]+)/', // jQuery
			'/[\'"\s(,>](?:\.)(um-[\w-]+)/',                      // Selektoren in JS/PHP
		);

		foreach ( $sources as $file ) {
			$src = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$rel = str_replace( $root, '', wp_normalize_path( $file ) );
			$hit = array();
			foreach ( $patterns as $p ) {
				if ( ! preg_match_all( $p, $src, $m ) ) {
					continue;
				}
				foreach ( $m[1] as $chunk ) {
					foreach ( preg_split( '/\s+/', $chunk ) as $token ) {
						$token = trim( $token, '. ' );
						if ( preg_match( '/^(um[\w-]*|uimob\d+|select2-[\w-]+|picker__[\w-]+)$/', $token ) && ! self::is_icon( $token ) && ! preg_match( '/-$/', $token ) ) {
							$hit[ $token ] = true;
						}
					}
				}
			}
			foreach ( array_keys( $hit ) as $class ) {
				$classes[ $class ]['used'][ $rel ] = true;
			}
		}

		// 3. Gruppieren und sortieren.
		$groups = self::groups();
		$out    = array();
		foreach ( $classes as $class => $info ) {
			$group = 'other';
			foreach ( $groups as $gkey => $g ) {
				if ( preg_match( $g['pattern'], $class ) ) {
					$group = $gkey;
					break;
				}
			}
			$out[ $class ] = array(
				'group' => $group,
				'css'   => isset( $info['css'] ) ? array_keys( $info['css'] ) : array(),
				'used'  => isset( $info['used'] ) ? array_keys( $info['used'] ) : array(),
			);
		}
		// Plugin-eigene Klassen (um-*, .um) zuerst, danach Klassen der mitgelieferten Bibliotheken.
		uksort(
			$out,
			static function ( $a, $b ) {
				$rank = static function ( $c ) {
					if ( 'um' === $c || 0 === strpos( $c, 'um-' ) ) {
						return 0;
					}
					return 0 === strpos( $c, 'uimob' ) ? 1 : 2;
				};
				$ua   = $rank( $a );
				$ub   = $rank( $b );
				return $ua === $ub ? strnatcmp( $a, $b ) : $ua - $ub;
			}
		);

		// Icon-Klassen aus den Icon-Stylesheets zählen.
		$icon_files = array_merge( (array) glob( UM_PATH . 'assets/css/um-fontawesome.css' ), (array) glob( UM_PATH . 'assets/libs/legacy/fonticons/*.css' ) );
		foreach ( $icon_files as $file ) {
			if ( false !== strpos( $file, '.min.' ) ) {
				continue;
			}
			if ( preg_match_all( '/\.((?:um-faicon|um-icon)-[\w-]+)/', (string) file_get_contents( $file ), $m ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				foreach ( $m[1] as $icon ) {
					$icons[ $icon ] = true;
				}
			}
		}

		return array(
			'classes'   => $out,
			'icons'     => count( $icons ),
			'generated' => time(),
		);
	}

	/**
	 * Rekursives glob.
	 *
	 * @param string $dir Verzeichnis.
	 * @param string $ext Endung.
	 * @return string[]
	 */
	private static function rglob( $dir, $ext ) {
		$out = array();
		if ( ! is_dir( $dir ) ) {
			return $out;
		}
		$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) );
		foreach ( $it as $file ) {
			if ( $file->isFile() && strtolower( $file->getExtension() ) === $ext ) {
				$out[] = $file->getPathname();
			}
		}
		return $out;
	}

	/**
	 * Erzeugt ein Starter-Stylesheet mit allen Klassen und Variablen.
	 *
	 * @return string
	 */
	public static function starter_css() {
		$data   = self::get();
		$groups = self::groups();
		$values = MP_Design::resolved_values();
		$vars   = MP_Design::variables();

		$css  = "/*\n * Members Plugin – Starter-Stylesheet\n";
		$css .= ' * Erstellt: ' . gmdate( 'Y-m-d H:i' ) . " UTC\n";
		$css .= " *\n * So verwendest du diese Datei:\n";
		$css .= " *  - Kopiere nur die Regeln, die du ändern willst, nach\n";
		$css .= " *    \"Members Plugin › Design & CSS › Eigenes CSS\" oder in das Stylesheet deines Child-Themes.\n";
		$css .= " *  - Farben am besten über die Variablen unten ändern – dann passen alle Komponenten zusammen.\n";
		$css .= " *  - Für höhere Spezifität \".um \" voranstellen, z.B. \".um .um-button { … }\".\n */\n\n";

		$css .= "/* ==== Design-Variablen (aktuelle Werte) ==== */\n:root {\n";
		foreach ( $vars as $key => $v ) {
			$current = isset( $values[ $key ] ) ? $values[ $key ] : $v['default'];
			$css    .= sprintf( "\t/* %s */\n\t/* --um-%s: %s; */\n", $v['label'], $key, '' === $current ? '(Original)' : $current );
		}
		$css .= "}\n\n";

		foreach ( $groups as $gkey => $g ) {
			$items = array_filter(
				$data['classes'],
				static function ( $c ) use ( $gkey ) {
					return $c['group'] === $gkey;
				}
			);
			if ( empty( $items ) ) {
				continue;
			}
			$css .= '/* ==== ' . $g['label'] . ' (' . count( $items ) . ") ==== */\n\n";
			foreach ( $items as $class => $info ) {
				$where = array_merge( $info['css'], $info['used'] );
				$css  .= '/* ' . implode( ', ', array_slice( $where, 0, 4 ) ) . ( count( $where ) > 4 ? ', …' : '' ) . " */\n";
				$css  .= '.' . $class . " {\n}\n\n";
			}
		}
		return $css;
	}
}
