<?php
/**
 * Plugin Name: Members Plugin – Profiles & Community
 * Plugin URI: https://github.com/paulhubacek1-afk/MembersPlugin
 * Description: Mitgliederprofile, Community-Funktionen, Profilbild-Ringfarben, automatische Updates sowie Sicherung und Import.
 * Version: 1.1.5
 * Author: Paul Hubacek
 * Author URI: https://github.com/paulhubacek1-afk
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: members-plugin
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 7.0
 * Update URI: https://github.com/paulhubacek1-afk/MembersPlugin
 *
 * @package MembersPlugin
 */

defined( 'ABSPATH' ) || exit;

// This independent backup tool must load before the legacy-core guard so administrators
// can export existing membership data before removing the separately installed UM plugin.
require_once __DIR__ . '/includes/class-mp-migration-backup.php';

require_once ABSPATH . 'wp-admin/includes/plugin.php';

/*
 * The bundled legacy membership core still contains overlapping internal symbols
 * with the separately distributed upstream plugin. Keep the guard until those
 * symbols are migrated and tested; do not run both cores at once.
 */
if ( function_exists( 'UM' ) || is_plugin_active( 'ultimate-member/ultimate-member.php' ) ) {
	add_action(
		'admin_notices',
		static function () {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			echo '<div class="notice notice-error"><p>' .
				esc_html__( 'Members Plugin bringt eine eigene Mitgliederverwaltung mit. Eine separat installierte ältere Mitgliederverwaltung darf aktuell nicht parallel aktiviert sein, da noch interne Funktionsnamen überschneiden. Bitte deaktiviere die separate Installation, bevor du Members Plugin verwendest.', 'ultimate-member' ) .
				'</p></div>';
		}
	);
	return;
}

$plugin_data = get_plugin_data( __FILE__, true, false );

// phpcs:disable Generic.NamingConventions.UpperCaseConstantName
define( 'um_url', plugin_dir_url( __FILE__ ) );
define( 'um_path', plugin_dir_path( __FILE__ ) );
define( 'um_plugin', plugin_basename( __FILE__ ) );
define( 'ultimatemember_version', '2.14.1' );
define( 'ultimatemember_plugin_name', $plugin_data['Name'] );
// phpcs:enable Generic.NamingConventions.UpperCaseConstantName

define( 'UM_URL', plugin_dir_url( __FILE__ ) );
define( 'UM_PATH', plugin_dir_path( __FILE__ ) );
define( 'UM_PLUGIN', plugin_basename( __FILE__ ) );
// Interne Kernversion; nicht mit der Members-Plugin-Version verwechseln.
define( 'UM_VERSION', '2.14.1' );
define( 'UM_PLUGIN_NAME', $plugin_data['Name'] );
define( 'UM_WP_FUNCTIONS_VERSION', '7.0.1' );
define( 'UM_LICENSE_REQUEST_DEBUG', false );
define( 'UM_UPDATER_DEBUG', false );

define( 'MP_VERSION', $plugin_data['Version'] );
define( 'MP_FILE', __FILE__ );
define( 'MP_PATH', UM_PATH . 'includes/members-plugin/' );
define( 'MP_URL', UM_URL . 'includes/members-plugin/' );

// Respect the WordPress site language for both the new product domain and the
// legacy core domain whose translation files are still named "ultimate-member".
add_action(
	'init',
	static function () {
		$language_path = dirname( plugin_basename( MP_FILE ) ) . '/languages';
		load_plugin_textdomain( 'members-plugin', false, $language_path );
		load_plugin_textdomain( 'ultimate-member', false, $language_path );
	},
	0
);

require_once 'includes/class-functions.php';
require_once 'includes/class-init.php';
require_once MP_PATH . 'class-mp-loader.php';
