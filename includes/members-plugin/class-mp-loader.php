<?php
/**
 * Members Plugin – lädt die Erweiterungen des Forks.
 *
 * @package MembersPlugin
 */

defined( 'ABSPATH' ) || exit;

require_once MP_PATH . 'class-mp-color.php';
require_once MP_PATH . 'class-mp-theme-colors.php';
require_once MP_PATH . 'class-mp-design.php';
require_once MP_PATH . 'class-mp-class-reference.php';
require_once MP_PATH . 'class-mp-inspector.php';
require_once MP_PATH . 'class-mp-security.php';
require_once MP_PATH . 'class-mp-updater.php';

MP_Design::init();
MP_Inspector::init();
MP_Security::init();
MP_Updater::init();

// MP_Admin::SLUG wird auch im Frontend (Admin-Leiste) benötigt.
require_once MP_PATH . 'class-mp-admin.php';
if ( is_admin() ) {
	MP_Admin::init();
}
