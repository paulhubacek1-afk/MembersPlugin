<?php
/**
 * Members Plugin – online/offline presence indicators and activity tracking.
 *
 * Presence dots are added automatically to profile headers only. Member-directory
 * cards intentionally do not display online/offline status. Legacy list shortcodes remain supported.
 *
 * @package MembersPlugin
 */

defined( 'ABSPATH' ) || exit;

final class MP_Online_Status {

	const META_KEY = '_mp_last_seen_at';
	const DEFAULT_WINDOW = 300;

	/**
	 * Register activity tracking, automatic indicators, assets and AJAX endpoints.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'mark_current_user_online' ), 50 );
		add_action( 'um_after_profile_name_inline', array( __CLASS__, 'profile_name_indicator' ), 20, 2 );
		add_shortcode( 'mp_online_users', array( __CLASS__, 'render_online_users' ) );
		add_shortcode( 'users_online', array( __CLASS__, 'render_online_users' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_styles' ) );
		add_action( 'wp_ajax_mp_online_presence_poll', array( __CLASS__, 'ajax_poll_presence' ) );
		add_action( 'wp_ajax_nopriv_mp_online_presence_poll', array( __CLASS__, 'ajax_poll_presence' ) );
		add_action( 'wp_ajax_mp_online_presence_heartbeat', array( __CLASS__, 'ajax_heartbeat' ) );
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
	 * Check if a member has activity inside the configured online window.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return bool
	 */
	public static function is_online( $user_id ) {
		$user_id = absint( $user_id );
		if ( ! $user_id ) {
			return false;
		}

		$window = (int) apply_filters( 'mp_online_status_window', self::DEFAULT_WINDOW );
		$window = max( MINUTE_IN_SECONDS, $window );
		$last   = absint( get_user_meta( $user_id, self::META_KEY, true ) );

		return $last > 0 && $last >= ( time() - $window );
	}

	/**
	 * Print a presence indicator directly after the profile header name.
	 *
	 * @param array $args    Profile arguments.
	 * @param int   $user_id Profile owner ID.
	 */
	public static function profile_name_indicator( $args, $user_id ) {
		self::output_presence_indicator( $user_id );
	}

	/**
	 * Render an accessible green or grey status dot.
	 *
	 * @param int $user_id User ID.
	 */
	private static function output_presence_indicator( $user_id ) {
		$user_id = absint( $user_id );
		if ( ! $user_id || ! get_userdata( $user_id ) ) {
			return;
		}

		$online = self::is_online( $user_id );
		$state  = $online ? 'online' : 'offline';
		$label  = $online
			? __( 'Online', 'ultimate-member' )
			: __( 'Offline', 'ultimate-member' );

		printf(
			'<span class="mp-presence-dot mp-presence-dot--%1$s" data-user-id="%2$d" data-presence="%1$s" role="img" aria-label="%3$s" title="%3$s"></span>',
			esc_attr( $state ),
			$user_id,
			esc_attr( $label )
		);
	}

	/**
	 * Register and load presence styles and live status updater.
	 */
	public static function register_styles() {
		if ( ! defined( 'MP_URL' ) ) {
			return;
		}

		wp_register_style(
			'mp-online-members',
			MP_URL . 'assets/online.css',
			array(),
			defined( 'MP_VERSION' ) ? MP_VERSION : null
		);
		wp_enqueue_style( 'mp-online-members' );

		wp_register_script(
			'mp-online-presence',
			MP_URL . 'assets/online-presence.js',
			array(),
			defined( 'MP_VERSION' ) ? MP_VERSION : null,
			true
		);
		wp_localize_script(
			'mp-online-presence',
			'MPOnlinePresence',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'mp_online_presence' ),
				'loggedIn'   => is_user_logged_in(),
				'interval'   => 60000,
				'onlineLabel'  => __( 'Online', 'ultimate-member' ),
				'offlineLabel' => __( 'Offline', 'ultimate-member' ),
			)
		);
		wp_enqueue_script( 'mp-online-presence' );
	}

	/**
	 * AJAX heartbeat from an active, logged-in browser tab.
	 */
	public static function ajax_heartbeat() {
		check_ajax_referer( 'mp_online_presence', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'not_logged_in' ), 401 );
		}

		// mark_current_user_online() already ran on 'init' for this request and
		// throttles writes to one per minute.
		wp_send_json_success();
	}

	/**
	 * Return current presence states for IDs actually displayed on the page.
	 * At most 100 IDs are accepted per request.
	 */
	public static function ajax_poll_presence() {
		check_ajax_referer( 'mp_online_presence', 'nonce' );

		$user_ids = isset( $_POST['user_ids'] ) ? wp_unslash( $_POST['user_ids'] ) : array();
		if ( ! is_array( $user_ids ) ) {
			$user_ids = array( $user_ids );
		}

		$user_ids = array_values( array_unique( array_filter( array_map( 'absint', array_slice( $user_ids, 0, 100 ) ) ) ) );
		$states   = array();

		// Load all requested users and their meta with two queries instead of two per user.
		if ( ! empty( $user_ids ) ) {
			cache_users( $user_ids );
			update_meta_cache( 'user', $user_ids );
		}

		foreach ( $user_ids as $user_id ) {
			// Only report members whose profile the visitor may see (no enumeration of
			// arbitrary user IDs or private profiles through this public endpoint).
			if ( function_exists( 'um_can_view_profile' ) && ! um_can_view_profile( $user_id ) ) {
				continue;
			}
			if ( get_userdata( $user_id ) ) {
				$states[ (string) $user_id ] = self::is_online( $user_id ) ? 'online' : 'offline';
			}
		}

		wp_send_json_success( $states );
	}

	/**
	 * Render the optional legacy online-member list shortcode.
	 *
	 * Usage: [mp_online_users] or [mp_online_users limit="20"].
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_online_users( $atts = array() ) {
		wp_enqueue_style( 'mp-online-members' );

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

		$output = '<div class="mp-online-members"><ul class="mp-online-members__list" role="list">';
		foreach ( $users as $user ) {
			if ( ! $user instanceof WP_User ) {
				continue;
			}

			// Respect profile privacy and never expose login names (used for brute-force attacks).
			if ( function_exists( 'um_can_view_profile' ) && ! um_can_view_profile( $user->ID ) ) {
				continue;
			}
			$name = trim( (string) $user->display_name );
			if ( '' === $name ) {
				$name = __( 'Mitglied', 'ultimate-member' );
			}
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
