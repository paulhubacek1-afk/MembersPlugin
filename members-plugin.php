<?php
/**
 * Plugin Name: Members Plugin – Profiles & Community
 * Plugin URI: https://github.com/paulhubacek1-afk/MembersPlugin
 * Description: Mitgliederverwaltung mit Profilen, Registrierung, Mitgliederverzeichnis, automatischer Theme-Farbanpassung, ID-basierten Profilbildern und integrierter Online-Mitgliederliste.
 * Version: 1.0.1
 * Author: Paul Hubacek
 * Author URI: https://github.com/paulhubacek1-afk
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: ultimate-member
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 7.0
 * Update URI: https://github.com/paulhubacek1-afk/MembersPlugin
 *
 * @package UM
 */

defined( 'ABSPATH' ) || exit;

require_once ABSPATH . 'wp-admin/includes/plugin.php';

/*
 * Compatibility guard: this plugin bundles its own complete membership core,
 * but the legacy core still uses the same global symbols as the upstream plugin.
 * Do not remove this guard until the internal API has been fully namespaced.
 */
if ( function_exists( 'UM' ) || is_plugin_active( 'ultimate-member/ultimate-member.php' ) ) {
	add_action(
		'admin_notices',
		static function () {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			echo '<div class="notice notice-error"><p>' .
				esc_html__( 'Members Plugin enthält einen eigenen Mitgliederverwaltungskern und benötigt keine separate Ultimate-Member-Installation. Beide Plugins dürfen aktuell jedoch nicht gleichzeitig aktiv sein, da ältere interne Klassen noch gemeinsame Namen verwenden. Bitte deaktiviere das Original-Plugin, bevor du Members Plugin verwendest.', 'ultimate-member' ) .
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

require_once 'includes/class-functions.php';
require_once 'includes/class-init.php';
require_once MP_PATH . 'class-mp-loader.php';
