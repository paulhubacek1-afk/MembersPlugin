<?php
/**
 * Members Plugin – admin-only diagnostics for profile tabs.
 *
 * Open a profile page as administrator with ?mp_debug=tabs. The page then contains a
 * <script type="application/json" id="mp-debug-tabs"> block that lists the profile tabs
 * at each processing step and every callback attached to the relevant hooks. Nothing
 * is shown to other users and nothing is stored.
 *
 * @package MembersPlugin
 */

defined( 'ABSPATH' ) || exit;

final class MP_Diagnostics {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'wp_footer', array( __CLASS__, 'profile_tabs' ), 1 );
	}

	/**
	 * Print the diagnostics block.
	 */
	public static function profile_tabs() {
		if ( ! isset( $_GET['mp_debug'] ) || 'tabs' !== $_GET['mp_debug'] || ! is_user_logged_in() || ! current_user_can( 'manage_options' ) || ! function_exists( 'UM' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$data = array(
			'profile_menu' => UM()->options()->get( 'profile_menu' ),
			'target_id'    => (int) UM()->user()->target_id,
			'profile_id'   => function_exists( 'um_profile_id' ) ? (int) um_profile_id() : 0,
			'viewer'       => get_current_user_id(),
			'callbacks'    => array(),
		);

		foreach ( array( 'um_profile_tabs', 'um_user_profile_tabs', 'um_profile_menu_tab_privacy', 'um_profile_menu_can_view_tab', 'um_profile_menu' ) as $hook ) {
			$data['callbacks'][ $hook ] = self::callbacks( $hook );
		}

		try {
			// Raw list after um_profile_tabs, before privacy checks.
			$raw = apply_filters(
				'um_profile_tabs',
				array(
					'main'     => array( 'name' => 'About' ),
					'posts'    => array( 'name' => 'Posts' ),
					'comments' => array( 'name' => 'Comments' ),
				)
			);
			$data['after_um_profile_tabs'] = array_keys( (array) $raw );

			$privacy = array();
			foreach ( (array) $raw as $id => $tab ) {
				$privacy[ $id ] = array(
					'enabled'  => (bool) UM()->options()->get( 'profile_tab_' . $id ),
					'privacy'  => UM()->options()->get( 'profile_tab_' . $id . '_privacy' ),
					'can_view' => UM()->profile()->can_view_tab( $id, is_array( $tab ) ? $tab : array() ),
				);
			}
			$data['per_tab'] = $privacy;

			$data['tabs']        = array_keys( UM()->profile()->tabs() );
			$data['tabs_active'] = array_keys( UM()->profile()->tabs_active() );
		} catch ( \Throwable $e ) {
			$data['error'] = get_class( $e ) . ': ' . $e->getMessage() . ' @ ' . wp_basename( $e->getFile() ) . ':' . $e->getLine();
		}

		echo '<script type="application/json" id="mp-debug-tabs">' . wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP ) . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Describe the callbacks of a hook.
	 *
	 * @param string $hook Hook name.
	 * @return string[]
	 */
	private static function callbacks( $hook ) {
		global $wp_filter;
		$out = array();
		if ( empty( $wp_filter[ $hook ] ) || ! is_object( $wp_filter[ $hook ] ) ) {
			return $out;
		}
		foreach ( $wp_filter[ $hook ]->callbacks as $priority => $items ) {
			foreach ( $items as $item ) {
				$cb = $item['function'];
				if ( is_string( $cb ) ) {
					$name = $cb;
				} elseif ( is_array( $cb ) ) {
					$name = ( is_object( $cb[0] ) ? get_class( $cb[0] ) : (string) $cb[0] ) . '::' . $cb[1];
				} elseif ( $cb instanceof \Closure ) {
					try {
						$ref  = new \ReflectionFunction( $cb );
						$name = 'Closure@' . wp_basename( dirname( (string) $ref->getFileName() ) ) . '/' . wp_basename( (string) $ref->getFileName() ) . ':' . $ref->getStartLine();
					} catch ( \Throwable $e ) {
						$name = 'Closure';
					}
				} else {
					$name = 'callable';
				}
				$out[] = $priority . ' ' . $name;
			}
		}
		return $out;
	}
}
