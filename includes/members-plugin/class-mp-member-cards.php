<?php
/**
 * Members Plugin – "Bühne" member-card layout for the member directory.
 *
 * Grid of four cards, a large round profile photo that overlaps the top edge of the card,
 * names in bold, and the hover effects of the reference design (blue ring around the photo,
 * highlighted name and statistics chips). Fonts, sizes and colours are adjustable under
 * Members Plugin › Design & CSS › Mitgliederkarten (CSS variables --um-card-*).
 *
 * Works with the original card templates and with add-ons that decorate the cards (for
 * example the Stückverwaltung background layer and statistics).
 *
 * @package MembersPlugin
 */

defined( 'ABSPATH' ) || exit;

final class MP_Member_Cards {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
	}

	/**
	 * Is the layout enabled?
	 *
	 * @return bool
	 */
	public static function enabled() {
		$opts = MP_Design::get_options();
		return ! empty( $opts['card_layout_stage'] );
	}

	/**
	 * Body class used to scope the layout.
	 *
	 * @param string[] $classes Classes.
	 * @return string[]
	 */
	public static function body_class( $classes ) {
		if ( self::enabled() ) {
			$classes[] = 'um-mp-cards-stage';
		}
		return $classes;
	}

	/**
	 * Design variables for the card layout (merged into MP_Design::variables()).
	 *
	 * @return array<string,array> key => array( label, description, default, group )
	 */
	public static function variables() {
		return array(
			'card-font-family'      => array( __( 'Schriftart Karten', 'ultimate-member' ), __( 'Leer = Schrift der Website.', 'ultimate-member' ), '', 'cards' ),
			'card-name-font-family' => array( __( 'Schriftart Namen', 'ultimate-member' ), __( 'Leer = wie „Schriftart Karten“.', 'ultimate-member' ), '', 'cards' ),
			'card-name-font-size'   => array( __( 'Schriftgröße Namen', 'ultimate-member' ), '', '16px', 'cards' ),
			'card-name-font-weight' => array( __( 'Schriftstärke Namen', 'ultimate-member' ), __( 'z.B. 700 oder bold.', 'ultimate-member' ), '700', 'cards' ),
			'card-text-font-size'   => array( __( 'Schriftgröße Zusatztext', 'ultimate-member' ), __( 'Untertitel/Tagline.', 'ultimate-member' ), '13px', 'cards' ),
			'card-bg'               => array( __( 'Kartenhintergrund', 'ultimate-member' ), __( 'Grundfarbe; Hintergründe von Erweiterungen liegen darüber.', 'ultimate-member' ), '#6d0019', 'cards' ),
			'card-text'             => array( __( 'Textfarbe Karten', 'ultimate-member' ), '', '#ffffff', 'cards' ),
			'card-photo-border'     => array( __( 'Rahmen Profilfoto', 'ultimate-member' ), '', '#ffffff', 'cards' ),
			'card-hover'            => array( __( 'Hover-Farbe', 'ultimate-member' ), __( 'Ring um das Foto, Name und Statistik beim Darüberfahren.', 'ultimate-member' ), '#3c6999', 'cards' ),
			'card-hover-border'     => array( __( 'Hover-Rahmen Statistik', 'ultimate-member' ), '', '#628ab5', 'cards' ),
			'card-stat-bg'          => array( __( 'Statistik Hintergrund', 'ultimate-member' ), '', '#6d0019', 'cards' ),
			'card-stat-border'      => array( __( 'Statistik Rahmen', 'ultimate-member' ), '', '#76162c', 'cards' ),
			'card-stat-text'        => array( __( 'Statistik Text', 'ultimate-member' ), '', '#dddddd', 'cards' ),
		);
	}

	/**
	 * CSS of the layout.
	 *
	 * @return string
	 */
	public static function css() {
		if ( ! self::enabled() ) {
			return '';
		}

		$g  = 'body.um-mp-cards-stage .um-directory .um-members-wrapper .um-members.um-members-grid';
		$m  = $g . ' .um-member';
		$rs = array(
			// Grid: 4 columns, 3 / 2 on narrower directories.
			$m => 'width:24%!important;margin-top:40px!important;margin-bottom:20px!important;padding:0!important;border:0!important;border-radius:5px!important;overflow:visible!important;isolation:isolate;background:var(--um-card-bg,#6d0019)!important;color:var(--um-card-text,#fff);font-family:var(--um-card-font-family,inherit)',
			'body.um-mp-cards-stage .um-directory.uimob960 .um-members-wrapper .um-members.um-members-grid .um-member,body.um-mp-cards-stage .um-directory.uimob800 .um-members-wrapper .um-members.um-members-grid .um-member' => 'width:32.6667%!important',
			'body.um-mp-cards-stage .um-directory.uimob500 .um-members-wrapper .um-members.um-members-grid .um-member,body.um-mp-cards-stage .um-directory.uimob340 .um-members-wrapper .um-members.um-members-grid .um-member' => 'width:49.5%!important;margin-bottom:12px!important',
			$g . ' .um-gutter-sizer' => 'width:1%!important',
			$m . ' .um-member-cover' => 'display:none!important',
			$m . ' .stuecke-um-card-bg' => 'inset:0!important;z-index:-1!important;border-radius:5px',

			// Photo overlapping the top edge of the card.
			$m . ' .um-member-photo' => 'display:block!important;position:relative!important;z-index:1;top:-34px!important;margin:0 0 -34px!important;padding:0 0 20px!important;width:auto!important;height:auto!important;border:0!important;border-radius:0!important;box-shadow:none!important;background:transparent!important;overflow:visible!important;text-align:center!important',
			$m . ' .um-member-photo::after' => 'display:none!important',
			$m . ' .um-member-photo a' => 'display:inline-block!important;width:60%!important;height:auto!important;border:0!important;border-radius:50%!important;box-shadow:none!important;background:transparent!important;overflow:visible!important',
			$m . ' .um-member-photo a img' => 'display:block!important;width:100%!important;height:auto!important;aspect-ratio:1/1;object-fit:cover!important;border:5px solid var(--um-card-photo-border,#fff)!important;border-radius:50%!important;background:var(--um-card-photo-border,#fff)!important;position:static!important;top:auto!important;margin:0!important;box-shadow:none!important;transition:box-shadow .5s ease!important',
			$m . ' .um-member-photo a img:hover,' . $m . ' .um-member-photo a img.mp-role-avatar-ring:hover,' . $m . ' .um-member-photo a:focus-visible img' => 'box-shadow:0 0 0 .5rem var(--um-card-hover,#3c6999)!important',
			$m . ' .um-member-photo a:hover,' . $m . ' .um-member-photo a:focus-visible' => 'opacity:1!important',

			// Card text.
			$m . ' .um-member-card' => 'position:static!important;padding:.5em .5em 1em!important;text-shadow:1px 1px 3px rgba(0,0,0,.85)',
			$m . ' .um-member-card,' . $m . ' .um-member-card a' => 'color:var(--um-card-text,#fff)!important',
			$m . ' .um-member-card .um-member-name' => 'position:relative;z-index:1;margin:0 0 .5rem!important;font-family:var(--um-card-name-font-family,var(--um-card-font-family,inherit))!important;font-weight:var(--um-card-name-font-weight,700)!important;font-size:var(--um-card-name-font-size,16px)!important;line-height:1.1!important',
			$m . ' .um-member-card .um-member-name a' => 'font:inherit!important;text-decoration:none!important;transition:background-color .3s ease,outline-color .3s ease',
			$m . ' .um-member-card .um-member-name a:hover,' . $m . ' .um-member-card .um-member-name a:focus-visible' => 'background-color:var(--um-card-hover,#3c6999)!important;outline:5px solid var(--um-card-hover,#3c6999)!important;opacity:1!important',
			$m . ' .um-member-card .um-member-tagline,' . $m . ' .um-member-card .um-member-metaline' => 'position:relative;z-index:1;font-family:var(--um-card-font-family,inherit)!important;font-size:var(--um-card-text-font-size,13px)!important;line-height:1.1!important',
			$m . ' .um-members-edit-btn' => 'display:none!important',

			// Statistics chips (Stückverwaltung add-on).
			$m . ' .stuecke-um-list-stats' => 'position:relative;z-index:1;display:inline-flex!important;flex-wrap:wrap!important;justify-content:center!important;gap:0!important;margin:0 auto!important;background:transparent!important;border-radius:0!important;overflow:visible!important;width:auto!important',
			$m . ' .stuecke-um-list-stat' => 'margin:1rem 1px 1px!important;padding:.2rem .6rem!important;border:1px solid var(--um-card-stat-border,#76162c)!important;border-radius:5px!important;background:var(--um-card-stat-bg,#6d0019)!important;color:var(--um-card-stat-text,#ddd)!important;font-size:.9rem!important;line-height:1.3rem!important;text-shadow:none!important;transition:background-color .3s ease,border-color .3s ease',
			$m . ' .stuecke-um-list-stat+.stuecke-um-list-stat' => 'border-left:1px solid var(--um-card-stat-border,#76162c)!important',
			$m . ' .stuecke-um-list-stat:hover,' . $m . ' .stuecke-um-list-stat:focus-visible' => 'background:var(--um-card-hover,#3c6999)!important;border-color:var(--um-card-hover-border,#628ab5)!important;opacity:1!important',
			$m . ' .stuecke-um-list-stat *' => 'color:var(--um-card-stat-text,#ddd)!important',
			$m . ' .stuecke-um-list-stat-icon,' . $m . ' .stuecke-um-list-stat-count' => 'font-size:.9rem!important;font-family:var(--um-card-font-family,inherit)!important',
		);

		$css = '';
		foreach ( $rs as $selector => $declarations ) {
			$css .= $selector . '{' . $declarations . '}';
		}
		return $css;
	}
}
