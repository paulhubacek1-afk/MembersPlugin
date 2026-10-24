<?php
/**
 * Members Plugin – removes visible upstream (Ultimate Member) branding.
 *
 * The bundled membership core is a GPL fork of Ultimate Member. Internal names
 * (text domain, hooks, option keys, CSS classes) stay for compatibility, but no
 * user- or admin-visible text should show the upstream product name, its shop
 * links, review prompts or extension advertising.
 *
 * Licence credits in license.txt / readme.txt are intentionally kept (GPL).
 *
 * @package MembersPlugin
 */

defined( 'ABSPATH' ) || exit;

class MP_Branding {

	const BRAND = 'Members Plugin';

	public static function init() {
		add_filter( 'gettext', array( __CLASS__, 'filter_gettext' ), 20, 3 );
		add_filter( 'ngettext', array( __CLASS__, 'filter_ngettext' ), 20, 5 );
		add_filter( 'gettext_with_context', array( __CLASS__, 'filter_gettext_context' ), 20, 4 );

		// Extension shop, review prompt and upstream doc links.
		add_action( 'admin_menu', array( __CLASS__, 'remove_extension_menu' ), 10000 );
		add_filter( 'admin_footer_text', array( __CLASS__, 'footer_text' ), 1001 );
		add_action( 'um_admin_create_notices', array( __CLASS__, 'remove_upstream_notices' ), 999 );
	}

	/**
	 * Replace the upstream product name in strings of the bundled core.
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public static function rebrand( $text ) {
		if ( ! is_string( $text ) || '' === $text ) {
			return $text;
		}
		if ( false === stripos( $text, 'ultimate member' ) && false === stripos( $text, 'ultimatemember.com' ) ) {
			return $text;
		}

		$text = str_replace(
			array( 'Ultimate Member', 'ultimate member', 'ULTIMATE MEMBER' ),
			array( self::BRAND, self::BRAND, strtoupper( self::BRAND ) ),
			$text
		);

		// Drop links to the upstream shop/docs/feedback pages, keep the link text.
		$text = preg_replace(
			'#<a\s[^>]*href=(["\'])https?://(?:docs\.|www\.)?ultimatemember\.com[^"\']*\1[^>]*>(.*?)</a>#is',
			'$2',
			$text
		);

		return $text;
	}

	private static function is_core_domain( $domain ) {
		return 'ultimate-member' === $domain || 'members-plugin' === $domain || 'default' === $domain;
	}

	public static function filter_gettext( $translation, $text, $domain ) {
		return self::is_core_domain( $domain ) ? self::rebrand( $translation ) : $translation;
	}

	public static function filter_ngettext( $translation, $single, $plural, $number, $domain ) {
		return self::is_core_domain( $domain ) ? self::rebrand( $translation ) : $translation;
	}

	public static function filter_gettext_context( $translation, $text, $context, $domain ) {
		return self::is_core_domain( $domain ) ? self::rebrand( $translation ) : $translation;
	}

	/**
	 * The "Extensions" submenu is an advertising page for the upstream shop.
	 */
	public static function remove_extension_menu() {
		remove_submenu_page( 'ultimatemember', 'ultimatemember-extensions' );
	}

	/**
	 * Remove the "rate Ultimate Member ★★★★★" footer prompt on plugin admin pages.
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public static function footer_text( $text ) {
		if ( is_string( $text ) && false !== strpos( $text, 'um-admin-rating-link' ) ) {
			return '';
		}
		return $text;
	}

	/**
	 * Remove upstream marketing notices (All Access Pass, review/feedback requests).
	 */
	public static function remove_upstream_notices() {
		if ( ! function_exists( 'UM' ) ) {
			return;
		}
		foreach ( array( 'extensions_all_access', 'reviews_notice', 'future_changes' ) as $key ) {
			UM()->admin()->notices()->remove_notice( $key );
		}
	}
}
