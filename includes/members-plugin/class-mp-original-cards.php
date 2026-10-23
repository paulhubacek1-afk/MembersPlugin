<?php
/**
 * Members Plugin – Original member-directory cards.
 *
 * Keeps the member-directory cards exactly as in the original Ultimate Member code:
 * - the bundled original templates (members-grid.php, members-list.php, …) are used,
 *   even if a theme or another plugin overrides them;
 * - card styling that other plugins/themes add as inline CSS for `.um-member…`
 *   selectors is removed, so the original stylesheet decides the look.
 *
 * Can be switched off under Members Plugin › Design & CSS.
 *
 * @package MembersPlugin
 */

defined( 'ABSPATH' ) || exit;

final class MP_Original_Cards {

	/**
	 * Templates of the member directory that must come from the plugin itself.
	 *
	 * @var string[]
	 */
	private static $templates = array(
		'members.php',
		'members-grid.php',
		'members-list.php',
		'members-header.php',
		'members-pagination.php',
	);

	/**
	 * Hooks.
	 */
	public static function init() {
		// Registered after all plugins are loaded so this runs after other overrides.
		add_action(
			'init',
			static function () {
				add_filter( 'um_locate_template', array( __CLASS__, 'locate_template' ), PHP_INT_MAX, 2 );
				add_filter( 'um_get_template', array( __CLASS__, 'locate_template' ), PHP_INT_MAX, 2 );
			},
			PHP_INT_MAX
		);
		add_action( 'wp_print_styles', array( __CLASS__, 'strip_foreign_card_css' ), 1 );
		add_action( 'wp_print_footer_scripts', array( __CLASS__, 'strip_foreign_card_css' ), 1 );
	}

	/**
	 * Is the feature enabled?
	 *
	 * @return bool
	 */
	public static function enabled() {
		$opts = MP_Design::get_options();
		return ! empty( $opts['original_cards'] );
	}

	/**
	 * Always use the bundled original directory templates.
	 *
	 * @param string $located       Located template file.
	 * @param string $template_name Template name.
	 * @return string
	 */
	public static function locate_template( $located, $template_name ) {
		if ( ! self::enabled() || ! in_array( $template_name, self::$templates, true ) ) {
			return $located;
		}
		$original = UM_PATH . 'templates' . DIRECTORY_SEPARATOR . $template_name;
		return file_exists( $original ) ? $original : $located;
	}

	/**
	 * Remove card rules that themes/other plugins add through wp_add_inline_style().
	 */
	public static function strip_foreign_card_css() {
		if ( is_admin() || ! self::enabled() ) {
			return;
		}
		$styles = wp_styles();
		foreach ( $styles->registered as $handle => $style ) {
			if ( empty( $style->extra['after'] ) || ! is_array( $style->extra['after'] ) ) {
				continue;
			}
			// Leave the plugin's own styles alone.
			if ( 0 === strpos( $handle, 'um_' ) || 0 === strpos( $handle, 'um-' ) || 0 === strpos( $handle, 'mp-' ) ) {
				continue;
			}
			foreach ( $style->extra['after'] as $i => $css ) {
				if ( is_string( $css ) && false !== strpos( $css, 'um-member' ) ) {
					$styles->registered[ $handle ]->extra['after'][ $i ] = self::strip_rules( $css );
				}
			}
		}
	}

	/**
	 * Drop CSS rules whose selectors target member-directory cards.
	 *
	 * @param string $css CSS.
	 * @return string
	 */
	public static function strip_rules( $css ) {
		$out    = '';
		$length = strlen( $css );
		$pos    = 0;

		while ( $pos < $length ) {
			$open = strpos( $css, '{', $pos );
			if ( false === $open ) {
				$out .= substr( $css, $pos );
				break;
			}
			$selector = substr( $css, $pos, $open - $pos );

			// Find the matching closing brace (handles nested @media/@supports blocks).
			$depth = 1;
			$i     = $open + 1;
			while ( $i < $length && $depth > 0 ) {
				$char = $css[ $i ];
				if ( '{' === $char ) {
					$depth++;
				} elseif ( '}' === $char ) {
					$depth--;
				}
				$i++;
			}
			$body = substr( $css, $open + 1, $i - $open - 2 );

			$trimmed = ltrim( $selector );
			if ( 0 === strpos( $trimmed, '@media' ) || 0 === strpos( $trimmed, '@supports' ) || 0 === strpos( $trimmed, '@container' ) || 0 === strpos( $trimmed, '@layer' ) ) {
				$inner = self::strip_rules( $body );
				if ( '' !== trim( $inner ) ) {
					$out .= $selector . '{' . $inner . '}';
				}
			} elseif ( '@' !== substr( $trimmed, 0, 1 ) && self::targets_cards( $selector ) ) {
				// Keep only the selectors of the list that do not target the cards.
				$keep = array();
				foreach ( explode( ',', $selector ) as $part ) {
					if ( ! self::targets_cards( $part ) ) {
						$keep[] = $part;
					}
				}
				if ( ! empty( $keep ) ) {
					$out .= implode( ',', $keep ) . '{' . $body . '}';
				}
			} else {
				$out .= $selector . '{' . $body . '}';
			}

			$pos = $i;
		}

		return $out;
	}

	/**
	 * Does a selector target the member cards?
	 *
	 * @param string $selector Selector list.
	 * @return bool
	 */
	private static function targets_cards( $selector ) {
		return (bool) preg_match( '/\.um-member(?![\w-]*directory)|\.um-members\b/', $selector );
	}
}
