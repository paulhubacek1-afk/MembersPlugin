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

MP_Design::init();
MP_Inspector::init();
MP_Security::init();

if ( is_admin() ) {
	require_once MP_PATH . 'class-mp-admin.php';
	MP_Admin::init();
} else {
	// MP_Admin::SLUG wird auch im Frontend (Admin-Leiste) benötigt.
	require_once MP_PATH . 'class-mp-admin.php';
}
