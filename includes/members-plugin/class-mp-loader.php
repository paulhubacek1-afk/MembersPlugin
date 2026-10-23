<?php
/**
 * Members Plugin – loads product features.
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
require_once MP_PATH . 'class-mp-avatar.php';
require_once MP_PATH . 'class-mp-online-status.php';

MP_Design::init();
MP_Inspector::init();
MP_Security::init();
MP_Updater::init();
MP_Avatar::init();
MP_Online_Status::init();

// MP_Admin::SLUG is also used in the front-end admin bar.
require_once MP_PATH . 'class-mp-admin.php';
if ( is_admin() ) {
	MP_Admin::init();
}
