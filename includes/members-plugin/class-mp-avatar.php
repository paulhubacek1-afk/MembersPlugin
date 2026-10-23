<?php
/**
 * Members Plugin – resolves profile photos from the real WordPress user-ID folder.
 *
 * Profile photos are stored in uploads/ultimatemember/{user_id}/profile_photo.{ext}.
 * The WordPress user ID is always resolved from the avatar argument; the currently
 * viewed profile is never used as a substitute.
 *
 * @package MembersPlugin
 */

defined( 'ABSPATH' ) || exit;

final class MP_Avatar {

	/**
	 * Register after the bundled profile-avatar filter so the user's ID folder wins.
	 */
	public static function init() {
		add_filter( 'get_avatar', array( __CLASS__, 'filter_avatar' ), PHP_INT_MAX, 6 );
	}

	/**
	 * Replace the generated avatar only when a valid profile_photo file exists
	 * in this user's own ID directory. Otherwise keep WordPress' normal fallback.
	 *
	 * @param string          $avatar      Generated avatar HTML.
	 * @param mixed           $id_or_email User, ID, email, post or comment.
	 * @param int|string|array $size        Requested size.
	 * @param string          $default     Default avatar.
	 * @param string          $alt         Alternative text.
	 * @param array           $args        Additional avatar arguments.
	 * @return string
	 */
	public static function filter_avatar( $avatar, $id_or_email, $size, $default, $alt, $args = array() ) {
		$user_id = self::resolve_user_id( $id_or_email );
		if ( ! $user_id ) {
			return $avatar;
		}

		$image = self::find_profile_image( $user_id );
		if ( ! $image ) {
			return $avatar;
		}

		if ( is_array( $size ) ) {
			$width  = isset( $size['width'] ) ? max( 1, absint( $size['width'] ) ) : 96;
			$height = isset( $size['height'] ) ? max( 1, absint( $size['height'] ) ) : $width;
		} else {
			$width  = max( 1, absint( $size ) );
			$height = $width;
		}

		$classes = array( 'avatar', 'avatar-' . $width, 'photo' );
		if ( is_array( $args ) && ! empty( $args['class'] ) ) {
			$requested_classes = preg_split( '/\s+/', trim( (string) $args['class'] ) );
			foreach ( $requested_classes as $requested_class ) {
				$requested_class = sanitize_html_class( $requested_class );
				if ( '' !== $requested_class ) {
					$classes[] = $requested_class;
				}
			}
		}

		$attributes = array(
			'alt="' . esc_attr( (string) $alt ) . '"',
			'src="' . esc_url( $image['url'] ) . '"',
			'class="' . esc_attr( implode( ' ', array_unique( $classes ) ) ) . '"',
			'width="' . esc_attr( (string) $width ) . '"',
			'height="' . esc_attr( (string) $height ) . '"',
			'loading="lazy"',
			'decoding="async"',
		);

		return '<img ' . implode( ' ', $attributes ) . ' />';
	}

	/**
	 * Resolve common WordPress avatar inputs to a real user ID.
	 *
	 * @param mixed $id_or_email Input passed by WordPress.
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

	/**
	 * Find profile_photo.ext directly in the folder named after the WordPress user ID.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return array|false
	 */
	private static function find_profile_image( $user_id ) {
		$user_id = absint( $user_id );
		if ( ! $user_id ) {
			return false;
		}

		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) || empty( $uploads['baseurl'] ) ) {
			return false;
		}

		$directory  = trailingslashit( $uploads['basedir'] ) . 'ultimatemember/' . $user_id . '/';
		$url_base   = trailingslashit( $uploads['baseurl'] ) . 'ultimatemember/' . $user_id . '/';
		$extensions = array( 'jpg', 'jpeg', 'png', 'webp', 'gif', 'avif' );

		foreach ( $extensions as $extension ) {
			$filename = 'profile_photo.' . $extension;
			$path     = $directory . $filename;

			if ( ! is_file( $path ) || ! is_readable( $path ) ) {
				continue;
			}

			$filetype = wp_check_filetype( $filename );
			if ( empty( $filetype['type'] ) || 0 !== strpos( $filetype['type'], 'image/' ) ) {
				continue;
			}

			// Verify that the file is actually an image, not merely a renamed file.
			$image_info = @getimagesize( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( ! is_array( $image_info ) || empty( $image_info['mime'] ) || 0 !== strpos( $image_info['mime'], 'image/' ) ) {
				continue;
			}

			$url      = $url_base . rawurlencode( $filename );
			$modified = @filemtime( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( false !== $modified ) {
				$url = add_query_arg( 'mp-avatar-v', (string) $modified, $url );
			}

			return array(
				'path' => $path,
				'url'  => $url,
			);
		}

		return false;
	}
}
