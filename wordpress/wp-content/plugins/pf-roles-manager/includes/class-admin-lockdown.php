<?php
defined( 'ABSPATH' ) || exit;

class PF_Admin_Lockdown {

	private static $blocked_menus_global = [
		'plugins.php',
		'plugin-install.php',
		'themes.php',
		'theme-install.php',
		'update-core.php',
		'options-general.php',
		'options-reading.php',
		'options-writing.php',
		'options-permalink.php',
		'options-media.php',
		'tools.php',
		'export.php',
		'import.php',
	];

	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'remove_blocked_menus' ], 999 );
		add_action( 'admin_init', [ __CLASS__, 'block_admin_pages' ] );
		add_filter( 'user_has_cap', [ __CLASS__, 'filter_capabilities' ], 10, 4 );
		add_action( 'admin_bar_menu', [ __CLASS__, 'customize_admin_bar' ], 999 );
		add_filter( 'editable_roles', [ __CLASS__, 'filter_editable_roles' ] );
		add_action( 'edit_user_profile_update', [ __CLASS__, 'block_editing_admins' ] );
		add_action( 'personal_options_update', [ __CLASS__, 'block_editing_admins' ] );
	}

	public static function remove_blocked_menus() {
		$pf_role = PF_Roles::get_pf_role();
		if ( ! $pf_role ) {
			return;
		}

		if ( $pf_role === 'pf_global_admin' ) {
			foreach ( self::$blocked_menus_global as $menu ) {
				remove_menu_page( $menu );
			}
			remove_submenu_page( 'themes.php', 'theme-editor.php' );
			remove_submenu_page( 'plugins.php', 'plugin-editor.php' );
		}
	}

	public static function block_admin_pages() {
		if ( wp_doing_ajax() ) {
			return;
		}

		$pf_role = PF_Roles::get_pf_role();
		if ( ! $pf_role ) {
			return;
		}

		if ( in_array( $pf_role, PF_Roles::$section_roles, true ) ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}

		if ( $pf_role === 'pf_global_admin' ) {
			$blocked_pages = [
				'plugin-editor.php',
				'theme-editor.php',
				'export.php',
				'import.php',
				'tools.php',
				'update-core.php',
				'options-general.php',
			];
			$current       = basename( sanitize_text_field( wp_unslash( $_SERVER['PHP_SELF'] ?? '' ) ) );

			if ( in_array( $current, $blocked_pages, true ) ) {
				wp_die(
					'🚫 Bạn không có quyền truy cập trang này.',
					'Truy cập bị từ chối',
					[ 'response' => 403, 'back_link' => true ]
				);
			}
		}
	}

	public static function filter_capabilities( $allcaps, $caps, $args, $user ) {
		$pf_role = PF_Roles::get_pf_role( $user->ID );
		if ( ! $pf_role ) {
			return $allcaps;
		}

		$always_blocked = [
			'edit_plugins', 'edit_themes', 'edit_files',
			'activate_plugins', 'delete_plugins', 'install_plugins',
			'update_plugins', 'install_themes', 'update_themes',
			'switch_themes', 'update_core', 'export', 'import',
		];
		foreach ( $always_blocked as $cap ) {
			$allcaps[ $cap ] = false;
		}

		if ( in_array( $pf_role, PF_Roles::$section_roles, true ) ) {
			$section_blocked = [
				'manage_options', 'manage_categories', 'promote_users',
				'create_users', 'edit_users', 'delete_users',
				'publish_pages', 'edit_pages', 'delete_pages',
			];
			foreach ( $section_blocked as $cap ) {
				$allcaps[ $cap ] = false;
			}
		}

		return $allcaps;
	}

	public static function filter_editable_roles( $roles ) {
		if ( PF_Roles::get_pf_role() !== 'pf_global_admin' ) {
			return $roles;
		}

		$allowed = [ 'subscriber', 'contributor', 'author', 'editor' ];

		return array_intersect_key( $roles, array_flip( $allowed ) );
	}

	public static function block_editing_admins( $user_id ) {
		if ( PF_Roles::get_pf_role() !== 'pf_global_admin' ) {
			return;
		}

		$target = get_userdata( $user_id );
		if ( $target && in_array( 'administrator', (array) $target->roles, true ) ) {
			wp_die(
				'🚫 Bạn không có quyền chỉnh sửa tài khoản Super Admin.',
				'Truy cập bị từ chối',
				[ 'back_link' => true ]
			);
		}
	}

	public static function customize_admin_bar( $wp_admin_bar ) {
		$pf_role = PF_Roles::get_pf_role();
		if ( ! $pf_role ) {
			return;
		}

		$wp_admin_bar->remove_node( 'wp-logo' );
		$wp_admin_bar->remove_node( 'updates' );
		$wp_admin_bar->remove_node( 'customize' );

		if ( in_array( $pf_role, PF_Roles::$section_roles, true ) ) {
			$wp_admin_bar->remove_node( 'dashboard' );
			$wp_admin_bar->remove_node( 'site-name' );
		}
	}
}
