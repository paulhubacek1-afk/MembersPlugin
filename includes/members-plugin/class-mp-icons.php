<?php
/**
 * Members Plugin – use Font Awesome 5 for icons.
 *
 * The bundled membership core ships Font Awesome 6 and, inside `.um` containers,
 * overrides the `.fas/.far/.fab` font families of the site's Font Awesome 5. Icons of
 * the theme and of add-ons (for example the Stückverwaltung) then render with the wrong
 * font. With this option (Design & CSS, on by default) the core's Font Awesome
 * stylesheet is replaced by a bundled Font Awesome 5.15.4 Free (incl. v4 shims) plus a
 * mapping of the Font Awesome 6 icon names used by the core to Font Awesome 5 glyphs.
 *
 * @package MembersPlugin
 */

defined( 'ABSPATH' ) || exit;

final class MP_Icons {

	const FA5_VERSION = '5.15.4';

	/**
	 * Style handles that load the core's Font Awesome 6.
	 *
	 * @var string[]
	 */
	private static $handles = array( 'um_fontawesome', 'mp-prev-um-fa' );

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'wp_print_styles', array( __CLASS__, 'swap' ), 1 );
		add_action( 'admin_print_styles', array( __CLASS__, 'swap' ), 1 );
		add_action( 'wp_print_footer_scripts', array( __CLASS__, 'swap' ), 1 );
		add_action( 'admin_print_footer_scripts', array( __CLASS__, 'swap' ), 1 );
	}

	/**
	 * Is Font Awesome 5 mode enabled?
	 *
	 * @return bool
	 */
	public static function enabled() {
		$opts = MP_Design::get_options();
		return ! empty( $opts['fontawesome5'] );
	}

	/**
	 * URL of the bundled Font Awesome 5 stylesheet.
	 *
	 * @return string
	 */
	public static function stylesheet_url() {
		return UM_URL . 'assets/libs/fontawesome5/css/mp-fontawesome5.min.css';
	}

	/**
	 * Point the core's Font Awesome handles to Font Awesome 5.
	 */
	public static function swap() {
		if ( ! self::enabled() ) {
			return;
		}
		$styles = wp_styles();
		foreach ( self::$handles as $handle ) {
			if ( isset( $styles->registered[ $handle ] ) ) {
				$styles->registered[ $handle ]->src = self::stylesheet_url();
				$styles->registered[ $handle ]->ver = self::FA5_VERSION . '-mp' . ( defined( 'MP_VERSION' ) ? MP_VERSION : '' );
			}
		}
	}
}
