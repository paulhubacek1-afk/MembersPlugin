<?php
/**
 * Members Plugin – zusätzliche Sicherheitshärtung zur Laufzeit.
 *
 * Ergänzt die direkten Code-Fixes im Fork (siehe SECURITY.md) um Schutzmaßnahmen,
 * die zentral an WordPress-Hooks hängen.
 *
 * @package MembersPlugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class MP_Security
 */
final class MP_Security {

	/**
	 * Hooks.
	 */
	public static function init() {
		// Meta-Key-Whitelist auch für neu angelegte Meta-Einträge erzwingen (nicht nur für Updates).
		add_filter( 'add_user_metadata', array( __CLASS__, 'guard_add_user_meta' ), 10, 3 );

		// Admin-Host zusätzlich zu home_url() für interne Weiterleitungen erlauben.
		add_filter( 'allowed_redirect_hosts', array( __CLASS__, 'allowed_hosts' ) );

		// Clickjacking-Schutz und Referrer-Policy für Login, Registrierung, Konto, Passwort.
		add_action( 'template_redirect', array( __CLASS__, 'security_headers' ), 1 );
	}

	/**
	 * Während einer UM-Formularübermittlung keine gesperrten/unerlaubten Meta-Keys anlegen.
	 *
	 * @param null|bool $check     Kurzschluss-Wert.
	 * @param int       $object_id Benutzer-ID.
	 * @param string    $meta_key  Meta-Key.
	 * @return null|bool
	 */
	public static function guard_add_user_meta( $check, $object_id, $meta_key ) {
		if ( ! function_exists( 'UM' ) ) {
			return $check;
		}
		$user = UM()->user();
		if ( empty( $user->updating_process ) ) {
			return $check;
		}
		if ( $user->is_metakey_banned( $meta_key, 'submission' ) ) {
			return false;
		}
		return $check;
	}

	/**
	 * Erlaubte Hosts für wp_safe_redirect().
	 *
	 * @param string[] $hosts Hosts.
	 * @return string[]
	 */
	public static function allowed_hosts( $hosts ) {
		$site = wp_parse_url( site_url(), PHP_URL_HOST );
		if ( $site && ! in_array( $site, $hosts, true ) ) {
			$hosts[] = $site;
		}
		return $hosts;
	}

	/**
	 * Sicherheits-Header auf sensiblen UM-Seiten.
	 */
	public static function security_headers() {
		if ( headers_sent() || ! function_exists( 'um_is_core_page' ) ) {
			return;
		}
		$sensitive = false;
		foreach ( array( 'login', 'register', 'account', 'password-reset' ) as $page ) {
			if ( um_is_core_page( $page ) ) {
				$sensitive = true;
				break;
			}
		}
		/**
		 * Filtert, ob auf der aktuellen Seite Sicherheits-Header gesendet werden.
		 *
		 * @param bool $sensitive Sensible Seite.
		 */
		if ( ! apply_filters( 'mp_send_security_headers', $sensitive ) ) {
			return;
		}
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	}
}
