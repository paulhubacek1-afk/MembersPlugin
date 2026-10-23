<?php
/**
 * Members Plugin – birthday celebration on the member profile.
 *
 * The built-in birth_date field is stored by the membership form as Y/m/d.
 * A cake and a short CSS confetti animation are displayed on that member's
 * profile on their birthday. No shortcode or external library is required.
 *
 * @package MembersPlugin
 */

defined( 'ABSPATH' ) || exit;

final class MP_Birthday_Celebration {

	/**
	 * Register the profile hook.
	 */
	public static function init() {
		add_action( 'um_after_header_info', array( __CLASS__, 'render' ), 20, 2 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
	}

	/**
	 * Enqueue the small celebration stylesheet before the document head is printed.
	 */
	public static function enqueue_styles() {
		if ( defined( 'MP_URL' ) ) {
			wp_enqueue_style(
				'mp-birthday-celebration',
				MP_URL . 'assets/birthday.css',
				array(),
				defined( 'MP_VERSION' ) ? MP_VERSION : null
			);
		}
	}

	/**
	 * Display the birthday celebration when the profile owner's birthday is today.
	 *
	 * @param int   $user_id Profile owner ID.
	 * @param array $args    Profile template arguments.
	 */
	public static function render( $user_id, $args = array() ) {
		$user_id = absint( $user_id );
		if ( ! $user_id || um_is_on_edit_profile() ) {
			return;
		}

		$birthday = self::get_birthday( $user_id );
		if ( ! $birthday ) {
			return;
		}

		$today = wp_date( 'm-d', time(), wp_timezone() );
		if ( $birthday->format( 'm-d' ) !== $today ) {
			return;
		}

		$user = get_userdata( $user_id );
		if ( ! $user instanceof WP_User ) {
			return;
		}

		$name = $user->display_name ? $user->display_name : $user->user_login;

		echo '<section class="mp-birthday-celebration" aria-live="polite" role="status">';
		echo '<div class="mp-birthday-celebration__confetti" aria-hidden="true">';
		for ( $i = 0; $i < 32; $i++ ) {
			$left  = ( $i * 37 ) % 100;
			$delay = -1 * ( ( $i * 7 ) % 18 ) / 10;
			$turn  = ( $i * 53 ) % 360;
			printf(
				'<span class="mp-birthday-confetti-piece" style="--mp-confetti-left:%1$d%%;--mp-confetti-delay:%2$ss;--mp-confetti-rotate:%3$ddeg"></span>',
				$left,
				esc_attr( number_format( $delay, 1, '.', '' ) ),
				$turn
			);
		}
		echo '</div>';
		echo '<div class="mp-birthday-celebration__content">';
		echo '<span class="mp-birthday-celebration__cake" role="img" aria-label="' . esc_attr__( 'Geburtstagskuchen', 'ultimate-member' ) . '">🎂</span>';
		echo '<div class="mp-birthday-celebration__text">';
		printf(
			'<h3>%1$s</h3>',
			esc_html(
				sprintf(
					/* translators: %s: profile display name */
					__( 'Alles Gute zum Geburtstag, %s!', 'ultimate-member' ),
					$name
				)
			)
		);
		echo '<p>' . esc_html__( 'Wir wünschen dir einen wundervollen Tag!', 'ultimate-member' ) . '</p>';
		echo '</div></div></section>';
	}

	/**
	 * Read the built-in or a similarly named custom birthday field.
	 *
	 * @param int $user_id User ID.
	 * @return DateTimeImmutable|null
	 */
	private static function get_birthday( $user_id ) {
		$all_meta = get_user_meta( $user_id );
		if ( ! is_array( $all_meta ) ) {
			return null;
		}

		$priority_keys = array(
			'birth_date',
			'birthdate',
			'birthday',
			'date_of_birth',
			'date_birth',
			'geburtsdatum',
			'geburtstag',
			'dob',
		);

		foreach ( $all_meta as $key => $values ) {
			if ( ! in_array( $key, $priority_keys, true ) && ! preg_match( '/birth|geburt|(?:^|_)dob(?:_|$)/i', (string) $key ) ) {
				continue;
			}

			if ( ! is_array( $values ) || empty( $values[0] ) || ! is_scalar( $values[0] ) ) {
				continue;
			}

			$parsed = self::parse_date( (string) $values[0] );
			if ( $parsed ) {
				return $parsed;
			}
		}

		return null;
	}

	/**
	 * Parse the common formats used by HTML date inputs and membership fields.
	 *
	 * @param string $value Stored birthday.
	 * @return DateTimeImmutable|null
	 */
	private static function parse_date( $value ) {
		$value = trim( $value );
		if ( '' === $value || strlen( $value ) > 40 ) {
			return null;
		}

		$timezone = wp_timezone();
		$formats  = array(
			array( '!Y/m/d', 'Y/m/d' ),
			array( '!Y-m-d', 'Y-m-d' ),
			array( '!Y.m.d', 'Y.m.d' ),
			array( '!d.m.Y', 'd.m.Y' ),
			array( '!d/m/Y', 'd/m/Y' ),
			array( '!d-m-Y', 'd-m-Y' ),
			array( '!Ymd', 'Ymd' ),
		);

		foreach ( $formats as $format ) {
			$date   = DateTimeImmutable::createFromFormat( $format[0], $value, $timezone );
			$errors = DateTimeImmutable::getLastErrors();

			if ( $date instanceof DateTimeImmutable && ( false === $errors || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) ) ) {
				return $date;
			}
		}

		return null;
	}
}
