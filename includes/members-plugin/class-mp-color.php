<?php
/**
 * Members Plugin – Farb-Hilfsfunktionen (Hex parsen, mischen, Kontrast).
 *
 * @package MembersPlugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class MP_Color
 */
final class MP_Color {

	/**
	 * Wandelt eine CSS-Farbe (#rgb, #rrggbb, rgb(), rgba()) in [r,g,b] um.
	 *
	 * @param string $color Farbe.
	 * @return int[]|null
	 */
	public static function to_rgb( $color ) {
		if ( ! is_string( $color ) ) {
			return null;
		}
		$color = strtolower( trim( $color ) );

		if ( preg_match( '/^#([0-9a-f]{3,8})$/', $color, $m ) ) {
			$hex = $m[1];
			if ( 3 === strlen( $hex ) || 4 === strlen( $hex ) ) {
				$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
			}
			if ( strlen( $hex ) < 6 ) {
				return null;
			}
			return array( hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ) );
		}

		if ( preg_match( '/^rgba?\(\s*(\d{1,3})[\s,]+(\d{1,3})[\s,]+(\d{1,3})/', $color, $m ) ) {
			return array( min( 255, (int) $m[1] ), min( 255, (int) $m[2] ), min( 255, (int) $m[3] ) );
		}

		$named = array(
			'white' => array( 255, 255, 255 ),
			'black' => array( 0, 0, 0 ),
		);
		return isset( $named[ $color ] ) ? $named[ $color ] : null;
	}

	/**
	 * Normalisiert eine Farbe zu #rrggbb oder null.
	 *
	 * @param string $color Farbe.
	 * @return string|null
	 */
	public static function to_hex( $color ) {
		$rgb = self::to_rgb( $color );
		if ( ! $rgb ) {
			return null;
		}
		return sprintf( '#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2] );
	}

	/**
	 * Mischt zwei Farben. $weight = Anteil von $a (0..1).
	 *
	 * @param string $a      Farbe A.
	 * @param string $b      Farbe B.
	 * @param float  $weight Anteil A.
	 * @return string
	 */
	public static function mix( $a, $b, $weight ) {
		$ra = self::to_rgb( $a );
		$rb = self::to_rgb( $b );
		if ( ! $ra || ! $rb ) {
			return self::to_hex( $a ) ? self::to_hex( $a ) : '#000000';
		}
		$w   = max( 0, min( 1, (float) $weight ) );
		$out = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$out[] = (int) round( $ra[ $i ] * $w + $rb[ $i ] * ( 1 - $w ) );
		}
		return sprintf( '#%02x%02x%02x', $out[0], $out[1], $out[2] );
	}

	/**
	 * Relative Luminanz nach WCAG 2.x.
	 *
	 * @param string $color Farbe.
	 * @return float
	 */
	public static function luminance( $color ) {
		$rgb = self::to_rgb( $color );
		if ( ! $rgb ) {
			return 1.0;
		}
		$c = array();
		foreach ( $rgb as $v ) {
			$v   = $v / 255;
			$c[] = ( $v <= 0.03928 ) ? $v / 12.92 : pow( ( $v + 0.055 ) / 1.055, 2.4 );
		}
		return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
	}

	/**
	 * Kontrastverhältnis zweier Farben (1..21).
	 *
	 * @param string $a Farbe A.
	 * @param string $b Farbe B.
	 * @return float
	 */
	public static function contrast( $a, $b ) {
		$la = self::luminance( $a );
		$lb = self::luminance( $b );
		return ( max( $la, $lb ) + 0.05 ) / ( min( $la, $lb ) + 0.05 );
	}

	/**
	 * Lesbare Textfarbe (aus $candidates) auf einem Hintergrund.
	 *
	 * @param string   $bg         Hintergrund.
	 * @param string[] $candidates Kandidaten.
	 * @return string
	 */
	public static function readable_on( $bg, $candidates = array( '#ffffff', '#111111' ) ) {
		$best       = $candidates[0];
		$best_ratio = 0;
		foreach ( $candidates as $c ) {
			$r = self::contrast( $bg, $c );
			if ( $r > $best_ratio ) {
				$best_ratio = $r;
				$best       = $c;
			}
		}
		return self::to_hex( $best );
	}

	/**
	 * Sättigung (HSL) 0..1.
	 *
	 * @param string $color Farbe.
	 * @return float
	 */
	public static function saturation( $color ) {
		$rgb = self::to_rgb( $color );
		if ( ! $rgb ) {
			return 0.0;
		}
		$max = max( $rgb ) / 255;
		$min = min( $rgb ) / 255;
		$l   = ( $max + $min ) / 2;
		if ( $max === $min ) {
			return 0.0;
		}
		$d = $max - $min;
		return $l > 0.5 ? $d / ( 2 - $max - $min ) : $d / ( $max + $min );
	}

	/**
	 * Hover-Variante: dunkle Farben werden heller, helle dunkler.
	 *
	 * @param string $color Farbe.
	 * @param float  $amount Stärke 0..1.
	 * @return string
	 */
	public static function hover( $color, $amount = 0.14 ) {
		$toward = self::luminance( $color ) < 0.18 ? '#ffffff' : '#000000';
		return self::mix( $color, $toward, 1 - $amount );
	}

	/**
	 * Prüft, ob eine Farbe "dunkel" ist.
	 *
	 * @param string $color Farbe.
	 * @return bool
	 */
	public static function is_dark( $color ) {
		return self::luminance( $color ) < 0.2;
	}
}
