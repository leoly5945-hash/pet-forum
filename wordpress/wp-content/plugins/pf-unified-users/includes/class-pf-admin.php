<?php
defined( 'ABSPATH' ) || exit;

class PF_Admin_V2 {

	const MENU_SLUG = 'pf-unified';

	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );

		add_action( 'admin_post_pf_assign_role', [ __CLASS__, 'handle_assign_role' ] );
		add_action( 'admin_post_pf_revoke_role', [ __CLASS__, 'handle_revoke_role' ] );
		add_action( 'admin_post_pf_approve_vet', [ __CLASS__, 'handle_approve_vet' ] );
		add_action( 'admin_post_pf_reject_vet', [ __CLASS__, 'handle_reject_vet' ] );

		add_filter( 'manage_users_columns', [ __CLASS__, 'add_user_columns' ] );
		add_filter( 'manage_users_custom_column', [ __CLASS__, 'render_user_columns' ], 10, 3 );

		add_action( 'show_user_profile', [ __CLASS__, 'render_vet_profile_fields' ] );
		add_action( 'edit_user_profile', [ __CLASS__, 'render_vet_profile_fields' ] );
	}

	public static function register_menu() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		add_menu_page(
			'PetForum Admin',
			'🐾 PetForum',
			'manage_options',
			self::MENU_SLUG,
			[ __CLASS__, 'render_dashboard' ],
			'dashicons-pets',
			3
		);

		add_submenu_page(
			self::MENU_SLUG,
			'Dashboard',
			'Dashboard',
			'manage_options',
			self::MENU_SLUG,
			[ __CLASS__, 'render_dashboard' ]
		);

		add_submenu_page(
			self::MENU_SLUG,
			'Phân Quyền',
			'Phân Quyền',
			'manage_options',
			'pfu-roles',
			[ __CLASS__, 'render_roles_page' ]
		);

		add_submenu_page(
			self::MENU_SLUG,
			'Duyệt Bác Sĩ',
			'Duyệt Bác Sĩ',
			'manage_options',
			'pfu-vet-approval',
			[ __CLASS__, 'render_vet_approval_page' ]
		);

		add_submenu_page(
			self::MENU_SLUG,
			'Anti-Spam',
			'Anti-Spam',
			'manage_options',
			'pfu-antispam',
			[ __CLASS__, 'render_antispam_page' ]
		);
	}

	/* ── Dashboard ── */

	public static function render_dashboard() {
		$vet_pending  = get_users( [
			'meta_key'   => PF_Constants::META_VET_STATUS,
			'meta_value' => 'pending',
			'fields'     => 'ID',
			'number'     => 100,
		] );

		global $wpdb;
		$spam_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pf_spam_log" );
		?>
		<div class="wrap">
			<h1>🐾 PetForum — Dashboard</h1>

			<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;max-width:900px;margin-top:24px">
				<div class="card" style="padding:20px">
					<h3 style="margin:0 0 8px">🩺 Vet chờ duyệt</h3>
					<p style="font-size:32px;font-weight:700;margin:0;color:#d97706"><?php echo count( $vet_pending ); ?></p>
					<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=pfu-vet-approval' ) ); ?>">Xem danh sách →</a></p>
				</div>
				<div class="card" style="padding:20px">
					<h3 style="margin:0 0 8px">🛡 Spam log</h3>
					<p style="font-size:32px;font-weight:700;margin:0;color:#dc2626"><?php echo (int) $spam_count; ?></p>
					<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=pfu-antispam' ) ); ?>">Xem log →</a></p>
				</div>
				<div class="card" style="padding:20px">
					<h3 style="margin:0 0 8px">👑 Sub-Admins</h3>
					<p style="font-size:32px;font-weight:700;margin:0;color:#0369a1">
						<?php
						$mod_count = 0;
						foreach ( PF_Constants::ALL_PF_ROLES as $role ) {
							if ( $role === PF_Constants::ROLE_VERIFIED_VET ) {
								continue;
							}
							$mod_count += count( get_users( [ 'role' => $role, 'fields' => 'ID' ] ) );
						}
						echo (int) $mod_count;
						?>
					</p>
					<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=pfu-roles' ) ); ?>">Quản lý →</a></p>
				</div>
			</div>
		</div>
		<?php
	}

	/* ── Roles page ── */

	public static function render_roles_page() {
		$roles = [
			PF_Constants::ROLE_GLOBAL_ADMIN => '🌐 Global Sub-Admin',
			PF_Constants::ROLE_DOG_MOD      => '🐕 Dog Section Admin',
			PF_Constants::ROLE_CAT_MOD      => '🐈 Cat Section Admin',
			PF_Constants::ROLE_BIRD_MOD     => '🐦 Bird Section Admin',
			PF_Constants::ROLE_MARKET_MOD   => '🛒 Marketplace Admin',
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
							<td><?php echo esc_html( implode( ', ', PF_Roles_V2::get_allowed_forum_ids( $role_slug ) ) ?: '—' ); ?></td>
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

		$assignable = array_diff( PF_Constants::ALL_PF_ROLES, [ PF_Constants::ROLE_VERIFIED_VET ] );

		if ( $user_id && in_array( $role, $assignable, true ) ) {
			$user = new WP_User( $user_id );
			$user->set_role( $role );
			PF_Roles_V2::assign_section_mod( $user_id, $role );
			set_transient( 'pf_roles_msg', 'Đã gán quyền thành công!', 30 );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=pfu-roles' ) );
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
			PF_Roles_V2::revoke_mod( $user_id );
			set_transient( 'pf_roles_msg', 'Đã thu hồi quyền thành công!', 30 );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=pfu-roles' ) );
		exit;
	}

	/* ── Vet approval ── */

	public static function render_vet_approval_page() {
		$status_filter = sanitize_text_field( wp_unslash( $_GET['vet_status'] ?? 'pending' ) );
		$pending_vets  = get_users( [
			'meta_key'   => PF_Constants::META_VET_STATUS,
			'meta_value' => $status_filter,
			'number'     => 50,
		] );

		$msg = get_transient( 'pf_vet_msg' );
		if ( $msg ) {
			delete_transient( 'pf_vet_msg' );
		}
		?>
		<div class="wrap">
			<h1>🩺 Duyệt Bác sĩ Thú y / Chuyên gia</h1>

			<?php if ( $msg ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $msg ); ?></p></div>
			<?php endif; ?>

			<nav class="nav-tab-wrapper" style="margin-bottom:20px">
				<?php foreach ( [ 'pending' => '⏳ Chờ duyệt', 'approved' => '✅ Đã duyệt', 'rejected' => '❌ Từ chối' ] as $s => $label ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=pfu-vet-approval&vet_status=' . $s ) ); ?>"
				   class="nav-tab <?php echo $status_filter === $s ? 'nav-tab-active' : ''; ?>">
					<?php echo esc_html( $label ); ?>
				</a>
				<?php endforeach; ?>
			</nav>

			<?php if ( empty( $pending_vets ) ) : ?>
			<p style="color:#94a3b8">Không có hồ sơ nào.</p>
			<?php else : ?>
			<table class="widefat striped" style="max-width:1100px">
				<thead>
					<tr>
						<th>Họ tên</th><th>Email</th><th>Điện thoại</th>
						<th>Nơi công tác</th><th>Chuyên khoa</th><th>Bằng cấp</th><th>Đăng ký</th><th>Hành động</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $pending_vets as $vet ) :
						$phone     = get_user_meta( $vet->ID, PF_Constants::META_PHONE, true );
						$workplace = get_user_meta( $vet->ID, PF_Constants::META_WORKPLACE, true );
						$specialty = get_user_meta( $vet->ID, PF_Constants::META_SPECIALTY, true );
						$reg_at    = get_user_meta( $vet->ID, PF_Constants::META_REGISTERED_AT, true );
						$cert_url  = wp_nonce_url(
							admin_url( 'admin-post.php?action=pf_view_cert&user_id=' . $vet->ID ),
							'pf_view_cert_' . $vet->ID
						);
						?>
					<tr>
						<td><strong><?php echo esc_html( $vet->display_name ); ?></strong></td>
						<td><?php echo esc_html( $vet->user_email ); ?></td>
						<td><?php echo esc_html( $phone ); ?></td>
						<td><?php echo esc_html( $workplace ); ?></td>
						<td><?php echo esc_html( $specialty ?: '—' ); ?></td>
						<td>
							<?php if ( get_user_meta( $vet->ID, PF_Constants::META_CERT_PATH, true ) ) : ?>
							<a href="<?php echo esc_url( $cert_url ); ?>" class="button button-small" target="_blank">📎 Xem</a>
							<?php else : ?>—<?php endif; ?>
						</td>
						<td style="font-size:12px;color:#94a3b8"><?php echo esc_html( $reg_at ); ?></td>
						<td>
							<?php if ( $status_filter === 'pending' ) : ?>
							<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
								<?php wp_nonce_field( 'pf_approve_vet_' . $vet->ID ); ?>
								<input type="hidden" name="action" value="pf_approve_vet">
								<input type="hidden" name="user_id" value="<?php echo (int) $vet->ID; ?>">
								<input type="submit" class="button button-primary" value="✅ Duyệt">
							</form>
							<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;margin-left:6px">
								<?php wp_nonce_field( 'pf_reject_vet_' . $vet->ID ); ?>
								<input type="hidden" name="action" value="pf_reject_vet">
								<input type="hidden" name="user_id" value="<?php echo (int) $vet->ID; ?>">
								<input type="submit" class="button" value="❌ Từ chối"
									onclick="return confirm('Từ chối hồ sơ của <?php echo esc_js( $vet->display_name ); ?>?')">
							</form>
							<?php elseif ( $status_filter === 'approved' ) : ?>
								<span style="color:green;font-weight:600">✔ Verified Vet</span>
							<?php else : ?>
								<span style="color:#dc2626">✗ Đã từ chối</span>
							<?php endif; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function handle_approve_vet() {
		$user_id = (int) ( $_POST['user_id'] ?? 0 );
		check_admin_referer( 'pf_approve_vet_' . $user_id );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		PF_Roles_V2::approve_vet( $user_id );

		$user = get_userdata( $user_id );
		set_transient( 'pf_vet_msg', "✅ Đã duyệt và xác minh bác sĩ: {$user->display_name}", 30 );
		wp_safe_redirect( admin_url( 'admin.php?page=pfu-vet-approval&vet_status=approved' ) );
		exit;
	}

	public static function handle_reject_vet() {
		$user_id = (int) ( $_POST['user_id'] ?? 0 );
		check_admin_referer( 'pf_reject_vet_' . $user_id );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		PF_Roles_V2::reject_vet( $user_id );

		$user = get_userdata( $user_id );
		set_transient( 'pf_vet_msg', "❌ Đã từ chối hồ sơ: {$user->display_name}", 30 );
		wp_safe_redirect( admin_url( 'admin.php?page=pfu-vet-approval&vet_status=rejected' ) );
		exit;
	}

	/* ── Anti-spam settings ── */

	public static function render_antispam_page() {
		if ( ! empty( $_POST['pf_save_antispam'] ) ) {
			check_admin_referer( 'pf_antispam_save' );

			$keywords = array_filter( array_map( 'trim', explode( "\n", wp_unslash( $_POST['pf_banned_keywords'] ?? '' ) ) ) );
			update_option( 'pf_banned_keywords', $keywords );
			update_option( 'pf_turnstile_site_key', sanitize_text_field( wp_unslash( $_POST['pf_turnstile_site_key'] ?? '' ) ) );
			update_option( 'pf_turnstile_secret_key', sanitize_text_field( wp_unslash( $_POST['pf_turnstile_secret_key'] ?? '' ) ) );

			echo '<div class="notice notice-success"><p>✅ Đã lưu cài đặt Anti-Spam!</p></div>';
		}

		$keywords  = implode( "\n", (array) get_option( 'pf_banned_keywords', [] ) );
		$ts_site   = get_option( 'pf_turnstile_site_key', '' );
		$ts_secret = get_option( 'pf_turnstile_secret_key', '' );

		global $wpdb;
		$logs = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}pf_spam_log ORDER BY created_at DESC LIMIT 50" );
		?>
		<div class="wrap">
			<h1>🛡 PetForum Anti-Spam Settings</h1>
			<form method="POST">
				<?php wp_nonce_field( 'pf_antispam_save' ); ?>
				<h2>Cloudflare Turnstile</h2>
				<table class="form-table">
					<tr>
						<th>Site Key</th>
						<td><input type="text" name="pf_turnstile_site_key" value="<?php echo esc_attr( $ts_site ); ?>" class="regular-text" placeholder="0x4AAAAAAA..."></td>
					</tr>
					<tr>
						<th>Secret Key</th>
						<td><input type="password" name="pf_turnstile_secret_key" value="<?php echo esc_attr( $ts_secret ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th colspan="2">
							<a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank" rel="noopener">
								→ Lấy key miễn phí tại Cloudflare Turnstile Dashboard
							</a>
						</th>
					</tr>
				</table>

				<h2>Từ khóa cấm (mỗi dòng 1 từ)</h2>
				<textarea name="pf_banned_keywords" rows="12" cols="60" class="large-text"><?php echo esc_textarea( $keywords ); ?></textarea>
				<p class="description">Bài chứa các từ này sẽ tự động chuyển sang Pending để Mod duyệt.</p>

				<p><input type="submit" name="pf_save_antispam" class="button button-primary" value="💾 Lưu cài đặt"></p>
			</form>

			<hr>
			<h2>📋 Spam Log (50 mục gần nhất)</h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th>User</th><th>Loại</th><th>Lý do</th><th>Keyword</th><th>IP</th><th>Thời gian</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $logs as $log ) : ?>
					<?php $log_user = get_userdata( $log->user_id ); ?>
					<tr>
						<td><?php echo esc_html( $log_user ? $log_user->display_name : "User #{$log->user_id}" ); ?></td>
						<td><?php echo esc_html( $log->post_type ); ?></td>
						<td><?php echo esc_html( $log->reason ); ?></td>
						<td><?php echo esc_html( $log->keyword ); ?></td>
						<td><?php echo esc_html( $log->ip_address ); ?></td>
						<td><?php echo esc_html( $log->created_at ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/* ── User list columns ── */

	public static function add_user_columns( $columns ) {
		$columns['pf_type'] = '🐾 Type';
		$columns['pf_vet']  = '🩺 Vet';

		return $columns;
	}

	public static function render_user_columns( $value, $column, $user_id ) {
		switch ( $column ) {
			case 'pf_type':
				$type = PF_Constants::get_user_type( $user_id );
				$map  = [
					PF_Constants::TYPE_MEMBER       => 'Thành viên',
					PF_Constants::TYPE_VET_PENDING  => '⏳ Vet pending',
					PF_Constants::TYPE_VERIFIED_VET => '✔ Verified Vet',
				];

				return esc_html( $map[ $type ] ?? $type );

			case 'pf_vet':
				$status = get_user_meta( $user_id, PF_Constants::META_VET_STATUS, true );
				$map    = [
					'pending'  => '<span style="color:#d97706">⏳ Chờ duyệt</span>',
					'approved' => '<span style="color:#15803d">✔ Verified</span>',
					'rejected' => '<span style="color:#dc2626">✗ Từ chối</span>',
				];

				return $map[ $status ] ?? '—';
		}

		return $value;
	}

	/* ── Vet profile fields in WP admin ── */

	public static function render_vet_profile_fields( $user ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$vet_status = get_user_meta( $user->ID, PF_Constants::META_VET_STATUS, true );
		$workplace  = get_user_meta( $user->ID, PF_Constants::META_WORKPLACE, true );
		$specialty  = get_user_meta( $user->ID, PF_Constants::META_SPECIALTY, true );
		$user_type  = PF_Constants::get_user_type( $user->ID );
		$pf_role    = PF_Constants::get_pf_role( $user->ID );

		if ( ! $vet_status && $user_type === PF_Constants::TYPE_MEMBER && ! $pf_role ) {
			return;
		}

		$cert_url = '';
		if ( get_user_meta( $user->ID, PF_Constants::META_CERT_PATH, true ) ) {
			$cert_url = wp_nonce_url(
				admin_url( 'admin-post.php?action=pf_view_cert&user_id=' . $user->ID ),
				'pf_view_cert_' . $user->ID
			);
		}
		?>
		<h2>🩺 PetForum — Thông tin Bác sĩ / Phân quyền</h2>
		<table class="form-table">
			<tr>
				<th>Loại tài khoản</th>
				<td><?php echo esc_html( $user_type ); ?></td>
			</tr>
			<?php if ( $pf_role ) : ?>
			<tr>
				<th>PF Role</th>
				<td><code><?php echo esc_html( $pf_role ); ?></code></td>
			</tr>
			<?php endif; ?>
			<?php if ( $vet_status ) : ?>
			<tr>
				<th>Trạng thái Vet</th>
				<td><?php echo esc_html( $vet_status ); ?></td>
			</tr>
			<tr>
				<th>Nơi công tác</th>
				<td><?php echo esc_html( $workplace ?: '—' ); ?></td>
			</tr>
			<tr>
				<th>Chuyên khoa</th>
				<td><?php echo esc_html( $specialty ?: '—' ); ?></td>
			</tr>
			<?php if ( $cert_url ) : ?>
			<tr>
				<th>Bằng cấp</th>
				<td><a href="<?php echo esc_url( $cert_url ); ?>" class="button" target="_blank">📎 Xem chứng chỉ</a></td>
			</tr>
			<?php endif; ?>
			<?php endif; ?>
		</table>
		<?php
	}
}
