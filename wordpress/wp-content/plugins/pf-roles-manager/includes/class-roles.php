<?php
defined( 'ABSPATH' ) || exit;

class PF_Roles {

	public static $pf_role_slugs = [
		'pf_global_admin',
		'pf_dog_admin',
		'pf_cat_admin',
		'pf_bird_admin',
		'pf_market_admin',
	];

	public static $section_roles = [
		'pf_dog_admin',
		'pf_cat_admin',
		'pf_bird_admin',
		'pf_market_admin',
	];

	// Slug diễn đàn — cập nhật sau khi tạo forums trong wpForo Admin
	public static $section_slugs = [
		'pf_dog_admin'    => [ 'cho-canh', 'hoi-cuong-cho', 'dogs', 'dog-lovers' ],
		'pf_cat_admin'    => [ 'meo-canh', 'hoi-cuong-meo', 'cats', 'cat-lovers', 'test-cat-care', 'test-cat-care-discussions' ],
		'pf_bird_admin'   => [ 'chim-canh', 'hoi-cuong-chim', 'birds', 'bird-lovers' ],
		'pf_market_admin' => [ 'goc-mua-ban', 'marketplace', 'mua-ban', 'buy-sell' ],
	];

	public static function init() {
		add_action( 'init', [ __CLASS__, 'create_all_roles' ], 1 );

		add_action( 'admin_menu', [ __CLASS__, 'register_admin_page' ] );
		add_action( 'admin_post_pf_assign_role', [ __CLASS__, 'handle_assign_role' ] );
		add_action( 'admin_post_pf_revoke_role', [ __CLASS__, 'handle_revoke_role' ] );
	}

	public static function create_all_roles() {
		if ( ! get_role( 'pf_global_admin' ) ) {
			add_role(
				'pf_global_admin',
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

		$section_caps = [
			'read'              => true,
			'edit_posts'        => true,
			'moderate_comments' => true,
			'pf_section_admin'  => true,
		];

		$section_defs = [
			'pf_dog_admin'    => [ '🐕 Dog Section Admin', 'pf_dog_admin' ],
			'pf_cat_admin'    => [ '🐈 Cat Section Admin', 'pf_cat_admin' ],
			'pf_bird_admin'   => [ '🐦 Bird Section Admin', 'pf_bird_admin' ],
			'pf_market_admin' => [ '🛒 Marketplace Admin', 'pf_market_admin' ],
		];

		foreach ( $section_defs as $slug => [ $label, $cap ] ) {
			if ( ! get_role( $slug ) ) {
				add_role( $slug, $label, array_merge( $section_caps, [ $cap => true ] ) );
			}
		}
	}

	public static function remove_all_roles() {
		foreach ( self::$pf_role_slugs as $role ) {
			remove_role( $role );
		}
	}

	public static function register_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		add_menu_page(
			'PF Roles Manager',
			'👑 Phân Quyền',
			'manage_options',
			'pf-roles-manager',
			[ __CLASS__, 'render_admin_page' ],
			'dashicons-groups',
			3
		);
	}

	public static function render_admin_page() {
		$roles = [
			'pf_global_admin' => '🌐 Global Sub-Admin',
			'pf_dog_admin'    => '🐕 Dog Section Admin',
			'pf_cat_admin'    => '🐈 Cat Section Admin',
			'pf_bird_admin'   => '🐦 Bird Section Admin',
			'pf_market_admin' => '🛒 Marketplace Admin',
		];

		$role_users = [];
		foreach ( array_keys( $roles ) as $role ) {
			$role_users[ $role ] = get_users( [
				'role'   => $role,
				'fields' => [ 'ID', 'display_name', 'user_email' ],
			] );
		}
		?>
		<div class="wrap">
			<h1>👑 PetForum — Quản lý phân quyền Sub-Admin</h1>

			<?php if ( $msg = get_transient( 'pf_roles_msg' ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $msg ); ?></p></div>
			<?php delete_transient( 'pf_roles_msg' ); endif; ?>

			<div class="card" style="max-width:600px;padding:20px;margin-bottom:24px">
				<h2>➕ Gán quyền Sub-Admin</h2>
				<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'pf_assign_role' ); ?>
					<input type="hidden" name="action" value="pf_assign_role">
					<table class="form-table">
						<tr>
							<th><label for="pf_user_id">Chọn User</label></th>
							<td>
								<select name="user_id" id="pf_user_id" style="min-width:250px">
									<option value="">— Chọn thành viên —</option>
									<?php
									$all_users = get_users( [
										'role__not_in' => [ 'administrator' ],
										'number'       => 200,
									] );
									foreach ( $all_users as $u ) :
										?>
									<option value="<?php echo (int) $u->ID; ?>">
										<?php echo esc_html( $u->display_name ); ?> (<?php echo esc_html( $u->user_email ); ?>)
									</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="pf_role">Vai trò</label></th>
							<td>
								<select name="role" id="pf_role">
									<?php foreach ( $roles as $slug => $name ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
					</table>
					<?php submit_button( '✅ Gán quyền', 'primary' ); ?>
				</form>
			</div>

			<h2>📋 Danh sách Sub-Admin hiện tại</h2>
			<?php foreach ( $roles as $role_slug => $role_name ) : ?>
			<div class="card" style="max-width:700px;padding:20px;margin-bottom:16px">
				<h3><?php echo esc_html( $role_name ); ?></h3>
				<?php if ( empty( $role_users[ $role_slug ] ) ) : ?>
					<p style="color:#94a3b8">Chưa có ai được gán vai trò này.</p>
				<?php else : ?>
				<table class="widefat" style="margin-top:8px">
					<thead><tr><th>Tên</th><th>Email</th><th>Forums</th><th>Hành động</th></tr></thead>
					<tbody>
						<?php foreach ( $role_users[ $role_slug ] as $u ) : ?>
						<tr>
							<td><?php echo esc_html( $u->display_name ); ?></td>
							<td><?php echo esc_html( $u->user_email ); ?></td>
							<td><?php echo esc_html( implode( ', ', self::get_allowed_forum_ids( $role_slug ) ) ?: '—' ); ?></td>
							<td>
								<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
									<?php wp_nonce_field( 'pf_revoke_role' ); ?>
									<input type="hidden" name="action" value="pf_revoke_role">
									<input type="hidden" name="user_id" value="<?php echo (int) $u->ID; ?>">
									<input type="hidden" name="role" value="<?php echo esc_attr( $role_slug ); ?>">
									<input type="submit" class="button button-small" value="🗑 Thu hồi quyền"
										onclick="return confirm('Thu hồi quyền của <?php echo esc_js( $u->display_name ); ?>?')">
								</form>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	public static function handle_assign_role() {
		check_admin_referer( 'pf_assign_role' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		$user_id = (int) ( $_POST['user_id'] ?? 0 );
		$role    = sanitize_key( $_POST['role'] ?? '' );

		if ( $user_id && in_array( $role, self::$pf_role_slugs, true ) ) {
			$user = new WP_User( $user_id );
			$user->set_role( $role );
			PF_WpForo_Perms::assign_moderator( $user_id, $role );
			set_transient( 'pf_roles_msg', 'Đã gán quyền thành công!', 30 );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=pf-roles-manager' ) );
		exit;
	}

	public static function handle_revoke_role() {
		check_admin_referer( 'pf_revoke_role' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		$user_id = (int) ( $_POST['user_id'] ?? 0 );

		if ( $user_id ) {
			$user = new WP_User( $user_id );
			$user->set_role( 'subscriber' );
			PF_WpForo_Perms::revoke_moderator( $user_id );
			set_transient( 'pf_roles_msg', 'Đã thu hồi quyền thành công!', 30 );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=pf-roles-manager' ) );
		exit;
	}

	public static function get_pf_role( $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return null;
		}

		foreach ( self::$pf_role_slugs as $role ) {
			if ( in_array( $role, (array) $user->roles, true ) ) {
				return $role;
			}
		}

		return null;
	}

	public static function get_allowed_forum_ids( $role ) {
		if ( $role === 'pf_global_admin' ) {
			return [ 0 ];
		}

		$cached = get_option( 'pf_forum_map_' . $role );
		if ( is_array( $cached ) && ! empty( $cached ) ) {
			return $cached;
		}

		$slugs = self::$section_slugs[ $role ] ?? [];
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

		$pf_role = self::get_pf_role( $user_id );
		if ( ! $pf_role ) {
			return false;
		}
		if ( $pf_role === 'pf_global_admin' ) {
			return true;
		}

		$allowed = self::get_allowed_forum_ids( $pf_role );

		return in_array( (int) $forum_id, $allowed, true );
	}

	public static function flush_forum_map_cache() {
		foreach ( self::$section_roles as $role ) {
			delete_option( 'pf_forum_map_' . $role );
		}
	}
}
