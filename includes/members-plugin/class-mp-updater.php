<?php
/**
 * Members Plugin – Updates direkt aus GitHub-Releases.
 *
 * Funktionsweise:
 *  - Der Plugin-Header "Update URI: https://github.com/…" sorgt dafür, dass WordPress
 *    nicht bei WordPress.org nachfragt, sondern den Filter `update_plugins_github.com` nutzt.
 *  - Dieser Updater fragt das neueste Release des Repos über die GitHub-API ab
 *    (Ergebnis 6 Stunden gecacht) und meldet es WordPress als Update.
 *  - Ein WordPress-Cron prüft zusätzlich alle sechs Stunden automatisch nach neuen Releases;
 *    verfügbare Updates werden angezeigt, aber nicht ungefragt installiert.
 *  - Paket: das Release-Asset "members-plugin.zip" (wird von der GitHub Action gebaut),
 *    ersatzweise das Quellcode-ZIP des Tags (der Ordner wird dann automatisch umbenannt).
 *  - Private Repos: GitHub-Token unter "Members Plugin › Updates" oder als Konstante
 *    MP_GITHUB_TOKEN in der wp-config.php.
 *
 * @package MembersPlugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class MP_Updater
 */
final class MP_Updater {

	const TRANSIENT    = 'mp_github_release_v2';
	const TOKEN_OPTION = 'mp_github_token';
	const PAGE         = 'mp-updates';
	const ASSET_NAME   = 'members-plugin.zip';
	const CRON_HOOK    = 'mp_automatic_update_check';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_filter( 'cron_schedules', array( __CLASS__, 'add_cron_schedule' ) );
		add_action( 'init', array( __CLASS__, 'schedule_automatic_check' ), 30 );
		add_action( self::CRON_HOOK, array( __CLASS__, 'automatic_check' ) );
		if ( defined( 'MP_FILE' ) && function_exists( 'register_deactivation_hook' ) ) {
			register_deactivation_hook( MP_FILE, array( __CLASS__, 'deactivate' ) );
		}

		add_filter( 'update_plugins_github.com', array( __CLASS__, 'check_update' ), 10, 3 );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_info' ), 20, 3 );
		add_filter( 'upgrader_pre_download', array( __CLASS__, 'pre_download' ), 10, 3 );
		add_filter( 'upgrader_source_selection', array( __CLASS__, 'fix_folder_name' ), 10, 4 );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'flush' ), 10, 0 );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( __CLASS__, 'menu' ), 1002 );
			add_action( 'admin_post_mp_updater_save', array( __CLASS__, 'save' ) );
			add_action( 'admin_post_mp_updater_check', array( __CLASS__, 'force_check' ) );
			add_filter( 'plugin_row_meta', array( __CLASS__, 'row_meta' ), 10, 2 );
		}
	}

	/**
	 * Add a six-hour WordPress cron interval for background release checks.
	 *
	 * @param array $schedules Registered schedules.
	 * @return array
	 */
	public static function add_cron_schedule( $schedules ) {
		if ( ! isset( $schedules['mp_every_six_hours'] ) ) {
			$schedules['mp_every_six_hours'] = array(
				'interval' => 6 * HOUR_IN_SECONDS,
				'display'  => __( 'Alle sechs Stunden (Members Plugin)', 'ultimate-member' ),
			);
		}
		return $schedules;
	}

	/**
	 * Ensure automatic update checks are scheduled while the plugin is active.
	 */
	public static function schedule_automatic_check() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + 5 * MINUTE_IN_SECONDS, 'mp_every_six_hours', self::CRON_HOOK );
		}
	}

	/**
	 * Remove the scheduled updater event on plugin deactivation.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * Fetch the latest release and refresh WordPress's available-updates transient.
	 * This only checks and advertises updates; it does not install them automatically.
	 */
	public static function automatic_check() {
		self::get_release( true );
		delete_site_transient( 'update_plugins' );

		if ( function_exists( 'wp_update_plugins' ) ) {
			wp_update_plugins();
		}
	}

	/**
	 * Repository "owner/name" (aus dem Update-URI-Header, per Konstante überschreibbar).
	 *
	 * @return string
	 */
	public static function repo() {
		if ( defined( 'MP_GITHUB_REPO' ) && is_string( MP_GITHUB_REPO ) && preg_match( '#^[\w.-]+/[\w.-]+$#', MP_GITHUB_REPO ) ) {
			return MP_GITHUB_REPO;
		}
		return 'paulhubacek1-afk/MembersPlugin';
	}

	/**
	 * GitHub-Token (Konstante hat Vorrang vor der Option).
	 *
	 * @return string
	 */
	private static function token() {
		if ( defined( 'MP_GITHUB_TOKEN' ) && is_string( MP_GITHUB_TOKEN ) && '' !== MP_GITHUB_TOKEN ) {
			return MP_GITHUB_TOKEN;
		}
		$token = get_option( self::TOKEN_OPTION, '' );
		return is_string( $token ) ? $token : '';
	}

	/**
	 * Standard-Header für GitHub-Anfragen.
	 *
	 * @param string $accept Accept-Header.
	 * @return array
	 */
	private static function headers( $accept = 'application/vnd.github+json' ) {
		$headers = array(
			'Accept'               => $accept,
			'User-Agent'           => 'MembersPlugin-Updater/' . MP_VERSION . '; ' . home_url( '/' ),
			'X-GitHub-Api-Version' => '2022-11-28',
		);
		$token   = self::token();
		if ( '' !== $token ) {
			$headers['Authorization'] = 'Bearer ' . $token;
		}
		return $headers;
	}

	/**
	 * Neuestes Release abrufen (gecacht).
	 *
	 * @param bool $force Cache ignorieren.
	 * @return array{version:string,tag:string,package:string,url:string,body:string,published:string,error:string}|null
	 */
	public static function get_release( $force = false ) {
		$cached = get_site_transient( self::TRANSIENT );
		if ( ! $force && is_array( $cached ) ) {
			return empty( $cached['version'] ) ? null : $cached;
		}

		// The repository also carries the bundled core's upstream tags (e.g. 2.14.1).
		// "releases/latest" could therefore point at a foreign version, so fetch the
		// recent releases and pick the highest Members Plugin release (1.x line).
		$response = wp_remote_get(
			'https://api.github.com/repos/' . self::repo() . '/releases?per_page=30',
			array(
				'timeout' => 10,
				'headers' => self::headers(),
			)
		);

		$code     = (int) wp_remote_retrieve_response_code( $response );
		$releases = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$data     = is_array( $releases ) ? self::pick_release( $releases ) : null;

		if ( is_wp_error( $response ) || 200 !== $code || ! is_array( $data ) || empty( $data['tag_name'] ) ) {
			if ( is_wp_error( $response ) ) {
				$error = $response->get_error_message();
			} elseif ( 200 === $code ) {
				$error = __( 'Kein passendes Members-Plugin-Release (1.x mit members-plugin.zip) gefunden.', 'ultimate-member' );
			} elseif ( 404 === $code ) {
				$error = '' === self::token()
					? __( 'Kein Release gefunden. Bei einem privaten Repository wird ein GitHub-Token benötigt.', 'ultimate-member' )
					: __( 'Kein Release gefunden (oder der Token hat keinen Zugriff auf das Repository).', 'ultimate-member' );
			} elseif ( 401 === $code ) {
				$error = __( 'Der GitHub-Token ist ungültig oder abgelaufen.', 'ultimate-member' );
			} elseif ( 403 === $code ) {
				$error = __( 'GitHub-API-Limit erreicht oder Zugriff verweigert. Später erneut versuchen.', 'ultimate-member' );
			} else {
				/* translators: %d HTTP status */
				$error = sprintf( __( 'Unerwartete Antwort von GitHub (HTTP %d).', 'ultimate-member' ), $code );
			}
			// Fehler kurz cachen, damit nicht jede Admin-Seite GitHub anfragt.
			set_site_transient( self::TRANSIENT, array( 'version' => '', 'error' => $error ), 30 * MINUTE_IN_SECONDS );
			return null;
		}

		$package = '';
		if ( ! empty( $data['assets'] ) && is_array( $data['assets'] ) ) {
			foreach ( $data['assets'] as $asset ) {
				if ( isset( $asset['name'], $asset['url'] ) && self::ASSET_NAME === $asset['name'] ) {
					// API-URL des Assets: funktioniert auch bei privaten Repos (mit Token).
					$package = $asset['url'];
					break;
				}
			}
		}
		if ( '' === $package && ! empty( $data['zipball_url'] ) ) {
			$package = $data['zipball_url'];
		}

		$release = array(
			'version'   => ltrim( (string) $data['tag_name'], 'vV' ),
			'tag'       => (string) $data['tag_name'],
			'package'   => esc_url_raw( $package ),
			'url'       => esc_url_raw( isset( $data['html_url'] ) ? $data['html_url'] : 'https://github.com/' . self::repo() ),
			'body'      => isset( $data['body'] ) ? (string) $data['body'] : '',
			'published' => isset( $data['published_at'] ) ? (string) $data['published_at'] : '',
			'error'     => '',
		);

		set_site_transient( self::TRANSIENT, $release, 6 * HOUR_IN_SECONDS );
		return $release;
	}

	/**
	 * Select the newest published Members Plugin release (1.x line) from a list.
	 * Releases with the members-plugin.zip asset win over source-only releases.
	 *
	 * @param array $releases GitHub API release objects.
	 * @return array|null
	 */
	private static function pick_release( $releases ) {
		$best = null;
		foreach ( $releases as $item ) {
			if ( ! is_array( $item ) || empty( $item['tag_name'] ) || ! empty( $item['draft'] ) || ! empty( $item['prerelease'] ) ) {
				continue;
			}
			$version = ltrim( (string) $item['tag_name'], 'vV' );
			if ( ! preg_match( '/^1\.\d+(?:\.\d+){0,2}$/', $version ) ) {
				continue;
			}
			$has_asset = false;
			if ( ! empty( $item['assets'] ) && is_array( $item['assets'] ) ) {
				foreach ( $item['assets'] as $asset ) {
					if ( isset( $asset['name'] ) && self::ASSET_NAME === $asset['name'] ) {
						$has_asset = true;
						break;
					}
				}
			}
			if ( ! $has_asset && empty( $item['zipball_url'] ) ) {
				continue;
			}
			if ( null === $best || version_compare( $version, ltrim( (string) $best['tag_name'], 'vV' ), '>' ) ) {
				$best = $item;
			}
		}
		return $best;
	}

	/**
	 * Letzter Fehler (für die Admin-Seite).
	 *
	 * @return string
	 */
	public static function last_error() {
		$cached = get_site_transient( self::TRANSIENT );
		return is_array( $cached ) && ! empty( $cached['error'] ) ? $cached['error'] : '';
	}

	/**
	 * Update-Daten an WordPress liefern.
	 *
	 * @param array|false $update      Bisheriges Ergebnis.
	 * @param array       $plugin_data Plugin-Header.
	 * @param string      $plugin_file Plugin-Datei.
	 * @return array|false
	 */
	public static function check_update( $update, $plugin_data, $plugin_file ) {
		if ( UM_PLUGIN !== $plugin_file ) {
			return $update;
		}
		// "Erneut prüfen" in Dashboard › Aktualisierungen umgeht den Cache.
		$force   = is_admin() && isset( $_GET['force-check'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$release = self::get_release( $force );
		if ( ! $release || empty( $release['package'] ) ) {
			return $update;
		}
		return array(
			'slug'         => dirname( UM_PLUGIN ),
			'version'      => $release['version'],
			'url'          => $release['url'],
			'package'      => $release['package'],
			'requires_php' => '7.0',
			'tested'       => get_bloginfo( 'version' ),
		);
	}

	/**
	 * "Details ansehen" in der Pluginliste.
	 *
	 * @param false|object|array $result Ergebnis.
	 * @param string             $action Aktion.
	 * @param object             $args   Argumente.
	 * @return false|object|array
	 */
	public static function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || dirname( UM_PLUGIN ) !== $args->slug ) {
			return $result;
		}
		$release = self::get_release();
		$notes   = $release && '' !== $release['body'] ? $release['body'] : __( 'Keine Release-Notizen vorhanden.', 'ultimate-member' );

		return (object) array(
			'name'          => UM_PLUGIN_NAME,
			'slug'          => $args->slug,
			'version'       => $release ? $release['version'] : MP_VERSION,
			'author'        => 'Members Plugin',
			'homepage'      => 'https://github.com/' . self::repo(),
			'requires'      => '6.2',
			'requires_php'  => '7.0',
			'last_updated'  => $release ? $release['published'] : '',
			'download_link' => $release ? $release['package'] : '',
			'sections'      => array(
				'description' => '<p>' . esc_html__( 'Mitgliederbereich, Profile, Registrierung und Mitgliederverzeichnis mit automatischer Theme-Farbanpassung. Fork von Ultimate Member.', 'ultimate-member' ) . '</p>',
				'changelog'   => wpautop( esc_html( $notes ) ),
			),
		);
	}

	/**
	 * Paket von GitHub laden (mit Token, ohne den Token an den Download-Server weiterzugeben).
	 *
	 * @param bool|WP_Error|string $reply   Kurzschluss.
	 * @param string               $package Paket-URL.
	 * @param WP_Upgrader          $upgrader Upgrader.
	 * @return bool|WP_Error|string
	 */
	public static function pre_download( $reply, $package, $upgrader ) {
		$prefix = 'https://api.github.com/repos/' . self::repo() . '/';
		if ( false !== $reply || ! is_string( $package ) || 0 !== stripos( $package, $prefix ) ) {
			return $reply;
		}

		$is_asset = false !== strpos( $package, '/releases/assets/' );
		$response = wp_remote_get(
			$package,
			array(
				'timeout'     => 30,
				'redirection' => 0,
				'headers'     => self::headers( $is_asset ? 'application/octet-stream' : 'application/vnd.github+json' ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code     = (int) wp_remote_retrieve_response_code( $response );
		$location = wp_remote_retrieve_header( $response, 'location' );

		if ( $code >= 300 && $code < 400 && $location ) {
			// Signierte Download-URL – ohne Authorization-Header laden.
			$file = download_url( is_array( $location ) ? end( $location ) : $location, 300 );
			return $file;
		}

		if ( 200 === $code ) {
			$tmp = wp_tempnam( 'members-plugin.zip' );
			if ( ! $tmp ) {
				return new WP_Error( 'mp_download', __( 'Temporäre Datei konnte nicht angelegt werden.', 'ultimate-member' ) );
			}
			file_put_contents( $tmp, wp_remote_retrieve_body( $response ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			return $tmp;
		}

		/* translators: %d HTTP status */
		return new WP_Error( 'mp_download', sprintf( __( 'Download von GitHub fehlgeschlagen (HTTP %d). Prüfe den Token unter Members Plugin › Updates.', 'ultimate-member' ), $code ) );
	}

	/**
	 * Quellcode-ZIPs von GitHub entpacken in "owner-repo-sha/" – auf den bisherigen Ordnernamen umbenennen.
	 *
	 * @param string      $source        Entpackter Ordner.
	 * @param string      $remote_source Übergeordneter Ordner.
	 * @param WP_Upgrader $upgrader      Upgrader.
	 * @param array       $hook_extra    Zusatzinfos.
	 * @return string|WP_Error
	 */
	public static function fix_folder_name( $source, $remote_source, $upgrader, $hook_extra = array() ) {
		if ( empty( $hook_extra['plugin'] ) || UM_PLUGIN !== $hook_extra['plugin'] ) {
			return $source;
		}
		$wanted = trailingslashit( $remote_source ) . dirname( UM_PLUGIN ) . '/';
		if ( trailingslashit( $source ) === $wanted ) {
			return $source;
		}
		if ( ! file_exists( trailingslashit( $source ) . 'members-plugin.php' ) ) {
			return new WP_Error( 'mp_package', __( 'Das Update-Paket enthält kein Members Plugin.', 'ultimate-member' ) );
		}
		global $wp_filesystem;
		if ( $wp_filesystem && $wp_filesystem->move( $source, $wanted, true ) ) {
			return $wanted;
		}
		return new WP_Error( 'mp_package', __( 'Der Plugin-Ordner konnte nicht umbenannt werden.', 'ultimate-member' ) );
	}

	/**
	 * Cache leeren.
	 */
	public static function flush() {
		delete_site_transient( self::TRANSIENT );
	}

	/**
	 * Link in der Pluginliste.
	 *
	 * @param array  $meta Links.
	 * @param string $file Plugin.
	 * @return array
	 */
	public static function row_meta( $meta, $file ) {
		if ( UM_PLUGIN === $file && current_user_can( 'update_plugins' ) ) {
			$meta[] = '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mp_updater_check&return=plugins' ), 'mp_updater_check' ) ) . '">' . esc_html__( 'Nach Updates suchen', 'ultimate-member' ) . '</a>';
			$meta[] = '<a href="' . esc_url( admin_url( 'admin.php?page=' . self::PAGE ) ) . '">' . esc_html__( 'Update-Einstellungen', 'ultimate-member' ) . '</a>';
		}
		return $meta;
	}

	/**
	 * Menüpunkt.
	 */
	public static function menu() {
		add_submenu_page( 'ultimatemember', __( 'Updates', 'ultimate-member' ), __( 'Updates', 'ultimate-member' ), 'update_plugins', self::PAGE, array( __CLASS__, 'render' ) );
	}

	/**
	 * Token speichern.
	 */
	public static function save() {
		if ( ! current_user_can( 'update_plugins' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'ultimate-member' ), 403 );
		}
		check_admin_referer( 'mp_updater_save' );

		if ( ! empty( $_POST['mp_delete_token'] ) ) {
			delete_option( self::TOKEN_OPTION );
		} elseif ( isset( $_POST['mp_github_token'] ) ) {
			$token = trim( sanitize_text_field( wp_unslash( $_POST['mp_github_token'] ) ) );
			if ( '' !== $token && preg_match( '/^[A-Za-z0-9_]{20,255}$/', $token ) ) {
				update_option( self::TOKEN_OPTION, $token, false );
			}
		}
		self::flush();
		self::get_release( true );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE . '&mp-saved=1' ) );
		exit;
	}

	/**
	 * Jetzt nach Updates suchen.
	 */
	public static function force_check() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'ultimate-member' ), 403 );
		}
		check_admin_referer( 'mp_updater_check' );
		self::flush();
		self::get_release( true );
		delete_site_transient( 'update_plugins' );
		wp_update_plugins();
		$back = ( isset( $_GET['return'] ) && 'plugins' === $_GET['return'] ) ? admin_url( 'plugins.php' ) : admin_url( 'admin.php?page=' . self::PAGE . '&mp-checked=1' );
		wp_safe_redirect( $back );
		exit;
	}

	/**
	 * Admin-Seite.
	 */
	public static function render() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}
		$release   = self::get_release();
		$error     = self::last_error();
		$has_const = defined( 'MP_GITHUB_TOKEN' ) && MP_GITHUB_TOKEN;
		$has_opt   = '' !== (string) get_option( self::TOKEN_OPTION, '' );
		$newer     = $release && version_compare( $release['version'], MP_VERSION, '>' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Updates', 'ultimate-member' ); ?></h1>
			<?php if ( isset( $_GET['mp-saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Gespeichert.', 'ultimate-member' ); ?></p></div>
			<?php endif; ?>

			<table class="form-table" role="presentation">
				<tr><th><?php esc_html_e( 'Installierte Version', 'ultimate-member' ); ?></th><td><code><?php echo esc_html( MP_VERSION ); ?></code> <span class="description">(<?php echo esc_html( sprintf( 'Ultimate Member %s', UM_VERSION ) ); ?>)</span></td></tr>
				<tr><th><?php esc_html_e( 'Neueste Version auf GitHub', 'ultimate-member' ); ?></th><td>
					<?php if ( $release ) : ?>
						<code><?php echo esc_html( $release['version'] ); ?></code>
						<?php if ( $newer ) : ?>
							<strong style="color:#b32d2e"> – <?php esc_html_e( 'Update verfügbar', 'ultimate-member' ); ?></strong>
							<a class="button button-primary" style="margin-left:8px" href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>"><?php esc_html_e( 'Zu den Plugins', 'ultimate-member' ); ?></a>
						<?php else : ?>
							<span style="color:#008a20"> – <?php esc_html_e( 'aktuell', 'ultimate-member' ); ?></span>
						<?php endif; ?>
						<br><a href="<?php echo esc_url( $release['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Release auf GitHub ansehen', 'ultimate-member' ); ?></a>
					<?php else : ?>
						<span style="color:#b32d2e"><?php echo esc_html( $error ? $error : __( 'Noch nicht abgefragt.', 'ultimate-member' ) ); ?></span>
					<?php endif; ?>
				</td></tr>
				<tr><th><?php esc_html_e( 'Repository', 'ultimate-member' ); ?></th><td><code><?php echo esc_html( self::repo() ); ?></code></td></tr>
			</table>

			<p>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mp_updater_check' ), 'mp_updater_check' ) ); ?>"><?php esc_html_e( 'Jetzt nach Updates suchen', 'ultimate-member' ); ?></a>
			</p>

			<h2><?php esc_html_e( 'Zugriff auf das GitHub-Repository', 'ultimate-member' ); ?></h2>
			<p><?php esc_html_e( 'Für ein privates Repository braucht WordPress einen GitHub-Token mit Lesezugriff. Empfohlen: ein „Fine-grained personal access token“, beschränkt auf dieses eine Repository, Berechtigung „Contents: Read-only“.', 'ultimate-member' ); ?>
				<a href="https://github.com/settings/personal-access-tokens/new" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Token erstellen', 'ultimate-member' ); ?></a></p>

			<?php if ( $has_const ) : ?>
				<p><strong><?php esc_html_e( 'Der Token ist in der wp-config.php gesetzt (MP_GITHUB_TOKEN).', 'ultimate-member' ); ?></strong></p>
			<?php else : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="mp_updater_save">
					<?php wp_nonce_field( 'mp_updater_save' ); ?>
					<table class="form-table" role="presentation">
						<tr>
							<th><label for="mp_github_token"><?php esc_html_e( 'GitHub-Token', 'ultimate-member' ); ?></label></th>
							<td>
								<input type="password" id="mp_github_token" name="mp_github_token" class="regular-text" autocomplete="off" placeholder="<?php echo esc_attr( $has_opt ? __( '•••••••• (gespeichert – leer lassen, um ihn zu behalten)', 'ultimate-member' ) : 'github_pat_…' ); ?>">
								<?php if ( $has_opt ) : ?>
									<br><label><input type="checkbox" name="mp_delete_token" value="1"> <?php esc_html_e( 'Gespeicherten Token löschen', 'ultimate-member' ); ?></label>
								<?php endif; ?>
								<p class="description"><?php esc_html_e( 'Sicherer: statt hier zu speichern in die wp-config.php eintragen:', 'ultimate-member' ); ?> <code>define( 'MP_GITHUB_TOKEN', 'github_pat_…' );</code></p>
							</td>
						</tr>
					</table>
					<?php submit_button( __( 'Speichern & prüfen', 'ultimate-member' ) ); ?>
				</form>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Neue Version veröffentlichen', 'ultimate-member' ); ?></h2>
			<ol>
				<li><?php esc_html_e( 'In members-plugin.php die Zeile „Version:“ erhöhen (z.B. 1.0.1) und auf GitHub pushen.', 'ultimate-member' ); ?></li>
				<li><?php esc_html_e( 'Auf GitHub ein Release mit dem Tag v1.0.1 erstellen (oder den Tag pushen).', 'ultimate-member' ); ?></li>
				<li><?php esc_html_e( 'Die GitHub Action baut automatisch members-plugin.zip und hängt sie an das Release.', 'ultimate-member' ); ?></li>
				<li><?php esc_html_e( 'WordPress zeigt das Update innerhalb von 6 Stunden an – oder sofort über „Jetzt nach Updates suchen“.', 'ultimate-member' ); ?></li>
			</ol>
		</div>
		<?php
	}
}
