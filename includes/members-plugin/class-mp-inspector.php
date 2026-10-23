<?php
/**
 * Members Plugin – Klassen-Inspector im Frontend (nur für Administratoren).
 *
 * @package MembersPlugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class MP_Inspector
 */
final class MP_Inspector {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar' ), 999 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ), 10000 );
	}

	/**
	 * Darf der Inspector angezeigt werden?
	 *
	 * @return bool
	 */
	private static function allowed() {
		$opts = MP_Design::get_options();
		return ! is_admin() && ! empty( $opts['inspector'] ) && current_user_can( 'manage_options' ) && is_admin_bar_showing();
	}

	/**
	 * Admin-Leiste.
	 *
	 * @param WP_Admin_Bar $bar Leiste.
	 */
	public static function admin_bar( $bar ) {
		if ( ! self::allowed() ) {
			return;
		}
		$bar->add_node(
			array(
				'id'    => 'mp-inspector',
				'title' => '<span class="ab-icon dashicons dashicons-search" aria-hidden="true"></span><span class="ab-label">' . esc_html__( 'CSS-Klassen', 'ultimate-member' ) . '</span>',
				'href'  => '#',
				'meta'  => array(
					'title' => esc_attr__( 'Members Plugin: CSS-Klassen-Inspector ein/aus (Esc zum Beenden)', 'ultimate-member' ),
				),
			)
		);
		$bar->add_node(
			array(
				'parent' => 'mp-inspector',
				'id'     => 'mp-inspector-settings',
				'title'  => esc_html__( 'Design & CSS öffnen', 'ultimate-member' ),
				'href'   => admin_url( 'admin.php?page=' . MP_Admin::SLUG ),
			)
		);
	}

	/**
	 * Assets.
	 */
	public static function assets() {
		if ( ! self::allowed() ) {
			return;
		}
		wp_enqueue_style( 'mp-inspector', MP_URL . 'assets/inspector.css', array(), MP_VERSION );
		wp_enqueue_script( 'mp-inspector', MP_URL . 'assets/inspector.js', array(), MP_VERSION, true );
		wp_localize_script(
			'mp-inspector',
			'mpInspector',
			array(
				'title'    => __( 'CSS-Klassen-Inspector', 'ultimate-member' ),
				'hint'     => __( 'Auf ein Element zeigen. Klick = festhalten. Esc = beenden.', 'ultimate-member' ),
				'selector' => __( 'Empfohlener Selektor', 'ultimate-member' ),
				'path'     => __( 'Pfad', 'ultimate-member' ),
				'styles'   => __( 'Berechnete Werte', 'ultimate-member' ),
				'copy'     => __( 'Kopieren', 'ultimate-member' ),
				'copied'   => __( 'Kopiert!', 'ultimate-member' ),
				'close'    => __( 'Schließen', 'ultimate-member' ),
				'onlyUm'   => __( 'Nur Plugin-Elemente', 'ultimate-member' ),
				'settings' => admin_url( 'admin.php?page=' . MP_Admin::SLUG . '&tab=css' ),
				'editCss'  => __( 'Eigenes CSS bearbeiten', 'ultimate-member' ),
			)
		);
	}
}
