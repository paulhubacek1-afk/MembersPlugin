<?php
/**
 * Members Plugin – Admin-Seite "Design & CSS".
 *
 * @package MembersPlugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class MP_Admin
 */
final class MP_Admin {

	const SLUG = 'mp-design';

	/**
	 * Hook-Suffix der Seite.
	 *
	 * @var string
	 */
	private static $hook = '';

	/**
	 * Hooks registrieren.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 1001 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'settings_media_assets' ), 20 );
		add_action( 'admin_post_mp_save_design', array( __CLASS__, 'save' ) );
		add_action( 'admin_post_mp_reset_design', array( __CLASS__, 'reset' ) );
		add_action( 'admin_post_mp_download_starter', array( __CLASS__, 'download_starter' ) );
		add_action( 'admin_post_mp_rescan_classes', array( __CLASS__, 'rescan' ) );
		add_filter( 'plugin_action_links_' . UM_PLUGIN, array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Menüpunkt unter "Ultimate Member".
	 */
	public static function menu() {
		self::$hook = add_submenu_page(
			'ultimatemember',
			__( 'Design & CSS', 'ultimate-member' ),
			__( 'Design & CSS', 'ultimate-member' ),
			'manage_options',
			self::SLUG,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Link in der Pluginliste.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public static function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ) . '">' . esc_html__( 'Design & CSS', 'ultimate-member' ) . '</a>' );
		return $links;
	}

	/**
	 * Aktueller Tab.
	 *
	 * @return string
	 */
	private static function tab() {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'design'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return in_array( $tab, array( 'design', 'css', 'classes', 'variables' ), true ) ? $tab : 'design';
	}

	/**
	 * Assets laden.
	 *
	 * @param string $hook Hook.
	 */
	public static function assets( $hook ) {
		if ( $hook !== self::$hook ) {
			return;
		}
		$css_url = UM_URL . 'assets/css/';
		$suffix  = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		// Originale UM-Stylesheets für die Live-Vorschau.
		wp_enqueue_style( 'mp-prev-um-styles', $css_url . 'um-styles' . $suffix . '.css', array(), UM_VERSION );
		wp_enqueue_style( 'mp-prev-um-profile', $css_url . 'um-profile' . $suffix . '.css', array(), UM_VERSION );
		wp_enqueue_style( 'mp-prev-um-default', $css_url . 'um-old-default' . $suffix . '.css', array( 'mp-prev-um-styles' ), UM_VERSION );
		wp_enqueue_style( 'mp-prev-um-fa', $css_url . 'um-fontawesome' . $suffix . '.css', array(), UM_VERSION );

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'mp-admin', MP_URL . 'assets/admin.css', array(), MP_VERSION );
		wp_enqueue_script( 'mp-admin', MP_URL . 'assets/admin.js', array( 'jquery', 'wp-color-picker' ), MP_VERSION, true );

		if ( 'css' === self::tab() && current_user_can( 'edit_css' ) ) {
			$settings = wp_enqueue_code_editor( array( 'type' => 'text/css' ) );
			wp_localize_script( 'mp-admin', 'mpCodeEditor', $settings ? $settings : array() );
		}

		wp_localize_script(
			'mp-admin',
			'mpAdmin',
			array(
				'copied' => __( 'Kopiert!', 'ultimate-member' ),
				'copy'   => __( 'Kopieren', 'ultimate-member' ),
			)
		);
	}

	/**
	 * Mediathek auf den Plugin-Einstellungen (z.B. Standard-Profilbild / Standard-Titelbild).
	 *
	 * Unabhängig vom internen Seitennamen: es zählt nur ?page=um_options.
	 */
	public static function settings_media_assets() {
		if ( ! isset( $_GET['page'] ) || 'um_options' !== $_GET['page'] || ! current_user_can( 'manage_options' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( 'mp-media-picker', MP_URL . 'assets/media-picker.js', array( 'jquery', 'media-editor', 'wp-i18n' ), MP_VERSION, true );
	}

	/**
	 * Formular speichern.
	 */
	public static function save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'ultimate-member' ), 403 );
		}
		check_admin_referer( 'mp_save_design' );

		$opts    = MP_Design::get_options();
		$section = isset( $_POST['mp_section'] ) ? sanitize_key( wp_unslash( $_POST['mp_section'] ) ) : '';

		if ( 'design' === $section ) {
			$opts['auto_colors']   = empty( $_POST['auto_colors'] ) ? 0 : 1;
			$opts['inspector']     = empty( $_POST['inspector'] ) ? 0 : 1;
			$opts['editor_styles'] = empty( $_POST['editor_styles'] ) ? 0 : 1;
			$opts['theme_typography'] = empty( $_POST['theme_typography'] ) ? 0 : 1;
			$opts['original_cards']   = empty( $_POST['original_cards'] ) ? 0 : 1;
			$opts['card_layout_stage'] = empty( $_POST['card_layout_stage'] ) ? 0 : 1;
			$opts['strip_foreign_card_css'] = empty( $_POST['strip_foreign_card_css'] ) ? 0 : 1;

			$raw  = isset( $_POST['overrides'] ) && is_array( $_POST['overrides'] ) ? wp_unslash( $_POST['overrides'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wird unten pro Typ bereinigt.
			$vars = MP_Design::variables();
			$out  = array();
			foreach ( $vars as $key => $v ) {
				$val = isset( $raw[ $key ] ) && is_string( $raw[ $key ] ) ? trim( $raw[ $key ] ) : '';
				if ( '' === $val ) {
					continue;
				}
				if ( 'color' === $v['type'] ) {
					$val = sanitize_hex_color( $val );
				} elseif ( 'size' === $v['type'] ) {
					$val = preg_match( '/^\d{1,3}(\.\d{1,2})?(px|rem|em|%)$/', $val ) ? $val : ( preg_match( '/^\d{1,3}$/', $val ) ? $val . 'px' : '' );
				} else {
					$val = preg_match( '/^[\w\s,"\'-]{1,120}$/u', $val ) ? $val : '';
				}
				if ( $val ) {
					$out[ $key ] = $val;
				}
			}
			$opts['overrides'] = $out;
		} elseif ( 'css' === $section ) {
			// Eigenes CSS nur für Benutzer, die auch im Customizer CSS bearbeiten dürfen.
			if ( ! current_user_can( 'edit_css' ) ) {
				wp_die( esc_html__( 'Du darfst kein eigenes CSS speichern.', 'ultimate-member' ), 403 );
			}
			$css                = isset( $_POST['custom_css'] ) ? wp_unslash( $_POST['custom_css'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize_custom_css().
			$opts['custom_css'] = MP_Design::sanitize_custom_css( $css );
		}

		update_option( MP_Design::OPTION, $opts, true );
		MP_Theme_Colors::flush();

		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG, 'tab' => 'css' === $section ? 'css' : 'design', 'mp-saved' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Zurücksetzen.
	 */
	public static function reset() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'ultimate-member' ), 403 );
		}
		check_admin_referer( 'mp_reset_design' );
		$opts              = MP_Design::get_options();
		$opts['overrides'] = array();
		update_option( MP_Design::OPTION, $opts, true );
		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG, 'mp-saved' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Klassen neu einlesen.
	 */
	public static function rescan() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'ultimate-member' ), 403 );
		}
		check_admin_referer( 'mp_rescan_classes' );
		MP_Class_Reference::get( true );
		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG, 'tab' => 'classes' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Starter-CSS herunterladen.
	 */
	public static function download_starter() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'ultimate-member' ), 403 );
		}
		check_admin_referer( 'mp_download_starter' );
		nocache_headers();
		header( 'Content-Type: text/css; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="members-plugin-starter.css"' );
		header( 'X-Content-Type-Options: nosniff' );
		echo MP_Class_Reference::starter_css(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS-Datei-Download.
		exit;
	}

	/**
	 * Seite ausgeben.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$tab  = self::tab();
		$tabs = array(
			'design'    => __( 'Farben & Form', 'ultimate-member' ),
			'css'       => __( 'Eigenes CSS', 'ultimate-member' ),
			'classes'   => __( 'CSS-Klassen', 'ultimate-member' ),
			'variables' => __( 'Variablen & Anleitung', 'ultimate-member' ),
		);
		?>
		<div class="wrap mp-wrap">
			<h1><?php esc_html_e( 'Design & CSS', 'ultimate-member' ); ?></h1>
			<?php if ( isset( $_GET['mp-saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Gespeichert.', 'ultimate-member' ); ?></p></div>
			<?php endif; ?>
			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $key => $label ) : ?>
					<a class="nav-tab <?php echo $tab === $key ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => self::SLUG, 'tab' => $key ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>
			<div class="mp-tab mp-tab-<?php echo esc_attr( $tab ); ?>">
				<?php
				switch ( $tab ) {
					case 'css':
						self::render_css();
						break;
					case 'classes':
						self::render_classes();
						break;
					case 'variables':
						self::render_variables();
						break;
					default:
						self::render_design();
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Tab: Farben & Form.
	 */
	private static function render_design() {
		$opts     = MP_Design::get_options();
		$vars     = MP_Design::variables();
		$auto     = MP_Design::auto_values();
		$detected = MP_Theme_Colors::detect();
		$resolved = MP_Design::resolved_values();
		$palette  = MP_Theme_Colors::get_palette();
		$theme    = wp_get_theme();

		$style = '';
		foreach ( $resolved as $k => $v ) {
			$v = MP_Design::sanitize_css_value( $v );
			if ( '' !== $v ) {
				$style .= '--um-' . $k . ':' . $v . ';';
			}
		}
		?>
		<div class="mp-grid">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mp-card mp-form">
				<input type="hidden" name="action" value="mp_save_design">
				<input type="hidden" name="mp_section" value="design">
				<?php wp_nonce_field( 'mp_save_design' ); ?>

				<h2><?php esc_html_e( 'Theme-Anpassung', 'ultimate-member' ); ?></h2>
				<label class="mp-switch">
					<input type="checkbox" name="auto_colors" value="1" <?php checked( ! empty( $opts['auto_colors'] ) ); ?> data-mp-auto>
					<span><?php echo esc_html( sprintf( /* translators: %s theme name */ __( 'Farben automatisch vom Theme „%s“ übernehmen', 'ultimate-member' ), $theme->get( 'Name' ) ) ); ?></span>
				</label>
				<p class="description"><?php esc_html_e( 'Liest Primär-, Text- und Hintergrundfarbe, Schrift und Button-Radius aus theme.json, der Theme-Palette, dem Customizer bzw. Elementor. Alle Abstufungen (Rahmen, Hover, gedämpfter Text …) werden daraus berechnet – auch für dunkle Themes. Eigene Werte unten haben immer Vorrang.', 'ultimate-member' ); ?></p>

				<?php if ( $palette ) : ?>
					<div class="mp-palette" aria-label="<?php esc_attr_e( 'Erkannte Theme-Palette', 'ultimate-member' ); ?>">
						<?php foreach ( $palette as $p ) : ?>
							<span class="mp-swatch" style="background:<?php echo esc_attr( $p['color'] ); ?>" title="<?php echo esc_attr( $p['name'] . ' (' . $p['slug'] . '): ' . $p['color'] ); ?>"></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php
				$group_labels = array(
					'colors' => __( 'Farben', 'ultimate-member' ),
					'status' => __( 'Statusfarben', 'ultimate-member' ),
					'shape'  => __( 'Form & Schrift', 'ultimate-member' ),
					'cards'  => __( 'Mitgliederkarten (Bühnen-Layout)', 'ultimate-member' ),
				);
				foreach ( $group_labels as $group => $glabel ) :
					?>
					<h2><?php echo esc_html( $glabel ); ?></h2>
					<table class="mp-vars widefat striped">
						<thead><tr>
							<th><?php esc_html_e( 'Einstellung', 'ultimate-member' ); ?></th>
							<th><?php esc_html_e( 'Vom Theme', 'ultimate-member' ); ?></th>
							<th><?php esc_html_e( 'Eigener Wert', 'ultimate-member' ); ?></th>
						</tr></thead>
						<tbody>
						<?php
						foreach ( $vars as $key => $v ) :
							if ( $v['group'] !== $group ) {
								continue;
							}
							$auto_val = isset( $auto[ $key ] ) ? $auto[ $key ] : '';
							$override = isset( $opts['overrides'][ $key ] ) ? $opts['overrides'][ $key ] : '';
							$src_key  = str_replace( '-', '_', $key );
							$source   = isset( $detected['sources'][ $src_key ] ) ? $detected['sources'][ $src_key ] : ( $auto_val ? __( 'berechnet', 'ultimate-member' ) : '' );
							?>
							<tr>
								<td>
									<strong><?php echo esc_html( $v['label'] ); ?></strong>
									<code class="mp-var-name">--um-<?php echo esc_html( $key ); ?></code>
									<?php if ( $v['desc'] ) : ?><br><span class="description"><?php echo esc_html( $v['desc'] ); ?></span><?php endif; ?>
								</td>
								<td class="mp-auto-cell">
									<?php if ( $auto_val ) : ?>
										<?php if ( 'color' === $v['type'] ) : ?><span class="mp-swatch" style="background:<?php echo esc_attr( MP_Design::sanitize_css_value( $auto_val ) ); ?>"></span><?php endif; ?>
										<code><?php echo esc_html( $auto_val ); ?></code>
										<?php if ( $source ) : ?><br><small title="<?php echo esc_attr( $source ); ?>"><?php echo esc_html( $source ); ?></small><?php endif; ?>
									<?php else : ?>
										<span class="mp-muted"><?php echo esc_html( '' !== $v['default'] ? sprintf( /* translators: %s value */ __( 'Original: %s', 'ultimate-member' ), $v['default'] ) : __( 'Original', 'ultimate-member' ) ); ?></span>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( 'color' === $v['type'] ) : ?>
										<input type="text" class="mp-color" name="overrides[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $override ); ?>" data-var="<?php echo esc_attr( $key ); ?>" data-default-color="" placeholder="<?php echo esc_attr( $auto_val ? $auto_val : $v['default'] ); ?>">
									<?php else : ?>
										<input type="text" class="regular-text mp-text-var" name="overrides[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $override ); ?>" data-var="<?php echo esc_attr( $key ); ?>" placeholder="<?php echo esc_attr( $auto_val ? $auto_val : ( 'font' === $v['type'] ? 'inherit' : 'z.B. 6px' ) ); ?>">
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endforeach; ?>

				<h2><?php esc_html_e( 'Werkzeuge', 'ultimate-member' ); ?></h2>
				<label class="mp-switch"><input type="checkbox" name="inspector" value="1" <?php checked( ! empty( $opts['inspector'] ) ); ?>> <span><?php esc_html_e( 'Klassen-Inspector im Frontend (nur für Administratoren, über die Admin-Leiste „CSS-Klassen“)', 'ultimate-member' ); ?></span></label><br>
				<label class="mp-switch"><input type="checkbox" name="editor_styles" value="1" <?php checked( ! empty( $opts['editor_styles'] ) ); ?>> <span><?php esc_html_e( 'Farben auch im Block-Editor anwenden', 'ultimate-member' ); ?></span></label><br>
				<label class="mp-switch"><input type="checkbox" name="theme_typography" value="1" <?php checked( ! empty( $opts['theme_typography'] ) ); ?>> <span><?php esc_html_e( 'Typografie des Themes übernehmen (Schriftart, Schriftgröße, Zeilenhöhe, Überschriften-Schrift)', 'ultimate-member' ); ?></span></label><br>
				<label class="mp-switch"><input type="checkbox" name="original_cards" value="1" <?php checked( ! empty( $opts['original_cards'] ) ); ?>> <span><?php esc_html_e( 'Mitgliederkarten aus den Original-Vorlagen (Vorlagen-Überschreibungen des Themes ignorieren)', 'ultimate-member' ); ?></span></label><br>
				<label class="mp-switch"><input type="checkbox" name="card_layout_stage" value="1" <?php checked( ! empty( $opts['card_layout_stage'] ) ); ?>> <span><?php esc_html_e( 'Mitgliederkarten im Bühnen-Layout (4 Spalten, Foto ragt über die Karte, Hover-Effekte; Schriften und Farben unten unter „Mitgliederkarten“)', 'ultimate-member' ); ?></span></label><br>
				<label class="mp-switch"><input type="checkbox" name="strip_foreign_card_css" value="1" <?php checked( ! empty( $opts['strip_foreign_card_css'] ) ); ?>> <span><?php esc_html_e( 'Karten-Stile anderer Plugins/des Themes entfernen (reines Original-Design, z.B. ohne Stückverwaltungs-Hintergrund)', 'ultimate-member' ); ?></span></label>

				<p class="submit">
					<?php submit_button( __( 'Speichern', 'ultimate-member' ), 'primary', 'submit', false ); ?>
				</p>
			</form>

			<div class="mp-card mp-preview-card">
				<h2><?php esc_html_e( 'Live-Vorschau', 'ultimate-member' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Echte Plugin-Klassen – Änderungen links werden sofort angezeigt. Klasse anklicken, um sie zu kopieren.', 'ultimate-member' ); ?></p>
				<div class="mp-preview-scope" style="<?php echo esc_attr( $style ); ?>" data-initial-style="<?php echo esc_attr( $style ); ?>">
					<?php self::preview_markup(); ?>
				</div>
				<div class="mp-preview-classes" aria-live="polite"></div>
			</div>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mp-reset" onsubmit="return confirm('<?php echo esc_js( __( 'Alle eigenen Werte entfernen?', 'ultimate-member' ) ); ?>');">
			<input type="hidden" name="action" value="mp_reset_design">
			<?php wp_nonce_field( 'mp_reset_design' ); ?>
			<?php submit_button( __( 'Eigene Werte zurücksetzen', 'ultimate-member' ), 'secondary', 'submit', false ); ?>
		</form>
		<?php
	}

	/**
	 * Vorschau-Markup mit Originalklassen.
	 */
	private static function preview_markup() {
		?>
		<div class="um um-login um-mp-preview">
			<div class="um-form">
				<div class="um-row _um_row_1">
					<div class="um-col-1">
						<div class="um-field um-field-text um-field-username">
							<div class="um-field-label"><label><?php esc_html_e( 'Benutzername oder E-Mail', 'ultimate-member' ); ?></label><span class="um-req">*</span><div class="um-clear"></div></div>
							<div class="um-field-area"><input class="um-form-field" type="text" placeholder="<?php esc_attr_e( 'max@beispiel.de', 'ultimate-member' ); ?>"></div>
						</div>
						<div class="um-field um-field-password">
							<div class="um-field-label"><label><?php esc_html_e( 'Passwort', 'ultimate-member' ); ?></label><div class="um-clear"></div></div>
							<div class="um-field-area"><input class="um-form-field um-error" type="password" value="secret"></div>
							<div class="um-field-error"><span class="um-field-arrow"><i class="um-faicon-caret-up"></i></span><?php esc_html_e( 'Das Passwort ist falsch.', 'ultimate-member' ); ?></div>
						</div>
					</div>
				</div>
				<div class="um-col-alt">
					<div class="um-left um-half"><input type="submit" value="<?php esc_attr_e( 'Anmelden', 'ultimate-member' ); ?>" class="um-button" onclick="return false;"></div>
					<div class="um-right um-half"><a href="#" class="um-button um-alt" onclick="return false;"><?php esc_html_e( 'Registrieren', 'ultimate-member' ); ?></a></div>
					<div class="um-clear"></div>
				</div>
				<div class="um-col-alt-b"><a href="#" class="um-link-alt" onclick="return false;"><?php esc_html_e( 'Passwort vergessen?', 'ultimate-member' ); ?></a> &nbsp;·&nbsp; <a href="#" class="um-link" onclick="return false;"><?php esc_html_e( 'Link (.um-link)', 'ultimate-member' ); ?></a></div>
				<p class="um-notice success"><?php esc_html_e( 'Profil gespeichert.', 'ultimate-member' ); ?></p>
				<p class="um-notice err"><?php esc_html_e( 'Bitte prüfe deine Eingaben.', 'ultimate-member' ); ?></p>
				<p class="um-notice warning"><?php esc_html_e( 'Dein Konto wartet auf Freigabe.', 'ultimate-member' ); ?></p>
			</div>
		</div>
		<div class="um um-profile um-mp-preview">
			<div class="um-profile-nav">
				<div class="um-profile-nav-item um-profile-nav-main active"><a href="#" onclick="return false;"><i class="um-faicon-user"></i><span class="title"><?php esc_html_e( 'Über mich', 'ultimate-member' ); ?></span></a></div>
				<div class="um-profile-nav-item um-profile-nav-posts"><a href="#" onclick="return false;"><i class="um-faicon-pencil"></i><span class="title"><?php esc_html_e( 'Beiträge', 'ultimate-member' ); ?></span></a></div>
				<div class="um-profile-nav-item um-profile-nav-comments"><a href="#" onclick="return false;"><i class="um-faicon-comment"></i><span class="title"><?php esc_html_e( 'Kommentare', 'ultimate-member' ); ?></span></a></div>
				<div class="um-clear"></div>
			</div>
			<div class="um-profile-meta"><div class="um-main-meta"><div class="um-name"><a href="#" onclick="return false;">Max Mustermann</a></div><div class="um-clear"></div></div><div class="um-meta"><span>Wien</span></div></div>
		</div>
		<?php
	}

	/**
	 * Tab: Eigenes CSS.
	 */
	private static function render_css() {
		$opts = MP_Design::get_options();
		if ( ! current_user_can( 'edit_css' ) ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Dein Benutzerkonto darf kein eigenes CSS bearbeiten (Berechtigung "edit_css").', 'ultimate-member' ) . '</p></div>';
			return;
		}
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mp-card">
			<input type="hidden" name="action" value="mp_save_design">
			<input type="hidden" name="mp_section" value="css">
			<?php wp_nonce_field( 'mp_save_design' ); ?>
			<p><?php esc_html_e( 'Dieses CSS wird auf allen Seiten nach den Plugin-Styles geladen. Tipp: Nutze die Variablen (z.B. var(--um-primary)), damit dein CSS sich weiterhin dem Theme anpasst. Alle Klassen findest du im Tab „CSS-Klassen“ oder im Frontend über den Klassen-Inspector.', 'ultimate-member' ); ?></p>
			<textarea name="custom_css" id="mp-custom-css" rows="22" class="large-text code" spellcheck="false" placeholder="/* Beispiel */&#10;.um .um-button {&#10;	text-transform: uppercase;&#10;}"><?php echo esc_textarea( $opts['custom_css'] ); ?></textarea>
			<?php submit_button( __( 'CSS speichern', 'ultimate-member' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Tab: CSS-Klassen.
	 */
	private static function render_classes() {
		$data   = MP_Class_Reference::get();
		$groups = MP_Class_Reference::groups();
		$count  = array();
		foreach ( $data['classes'] as $info ) {
			$count[ $info['group'] ] = isset( $count[ $info['group'] ] ) ? $count[ $info['group'] ] + 1 : 1;
		}
		?>
		<div class="mp-card">
			<div class="mp-class-toolbar">
				<input type="search" class="regular-text" id="mp-class-search" placeholder="<?php esc_attr_e( 'Klasse suchen, z.B. button, profile-nav, field-label …', 'ultimate-member' ); ?>" autofocus>
				<select id="mp-class-group">
					<option value=""><?php echo esc_html( sprintf( /* translators: %d count */ __( 'Alle Bereiche (%d)', 'ultimate-member' ), count( $data['classes'] ) ) ); ?></option>
					<?php foreach ( $groups as $gkey => $g ) : ?>
						<?php if ( ! empty( $count[ $gkey ] ) ) : ?>
							<option value="<?php echo esc_attr( $gkey ); ?>"><?php echo esc_html( $g['label'] . ' (' . $count[ $gkey ] . ')' ); ?></option>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>
				<label><input type="checkbox" id="mp-class-styled"> <?php esc_html_e( 'nur gestylte Klassen', 'ultimate-member' ); ?></label>
				<span class="mp-spacer"></span>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="mp_download_starter">
					<?php wp_nonce_field( 'mp_download_starter' ); ?>
					<button class="button button-primary"><?php esc_html_e( 'Starter-CSS mit allen Klassen herunterladen', 'ultimate-member' ); ?></button>
				</form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="mp_rescan_classes">
					<?php wp_nonce_field( 'mp_rescan_classes' ); ?>
					<button class="button"><?php esc_html_e( 'Neu einlesen', 'ultimate-member' ); ?></button>
				</form>
			</div>
			<p class="description">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: class count 2: icon count */
						__( '%1$d Klassen aus Stylesheets, Templates, PHP und JavaScript des Plugins. Dazu kommen %2$d Icon-Klassen (um-faicon-*, um-icon-*). „Gestylt in“ = hier ist das Aussehen definiert; „Verwendet in“ = hier wird die Klasse im HTML ausgegeben. Templates kannst du in dein Theme kopieren: wp-content/themes/DEIN-THEME/ultimate-member/…', 'ultimate-member' ),
						count( $data['classes'] ),
						(int) $data['icons']
					)
				);
				?>
			</p>
			<table class="widefat striped mp-class-table">
				<thead><tr>
					<th style="width:28%"><?php esc_html_e( 'Klasse', 'ultimate-member' ); ?></th>
					<th style="width:16%"><?php esc_html_e( 'Bereich', 'ultimate-member' ); ?></th>
					<th><?php esc_html_e( 'Gestylt in', 'ultimate-member' ); ?></th>
					<th><?php esc_html_e( 'Verwendet in', 'ultimate-member' ); ?></th>
				</tr></thead>
				<tbody>
				<?php foreach ( $data['classes'] as $class => $info ) : ?>
					<tr data-class="<?php echo esc_attr( $class ); ?>" data-group="<?php echo esc_attr( $info['group'] ); ?>" data-styled="<?php echo $info['css'] ? '1' : '0'; ?>">
						<td><button type="button" class="mp-copy mp-class-chip" data-copy=".<?php echo esc_attr( $class ); ?>" title="<?php esc_attr_e( 'Selektor kopieren', 'ultimate-member' ); ?>">.<?php echo esc_html( $class ); ?></button></td>
						<td><?php echo esc_html( isset( $groups[ $info['group'] ] ) ? $groups[ $info['group'] ]['label'] : $info['group'] ); ?></td>
						<td class="mp-files"><?php echo esc_html( implode( ', ', $info['css'] ) ); ?></td>
						<td class="mp-files"><?php echo esc_html( implode( ', ', array_slice( $info['used'], 0, 5 ) ) . ( count( $info['used'] ) > 5 ? ' … (+' . ( count( $info['used'] ) - 5 ) . ')' : '' ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p class="mp-empty" hidden><?php esc_html_e( 'Keine Klasse gefunden.', 'ultimate-member' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Tab: Variablen & Anleitung.
	 */
	private static function render_variables() {
		$vars   = MP_Design::variables();
		$values = MP_Design::resolved_values();
		?>
		<div class="mp-card">
			<h2><?php esc_html_e( 'So passt du das Plugin an', 'ultimate-member' ); ?></h2>
			<ol class="mp-steps">
				<li><strong><?php esc_html_e( 'Farben & Form', 'ultimate-member' ); ?>:</strong> <?php esc_html_e( 'Standardmäßig übernimmt das Plugin die Farben deines Themes. Einzelne Werte kannst du überschreiben.', 'ultimate-member' ); ?></li>
				<li><strong><?php esc_html_e( 'Klasse finden', 'ultimate-member' ); ?>:</strong> <?php esc_html_e( 'Im Frontend in der Admin-Leiste „CSS-Klassen“ anklicken und mit der Maus auf ein Element zeigen. Klick = Element festhalten, Klassen und einen passenden Selektor kopieren. Esc beendet den Inspector.', 'ultimate-member' ); ?></li>
				<li><strong><?php esc_html_e( 'CSS schreiben', 'ultimate-member' ); ?>:</strong> <?php esc_html_e( 'Im Tab „Eigenes CSS“ oder im Child-Theme. Verwende die Variablen, damit Hover-, Rahmen- und Textfarben zusammenpassen.', 'ultimate-member' ); ?></li>
				<li><strong><?php esc_html_e( 'Markup ändern', 'ultimate-member' ); ?>:</strong> <?php esc_html_e( 'Templates aus /templates/ nach wp-content/themes/DEIN-THEME/ultimate-member/ kopieren und dort anpassen. Die Klassen bleiben dieselben.', 'ultimate-member' ); ?></li>
				<li><strong><?php esc_html_e( 'Für Entwickler', 'ultimate-member' ); ?>:</strong> <?php esc_html_e( 'Filter mp_css_variables (Werte), mp_design_variables (Liste), mp_detected_theme_colors (Theme-Erkennung). Body-Klassen: um-mp-design, um-theme-dark.', 'ultimate-member' ); ?></li>
			</ol>
			<h2><?php esc_html_e( 'Alle Variablen', 'ultimate-member' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Variable', 'ultimate-member' ); ?></th><th><?php esc_html_e( 'Aktueller Wert', 'ultimate-member' ); ?></th><th><?php esc_html_e( 'Original', 'ultimate-member' ); ?></th><th><?php esc_html_e( 'Wofür', 'ultimate-member' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $vars as $key => $v ) : ?>
					<tr>
						<td><button type="button" class="mp-copy mp-class-chip" data-copy="var(--um-<?php echo esc_attr( $key ); ?>)">--um-<?php echo esc_html( $key ); ?></button></td>
						<td><?php if ( isset( $values[ $key ] ) ) : ?><?php if ( 'color' === $v['type'] ) : ?><span class="mp-swatch" style="background:<?php echo esc_attr( MP_Design::sanitize_css_value( $values[ $key ] ) ); ?>"></span><?php endif; ?><code><?php echo esc_html( $values[ $key ] ); ?></code><?php else : ?><span class="mp-muted">—</span><?php endif; ?></td>
						<td><code><?php echo esc_html( '' !== $v['default'] ? $v['default'] : '—' ); ?></code></td>
						<td><?php echo esc_html( $v['label'] . ( $v['desc'] ? ' – ' . $v['desc'] : '' ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<h2><?php esc_html_e( 'Beispiele', 'ultimate-member' ); ?></h2>
<pre class="mp-code">/* Runde Buttons in Theme-Farbe, Großbuchstaben */
.um .um-button {
	border-radius: 999px !important;
	text-transform: uppercase;
	letter-spacing: .04em;
}

/* Aktiver Profil-Tab mit Unterstrich statt Fläche */
.um .um-profile-nav-item.active a {
	background: transparent;
	color: var(--um-primary);
	box-shadow: inset 0 -3px 0 var(--um-primary);
}

/* Mitgliederkarten im Verzeichnis */
.um-directory .um-member {
	border-color: var(--um-border);
	background: var(--um-bg-alt);
}

/* Nur bei dunklen Themes */
.um-theme-dark .um .um-form input[type=text] {
	box-shadow: none;
}</pre>
		</div>
		<?php
	}
}
