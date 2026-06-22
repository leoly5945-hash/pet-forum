<?php
/**
 * Plugin Name:  PF Unified Users
 * Description:  Hệ thống user thống nhất — Đăng ký, phân quyền, meta, chống spam cho Pet Forum
 * Version:      2.0.0
 * Author:       PetForum
 * Requires PHP: 7.4
 */
defined( 'ABSPATH' ) || exit;

define( 'PFU_VERSION', '2.0.0' );
define( 'PFU_DIR', plugin_dir_path( __FILE__ ) );
define( 'PFU_URL', plugin_dir_url( __FILE__ ) );
define( 'PFU_SLUG', 'pf-unified-users' );

$includes = [
	'includes/class-pf-constants.php',
	'includes/class-pf-user-meta.php',
	'includes/class-pf-antispam.php',
	'includes/class-pf-register.php',
	'includes/class-pf-roles.php',
	'includes/class-pf-frontend-mod.php',
	'includes/class-pf-admin.php',
];
foreach ( $includes as $file ) {
	require_once PFU_DIR . $file;
}

register_activation_hook( __FILE__, function () {
	PF_Roles_V2::create_all_roles();
	PF_AntiSpam_V2::create_tables();
	PF_Frontend_Mod_V2::create_log_table();
	PF_User_Meta_V2::set_default_options();

	update_option( 'users_can_register', 1 );
	update_option( 'default_role', 'subscriber' );
	update_option( 'pf_email_verify_required', 1 );

	if ( class_exists( 'PF_User_Meta_V2' ) ) {
		PF_User_Meta_V2::register_wpforo_custom_fields();
	}

	PF_Roles_V2::ensure_vet_usergroup();
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function () {
	flush_rewrite_rules();
} );

add_action( 'plugins_loaded', function () {
	PF_User_Meta_V2::init();
	PF_AntiSpam_V2::init();
	PF_Register_V2::init();
	PF_Roles_V2::init();
	PF_Frontend_Mod_V2::init();
	PF_Admin_V2::init();
}, 10 );
