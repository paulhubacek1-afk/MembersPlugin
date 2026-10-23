<?php
/**
 * Members Plugin – role-specific avatar ring colors.
 *
 * @package MembersPlugin
 */

defined( 'ABSPATH' ) || exit;

final class MP_Role_Avatar_Rings {

	const OPTION = 'mp_role_avatar_ring_colors';
	const PAGE   = 'mp-role-avatar-rings';

	/**
	 * Register the settings screen and avatar styling hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 1002 );
		add_action( 'admin_post_mp_save_role_avatar_rings', array( __CLASS__, 'save' ) );
		add_filter( 'get_avatar', array( __CLASS__, 'filter_avatar' ), PHP_INT_MAX, 6 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ), 100 );
	}

	/**
	 * Add an administration page under the membership menu.
	 */
	public static function register_menu() {
		add_submenu_page(
			'ultimatemember',
			__( 'Profilbild-Ringfarben', 'ultimate-member' ),
			__( 'Profilbild-Ringfarben', 'ultimate-member' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Return saved and validated role colors.
	 *
	 * @return array<string,string>
	 */
	public static function get_colors() {
		$saved = get_option( self::OPTION, array() );
		if ( ! is_array( $saved ) ) {
			return array();
		}

		$roles  = wp_roles()->roles;
		$colors = array();
		foreach ( $roles as $role_key => $role_data ) {
			if ( ! isset( $saved[ $role_key ] ) || ! is_string( $saved[ $role_key ] ) ) {
				continue;
			}

			$color = sanitize_hex_color( $saved[ $role_key ] );
			if ( $color ) {
				$colors[ $role_key ] = $color;
			}
		}

		return $colors;
	}

	/**
	 * Save the ring color settings.
	 */
	public static function save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'ultimate-member' ), 403 );
		}

		check_admin_referer( 'mp_save_role_avatar_rings' );

		$enabled = isset( $_POST['mp_ring_enabled'] ) && is_array( $_POST['mp_ring_enabled'] )
			? wp_unslash( $_POST['mp_ring_enabled'] )
			: array();
		$input = isset( $_POST['mp_ring_colors'] ) && is_array( $_POST['mp_ring_colors'] )
			? wp_unslash( $_POST['mp_ring_colors'] )
			: array();

		$colors = array();
		foreach ( wp_roles()->roles as $role_key => $role_data ) {
			if ( empty( $enabled[ $role_key ] ) || ! isset( $input[ $role_key ] ) || ! is_string( $input[ $role_key ] ) ) {
				continue;
			}

			$color = sanitize_hex_color( $input[ $role_key ] );
			if ( $color ) {
				$colors[ $role_key ] = $color;
			}
		}

		update_option( self::OPTION, $colors );
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'     => self::PAGE,
					'mp-saved' => 1,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Render the role color settings screen.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$roles  = wp_roles()->roles;
		$colors = self::get_colors();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Profilbild-Ringfarben', 'ultimate-member' ); ?></h1>
			<?php if ( isset( $_GET['mp-saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Ringfarben gespeichert.', 'ultimate-member' ); ?></p></div>
			<?php endif; ?>
			<p><?php esc_html_e( 'Aktiviere einen Ring und wähle für jede Benutzerrolle eine eigene Farbe. Nicht aktivierte Rollen behalten ihre normalen Profilbilder. Wenn ein Konto mehrere Rollen hat, wird die erste Rolle mit aktivierter Ringfarbe verwendet.', 'ultimate-member' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="mp_save_role_avatar_rings">
				<?php wp_nonce_field( 'mp_save_role_avatar_rings' ); ?>
				<table class="widefat striped" style="max-width:900px">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Rolle', 'ultimate-member' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Rollen-Schlüssel', 'ultimate-member' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Ring aktiv', 'ultimate-member' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Ringfarbe', 'ultimate-member' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $roles as $role_key => $role_data ) : ?>
							<?php
							$enabled = isset( $colors[ $role_key ] );
							$color   = $enabled ? $colors[ $role_key ] : '#3ba1da';
							$label   = translate_user_role( $role_data['name'] );
							?>
							<tr>
								<th scope="row">
									<label for="mp-ring-enabled-<?php echo esc_attr( $role_key ); ?>"><?php echo esc_html( $label ); ?></label>
								</th>
								<td><code><?php echo esc_html( $role_key ); ?></code></td>
								<td>
									<input
										type="checkbox"
										id="mp-ring-enabled-<?php echo esc_attr( $role_key ); ?>"
										name="mp_ring_enabled[<?php echo esc_attr( $role_key ); ?>]"
										value="1"
										<?php checked( $enabled ); ?>
									>
								</td>
								<td>
									<input
										type="color"
										name="mp_ring_colors[<?php echo esc_attr( $role_key ); ?>]"
										value="<?php echo esc_attr( $color ); ?>"
										aria-label="<?php echo esc_attr( sprintf( /* translators: %s: role name */ __( 'Ringfarbe für %s', 'ultimate-member' ), $label ) ); ?>"
									>
									<code><?php echo esc_html( $color ); ?></code>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php submit_button( __( 'Ringfarben speichern', 'ultimate-member' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Add a role-specific class to WordPress/Ultimate Member avatar markup.
	 *
	 * @param string           $avatar      Avatar HTML.
	 * @param mixed            $id_or_email Avatar owner identifier.
	 * @param int|string|array $size        Requested size.
	 * @param string           $default     Default avatar.
	 * @param string           $alt         Alternative text.
	 * @param array            $args        Additional arguments.
	 * @return string
	 */
	public static function filter_avatar( $avatar, $id_or_email, $size, $default, $alt, $args = array() ) {
		$user_id = self::resolve_user_id( $id_or_email );
		if ( ! $user_id || ! is_string( $avatar ) || false === stripos( $avatar, '<img' ) ) {
			return $avatar;
		}

		$user  = get_userdata( $user_id );
		$roles = $user instanceof WP_User ? (array) $user->roles : array();
		$colors = self::get_colors();
		$role   = '';
		foreach ( $roles as $user_role ) {
			if ( isset( $colors[ $user_role ] ) ) {
				$role = $user_role;
				break;
			}
		}

		if ( '' === $role ) {
			return $avatar;
		}

		$ring_class = 'mp-role-avatar-ring--' . sanitize_html_class( $role );
		$avatar = preg_replace_callback(
			'/<img\b[^>]*>/i',
			static function ( $matches ) use ( $ring_class ) {
				$tag = $matches[0];

				if ( preg_match( '/\bclass=(["\'])(.*?)\1/i', $tag, $class_match ) ) {
					$classes = preg_split( '/\s+/', trim( $class_match[2] ) );
					if ( ! in_array( 'mp-role-avatar-ring', $classes, true ) ) {
						$classes[] = 'mp-role-avatar-ring';
					}
					if ( ! in_array( $ring_class, $classes, true ) ) {
						$classes[] = $ring_class;
					}
					$new_class = 'class=' . $class_match[1] . esc_attr( implode( ' ', array_filter( $classes ) ) ) . $class_match[1];
					$tag       = substr_replace( $tag, $new_class, strpos( $tag, $class_match[0] ), strlen( $class_match[0] ) );
				} else {
					$tag = preg_replace( '/<img\b/i', '<img class="mp-role-avatar-ring ' . esc_attr( $ring_class ) . '"', $tag, 1 );
				}

				return $tag;
			},
			$avatar,
			1
		);

		return is_string( $avatar ) ? $avatar : '';
	}

	/**
	 * Add role-specific CSS for Ultimate Member templates that output profile photos directly.
	 */
	public static function enqueue_styles() {
		$colors = self::get_colors();
		if ( empty( $colors ) ) {
			return;
		}

		wp_register_style( 'mp-role-avatar-rings', false, array(), defined( 'MP_VERSION' ) ? MP_VERSION : null );
		wp_enqueue_style( 'mp-role-avatar-rings' );

		$css = '.mp-role-avatar-ring{box-shadow:0 0 0 3px currentColor;box-shadow:0 0 0 3px var(--mp-role-ring-color, currentColor)!important;}';
		foreach ( $colors as $role => $color ) {
			$role_class = sanitize_html_class( 'um-role-' . $role );
			$ring_class = sanitize_html_class( 'mp-role-avatar-ring--' . $role );
			$selectors  = array(
				'.' . $ring_class,
				'.' . $role_class . ' .um-profile-photo img',
				'.' . $role_class . ' .um-profile-photo-img',
				'.' . $role_class . ' .um-profile-photo-img img',
				'.' . $role_class . ' .um-member-photo img',
				'.um-member.' . $role_class . ' .um-member-photo img',
				'.' . $role_class . ' img.um-avatar',
			);
			$selectors = array_unique( $selectors );
			$css .= implode( ',', $selectors ) . '{box-shadow:0 0 0 3px ' . $color . '!important;}';
		}

		wp_add_inline_style( 'mp-role-avatar-rings', $css );
	}

	/**
	 * Resolve common avatar inputs to a WordPress user ID.
	 *
	 * @param mixed $id_or_email Avatar owner identifier.
	 * @return int
	 */
	private static function resolve_user_id( $id_or_email ) {
		if ( $id_or_email instanceof WP_User ) {
			return absint( $id_or_email->ID );
		}
		if ( $id_or_email instanceof WP_Post ) {
			return absint( $id_or_email->post_author );
		}
		if ( $id_or_email instanceof WP_Comment ) {
			if ( ! empty( $id_or_email->user_id ) ) {
				return absint( $id_or_email->user_id );
			}
			$id_or_email = $id_or_email->comment_author_email;
		}
		if ( is_numeric( $id_or_email ) ) {
			return absint( $id_or_email );
		}
		if ( is_object( $id_or_email ) && ! empty( $id_or_email->user_id ) ) {
			return absint( $id_or_email->user_id );
		}
		if ( is_object( $id_or_email ) && ! empty( $id_or_email->ID ) ) {
			return absint( $id_or_email->ID );
		}
		if ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
			$user = get_user_by( 'email', $id_or_email );
			return $user ? absint( $user->ID ) : 0;
		}

		return 0;
	}
}
