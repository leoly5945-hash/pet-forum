<?php
/**
 * Plugin Name: PF Member Shield
 * Description: Hệ thống thành viên + chống spam cho Pet Forum
 * Version: 1.0.0
 * Author: PetForum
 */

defined( 'ABSPATH' ) || exit;

define( 'PF_SHIELD_DIR', plugin_dir_path( __FILE__ ) );
define( 'PF_SHIELD_URL', plugin_dir_url( __FILE__ ) );
define( 'PF_SHIELD_VER', '1.0.0' );

require_once PF_SHIELD_DIR . 'modules/class-onboarding.php';
require_once PF_SHIELD_DIR . 'modules/class-user-meta.php';
require_once PF_SHIELD_DIR . 'modules/class-antispam.php';
require_once PF_SHIELD_DIR . 'modules/class-split-register.php';

add_action( 'plugins_loaded', function () {
	PF_Onboarding::init();
	PF_User_Meta::init();
	PF_AntiSpam::init();
	PF_Split_Register::init();
} );

register_activation_hook( __FILE__, function () {
	PF_AntiSpam::create_tables();

	update_option( 'users_can_register', 1 );
	update_option( 'default_role', 'subscriber' );
	update_option( 'pf_email_verify_required', 1 );

	if ( class_exists( 'PF_User_Meta' ) ) {
		PF_User_Meta::register_wpforo_custom_fields();
	}
} );
