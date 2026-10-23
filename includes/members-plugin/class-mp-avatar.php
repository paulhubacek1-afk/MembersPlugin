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

		if ( is_array( $size ) ) {
			$width  = isset( $size['width'] ) ? max( 1, absint( $size['width'] ) ) : 96;
			$height = isset( $size['height'] ) ? max( 1, absint( $size['height'] ) ) : $width;
		} else {
			$width  = max( 1, absint( $size ) );
			$height = $width;
		}

		$image = self::find_profile_image( $user_id, max( $width, $height ) );
		if ( ! $image ) {
			return $avatar;
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
		);
		if ( ! empty( $image['srcset'] ) ) {
			$attributes[] = 'srcset="' . esc_attr( $image['srcset'] ) . '"';
		}
		$attributes = array_merge(
			$attributes,
			array(
				'class="' . esc_attr( implode( ' ', array_unique( $classes ) ) ) . '"',
				'width="' . esc_attr( (string) $width ) . '"',
				'height="' . esc_attr( (string) $height ) . '"',
				'loading="lazy"',
				'decoding="async"',
			)
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
	 * Find the best profile_photo file in the folder named after the WordPress user ID.
	 *
	 * Uses the smallest generated thumbnail (profile_photo-{n}x{n}.ext) that is at least
	 * as large as the requested size and falls back to the original upload. Member lists
	 * therefore load small thumbnails instead of the full-size original photo. Results
	 * are cached per request, and the URL is versioned with the file modification time
	 * so browsers can cache it.
	 *
	 * @param int $user_id WordPress user ID.
	 * @param int $size    Requested display size in pixels.
	 * @return array|false
	 */
	private static function find_profile_image( $user_id, $size = 0 ) {
		static $cache = array();

		$user_id = absint( $user_id );
		$size    = absint( $size );
		if ( ! $user_id ) {
			return false;
		}

		$cache_key = $user_id . '|' . $size;
		if ( array_key_exists( $cache_key, $cache ) ) {
			return $cache[ $cache_key ];
		}

		$cache[ $cache_key ] = false;

		$files = self::profile_files( $user_id );
		if ( empty( $files['originals'] ) ) {
			return false;
		}

		$uploads   = self::uploads();
		$directory = trailingslashit( $uploads['basedir'] ) . 'ultimatemember/' . $user_id . '/';
		$url_base  = trailingslashit( $uploads['baseurl'] ) . 'ultimatemember/' . $user_id . '/';

		// Candidate order: best-fitting thumbnail first, then the originals.
		$candidates = array();
		if ( $size > 0 && ! empty( $files['thumbs'] ) ) {
			$thumbs = $files['thumbs'];
			ksort( $thumbs, SORT_NUMERIC );
			foreach ( $thumbs as $thumb_size => $filename ) {
				if ( $thumb_size >= $size ) {
					$candidates[] = $filename;
					break;
				}
			}
		}
		foreach ( $files['originals'] as $filename ) {
			$candidates[] = $filename;
		}

		foreach ( $candidates as $index => $filename ) {
			$image = self::image_entry( $directory, $url_base, $filename );
			if ( ! $image ) {
				continue;
			}

			// A thumbnail was chosen: offer the original for high-density screens so
			// avatars stay sharp while normal screens download the small file.
			$is_thumb = ! in_array( $filename, $files['originals'], true );
			if ( $is_thumb ) {
				foreach ( $files['originals'] as $original ) {
					$original_image = self::image_entry( $directory, $url_base, $original );
					if ( $original_image ) {
						$image['srcset'] = $image['url'] . ' 1x, ' . $original_image['url'] . ' 2x';
						break;
					}
				}
			}

			$cache[ $cache_key ] = $image;
			break;
		}

		return $cache[ $cache_key ];
	}

	/**
	 * Validate one image file and build its versioned URL.
	 *
	 * @param string $directory Directory with trailing slash.
	 * @param string $url_base  URL with trailing slash.
	 * @param string $filename  File name.
	 * @return array|false
	 */
	private static function image_entry( $directory, $url_base, $filename ) {
		static $cache = array();

		$path = $directory . $filename;
		if ( array_key_exists( $path, $cache ) ) {
			return $cache[ $path ];
		}
		$cache[ $path ] = false;

		$filetype = wp_check_filetype( $filename );
		if ( empty( $filetype['type'] ) || 0 !== strpos( $filetype['type'], 'image/' ) ) {
			return false;
		}

		// Verify that the file is actually an image, not merely a renamed file.
		$image_info = @getimagesize( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! is_array( $image_info ) || empty( $image_info['mime'] ) || 0 !== strpos( $image_info['mime'], 'image/' ) ) {
			return false;
		}

		$url      = $url_base . rawurlencode( $filename );
		$modified = @filemtime( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( false !== $modified ) {
			$url = add_query_arg( 'mp-avatar-v', (string) $modified, $url );
		}

		$cache[ $path ] = array(
			'path' => $path,
			'url'  => $url,
		);
		return $cache[ $path ];
	}

	/**
	 * Cached wp_upload_dir() result.
	 *
	 * @return array
	 */
	private static function uploads() {
		static $uploads = null;
		if ( null === $uploads ) {
			$uploads = wp_upload_dir( null, false );
		}
		return $uploads;
	}

	/**
	 * List the profile photo files of one user with a single directory read.
	 *
	 * @param int $user_id User ID.
	 * @return array{originals:string[],thumbs:array<int,string>}
	 */
	private static function profile_files( $user_id ) {
		static $cache = array();

		if ( isset( $cache[ $user_id ] ) ) {
			return $cache[ $user_id ];
		}

		$result = array(
			'originals' => array(),
			'thumbs'    => array(),
		);

		$uploads = self::uploads();
		if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) || empty( $uploads['baseurl'] ) ) {
			$cache[ $user_id ] = $result;
			return $result;
		}

		$directory = trailingslashit( $uploads['basedir'] ) . 'ultimatemember/' . $user_id . '/';
		$entries   = is_dir( $directory ) ? @scandir( $directory ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! is_array( $entries ) ) {
			$cache[ $user_id ] = $result;
			return $result;
		}

		$extensions = array( 'jpg', 'jpeg', 'png', 'webp', 'gif', 'avif' );
		$originals  = array();
		foreach ( $entries as $entry ) {
			if ( ! preg_match( '/^profile_photo(?:-(\d+)(?:x(\d+))?)?\.([a-z0-9]+)$/i', $entry, $m ) ) {
				continue;
			}
			$extension = strtolower( $m[3] );
			if ( ! in_array( $extension, $extensions, true ) || ! is_file( $directory . $entry ) || ! is_readable( $directory . $entry ) ) {
				continue;
			}
			if ( '' === $m[1] ) {
				$originals[ array_search( $extension, $extensions, true ) ] = $entry;
			} elseif ( '' === $m[2] || $m[1] === $m[2] ) {
				// Square thumbnails only; prefer "-{n}x{n}" over "-{n}".
				$thumb_size = (int) $m[1];
				if ( ! isset( $result['thumbs'][ $thumb_size ] ) || '' !== $m[2] ) {
					$result['thumbs'][ $thumb_size ] = $entry;
				}
			}
		}

		// Keep the original extension priority (jpg, jpeg, png, webp, gif, avif).
		ksort( $originals, SORT_NUMERIC );
		$result['originals'] = array_values( $originals );

		$cache[ $user_id ] = $result;
		return $result;
	}
}
