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

		add_action( 'wp_ajax_pf_warn_user', [ __CLASS__, 'ajax_warn_user' ] );
		add_action( 'wp_ajax_pf_clear_warn', [ __CLASS__, 'ajax_clear_warn' ] );

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

		add_submenu_page(
			self::MENU_SLUG,
			'Cảnh báo User',
			'⚠️ Cảnh báo',
			'manage_options',
			'pfu-warnings',
			[ __CLASS__, 'render_warnings_page' ]
		);

		add_submenu_page(
			self::MENU_SLUG,
			'Mod Log',
			'📋 Mod Log',
			'manage_options',
			'pfu-modlog',
			[ __CLASS__, 'render_modlog_page' ]
		);

		add_submenu_page(
			self::MENU_SLUG,
			'Bàn giao',
			'🚪 Bàn giao',
			'manage_options',
			'pfu-handover',
			[ __CLASS__, 'render_handover_page' ]
		);

		add_submenu_page(
			self::MENU_SLUG,
			'Cấu hình Quảng cáo',
			'📢 Quảng cáo',
			'manage_options',
			'pfu-ads',
			[ __CLASS__, 'render_ads_settings_page' ]
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
					<h3 style="margin:0 0 8px">⚠️ Cảnh báo</h3>
					<p style="font-size:32px;font-weight:700;margin:0;color:#b45309">
						<?php
						echo count( get_users( [
							'meta_query' => [
								[
									'key'     => PF_Constants::META_WARN_LEVEL,
									'value'   => 0,
									'compare' => '>',
									'type'    => 'NUMERIC',
								],
							],
							'fields' => 'ID',
							'number' => 100,
						] ) );
						?>
					</p>
					<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=pfu-warnings' ) ); ?>">Quản lý →</a></p>
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
			$target = get_userdata( $user_id );
			PF_Logger::log(
				get_current_user_id(),
				'assign_role',
				'user',
				$user_id,
				$target ? $target->user_email : "#{$user_id}",
				"Assigned role: {$role}"
			);
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
			$target = get_userdata( $user_id );
			$user   = new WP_User( $user_id );
			$user->set_role( 'subscriber' );
			PF_Roles_V2::revoke_mod( $user_id );
			PF_Logger::log(
				get_current_user_id(),
				'revoke_role',
				'user',
				$user_id,
				$target ? $target->user_email : "#{$user_id}",
				'Role revoked by Super Admin'
			);
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
		PF_Logger::log(
			get_current_user_id(),
			'approve_vet',
			'user',
			$user_id,
			$user->user_email,
			'Vet application approved'
		);
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
		PF_Logger::log(
			get_current_user_id(),
			'reject_vet',
			'user',
			$user_id,
			$user->user_email,
			'Vet application rejected'
		);
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

	/* ── Warnings ── */

	public static function ajax_warn_user(): void {
		check_ajax_referer( 'pf_admin_action' );

		if ( ! current_user_can( 'manage_options' ) && ! PF_Constants::get_pf_role() ) {
			wp_send_json_error( [ 'message' => 'Không có quyền.' ] );
		}

		$input  = sanitize_text_field( wp_unslash( $_POST['user_id'] ?? '' ) );
		$level  = (int) ( $_POST['level'] ?? 1 );
		$reason = sanitize_text_field( wp_unslash( $_POST['reason'] ?? '' ) );

		if ( ! $input || ! $reason ) {
			wp_send_json_error( [ 'message' => 'Thiếu thông tin user hoặc lý do.' ] );
		}

		$user = str_contains( $input, '@' ) ? get_user_by( 'email', $input ) : get_userdata( (int) $input );
		if ( ! $user ) {
			wp_send_json_error( [ 'message' => 'Không tìm thấy user.' ] );
		}

		$pf_role = PF_Constants::get_pf_role();
		if ( $pf_role && ! current_user_can( 'manage_options' ) ) {
			if ( $level >= PF_Constants::WARN_BANNED ) {
				wp_send_json_error( [ 'message' => 'Mod không có quyền khóa tài khoản — chỉ Admin được làm điều này.' ] );
			}
			if ( $level > PF_Constants::WARN_CAUTION ) {
				wp_send_json_error( [ 'message' => 'Mod chỉ được cảnh báo cấp 1–2.' ] );
			}
		}

		PF_Roles_V2::warn_user( (int) $user->ID, $reason, $level );
		PF_Logger::log(
			get_current_user_id(),
			'warn_user',
			'user',
			(int) $user->ID,
			$user->user_email,
			$reason,
			[ 'level' => $level ]
		);
		wp_send_json_success( [ 'message' => 'Đã gửi cảnh báo!' ] );
	}

	public static function ajax_clear_warn(): void {
		check_ajax_referer( 'pf_admin_action' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Chỉ Admin mới được gỡ cảnh báo.' ] );
		}

		$uid = (int) ( $_POST['user_id'] ?? 0 );
		if ( ! $uid ) {
			wp_send_json_error( [ 'message' => 'User ID không hợp lệ.' ] );
		}

		PF_Roles_V2::clear_warning( $uid );
		$target = get_userdata( $uid );
		PF_Logger::log(
			get_current_user_id(),
			'clear_warning',
			'user',
			$uid,
			$target ? $target->user_email : "#{$uid}",
			'Warning cleared by Super Admin'
		);
		wp_send_json_success( [ 'message' => 'Đã gỡ cảnh báo!' ] );
	}

	public static function render_warnings_page(): void {
		$warned_users = get_users( [
			'meta_query' => [
				[
					'key'     => PF_Constants::META_WARN_LEVEL,
					'value'   => 0,
					'compare' => '>',
					'type'    => 'NUMERIC',
				],
			],
			'number' => 100,
		] );
		?>
		<div class="wrap">
			<h1>⚠️ Quản lý Cảnh báo thành viên</h1>

			<div class="card" style="max-width:600px;padding:20px;margin:16px 0">
				<h2>Gửi cảnh báo</h2>
				<div id="pf-warn-form">
					<p>
						<label for="pf-warn-user"><strong>User ID hoặc Email:</strong></label><br>
						<input type="text" id="pf-warn-user" class="regular-text" placeholder="user@email.com hoặc ID">
					</p>
					<p>
						<label for="pf-warn-level"><strong>Mức độ:</strong></label><br>
						<select id="pf-warn-level">
							<option value="1">⚠️ Cấp 1 — Cảnh báo</option>
							<option value="2">🔴 Cấp 2 — Cảnh cáo</option>
							<option value="3">🔒 Cấp 3 — Hạn chế đăng bài</option>
							<option value="4">🚫 Cấp 4 — Khóa tài khoản</option>
						</select>
					</p>
					<p>
						<label for="pf-warn-reason"><strong>Lý do:</strong></label><br>
						<textarea id="pf-warn-reason" rows="3" class="large-text" placeholder="Mô tả vi phạm..."></textarea>
					</p>
					<button type="button" class="button button-primary" onclick="pfSendWarning()">Gửi cảnh báo</button>
				</div>
			</div>

			<h2>Đang bị cảnh báo (<?php echo count( $warned_users ); ?>)</h2>
			<?php if ( empty( $warned_users ) ) : ?>
				<p style="color:#94a3b8">Không có ai bị cảnh báo.</p>
			<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th>User</th>
						<th>Cấp độ</th>
						<th>Lý do</th>
						<th>Số lần</th>
						<th>Thời gian</th>
						<th>Hành động</th>
					</tr>
				</thead>
				<tbody>
				<?php
				$badges = [ 1 => '⚠️', 2 => '🔴', 3 => '🔒', 4 => '🚫' ];
				$labels = [ 1 => 'Cảnh báo', 2 => 'Cảnh cáo', 3 => 'Hạn chế', 4 => 'Bị khóa' ];
				foreach ( $warned_users as $u ) :
					$level  = (int) get_user_meta( $u->ID, PF_Constants::META_WARN_LEVEL, true );
					$reason = get_user_meta( $u->ID, PF_Constants::META_WARN_REASON, true );
					$count  = get_user_meta( $u->ID, PF_Constants::META_WARN_COUNT, true );
					$at     = get_user_meta( $u->ID, PF_Constants::META_WARN_AT, true );
					?>
				<tr>
					<td>
						<strong><?php echo esc_html( $u->display_name ); ?></strong><br>
						<small style="color:#94a3b8"><?php echo esc_html( $u->user_email ); ?></small>
					</td>
					<td><?php echo esc_html( ( $badges[ $level ] ?? '' ) . ' ' . ( $labels[ $level ] ?? $level ) ); ?></td>
					<td><?php echo esc_html( $reason ); ?></td>
					<td style="text-align:center"><?php echo (int) $count; ?></td>
					<td style="font-size:11px"><?php echo esc_html( $at ); ?></td>
					<td>
						<button type="button" class="button button-small" onclick="pfClearWarning(<?php echo (int) $u->ID; ?>)">✅ Gỡ cảnh báo</button>
						<?php if ( $level < 4 ) : ?>
						<button type="button" class="button button-small" style="margin-top:4px"
							onclick="pfEscalate(<?php echo (int) $u->ID; ?>, <?php echo (int) $level + 1; ?>)">
							⬆️ Leo thang
						</button>
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>

		<script>
		const pfWarnNonce = '<?php echo esc_js( wp_create_nonce( 'pf_admin_action' ) ); ?>';

		function pfSendWarning() {
			const user   = document.getElementById('pf-warn-user').value;
			const level  = document.getElementById('pf-warn-level').value;
			const reason = document.getElementById('pf-warn-reason').value;
			if (!user || !reason) return alert('Nhập đầy đủ thông tin!');

			fetch(ajaxurl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams({ action: 'pf_warn_user', user_id: user, level, reason, _ajax_nonce: pfWarnNonce })
			}).then(r => r.json()).then(d => {
				alert(d.data?.message || (d.success ? 'OK' : 'Lỗi'));
				if (d.success) location.reload();
			});
		}

		function pfClearWarning(uid) {
			if (!confirm('Gỡ cảnh báo cho user này?')) return;
			fetch(ajaxurl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams({ action: 'pf_clear_warn', user_id: uid, _ajax_nonce: pfWarnNonce })
			}).then(r => r.json()).then(d => {
				alert(d.data?.message || (d.success ? 'OK' : 'Lỗi'));
				if (d.success) location.reload();
			});
		}

		function pfEscalate(uid, newLevel) {
			const reason = prompt('Lý do leo thang cảnh báo:');
			if (!reason) return;
			fetch(ajaxurl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams({ action: 'pf_warn_user', user_id: uid, level: newLevel, reason, _ajax_nonce: pfWarnNonce })
			}).then(r => r.json()).then(d => {
				alert(d.data?.message || (d.success ? 'OK' : 'Lỗi'));
				if (d.success) location.reload();
			});
		}
		</script>
		<?php
	}

	/* ── Mod Log ── */

	public static function render_modlog_page(): void {
		$filter_mod = (int) ( $_GET['mod_id'] ?? 0 );
		$filter_act = sanitize_key( wp_unslash( $_GET['action_filter'] ?? '' ) );
		$date_from  = sanitize_text_field( wp_unslash( $_GET['date_from'] ?? gmdate( 'Y-m-01' ) ) );
		$date_to    = sanitize_text_field( wp_unslash( $_GET['date_to'] ?? gmdate( 'Y-m-d' ) ) );
		$page_num   = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
		$per_page   = 30;

		$logs = PF_Logger::get_logs( [
			'mod_id'    => $filter_mod ?: null,
			'action'    => $filter_act ?: null,
			'date_from' => $date_from,
			'date_to'   => $date_to,
			'limit'     => $per_page,
			'offset'    => ( $page_num - 1 ) * $per_page,
		] );

		$action_labels = PF_Logger::action_labels();
		$mod_roles     = array_merge( [ PF_Constants::ROLE_GLOBAL_ADMIN ], PF_Constants::SECTION_MOD_ROLES );

		$export_url = add_query_arg( [
			'action'    => 'pf_export_mod_log',
			'mod_id'    => $filter_mod,
			'date_from' => $date_from,
			'date_to'   => $date_to,
			'_wpnonce'  => wp_create_nonce( 'pf_admin_action' ),
		], admin_url( 'admin-ajax.php' ) );
		?>
		<div class="wrap">
			<h1>📋 Mod Action Log</h1>

			<div class="card" style="padding:16px;margin:16px 0;max-width:860px">
				<form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
					<input type="hidden" name="page" value="pfu-modlog">
					<div>
						<label style="display:block;font-size:12px;color:#64748b;margin-bottom:4px">Mod</label>
						<select name="mod_id">
							<option value="">— Tất cả —</option>
							<?php foreach ( get_users( [ 'role__in' => $mod_roles, 'number' => 200 ] ) as $u ) : ?>
							<option value="<?php echo (int) $u->ID; ?>" <?php selected( $filter_mod, $u->ID ); ?>><?php echo esc_html( $u->display_name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div>
						<label style="display:block;font-size:12px;color:#64748b;margin-bottom:4px">Hành động</label>
						<select name="action_filter">
							<option value="">— Tất cả —</option>
							<?php foreach ( $action_labels as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $filter_act, $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div>
						<label style="display:block;font-size:12px;color:#64748b;margin-bottom:4px">Từ ngày</label>
						<input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>">
					</div>
					<div>
						<label style="display:block;font-size:12px;color:#64748b;margin-bottom:4px">Đến ngày</label>
						<input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>">
					</div>
					<div>
						<input type="submit" class="button button-primary" value="🔍 Lọc">
						<a href="<?php echo esc_url( $export_url ); ?>" class="button" style="margin-left:8px">📥 Xuất CSV</a>
					</div>
				</form>
			</div>

			<table class="widefat striped" style="max-width:1100px">
				<thead>
					<tr><th>#</th><th>Mod</th><th>Hành động</th><th>Đối tượng</th><th>Lý do</th><th>IP</th><th>Thời gian</th></tr>
				</thead>
				<tbody>
				<?php if ( empty( $logs ) ) : ?>
					<tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:24px">Không có log nào.</td></tr>
				<?php else : ?>
					<?php foreach ( $logs as $log ) :
						$mod = get_userdata( (int) $log['mod_id'] );
						?>
					<tr>
						<td style="color:#94a3b8;font-size:12px">#<?php echo (int) $log['id']; ?></td>
						<td>
							<strong><?php echo $mod ? esc_html( $mod->display_name ) : '#' . (int) $log['mod_id']; ?></strong>
							<?php if ( $mod ) : ?><br><small style="color:#94a3b8"><?php echo esc_html( $mod->user_email ); ?></small><?php endif; ?>
						</td>
						<td><?php echo esc_html( $action_labels[ $log['action'] ] ?? $log['action'] ); ?></td>
						<td>
							<span style="font-size:12px;color:#64748b"><?php echo esc_html( $log['target_type'] ?? '' ); ?></span>
							<br><?php echo esc_html( $log['target_info'] ?: '#' . (int) $log['target_id'] ); ?>
						</td>
						<td style="max-width:200px;font-size:13px"><?php echo esc_html( $log['reason'] ?? '' ); ?></td>
						<td style="font-size:12px;color:#94a3b8"><?php echo esc_html( $log['ip_address'] ?? '' ); ?></td>
						<td style="font-size:12px;white-space:nowrap"><?php echo esc_html( $log['created_at'] ); ?></td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/* ── Handover ── */

	public static function render_handover_page(): void {
		$mod_roles = array_merge( [ PF_Constants::ROLE_GLOBAL_ADMIN ], PF_Constants::SECTION_MOD_ROLES );
		$all_mods  = get_users( [ 'role__in' => $mod_roles, 'number' => 100 ] );
		$nonce     = wp_create_nonce( 'pf_admin_action' );
		?>
		<div class="wrap">
			<h1>🚪 Quy trình Bàn giao Sub-Admin</h1>
			<p style="color:#64748b;max-width:700px">Khi một Sub-Admin rời khỏi vị trí, Super Admin cần hoàn thành đầy đủ quy trình bàn giao dưới đây trước khi thu hồi quyền.</p>

			<?php foreach ( $all_mods as $mod ) :
				$done      = get_user_meta( $mod->ID, PF_Constants::META_HANDOVER_DONE, true );
				$log_count = PF_Logger::count_logs( $mod->ID );
				$role      = PF_Constants::get_pf_role( $mod->ID );
				$accepted  = get_user_meta( $mod->ID, PF_Constants::META_SUBADMIN_TERMS_ACCEPTED, true );

				$export_url = add_query_arg( [
					'action'    => 'pf_export_mod_log',
					'mod_id'    => $mod->ID,
					'date_from' => '2020-01-01',
					'date_to'   => gmdate( 'Y-m-d' ),
					'_wpnonce'  => $nonce,
				], admin_url( 'admin-ajax.php' ) );
				?>
			<div class="card" style="max-width:760px;padding:24px;margin:16px 0;<?php echo $done ? 'opacity:.6' : ''; ?>">
				<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
					<div>
						<strong style="font-size:16px"><?php echo esc_html( $mod->display_name ); ?></strong>
						<span style="background:#e0f2fe;color:#0369a1;padding:2px 10px;border-radius:12px;font-size:12px;margin-left:8px">
							<?php echo esc_html( $role ?? 'unknown' ); ?>
						</span>
					</div>
					<?php if ( $done ) : ?>
					<span style="color:green;font-weight:600">✅ Đã bàn giao xong</span>
					<?php endif; ?>
				</div>

				<div style="background:#f8fafc;border-radius:8px;padding:16px;margin-bottom:16px">
					<h3 style="margin:0 0 12px;font-size:14px;color:#334155">Checklist bàn giao:</h3>

					<label style="display:flex;gap:10px;align-items:flex-start;margin-bottom:10px;cursor:pointer">
						<input type="checkbox" class="ho-check" data-uid="<?php echo (int) $mod->ID; ?>">
						<span>📥 Đã xuất và lưu trữ toàn bộ Mod Log
							<a href="<?php echo esc_url( $export_url ); ?>" style="font-size:12px;margin-left:8px">
								(Tải CSV — <?php echo (int) $log_count; ?> records)
							</a>
						</span>
					</label>

					<label style="display:flex;gap:10px;align-items:flex-start;margin-bottom:10px;cursor:pointer">
						<input type="checkbox" class="ho-check" data-uid="<?php echo (int) $mod->ID; ?>">
						<span>🗑 Sub-Admin đã xóa dữ liệu diễn đàn khỏi thiết bị cá nhân</span>
					</label>

					<label style="display:flex;gap:10px;align-items:flex-start;margin-bottom:10px;cursor:pointer">
						<input type="checkbox" class="ho-check" data-uid="<?php echo (int) $mod->ID; ?>">
						<span>🔑 Đã thu hồi mọi quyền truy cập và đổi mật khẩu liên quan</span>
					</label>

					<label style="display:flex;gap:10px;align-items:flex-start;margin-bottom:10px;cursor:pointer">
						<input type="checkbox" class="ho-check" data-uid="<?php echo (int) $mod->ID; ?>">
						<span>📋 Đã bàn giao các công việc đang xử lý dở cho Sub-Admin kế tiếp</span>
					</label>

					<label style="display:flex;gap:10px;align-items:flex-start;cursor:pointer">
						<input type="checkbox" class="ho-check" data-uid="<?php echo (int) $mod->ID; ?>">
						<span>✅ Sub-Admin đã cam kết không sử dụng dữ liệu thành viên sau khi rời khỏi vị trí</span>
					</label>
				</div>

				<p style="font-size:13px;color:#64748b">
					📋 Tổng hành động đã ghi log: <strong><?php echo (int) $log_count; ?></strong> &nbsp;|&nbsp;
					📅 Tham gia từ: <strong><?php echo esc_html( gmdate( 'd/m/Y', strtotime( $mod->user_registered ) ) ); ?></strong> &nbsp;|&nbsp;
					🤝 Đã ký cam kết Sub-Admin: <strong><?php echo $accepted ? '✅ Có (' . esc_html( gmdate( 'd/m/Y', strtotime( $accepted ) ) ) . ')' : '❌ Chưa'; ?></strong>
				</p>

				<?php if ( ! $done ) : ?>
				<button type="button" class="button button-primary ho-confirm-btn"
					data-uid="<?php echo (int) $mod->ID; ?>"
					data-nonce="<?php echo esc_attr( $nonce ); ?>"
					style="margin-top:8px" disabled>
					🚪 Xác nhận bàn giao &amp; Thu hồi quyền
				</button>
				<p style="font-size:12px;color:#dc2626;margin:6px 0 0">
					⚠️ Sau khi xác nhận, tài khoản sẽ bị thu hồi quyền Sub-Admin ngay lập tức.
				</p>
				<?php else :
					$by_id = (int) get_user_meta( $mod->ID, PF_Constants::META_HANDOVER_BY, true );
					$by    = $by_id ? get_userdata( $by_id ) : null;
					?>
				<p style="color:#64748b;font-size:13px">
					✅ Bàn giao xác nhận bởi: <strong><?php echo $by ? esc_html( $by->display_name ) : 'Super Admin'; ?></strong>
					lúc <?php echo esc_html( get_user_meta( $mod->ID, PF_Constants::META_HANDOVER_AT, true ) ); ?>
				</p>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>

		<script>
		document.querySelectorAll('.card').forEach(function(card) {
			var checks = card.querySelectorAll('.ho-check');
			var btn    = card.querySelector('.ho-confirm-btn');
			if (!btn) return;

			checks.forEach(function(c) {
				c.addEventListener('change', function() {
					var allChecked = Array.from(checks).every(function(x){ return x.checked; });
					btn.disabled = !allChecked;
				});
			});

			btn.addEventListener('click', function() {
				if (!confirm('Xác nhận bàn giao và THU HỒI QUYỀN của Sub-Admin này?')) return;
				var uid   = this.dataset.uid;
				var nonce = this.dataset.nonce;
				btn.textContent = '⏳ Đang xử lý...';
				btn.disabled    = true;
				fetch(ajaxurl, {
					method: 'POST',
					headers: {'Content-Type': 'application/x-www-form-urlencoded'},
					body: new URLSearchParams({action:'pf_confirm_handover', user_id:uid, _ajax_nonce:nonce})
				}).then(r=>r.json()).then(function(d){
					if (d.success) { alert('✅ '+d.data.message); location.reload(); }
					else alert('❌ '+(d.data && d.data.message ? d.data.message : 'Lỗi'));
				});
			});
		});
		</script>
		<?php
	}

	/* ── Ads settings ── */

	public static function render_ads_settings_page(): void {
		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && check_admin_referer( 'pf_ads_save' ) ) {
			update_option( 'pf_ads_enabled', isset( $_POST['ads_enabled'] ) );
			update_option( 'pf_ads_slot_header', isset( $_POST['slot_header'] ) );
			update_option( 'pf_ads_slot_sidebar', isset( $_POST['slot_sidebar'] ) );
			update_option( 'pf_ads_slot_in_content', isset( $_POST['slot_in_content'] ) );
			update_option( 'pf_ads_slot_affiliate', isset( $_POST['slot_affiliate'] ) );
			update_option( 'pf_ads_slot_mobile_sticky', isset( $_POST['slot_mobile'] ) );

			update_option( 'pf_adsense_client', sanitize_text_field( wp_unslash( $_POST['adsense_client'] ?? '' ) ) );
			update_option( 'pf_adsense_slot_header', sanitize_text_field( wp_unslash( $_POST['slot_id_header'] ?? '' ) ) );
			update_option( 'pf_adsense_slot_sidebar', sanitize_text_field( wp_unslash( $_POST['slot_id_sidebar'] ?? '' ) ) );
			update_option( 'pf_adsense_slot_in_content', sanitize_text_field( wp_unslash( $_POST['slot_id_in_content'] ?? '' ) ) );
			update_option( 'pf_adsense_slot_mobile', sanitize_text_field( wp_unslash( $_POST['slot_id_mobile'] ?? '' ) ) );

			delete_option( 'pf_ads_forum_section_map' );

			echo '<div class="notice notice-success"><p>✅ Đã lưu cấu hình quảng cáo.</p></div>';
		}

		$enabled     = get_option( 'pf_ads_enabled', false );
		$s_header    = get_option( 'pf_ads_slot_header', false );
		$s_sidebar   = get_option( 'pf_ads_slot_sidebar', false );
		$s_content   = get_option( 'pf_ads_slot_in_content', false );
		$s_affiliate = get_option( 'pf_ads_slot_affiliate', false );
		$s_mobile    = get_option( 'pf_ads_slot_mobile_sticky', false );

		$client      = get_option( 'pf_adsense_client', PF_Ads::ADSENSE_CLIENT );
		$id_header   = get_option( 'pf_adsense_slot_header', PF_Ads::SLOT_HEADER );
		$id_sidebar  = get_option( 'pf_adsense_slot_sidebar', PF_Ads::SLOT_SIDEBAR );
		$id_content  = get_option( 'pf_adsense_slot_in_content', PF_Ads::SLOT_IN_CONTENT );
		$id_mobile   = get_option( 'pf_adsense_slot_mobile', PF_Ads::SLOT_MOBILE );

		$forum_map = get_option( 'pf_ads_forum_section_map', [] );
		?>
		<div class="wrap" style="max-width:760px">
			<h1>📢 Cấu hình Quảng cáo</h1>

			<div style="background:#fff3cd;border-left:4px solid #f59e0b;padding:12px 16px;margin:16px 0;border-radius:6px">
				⚠️ Trước khi bật AdSense: đăng ký tại
				<a href="https://adsense.google.com" target="_blank" rel="noopener">adsense.google.com</a>,
				điền Publisher ID và Slot IDs bên dưới (hoặc trong <code>class-pf-ads.php</code>).
			</div>

			<form method="post">
				<?php wp_nonce_field( 'pf_ads_save' ); ?>
				<table class="form-table">
					<tr>
						<th>Bật toàn bộ quảng cáo</th>
						<td><label><input type="checkbox" name="ads_enabled" value="1" <?php checked( $enabled ); ?>> Bật</label></td>
					</tr>
					<tr>
						<th>AdSense Publisher ID</th>
						<td>
							<input type="text" name="adsense_client" value="<?php echo esc_attr( $client ); ?>" class="regular-text" placeholder="ca-pub-XXXXXXXXXX">
							<p class="description">Publisher ID từ AdSense → Account → Account information</p>
						</td>
					</tr>
					<tr><th colspan="2" style="background:#f8fafc;padding:10px 14px;font-size:12px;color:#64748b">— Vị trí AdSense —</th></tr>
					<tr>
						<th>Header Banner (728×90)</th>
						<td>
							<label><input type="checkbox" name="slot_header" value="1" <?php checked( $s_header ); ?>> Bật</label>
							<input type="text" name="slot_id_header" value="<?php echo esc_attr( $id_header ); ?>" class="regular-text" placeholder="Slot ID" style="margin-top:6px">
							<p class="description">Hiển thị bên dưới navigation trên toàn site</p>
						</td>
					</tr>
					<tr>
						<th>Sidebar (300×250)</th>
						<td>
							<label><input type="checkbox" name="slot_sidebar" value="1" <?php checked( $s_sidebar ); ?>> Bật</label>
							<input type="text" name="slot_id_sidebar" value="<?php echo esc_attr( $id_sidebar ); ?>" class="regular-text" placeholder="Slot ID" style="margin-top:6px">
							<p class="description">Sidebar homepage + widget area <code>pf-sidebar-ads</code></p>
						</td>
					</tr>
					<tr>
						<th>In-content (336×280)</th>
						<td>
							<label><input type="checkbox" name="slot_in_content" value="1" <?php checked( $s_content ); ?>> Bật</label>
							<input type="text" name="slot_id_in_content" value="<?php echo esc_attr( $id_content ); ?>" class="regular-text" placeholder="Slot ID" style="margin-top:6px">
							<p class="description">Chèn sau đoạn 2 trong WordPress posts / pet_news</p>
						</td>
					</tr>
					<tr>
						<th>Mobile Sticky (320×50)</th>
						<td>
							<label><input type="checkbox" name="slot_mobile" value="1" <?php checked( $s_mobile ); ?>> Bật</label>
							<input type="text" name="slot_id_mobile" value="<?php echo esc_attr( $id_mobile ); ?>" class="regular-text" placeholder="Slot ID (để trống = dùng Header slot)" style="margin-top:6px">
							<p class="description">Banner dính ở footer trên điện thoại</p>
						</td>
					</tr>
					<tr><th colspan="2" style="background:#f8fafc;padding:10px 14px;font-size:12px;color:#64748b">— Vị trí Affiliate —</th></tr>
					<tr>
						<th>Affiliate (forum + sidebar)</th>
						<td>
							<label><input type="checkbox" name="slot_affiliate" value="1" <?php checked( $s_affiliate ); ?>> Bật</label>
							<p class="description">3 sản phẩm sau bài đầu topic + 2 sản phẩm trong sidebar</p>
						</td>
					</tr>
				</table>
				<p><button type="submit" class="button button-primary">💾 Lưu cấu hình</button></p>
			</form>

			<?php if ( ! empty( $forum_map ) ) : ?>
			<hr style="margin:32px 0">
			<h3>🗺 Forum → Section map (tự động)</h3>
			<table class="widefat striped" style="max-width:400px">
				<thead><tr><th>Forum ID</th><th>Section</th></tr></thead>
				<tbody>
				<?php foreach ( $forum_map as $fid => $sec ) : ?>
				<tr><td><?php echo (int) $fid; ?></td><td><?php echo esc_html( $sec ); ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>

			<hr style="margin:32px 0">
			<h3>📊 Hướng dẫn lấy Slot ID từ Google AdSense</h3>
			<ol style="line-height:2">
				<li>Đăng nhập <a href="https://adsense.google.com" target="_blank" rel="noopener">adsense.google.com</a></li>
				<li>Ads → By ad unit → <strong>Create new ad unit</strong></li>
				<li>Chọn loại: Display ads (Header/Sidebar) hoặc In-article ads (In-content)</li>
				<li>Sao chép <code>data-ad-slot="XXXXXXXXXX"</code></li>
				<li>Dán vào form trên hoặc constants trong <code>class-pf-ads.php</code></li>
			</ol>

			<h3>🛒 Hướng dẫn Affiliate Shopee</h3>
			<ol style="line-height:2">
				<li>Đăng ký <a href="https://affiliate.shopee.vn" target="_blank" rel="noopener">affiliate.shopee.vn</a></li>
				<li>Tìm sản phẩm → Generate Link</li>
				<li>Cập nhật mảng <code>get_affiliate_products()</code> trong <code>class-pf-ads.php</code></li>
			</ol>
		</div>
		<?php
	}
}
