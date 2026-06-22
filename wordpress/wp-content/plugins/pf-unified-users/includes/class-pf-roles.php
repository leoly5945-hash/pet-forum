<?php
defined( 'ABSPATH' ) || exit;

class PF_Roles_V2 {

	private static $mod_caps = [
		'vf', 'enf', 'ct', 'vt', 'ent', 'et', 'dt',
		'cr', 'ocr', 'vr', 'er', 'dr', 'tag',
		'eot', 'eor', 'dot', 'dor', 'sb', 'l', 'r', 's',
		'au', 'p', 'op', 'vp', 'sv', 'osv', 'v', 'vop', 'vlp',
		'a', 'va', 'at', 'oat', 'aot', 'cot', 'mt',
	];

	public static function init() {
		add_action( 'init', [ __CLASS__, 'create_all_roles' ], 1 );
		add_action( 'init', [ __CLASS__, 'ensure_vet_usergroup' ], 20 );

		add_filter( 'wpforo_permissions_forum_can', [ __CLASS__, 'filter_forum_can' ], 10, 4 );
		add_filter( 'wpforo_current_user_is', [ __CLASS__, 'filter_current_user_is' ], 10, 2 );
		add_filter( 'wpforo_user_is', [ __CLASS__, 'filter_user_is' ], 10, 3 );
		add_filter( 'wpforo_user_display_name', [ __CLASS__, 'add_verified_badge' ], 10, 2 );

		add_action( 'admin_menu', [ __CLASS__, 'remove_blocked_menus' ], 999 );
		add_action( 'admin_init', [ __CLASS__, 'block_admin_pages' ] );
		add_filter( 'user_has_cap', [ __CLASS__, 'filter_capabilities' ], 10, 4 );
		add_action( 'admin_bar_menu', [ __CLASS__, 'customize_admin_bar' ], 999 );
		add_filter( 'editable_roles', [ __CLASS__, 'filter_editable_roles' ] );
		add_action( 'edit_user_profile_update', [ __CLASS__, 'block_editing_admins' ] );
		add_action( 'personal_options_update', [ __CLASS__, 'block_editing_admins' ] );
	}

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

	/* ── Role creation ── */

	public static function create_all_roles() {
		if ( ! get_role( PF_Constants::ROLE_GLOBAL_ADMIN ) ) {
			add_role(
				PF_Constants::ROLE_GLOBAL_ADMIN,
				'🌐 Global Sub-Admin',
				[
					'read'                   => true,
					'edit_posts'             => true,
					'edit_others_posts'      => true,
					'delete_posts'           => true,
					'delete_others_posts'    => true,
					'publish_posts'          => true,
					'edit_published_posts'   => true,
					'delete_published_posts' => true,
					'edit_pages'             => true,
					'edit_others_pages'      => true,
					'publish_pages'          => true,
					'delete_pages'           => true,
					'manage_categories'      => true,
					'moderate_comments'      => true,
					'create_users'           => true,
					'edit_users'             => true,
					'list_users'             => true,
					'pf_global_admin'        => true,
					'pf_approve_content'     => true,
					'pf_manage_forums'       => true,
					'pf_create_categories'   => true,
				]
			);
		}

		if ( ! get_role( PF_Constants::ROLE_VERIFIED_VET ) ) {
			add_role(
				PF_Constants::ROLE_VERIFIED_VET,
				'✔ Verified Vet',
				[
					'read'              => true,
					'edit_posts'        => true,
					'moderate_comments' => true,
					'pf_verified_vet'   => true,
				]
			);
		}

		$section_caps = [
			'read'              => true,
			'edit_posts'        => true,
			'moderate_comments' => true,
			'pf_section_admin'  => true,
		];

		$section_defs = [
			PF_Constants::ROLE_DOG_MOD    => [ '🐕 Dog Section Admin', PF_Constants::ROLE_DOG_MOD ],
			PF_Constants::ROLE_CAT_MOD    => [ '🐈 Cat Section Admin', PF_Constants::ROLE_CAT_MOD ],
			PF_Constants::ROLE_BIRD_MOD   => [ '🐦 Bird Section Admin', PF_Constants::ROLE_BIRD_MOD ],
			PF_Constants::ROLE_MARKET_MOD => [ '🛒 Marketplace Admin', PF_Constants::ROLE_MARKET_MOD ],
		];

		foreach ( $section_defs as $slug => [ $label, $cap ] ) {
			if ( ! get_role( $slug ) ) {
				add_role( $slug, $label, array_merge( $section_caps, [ $cap => true ] ) );
			}
		}
	}

	public static function ensure_vet_usergroup() {
		if ( ! function_exists( 'WPF' ) || PF_Constants::get_vet_group_id() ) {
			return;
		}

		foreach ( WPF()->usergroup->get_usergroups() as $group ) {
			if ( stripos( $group['name'], 'verified vet' ) !== false ) {
				update_option( 'pf_verified_vet_group_id', (int) $group['groupid'], false );
				return;
			}
		}

		$groupid = WPF()->usergroup->add(
			'Verified Vet',
			[],
			'Verified veterinarian / expert',
			'subscriber',
			'standard',
			'#0369a1'
		);
		if ( $groupid ) {
			update_option( 'pf_verified_vet_group_id', (int) $groupid, false );
		}
	}

	/* ── Vet approval ── */

	public static function approve_vet( $user_id ) {
		$user_id = (int) $user_id;
		$user    = new WP_User( $user_id );
		if ( ! $user->exists() ) {
			return false;
		}

		$user->set_role( PF_Constants::ROLE_VERIFIED_VET );

		update_user_meta( $user_id, PF_Constants::META_VET_STATUS, 'approved' );
		update_user_meta( $user_id, PF_Constants::META_USER_TYPE, PF_Constants::TYPE_VERIFIED_VET );
		update_user_meta( $user_id, PF_Constants::META_ACCOUNT_ACTIVE, '1' );
		update_user_meta( $user_id, PF_Constants::META_APPROVED_AT, current_time( 'mysql' ) );
		update_user_meta( $user_id, PF_Constants::META_APPROVED_BY, get_current_user_id() );

		$group_id = PF_Constants::get_vet_group_id();
		if ( $group_id && function_exists( 'WPF' ) ) {
			WPF()->init();
			WPF()->member->update_profile_fields( $user_id, [ 'groupid' => $group_id ], false );
		}

		self::send_vet_approved_email( $user_id );

		return true;
	}

	public static function reject_vet( $user_id ) {
		$user_id = (int) $user_id;
		$user    = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		update_user_meta( $user_id, PF_Constants::META_VET_STATUS, 'rejected' );
		update_user_meta( $user_id, PF_Constants::META_ACCOUNT_ACTIVE, '0' );
		update_user_meta( $user_id, PF_Constants::META_REJECTED_AT, current_time( 'mysql' ) );

		self::send_vet_rejected_email( $user_id );

		return true;
	}

	public static function send_vet_pending_email( $user_id ) {
		$user      = get_userdata( $user_id );
		$site_name = get_bloginfo( 'name' );
		$lang      = get_user_meta( $user_id, PF_Constants::META_PREFERRED_LANG, true ) ?: 'vi';

		if ( $lang === 'vi' ) {
			$subject = "[{$site_name}] Hồ sơ của bạn đang được xem xét";
			$fallback = "<p>Xin chào <strong>{$user->display_name}</strong>,</p><p>Cảm ơn bạn đã đăng ký làm Bác sĩ/Chuyên gia tại {$site_name}. Ban quản trị sẽ phản hồi trong <strong>24-48 giờ</strong>.</p>";
		} else {
			$subject = "[{$site_name}] Your application is under review";
			$fallback = "<p>Hello <strong>{$user->display_name}</strong>,</p><p>Thank you for applying. We'll notify you within <strong>24-48 hours</strong>.</p>";
		}

		$message = self::load_email_template(
			'vet-pending',
			[
				'display_name' => $user->display_name,
				'site_name'    => $site_name,
			],
			$fallback
		);

		wp_mail( $user->user_email, $subject, $message, [ 'Content-Type: text/html; charset=UTF-8' ] );
	}

	public static function send_vet_approved_email( $user_id ) {
		$user      = get_userdata( $user_id );
		$site_name = get_bloginfo( 'name' );
		$login_url = wp_login_url( home_url( '/community/' ) );
		$subject   = "[{$site_name}] 🎉 Hồ sơ Bác sĩ của bạn đã được xác minh!";
		$fallback  = "
		<div style='font-family:sans-serif;max-width:560px;margin:0 auto;padding:32px 24px'>
		  <h2 style='color:#15803d'>🎉 Chúc mừng! Hồ sơ đã được xác minh</h2>
		  <p>Xin chào <strong>{$user->display_name}</strong>,</p>
		  <p>Tài khoản Verified Vet của bạn đã được kích hoạt.</p>
		  <p><a href='{$login_url}'>Đăng nhập và bắt đầu →</a></p>
		</div>";

		$message = self::load_email_template(
			'vet-approved',
			[
				'display_name' => $user->display_name,
				'login_url'    => $login_url,
				'site_name'    => $site_name,
			],
			$fallback
		);

		wp_mail( $user->user_email, $subject, $message, [ 'Content-Type: text/html; charset=UTF-8' ] );
	}

	public static function send_vet_rejected_email( $user_id ) {
		$user        = get_userdata( $user_id );
		$site_name   = get_bloginfo( 'name' );
		$admin_email = get_option( 'admin_email' );
		$subject     = "[{$site_name}] Thông báo về hồ sơ đăng ký Bác sĩ";
		$fallback    = "<p>Xin chào <strong>{$user->display_name}</strong>,</p><p>Rất tiếc chúng tôi chưa thể xác minh hồ sơ của bạn lúc này. Liên hệ: {$admin_email}</p>";

		$message = self::load_email_template(
			'vet-rejected',
			[
				'display_name' => $user->display_name,
				'admin_email'  => $admin_email,
				'site_name'    => $site_name,
			],
			$fallback
		);

		wp_mail( $user->user_email, $subject, $message, [ 'Content-Type: text/html; charset=UTF-8' ] );
	}

	/* ── Section moderator assignment (wpForo 3.x) ── */

	public static function assign_section_mod( $user_id, $pf_role ) {
		$user_id = (int) $user_id;
		if ( ! $user_id || ! in_array( $pf_role, PF_Constants::ALL_PF_ROLES, true ) ) {
			return;
		}

		update_user_meta( $user_id, PF_Constants::META_WPFORO_ROLE, $pf_role );
		self::flush_forum_map_cache();
		$forum_ids = self::get_allowed_forum_ids( $pf_role );
		update_user_meta( $user_id, PF_Constants::META_MOD_FORUM_IDS, $forum_ids );

		if ( $pf_role === PF_Constants::ROLE_GLOBAL_ADMIN && function_exists( 'WPF' ) ) {
			$member = WPF()->member->get_member( $user_id );
			if ( $member && (int) ( $member['groupid'] ?? 0 ) === PF_Constants::WPFORO_GROUP_MEMBER ) {
				WPF()->member->update_profile_fields( $user_id, [ 'groupid' => 2 ], false );
			}
		}
	}

	public static function revoke_mod( $user_id ) {
		$user_id = (int) $user_id;
		delete_user_meta( $user_id, PF_Constants::META_WPFORO_ROLE );
		delete_user_meta( $user_id, PF_Constants::META_MOD_FORUM_IDS );
	}

	public static function get_allowed_forum_ids( $role ) {
		if ( $role === PF_Constants::ROLE_GLOBAL_ADMIN ) {
			return [ 0 ];
		}

		$cached = get_option( 'pf_forum_map_' . $role );
		if ( is_array( $cached ) && ! empty( $cached ) ) {
			return $cached;
		}

		$slugs = PF_Constants::SECTION_FORUM_SLUGS[ $role ] ?? [];
		if ( empty( $slugs ) ) {
			return [];
		}

		$all_ids = [];

		if ( function_exists( 'WPF' ) ) {
			if ( method_exists( WPF(), 'init' ) ) {
				WPF()->init();
			}

			$forums = WPF()->forum->get_forums( [ 'type' => 'category' ] );
			$forums = array_merge( $forums, WPF()->forum->get_forums( [ 'type' => 'forum' ] ) );

			foreach ( $forums as $forum ) {
				if ( in_array( $forum['slug'], $slugs, true ) ) {
					$all_ids[] = (int) $forum['forumid'];
					$children  = WPF()->forum->get_forums( [ 'parentid' => (int) $forum['forumid'] ] );
					foreach ( (array) $children as $child ) {
						$all_ids[] = (int) $child['forumid'];
					}
				}
			}
		}

		if ( empty( $all_ids ) ) {
			global $wpdb;
			$tables = [ $wpdb->prefix . 'wpforo_forums' ];
			if ( function_exists( 'WPF' ) && method_exists( WPF(), 'get_active_boards_tables' ) ) {
				$tables = array_merge( $tables, WPF()->get_active_boards_tables( 'wpforo_forums' ) );
			}
			$tables = array_unique( $tables );

			foreach ( $tables as $table ) {
				if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
					continue;
				}
				$placeholders = implode( ',', array_fill( 0, count( $slugs ), '%s' ) );
				$ids          = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT forumid FROM `$table` WHERE slug IN ($placeholders)",
						...$slugs
					)
				);
				foreach ( (array) $ids as $parent_id ) {
					$all_ids[] = (int) $parent_id;
					$children  = $wpdb->get_col(
						$wpdb->prepare( "SELECT forumid FROM `$table` WHERE parentid = %d", (int) $parent_id )
					);
					foreach ( (array) $children as $child_id ) {
						$all_ids[] = (int) $child_id;
					}
				}
			}
		}

		$all_ids = array_values( array_unique( array_filter( $all_ids ) ) );
		update_option( 'pf_forum_map_' . $role, $all_ids, false );

		return $all_ids;
	}

	public static function user_can_moderate_forum( $user_id, $forum_id ) {
		if ( ! $user_id || ! $forum_id ) {
			return false;
		}
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		$pf_role = PF_Constants::get_pf_role( $user_id );
		if ( ! $pf_role ) {
			return false;
		}
		if ( $pf_role === PF_Constants::ROLE_GLOBAL_ADMIN ) {
			return true;
		}

		$allowed = self::get_allowed_forum_ids( $pf_role );

		return in_array( (int) $forum_id, $allowed, true );
	}

	public static function flush_forum_map_cache() {
		foreach ( PF_Constants::SECTION_MOD_ROLES as $role ) {
			delete_option( 'pf_forum_map_' . $role );
		}
	}

	/* ── wpForo permission filters ── */

	public static function filter_forum_can( $result, $do, $forumid, $groupids ) {
		unset( $groupids );

		if ( ! is_user_logged_in() || ! function_exists( 'WPF' ) ) {
			return $result;
		}

		$user_id = get_current_user_id();
		$pf_role = PF_Constants::get_pf_role( $user_id );
		if ( ! $pf_role ) {
			return $result;
		}

		$forum_id = (int) ( is_array( $forumid ) ? ( $forumid['forumid'] ?? 0 ) : $forumid );
		if ( ! $forum_id ) {
			$forum_id = (int) wpfval( WPF()->current_object, 'forum', 'forumid' );
		}

		if ( $pf_role === PF_Constants::ROLE_GLOBAL_ADMIN ) {
			if ( in_array( $do, self::$mod_caps, true ) ) {
				return 1;
			}
			return $result;
		}

		if ( ! self::user_can_moderate_forum( $user_id, $forum_id ) ) {
			return $result;
		}

		if ( in_array( $do, self::$mod_caps, true ) ) {
			return 1;
		}

		return $result;
	}

	public static function filter_current_user_is( $result, $role ) {
		if ( ! is_null( $result ) ) {
			return $result;
		}

		if ( $role !== 'moderator' ) {
			return $result;
		}

		$pf_role = PF_Constants::get_pf_role();
		if ( $pf_role === PF_Constants::ROLE_GLOBAL_ADMIN ) {
			return true;
		}

		return $result;
	}

	public static function filter_user_is( $result, $userid, $role ) {
		if ( ! is_null( $result ) ) {
			return $result;
		}

		if ( $role !== 'moderator' ) {
			return $result;
		}

		$pf_role = PF_Constants::get_pf_role( $userid );
		if ( $pf_role === PF_Constants::ROLE_GLOBAL_ADMIN ) {
			return true;
		}

		return $result;
	}

	public static function add_verified_badge( $display_name, $user ) {
		$user_id = is_object( $user ) ? (int) ( $user->ID ?? 0 ) : (int) $user;
		if ( ! $user_id ) {
			return $display_name;
		}

		if ( get_user_meta( $user_id, PF_Constants::META_USER_TYPE, true ) !== PF_Constants::TYPE_VERIFIED_VET ) {
			return $display_name;
		}

		return $display_name . ' <span class="pf-verified-badge" title="Bác sĩ Thú y đã xác minh">✔</span>';
	}

	/* ── Admin lockdown ── */

	public static function remove_blocked_menus() {
		$pf_role = PF_Constants::get_pf_role();
		if ( ! $pf_role ) {
			return;
		}

		if ( $pf_role === PF_Constants::ROLE_GLOBAL_ADMIN ) {
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

		$pf_role = PF_Constants::get_pf_role();
		if ( ! $pf_role ) {
			return;
		}

		if ( in_array( $pf_role, PF_Constants::SECTION_MOD_ROLES, true ) ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}

		if ( $pf_role === PF_Constants::ROLE_GLOBAL_ADMIN ) {
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
		unset( $caps, $args );

		$pf_role = PF_Constants::get_pf_role( $user->ID );
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

		if ( in_array( $pf_role, PF_Constants::SECTION_MOD_ROLES, true ) ) {
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
		if ( PF_Constants::get_pf_role() !== PF_Constants::ROLE_GLOBAL_ADMIN ) {
			return $roles;
		}

		$allowed = [ 'subscriber', 'contributor', 'author', 'editor' ];

		return array_intersect_key( $roles, array_flip( $allowed ) );
	}

	public static function block_editing_admins( $user_id ) {
		if ( PF_Constants::get_pf_role() !== PF_Constants::ROLE_GLOBAL_ADMIN ) {
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
		$pf_role = PF_Constants::get_pf_role();
		if ( ! $pf_role ) {
			return;
		}

		$wp_admin_bar->remove_node( 'wp-logo' );
		$wp_admin_bar->remove_node( 'updates' );
		$wp_admin_bar->remove_node( 'customize' );

		if ( in_array( $pf_role, PF_Constants::SECTION_MOD_ROLES, true ) ) {
			$wp_admin_bar->remove_node( 'dashboard' );
			$wp_admin_bar->remove_node( 'site-name' );
		}
	}

	private static function load_email_template( $slug, $vars, $fallback ) {
		$path = PFU_DIR . 'templates/email/' . $slug . '.php';
		if ( ! file_exists( $path ) ) {
			return $fallback;
		}

		ob_start();
		extract( $vars, EXTR_SKIP );
		include $path;

		return ob_get_clean();
	}
}
