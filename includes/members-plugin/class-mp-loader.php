<?php
/**
 * Members Plugin – loads product features.
 *
 * @package MembersPlugin
 */

defined( 'ABSPATH' ) || exit;

require_once MP_PATH . 'class-mp-color.php';
require_once MP_PATH . 'class-mp-theme-colors.php';
require_once MP_PATH . 'class-mp-member-cards.php';
require_once MP_PATH . 'class-mp-icons.php';
require_once MP_PATH . 'class-mp-design.php';
require_once MP_PATH . 'class-mp-account-tabs.php';
require_once MP_PATH . 'class-mp-class-reference.php';
require_once MP_PATH . 'class-mp-inspector.php';
require_once MP_PATH . 'class-mp-security.php';
require_once MP_PATH . 'class-mp-updater.php';
require_once MP_PATH . 'class-mp-avatar.php';
require_once MP_PATH . 'class-mp-online-status.php';
require_once MP_PATH . 'class-mp-role-avatar-rings.php';
require_once MP_PATH . 'class-mp-birthday-celebration.php';
require_once MP_PATH . 'class-mp-mail-diagnostics.php';
require_once MP_PATH . 'class-mp-original-cards.php';
require_once MP_PATH . 'class-mp-diagnostics.php';
require_once MP_PATH . 'class-mp-branding.php';

/*
 * Bundled core assets are versioned with the internal core version (2.14.1), which never
 * changes between Members Plugin releases. Browsers and caching plugins therefore kept
 * serving old scripts after an update. Append the Members Plugin version.
 */
function mp_bust_core_asset_version( $src ) {
	if ( ! is_string( $src ) || 0 !== strpos( $src, UM_URL ) || ! defined( 'MP_VERSION' ) ) {
		return $src;
	}
	if ( false !== strpos( $src, 'ver=' . UM_VERSION ) ) {
		return str_replace( 'ver=' . UM_VERSION, 'ver=' . UM_VERSION . '-mp' . MP_VERSION, $src );
	}
	return $src;
}
add_filter( 'script_loader_src', 'mp_bust_core_asset_version', 20 );
add_filter( 'style_loader_src', 'mp_bust_core_asset_version', 20 );

MP_Member_Cards::init();
MP_Icons::init();
MP_Design::init();
MP_Account_Tabs::init();
MP_Inspector::init();
MP_Security::init();
MP_Updater::init();
MP_Avatar::init();
MP_Online_Status::init();
MP_Role_Avatar_Rings::init();
MP_Birthday_Celebration::init();
MP_Mail_Diagnostics::init();
MP_Original_Cards::init();
MP_Diagnostics::init();
MP_Branding::init();

// MP_Admin::SLUG is also used in the front-end admin bar.
require_once MP_PATH . 'class-mp-admin.php';
if ( is_admin() ) {
	MP_Admin::init();
}
