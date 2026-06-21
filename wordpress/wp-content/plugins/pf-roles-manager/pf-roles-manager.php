<?php
/**
 * Plugin Name: PF Roles Manager
 * Description: Hệ thống phân quyền Sub-Admin chuyên biệt cho Pet Forum
 * Version: 1.0.0
 * Author: PetForum
 */

defined( 'ABSPATH' ) || exit;

define( 'PF_ROLES_DIR', plugin_dir_path( __FILE__ ) );
define( 'PF_ROLES_URL', plugin_dir_url( __FILE__ ) );
define( 'PF_ROLES_VER', '1.0.0' );

require_once PF_ROLES_DIR . 'includes/class-roles.php';
require_once PF_ROLES_DIR . 'includes/class-wpforo-perms.php';
require_once PF_ROLES_DIR . 'includes/class-admin-lockdown.php';
require_once PF_ROLES_DIR . 'includes/class-frontend-mod.php';

register_activation_hook( __FILE__, function () {
	PF_Roles::create_all_roles();
	PF_Frontend_Mod::create_log_table();
} );

register_deactivation_hook( __FILE__, [ 'PF_Roles', 'remove_all_roles' ] );

add_action( 'plugins_loaded', function () {
	PF_Roles::init();
	PF_WpForo_Perms::init();
	PF_Admin_Lockdown::init();
	PF_Frontend_Mod::init();
} );
