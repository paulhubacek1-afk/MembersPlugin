<?php
/**
 * Members Plugin – liest das Farbspektrum des aktiven Themes aus.
 *
 * Quellen (in dieser Reihenfolge):
 *  1. Globale Stile / theme.json (Block-Themes und Classic-Themes mit theme.json):
 *     Button-, Link-, Text-, Hintergrund- und Überschriftenfarben.
 *  2. Farbpalette des Themes (theme.json "palette" bzw. editor-color-palette)
 *     mit Slug-Erkennung (primary, accent, contrast, base …) und bekannten
 *     Paletten von Astra, Kadence, Blocksy, GeneratePress, Twenty-Themes.
 *  3. Page-Builder/Theme-Optionen: Elementor Kit, OceanWP, Divi, Customizer-Hintergrund.
 *  4. Fallback: Originalfarben von Ultimate Member.
 *
 * @package MembersPlugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class MP_Theme_Colors
 */
final class MP_Theme_Colors {

	/**
	 * Laufzeit-Cache.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Gibt die erkannten Basisfarben zurück.
	 *
	 * @return array{primary:?string,primary_hover:?string,on_primary:?string,text:?string,heading:?string,bg:?string,button_radius:?string,font_family:?string,sources:array}
	 */
	public static function detect() {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		// Members Plugin: theme detection walks theme.json, palettes and builder options
		// on every page view. Cache the result; it is flushed on theme/style changes.
		$transient_key = self::transient_key();
		if ( ! is_admin() ) {
			$stored = get_transient( $transient_key );
			if ( is_array( $stored ) && array_key_exists( 'primary', $stored ) ) {
				self::$cache = $stored;
				return self::$cache;
			}
		}

		self::$cache = self::detect_uncached();
		if ( ! is_admin() ) {
			set_transient( $transient_key, self::$cache, 12 * HOUR_IN_SECONDS );
		}

		return self::$cache;
	}

	/**
	 * Cache key for the detected colors (changes with the active theme and its version).
	 *
	 * @return string
	 */
	private static function transient_key() {
		$theme = wp_get_theme();
		return 'mp_theme_colors_' . md5( get_stylesheet() . '|' . get_template() . '|' . $theme->get( 'Version' ) . '|' . ( defined( 'MP_VERSION' ) ? MP_VERSION : '' ) );
	}

	/**
	 * Performs the actual detection.
	 *
	 * @return array
	 */
	private static function detect_uncached() {
		$palette = self::get_palette();
		$found   = array(
			'primary'       => null,
			'primary_hover' => null,
			'on_primary'    => null,
			'text'          => null,
			'heading'       => null,
			'bg'            => null,
			'button_radius' => null,
			'font_family'   => null,
			'sources'       => array(),
		);

		// 1. Globale Stile (theme.json + Anpassungen im Website-Editor).
		if ( function_exists( 'wp_get_global_styles' ) ) {
			$map = array(
				'primary'       => array( array( 'elements', 'button', 'color', 'background' ), array( 'blocks', 'core/button', 'color', 'background' ) ),
				'on_primary'    => array( array( 'elements', 'button', 'color', 'text' ), array( 'blocks', 'core/button', 'color', 'text' ) ),
				'primary_hover' => array( array( 'elements', 'button', ':hover', 'color', 'background' ) ),
				'text'          => array( array( 'color', 'text' ) ),
				'bg'            => array( array( 'color', 'background' ) ),
				'heading'       => array( array( 'elements', 'heading', 'color', 'text' ), array( 'elements', 'h2', 'color', 'text' ) ),
			);
			foreach ( $map as $key => $paths ) {
				foreach ( $paths as $path ) {
					$value = self::resolve( self::global_style( $path ), $palette );
					if ( $value ) {
						$found[ $key ]            = $value;
						$found['sources'][ $key ] = 'theme.json: ' . implode( ' › ', $path );
						break;
					}
				}
			}

			// Link-Farbe als Primärfarbe, falls der Button keine eigene Farbe hat.
			if ( ! $found['primary'] ) {
				$link = self::resolve( self::global_style( array( 'elements', 'link', 'color', 'text' ) ), $palette );
				if ( $link && MP_Color::saturation( $link ) > 0.25 ) {
					$found['primary']            = $link;
					$found['sources']['primary'] = 'theme.json: elements › link';
				}
			}

			$radius = self::global_style( array( 'elements', 'button', 'border', 'radius' ) );
			if ( is_string( $radius ) && preg_match( '/^\d+(\.\d+)?(px|rem|em|%)$/', trim( $radius ) ) ) {
				$found['button_radius']            = trim( $radius );
				$found['sources']['button_radius'] = 'theme.json: elements › button › border › radius';
			}

			$font = self::global_style( array( 'typography', 'fontFamily' ) );
			if ( is_string( $font ) && '' !== $font ) {
				$found['font_family']            = self::resolve_font( $font );
				$found['sources']['font_family'] = 'theme.json: typography › fontFamily';
			}
		}

		// Hintergrundfarbe aus dem Customizer (Classic-Themes) – vor der Palette, weil sie die echte Seitenfarbe ist.
		if ( ! $found['bg'] && ! wp_is_block_theme() && current_theme_supports( 'custom-background' ) ) {
			$bg = get_background_color();
			if ( is_string( $bg ) && '' !== $bg ) {
				$hex = MP_Color::to_hex( '#' . ltrim( $bg, '#' ) );
				if ( $hex ) {
					$found['bg']            = $hex;
					$found['sources']['bg'] = 'Customizer: background_color';
				}
			}
		}

		// 2. Palette (Slugs + bekannte Themes).
		$roles = self::palette_roles( $palette );
		foreach ( array( 'primary', 'text', 'heading', 'bg' ) as $key ) {
			if ( ! $found[ $key ] && ! empty( $roles[ $key ] ) ) {
				$found[ $key ]            = $roles[ $key ]['color'];
				$found['sources'][ $key ] = sprintf( 'Palette: %s', $roles[ $key ]['slug'] );
			}
		}

		// 3. Theme-/Builder-Optionen.
		$builder = self::builder_colors();
		foreach ( $builder as $key => $info ) {
			// Elementor-Kits haben Vorrang vor generischen Paletten, aber nicht vor theme.json-Stilen.
			$from_palette = isset( $found['sources'][ $key ] ) && 0 === strpos( $found['sources'][ $key ], 'Palette' );
			if ( ( ! $found[ $key ] || $from_palette ) && ! empty( $info['color'] ) ) {
				$found[ $key ]            = $info['color'];
				$found['sources'][ $key ] = $info['source'];
			}
		}

		// Primärfarbe heuristisch: gesättigtste Palettenfarbe.
		if ( ! $found['primary'] && ! empty( $palette ) ) {
			$best = null;
			$sat  = 0.35;
			foreach ( $palette as $entry ) {
				$s = MP_Color::saturation( $entry['color'] );
				$l = MP_Color::luminance( $entry['color'] );
				if ( $s > $sat && $l > 0.03 && $l < 0.8 ) {
					$sat  = $s;
					$best = $entry;
				}
			}
			if ( $best ) {
				$found['primary']            = $best['color'];
				$found['sources']['primary'] = sprintf( 'Palette (gesättigtste Farbe): %s', $best['slug'] );
			}
		}

		// Theme ohne Akzentfarbe (monochrom): Buttons in Überschriften-/Textfarbe wie beim Theme.
		if ( ! $found['primary'] && ! empty( $palette ) && ( $found['heading'] || $found['text'] ) ) {
			$found['primary']            = $found['heading'] ? $found['heading'] : $found['text'];
			$found['sources']['primary'] = __( 'Theme ohne Akzentfarbe: Text-/Überschriftenfarbe', 'ultimate-member' );
		}

		/**
		 * Filtert die aus dem Theme erkannten Farben.
		 *
		 * @param array $found   Erkannte Werte.
		 * @param array $palette Theme-Palette.
		 */
		return apply_filters( 'mp_detected_theme_colors', $found, $palette );
	}

	/**
	 * Liefert die Farbpalette des Themes (Theme + benutzerdefiniert, ohne WP-Standardpalette).
	 *
	 * @return array<int,array{slug:string,name:string,color:string}>
	 */
	public static function get_palette() {
		$out = array();

		if ( function_exists( 'wp_get_global_settings' ) ) {
			$palette = wp_get_global_settings( array( 'color', 'palette' ) );
			if ( is_array( $palette ) ) {
				// Reihenfolge: benutzerdefiniert überschreibt Theme.
				foreach ( array( 'theme', 'custom' ) as $origin ) {
					if ( empty( $palette[ $origin ] ) || ! is_array( $palette[ $origin ] ) ) {
						continue;
					}
					foreach ( $palette[ $origin ] as $entry ) {
						if ( empty( $entry['slug'] ) || empty( $entry['color'] ) ) {
							continue;
						}
						$hex = MP_Color::to_hex( $entry['color'] );
						if ( ! $hex ) {
							continue;
						}
						$out[ $entry['slug'] ] = array(
							'slug'  => (string) $entry['slug'],
							'name'  => isset( $entry['name'] ) ? (string) $entry['name'] : (string) $entry['slug'],
							'color' => $hex,
						);
					}
				}
			}
		}

		if ( empty( $out ) ) {
			$support = get_theme_support( 'editor-color-palette' );
			if ( is_array( $support ) && ! empty( $support[0] ) && is_array( $support[0] ) ) {
				foreach ( $support[0] as $entry ) {
					if ( empty( $entry['slug'] ) || empty( $entry['color'] ) ) {
						continue;
					}
					$hex = MP_Color::to_hex( $entry['color'] );
					if ( $hex ) {
						$out[ $entry['slug'] ] = array(
							'slug'  => (string) $entry['slug'],
							'name'  => isset( $entry['name'] ) ? (string) $entry['name'] : (string) $entry['slug'],
							'color' => $hex,
						);
					}
				}
			}
		}

		return array_values( $out );
	}

	/**
	 * Liest einen Wert aus den globalen Stilen.
	 *
	 * @param string[] $path Pfad.
	 * @return mixed
	 */
	private static function global_style( $path ) {
		static $styles = null;
		if ( null === $styles ) {
			$styles = wp_get_global_styles();
		}
		$value = self::walk( $styles, $path );
		if ( null === $value ) {
			return null;
		}
		// Werte, die nur aus dem WordPress-Core-Standard stammen (z.B. Button #32373c), sind keine Theme-Farben.
		if ( $value === self::walk( self::core_styles(), $path ) ) {
			return null;
		}
		return $value;
	}

	/**
	 * Standard-Stile aus dem WordPress-Core-theme.json.
	 *
	 * @return array
	 */
	private static function core_styles() {
		static $core = null;
		if ( null === $core ) {
			$core = array();
			if ( class_exists( 'WP_Theme_JSON_Resolver' ) ) {
				$raw  = WP_Theme_JSON_Resolver::get_core_data()->get_raw_data();
				$core = isset( $raw['styles'] ) && is_array( $raw['styles'] ) ? $raw['styles'] : array();
			}
		}
		return $core;
	}

	/**
	 * Pfad in verschachteltem Array lesen.
	 *
	 * @param mixed    $data Daten.
	 * @param string[] $path Pfad.
	 * @return mixed
	 */
	private static function walk( $data, $path ) {
		foreach ( $path as $p ) {
			if ( ! is_array( $data ) || ! isset( $data[ $p ] ) ) {
				return null;
			}
			$data = $data[ $p ];
		}
		return $data;
	}

	/**
	 * Löst Werte wie "var:preset|color|primary", "var(--wp--preset--color--primary)" oder Hex auf.
	 *
	 * @param mixed $value   Wert.
	 * @param array $palette Palette.
	 * @return string|null
	 */
	private static function resolve( $value, $palette ) {
		if ( ! is_string( $value ) || '' === $value ) {
			return null;
		}
		$value = trim( $value );
		$slug  = null;

		if ( preg_match( '/^var:preset\|color\|([a-z0-9_-]+)$/i', $value, $m ) ) {
			$slug = $m[1];
		} elseif ( preg_match( '/var\(\s*--wp--preset--color--([a-z0-9_-]+)/i', $value, $m ) ) {
			$slug = $m[1];
		}

		if ( $slug ) {
			foreach ( $palette as $entry ) {
				if ( $entry['slug'] === $slug || _wp_to_kebab_case( $entry['slug'] ) === $slug ) {
					return $entry['color'];
				}
			}
			return null;
		}

		return MP_Color::to_hex( $value );
	}

	/**
	 * Löst eine Schriftfamilien-Referenz auf (Preset -> CSS-Variable).
	 *
	 * @param string $font Wert.
	 * @return string
	 */
	private static function resolve_font( $font ) {
		if ( preg_match( '/^var:preset\|font-family\|([a-z0-9_-]+)$/i', $font, $m ) ) {
			return 'var(--wp--preset--font-family--' . $m[1] . ')';
		}
		return $font;
	}

	/**
	 * Ordnet Palettenfarben Rollen zu.
	 *
	 * @param array $palette Palette.
	 * @return array
	 */
	private static function palette_roles( $palette ) {
		$by_slug = array();
		foreach ( $palette as $entry ) {
			$by_slug[ strtolower( $entry['slug'] ) ] = $entry;
		}

		// Bekannte Theme-Paletten.
		$known = array(
			// Astra.
			array(
				'primary' => 'ast-global-color-0',
				'heading' => 'ast-global-color-2',
				'text'    => 'ast-global-color-3',
				'bg'      => 'ast-global-color-5',
			),
			// Kadence.
			array(
				'primary' => 'theme-palette1',
				'heading' => 'theme-palette3',
				'text'    => 'theme-palette4',
				'bg'      => 'theme-palette9',
			),
			// Blocksy.
			array(
				'primary' => 'palette-color-1',
				'heading' => 'palette-color-4',
				'text'    => 'palette-color-3',
				'bg'      => 'palette-color-8',
			),
			// GeneratePress.
			array(
				'primary' => 'accent',
				'heading' => 'contrast',
				'text'    => 'contrast',
				'bg'      => 'base-3',
			),
		);
		// Generische Slug-Erkennung.
		$patterns = array(
			'primary' => array( 'primary', 'accent', 'accent-1', 'brand', 'main', 'theme', 'secondary', 'vivid' ),
			'text'    => array( 'contrast', 'foreground', 'text', 'body', 'dark', 'black', 'main-text' ),
			'heading' => array( 'heading', 'headings', 'contrast', 'foreground', 'dark' ),
			'bg'      => array( 'base', 'background', 'bg', 'white', 'light', 'body-bg' ),
		);
		$roles    = array();
		foreach ( $patterns as $role => $slugs ) {
			foreach ( $slugs as $slug ) {
				if ( isset( $by_slug[ $slug ] ) ) {
					$roles[ $role ] = $by_slug[ $slug ];
					break;
				}
			}
			if ( ! isset( $roles[ $role ] ) ) {
				foreach ( $by_slug as $s => $entry ) {
					foreach ( $slugs as $slug ) {
						if ( false !== strpos( $s, $slug ) ) {
							$roles[ $role ] = $entry;
							break 2;
						}
					}
				}
			}
		}

		// Bekannte Theme-Paletten überschreiben die generische Erkennung.
		foreach ( $known as $set ) {
			if ( ! isset( $by_slug[ $set['primary'] ] ) ) {
				continue;
			}
			$matched = 0;
			foreach ( $set as $slug ) {
				$matched += isset( $by_slug[ $slug ] ) ? 1 : 0;
			}
			if ( $matched < 3 ) {
				continue;
			}
			foreach ( $set as $role => $slug ) {
				if ( isset( $by_slug[ $slug ] ) ) {
					$roles[ $role ] = $by_slug[ $slug ];
				}
			}
			break;
		}

		// Primärfarbe darf nicht identisch mit Text/Hintergrund sein (z.B. "theme-dark").
		if ( isset( $roles['primary'] ) && MP_Color::saturation( $roles['primary']['color'] ) < 0.1 ) {
			unset( $roles['primary'] );
		}
		return $roles;
	}

	/**
	 * Farben aus Page-Buildern / Theme-Optionen.
	 *
	 * @return array
	 */
	private static function builder_colors() {
		$out = array();

		// Elementor: aktives Kit -> System-Farben.
		$kit_id = (int) get_option( 'elementor_active_kit' );
		if ( $kit_id ) {
			$settings = get_post_meta( $kit_id, '_elementor_page_settings', true );
			if ( is_array( $settings ) && ! empty( $settings['system_colors'] ) && is_array( $settings['system_colors'] ) ) {
				$roles = array(
					'primary' => array( 'accent', 'primary' ),
					'text'    => array( 'text' ),
					'heading' => array( 'secondary' ),
				);
				$sys   = array();
				foreach ( $settings['system_colors'] as $c ) {
					if ( isset( $c['_id'], $c['color'] ) ) {
						$sys[ $c['_id'] ] = MP_Color::to_hex( $c['color'] );
					}
				}
				foreach ( $roles as $role => $ids ) {
					foreach ( $ids as $id ) {
						if ( ! empty( $sys[ $id ] ) ) {
							$out[ $role ] = array(
								'color'  => $sys[ $id ],
								'source' => 'Elementor Kit: ' . $id,
							);
							break;
						}
					}
				}
			}
		}

		// OceanWP.
		$ocean = get_theme_mod( 'ocean_primary_color' );
		if ( ! isset( $out['primary'] ) && is_string( $ocean ) && MP_Color::to_hex( $ocean ) ) {
			$out['primary'] = array(
				'color'  => MP_Color::to_hex( $ocean ),
				'source' => 'OceanWP: ocean_primary_color',
			);
		}

		// Divi.
		if ( ! isset( $out['primary'] ) && function_exists( 'et_get_option' ) ) {
			$accent = et_get_option( 'accent_color' );
			if ( is_string( $accent ) && MP_Color::to_hex( $accent ) ) {
				$out['primary'] = array(
					'color'  => MP_Color::to_hex( $accent ),
					'source' => 'Divi: accent_color',
				);
			}
		}

		return $out;
	}

	/**
	 * Cache leeren (z.B. nach Theme-Wechsel).
	 */
	public static function flush() {
		self::$cache = null;
		delete_transient( self::transient_key() );
	}

	/**
	 * Hooks that invalidate the cached theme colors.
	 */
	public static function register_cache_invalidation() {
		foreach ( array( 'switch_theme', 'customize_save_after', 'save_post_wp_global_styles', 'upgrader_process_complete' ) as $hook ) {
			add_action( $hook, array( __CLASS__, 'flush' ), 10, 0 );
		}
		foreach ( array( 'elementor_active_kit', 'theme_mods_' . get_option( 'stylesheet' ) ) as $option ) {
			add_action( 'update_option_' . $option, array( __CLASS__, 'flush' ), 10, 0 );
		}
	}
}
