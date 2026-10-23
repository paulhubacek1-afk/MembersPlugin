<?php
/**
 * Standalone migration backup for Members Plugin.
 *
 * @package MembersPlugin
 */
defined( 'ABSPATH' ) || exit;

final class MP_Migration_Backup {
	const PAGE = 'mp-migration-backup';
	const TABLE = 'mp_backup_index';
	const SCHEMA_OPTION = 'mp_backup_schema_version';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'install_schema' ) );
		add_action( 'admin_post_mp_migration_create_backup', array( __CLASS__, 'create_backup' ) );
		add_action( 'admin_post_mp_migration_download_backup', array( __CLASS__, 'download_backup' ) );
	}

	public static function install_schema() {
		if ( ! current_user_can( 'manage_options' ) || '1' === get_option( self::SCHEMA_OPTION ) ) {
			return;
		}
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table = $wpdb->prefix . self::TABLE;
		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			filename varchar(191) NOT NULL,
			created_at datetime NOT NULL,
			file_size bigint(20) unsigned NOT NULL DEFAULT 0,
			checksum char(64) NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY filename (filename)
		) " . $wpdb->get_charset_collate() . ';';
		dbDelta( $sql );
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
			update_option( self::SCHEMA_OPTION, '1', false );
		}
	}

	public static function menu() {
		if ( current_user_can( 'manage_options' ) ) {
			add_management_page(
				__( 'MP Migration & Sicherung', 'members-plugin' ),
				__( 'MP Migration & Sicherung', 'members-plugin' ),
				'manage_options',
				self::PAGE,
				array( __CLASS__, 'render_page' )
			);
		}
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'members-plugin' ), 403 );
		}
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		$backups = array();
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
			$backups = $wpdb->get_results( "SELECT id, filename, created_at, file_size, checksum FROM {$table} ORDER BY id DESC LIMIT 20", ARRAY_A );
		}
		echo '<div class="wrap"><h1>' . esc_html__( 'MP Migration & Sicherung', 'members-plugin' ) . '</h1>';
		if ( isset( $_GET['mp_backup'] ) && 'created' === sanitize_key( wp_unslash( $_GET['mp_backup'] ) ) ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Sicherung wurde erstellt.', 'members-plugin' ) . '</p></div>';
		}
		if ( isset( $_GET['mp_backup_error'] ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Sicherung fehlgeschlagen. Prüfe ZipArchive, Schreibrechte und den privaten Speicherpfad.', 'members-plugin' ) . ' (' . esc_html( sanitize_key( wp_unslash( $_GET['mp_backup_error'] ) ) ) . ')</p></div>';
		}
		echo '<p>' . esc_html__( 'Erstelle eine Sicherung, bevor du Ultimate Member entfernst. Die ZIP-Datei wird außerhalb des WordPress-Webverzeichnisses gespeichert.', 'members-plugin' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Die Sicherung enthält WordPress-Benutzer und Metadaten, UM-bezogene Optionen und Tabellen, UM-Formulare/Verzeichnisse mit Metadaten sowie vorhandene Ultimate-Member-Uploadordner.', 'members-plugin' ) . '</strong></p>';
		echo '<p>' . esc_html__( 'Die Datei enthält private Benutzerdaten einschließlich Passwort-Hashes. Bewahre sie sicher auf.', 'members-plugin' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="mp_migration_create_backup">';
		wp_nonce_field( 'mp_migration_create_backup' );
		submit_button( __( 'Jetzt Sicherung erstellen', 'members-plugin' ) );
		echo '</form><h2>' . esc_html__( 'Vorhandene Sicherungen', 'members-plugin' ) . '</h2>';
		if ( empty( $backups ) ) {
			echo '<p>' . esc_html__( 'Noch keine Sicherung vorhanden.', 'members-plugin' ) . '</p>';
		} else {
			echo '<table class="widefat striped"><thead><tr><th>Datei</th><th>Erstellt (UTC)</th><th>Größe</th><th>SHA-256</th><th>Aktion</th></tr></thead><tbody>';
			foreach ( $backups as $backup ) {
				$url = wp_nonce_url( add_query_arg( array( 'action' => 'mp_migration_download_backup', 'filename' => $backup['filename'] ), admin_url( 'admin-post.php' ) ), 'mp_migration_download_' . $backup['filename'] );
				echo '<tr><td>' . esc_html( $backup['filename'] ) . '</td><td>' . esc_html( $backup['created_at'] ) . '</td><td>' . esc_html( size_format( (int) $backup['file_size'] ) ) . '</td><td><code>' . esc_html( $backup['checksum'] ) . '</code></td><td><a class="button" href="' . esc_url( $url ) . '">' . esc_html__( 'Herunterladen', 'members-plugin' ) . '</a></td></tr>';
			}
			echo '</tbody></table>';
		}
		echo '<hr><p><strong>' . esc_html__( 'Wichtig:', 'members-plugin' ) . '</strong> ' . esc_html__( 'Das ist eine Datensicherung, noch keine vollständige Migration auf den unabhängigen MP-Kern. Lade die ZIP-Datei herunter und prüfe sie, bevor du UM deinstallierst.', 'members-plugin' ) . '</p></div>';
	}

	private static function private_directory() {
		$root_real = realpath( ABSPATH );
		$root = wp_normalize_path( $root_real ? $root_real : ABSPATH );
		$dir = wp_normalize_path( dirname( untrailingslashit( $root ) ) . '/mp-private-backups' );
		if ( 0 === strpos( trailingslashit( $dir ), trailingslashit( $root ) ) ) {
			return new WP_Error( 'private_path', 'Kein privater Speicherpfad außerhalb des Webverzeichnisses verfügbar.' );
		}
		if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
			return new WP_Error( 'mkdir_failed', 'Der private Sicherungsordner konnte nicht angelegt werden.' );
		}
		if ( ! is_writable( $dir ) ) {
			return new WP_Error( 'not_writable', 'Der private Sicherungsordner ist nicht beschreibbar.' );
		}
		@chmod( $dir, 0700 );
		return $dir;
	}

	private static function database_snapshot() {
		global $wpdb;
		$all_tables = $wpdb->get_col( 'SHOW TABLES' );
		$legacy_tables = array();
		foreach ( $all_tables as $name ) {
			if ( 0 === strpos( $name, $wpdb->prefix . 'um_' ) || 0 === strpos( $name, $wpdb->prefix . 'ultimate_member_' ) ) {
				$legacy_tables[] = $name;
			}
		}
		$users = $wpdb->get_results( "SELECT * FROM {$wpdb->users}", ARRAY_A );
		$usermeta = $wpdb->get_results( "SELECT * FROM {$wpdb->usermeta}", ARRAY_A );
		$options = $wpdb->get_results(
			"SELECT * FROM {$wpdb->options} WHERE option_name LIKE 'um\\_%' OR option_name LIKE 'ultimatemember\\_%' OR option_name LIKE '%ultimate_member%'",
			ARRAY_A
		);
		$posts = $wpdb->get_results(
			"SELECT * FROM {$wpdb->posts} WHERE post_type LIKE 'um\\_%' OR post_type IN ('um_form', 'um_directory')",
			ARRAY_A
		);
		$post_ids = array();
		foreach ( $posts as $post ) {
			$post_ids[] = absint( $post['ID'] );
		}
		$postmeta = array();
		foreach ( array_chunk( $post_ids, 400 ) as $chunk ) {
			$ids = implode( ',', array_map( 'absint', $chunk ) );
			$postmeta = array_merge( $postmeta, $wpdb->get_results( "SELECT * FROM {$wpdb->postmeta} WHERE post_id IN ({$ids})", ARRAY_A ) );
		}
		$table_data = array();
		foreach ( $legacy_tables as $name ) {
			$safe_name = chr( 96 ) . str_replace( chr( 96 ), '', $name ) . chr( 96 );
			$table_data[ $name ] = $wpdb->get_results( "SELECT * FROM {$safe_name}", ARRAY_A );
		}
		return array(
			'format' => 'members-plugin-migration-backup',
			'format_version' => 1,
			'created_at_utc' => gmdate( 'c' ),
			'site_url' => home_url( '/' ),
			'wordpress_version' => get_bloginfo( 'version' ),
			'members_plugin_version' => defined( 'MP_VERSION' ) ? MP_VERSION : '1.0.7',
			'database_prefix' => $wpdb->prefix,
			'users' => $users,
			'usermeta' => $usermeta,
			'options' => $options,
			'posts' => $posts,
			'postmeta' => $postmeta,
			'legacy_tables' => $table_data,
			'note' => 'Snapshot only; this JSON is not a ready-to-run SQL restore script.',
		);
	}

	public static function create_backup() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'members-plugin' ), 403 );
		}
		check_admin_referer( 'mp_migration_create_backup' );
		if ( ! class_exists( 'ZipArchive' ) ) {
			self::redirect_error( 'zip_missing' );
		}
		$dir = self::private_directory();
		if ( is_wp_error( $dir ) ) {
			self::redirect_error( $dir->get_error_code() );
		}
		$filename = 'mp-migration-backup-' . gmdate( 'Ymd-His' ) . '.zip';
		$path = trailingslashit( $dir ) . $filename;
		$zip = new ZipArchive();
		if ( true !== $zip->open( $path, ZipArchive::CREATE | ZipArchive::EXCL ) ) {
			self::redirect_error( 'zip_create_failed' );
		}
		$json = wp_json_encode( self::database_snapshot(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		if ( false === $json || ! $zip->addFromString( 'database-snapshot.json', $json ) ) {
			$zip->close();
			@unlink( $path );
			self::redirect_error( 'snapshot_failed' );
		}
		$upload = wp_upload_dir();
		$media_roots = array(
			'ultimatemember' => trailingslashit( $upload['basedir'] ) . 'ultimatemember',
			'ultimate-member' => trailingslashit( $upload['basedir'] ) . 'ultimate-member',
		);
		foreach ( $media_roots as $label => $media_root ) {
			if ( ! is_dir( $media_root ) ) {
				continue;
			}
			$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $media_root, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::LEAVES_ONLY );
			foreach ( $iterator as $file ) {
				if ( $file->isFile() && ! $file->isLink() ) {
					$relative = ltrim( str_replace( wp_normalize_path( $media_root ), '', wp_normalize_path( $file->getPathname() ) ), '/' );
					$zip->addFile( $file->getPathname(), 'media/' . $label . '/' . $relative );
				}
			}
		}
		if ( ! $zip->close() || ! is_file( $path ) ) {
			@unlink( $path );
			self::redirect_error( 'zip_finalize_failed' );
		}
		@chmod( $path, 0600 );
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . self::TABLE,
			array( 'filename' => $filename, 'created_at' => current_time( 'mysql', true ), 'file_size' => filesize( $path ), 'checksum' => hash_file( 'sha256', $path ) ),
			array( '%s', '%s', '%d', '%s' )
		);
		wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE, 'mp_backup' => 'created' ), admin_url( 'tools.php' ) ) );
		exit;
	}

	public static function download_backup() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'members-plugin' ), 403 );
		}
		$filename = isset( $_GET['filename'] ) ? sanitize_file_name( wp_unslash( $_GET['filename'] ) ) : '';
		if ( '' === $filename || 0 !== strpos( $filename, 'mp-migration-backup-' ) || '.zip' !== substr( $filename, -4 ) ) {
			wp_die( esc_html__( 'Ungültige Sicherungsdatei.', 'members-plugin' ), 400 );
		}
		check_admin_referer( 'mp_migration_download_' . $filename );
		$dir = self::private_directory();
		if ( is_wp_error( $dir ) ) {
			wp_die( esc_html( $dir->get_error_message() ), 500 );
		}
		$path = trailingslashit( $dir ) . $filename;
		if ( ! is_file( $path ) || ! is_readable( $path ) ) {
			wp_die( esc_html__( 'Sicherungsdatei nicht gefunden.', 'members-plugin' ), 404 );
		}
		nocache_headers();
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . (string) filesize( $path ) );
		readfile( $path );
		exit;
	}

	private static function redirect_error( $code ) {
		wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE, 'mp_backup_error' => sanitize_key( $code ) ), admin_url( 'tools.php' ) ) );
		exit;
	}
}

MP_Migration_Backup::init();
