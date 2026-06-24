<?php
/**
 * Plugin Name:  PF Unified Users
 * Description:  Hệ thống user thống nhất — Đăng ký, phân quyền, meta, chống spam cho Pet Forum
 * Version:      2.1.0
 * Author:       PetForum
 * Requires PHP: 7.4
 */
defined( 'ABSPATH' ) || exit;

define( 'PFU_VERSION', '2.1.0' );
define( 'PFU_DIR', plugin_dir_path( __FILE__ ) );
define( 'PFU_URL', plugin_dir_url( __FILE__ ) );
define( 'PFU_SLUG', 'pf-unified-users' );

$includes = [
	'includes/class-pf-constants.php',
	'includes/class-pf-i18n.php',
	'includes/class-pf-theme.php',
	'includes/class-pf-logger.php',
	'includes/class-pf-2fa.php',
	'includes/class-pf-user-meta.php',
	'includes/class-pf-antispam.php',
	'includes/class-pf-register.php',
	'includes/class-pf-roles.php',
	'includes/class-pf-frontend-mod.php',
	'includes/class-pf-mod-center.php',
	'includes/class-pf-ads.php',
	'includes/class-pf-engagement.php',
	'includes/class-pf-nav-menu.php',
	'includes/class-pf-upload.php',
	'includes/class-pf-image-processor.php',
	'includes/class-pf-video-embed.php',
	'includes/class-pf-ai-chatbot.php',
	'includes/class-pf-security.php',
	'includes/class-pf-admin.php',
];
foreach ( $includes as $file ) {
	require_once PFU_DIR . $file;
}

register_activation_hook( __FILE__, function () {
	PF_Roles_V2::create_all_roles();
	PF_AntiSpam_V2::create_tables();
	PF_Logger::create_tables();
	PF_User_Meta_V2::set_default_options();

	update_option( 'users_can_register', 1 );
	update_option( 'default_role', 'subscriber' );
	update_option( 'pf_email_verify_required', 1 );

	if ( class_exists( 'PF_User_Meta_V2' ) ) {
		PF_User_Meta_V2::register_wpforo_custom_fields();
	}

	PF_Roles_V2::ensure_vet_usergroup();

	if ( class_exists( 'PF_Engagement' ) ) {
		PF_Engagement::schedule_cron();
		PF_Engagement::backfill_last_login();
	}

		if ( class_exists( 'PF_AI_Chatbot' ) ) {
		PF_AI_Chatbot::create_log_table();
	}
} );

register_deactivation_hook( __FILE__, function () {
	if ( class_exists( 'PF_Engagement' ) ) {
		PF_Engagement::unschedule_cron();
	}
	flush_rewrite_rules();
} );

add_action( 'plugins_loaded', function () {
	PF_I18n::init();
	PF_Theme::init();
	PF_User_Meta_V2::init();
	PF_AntiSpam_V2::init();
	PF_Register_V2::init();
	PF_Roles_V2::init();
	PF_Logger::init();
	PF_TwoFactor::init();
	PF_Frontend_Mod_V2::init();
	PF_Mod_Center::init();
	PF_Ads::init();
	PF_Engagement::init();
	PF_Nav_Menu::init();
	PF_Upload::init();
	PF_Image_Processor::init();
	PF_Video_Embed::init();
	PF_AI_Chatbot::init();
	PF_Security::init();
	PF_Admin_V2::init();
}, 10 );

add_action( 'init', [ 'PF_Engagement', 'handle_unsubscribe' ], 5 );
