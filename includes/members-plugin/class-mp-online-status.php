<?php
/**
 * Members Plugin – built-in online member list.
 *
 * A user's last-seen timestamp is updated at most once per minute. The
 * [mp_online_users] shortcode is restricted to logged-in visitors.
 *
 * @package MembersPlugin
 */

defined( 'ABSPATH' ) || exit;

final class MP_Online_Status {

	const META_KEY = '_mp_last_seen_at';
	const DEFAULT_WINDOW = 300;

	/**
	 * Register tracking and the built-in shortcode.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'mark_current_user_online' ), 50 );
		add_shortcode( 'mp_online_users', array( __CLASS__, 'render_online_users' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_styles' ) );
	}

	/**
	 * Update last-seen information without writing to the database on every request.
	 */
	public static function mark_current_user_online() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$user_id = get_current_user_id();
		$now     = time();
		$last    = absint( get_user_meta( $user_id, self::META_KEY, true ) );

		if ( $last > 0 && ( $now - $last ) < MINUTE_IN_SECONDS ) {
			return;
		}

		update_user_meta( $user_id, self::META_KEY, $now );
	}

	/**
	 * Register CSS; the file is enqueued only when the shortcode is rendered.
	 */
	public static function register_styles() {
		if ( defined( 'MP_URL' ) ) {
			wp_register_style(
				'mp-online-members',
				MP_URL . 'assets/online.css',
				array(),
				defined( 'MP_VERSION' ) ? MP_VERSION : null
			);
		}
	}

	/**
	 * Render a member-only list of users seen in the last five minutes.
	 *
	 * Usage: [mp_online_users] or [mp_online_users limit="20"].
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_online_users( $atts = array() ) {
		if ( ! is_user_logged_in() ) {
			return '<p class="mp-online-notice">' . esc_html__( 'Bitte anmelden, um die Online-Mitglieder zu sehen.', 'ultimate-member' ) . '</p>';
		}

		$atts  = shortcode_atts( array( 'limit' => 12 ), is_array( $atts ) ? $atts : array(), 'mp_online_users' );
		$limit = absint( $atts['limit'] );
		$limit = min( 50, max( 1, $limit ) );

		$window = (int) apply_filters( 'mp_online_status_window', self::DEFAULT_WINDOW );
		$window = max( MINUTE_IN_SECONDS, $window );
		$cutoff = time() - $window;

		$users = get_users(
			array(
				'number'     => $limit,
				'orderby'    => 'meta_value_num',
				'order'      => 'DESC',
				'meta_key'   => self::META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_query' => array(
					array(
						'key'     => self::META_KEY,
						'value'   => $cutoff,
						'compare' => '>=',
						'type'    => 'NUMERIC',
					),
				),
				'fields'     => 'all',
			)
		);

		if ( empty( $users ) ) {
			return '<div class="mp-online-members"><p class="mp-online-empty">' . esc_html__( 'Momentan ist niemand online.', 'ultimate-member' ) . '</p></div>';
		}

		wp_enqueue_style( 'mp-online-members' );

		$output = '<div class="mp-online-members"><ul class="mp-online-members__list" role="list">';
		foreach ( $users as $user ) {
			if ( ! $user instanceof WP_User ) {
				continue;
			}

			$name   = $user->display_name ? $user->display_name : $user->user_login;
			$avatar = get_avatar( $user->ID, 44, '', $name, array( 'class' => 'mp-online-members__avatar' ) );

			$output .= '<li class="mp-online-members__item">';
			$output .= '<span class="mp-online-members__avatar-wrap">' . $avatar . '<span class="mp-online-members__dot" aria-label="' . esc_attr__( 'Online', 'ultimate-member' ) . '"></span></span>';
			$output .= '<span class="mp-online-members__name">' . esc_html( $name ) . '</span>';
			$output .= '</li>';
		}
		$output .= '</ul></div>';

		return $output;
	}
}
