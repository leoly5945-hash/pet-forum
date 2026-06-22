<?php
defined( 'ABSPATH' ) || exit;

class PF_Split_Register {

	const VET_ROLE = 'pf_verified_vet';

	private static $registering_vet = false;

	public static function init() {
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
		add_shortcode( 'pf_split_register', [ __CLASS__, 'render_page' ] );

		add_action( 'wp_ajax_nopriv_pf_register_member', [ __CLASS__, 'ajax_register_member' ] );
		add_action( 'wp_ajax_nopriv_pf_register_vet', [ __CLASS__, 'ajax_register_vet' ] );

		add_action( 'admin_menu', [ __CLASS__, 'register_admin_page' ] );
		add_action( 'admin_post_pf_approve_vet', [ __CLASS__, 'handle_approve_vet' ] );
		add_action( 'admin_post_pf_reject_vet', [ __CLASS__, 'handle_reject_vet' ] );
		add_action( 'admin_post_pf_view_cert', [ __CLASS__, 'serve_certificate' ] );

		add_action( 'init', [ __CLASS__, 'create_vet_role' ], 1 );
		add_action( 'init', [ __CLASS__, 'ensure_vet_usergroup' ], 20 );

		add_filter( 'wpforo_user_display_name', [ __CLASS__, 'add_verified_badge' ], 10, 2 );
		add_filter( 'authenticate', [ __CLASS__, 'block_pending_vet_login' ], 30, 3 );

		add_filter( 'manage_users_columns', [ __CLASS__, 'add_vet_column' ] );
		add_filter( 'manage_users_custom_column', [ __CLASS__, 'render_vet_column' ], 10, 3 );
	}

	public static function is_registering_vet() {
		return self::$registering_vet;
	}

	public static function get_vet_group_id() {
		$id = (int) get_option( 'pf_verified_vet_group_id', 0 );
		if ( $id ) {
			return $id;
		}

		return (int) apply_filters( 'pf_verified_vet_group_id', 0 );
	}

	public static function create_vet_role() {
		if ( ! get_role( self::VET_ROLE ) ) {
			add_role(
				self::VET_ROLE,
				'✔ Verified Vet',
				[
					'read'              => true,
					'edit_posts'        => true,
					'moderate_comments' => true,
					'pf_verified_vet'   => true,
				]
			);
		}
	}

	public static function ensure_vet_usergroup() {
		if ( ! function_exists( 'WPF' ) || self::get_vet_group_id() ) {
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

	public static function enqueue_assets() {
		if ( ! self::is_register_page() ) {
			return;
		}

		wp_enqueue_style(
			'pf-split-register',
			PF_SHIELD_URL . 'assets/css/pf-split-register.css',
			[],
			PF_SHIELD_VER
		);
		wp_enqueue_script(
			'pf-split-register',
			PF_SHIELD_URL . 'assets/js/pf-split-register.js',
			[ 'jquery' ],
			PF_SHIELD_VER,
			true
		);

		wp_localize_script(
			'pf-split-register',
			'pfRegSplit',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'pf_split_register' ),
				'strings' => [
					'uploading'      => '⏳ Đang tải lên...',
					'registering'    => '⏳ Đang đăng ký...',
					'file_too_large' => 'File quá lớn (tối đa 5MB).',
					'file_type'      => 'Chỉ chấp nhận PDF, JPG, PNG.',
					'required'       => 'Vui lòng điền đầy đủ thông tin bắt buộc.',
				],
			]
		);
	}

	private static function is_register_page() {
		if ( is_page( 'register' ) ) {
			return true;
		}
		global $post;

		return $post && has_shortcode( $post->post_content, 'pf_split_register' );
	}

	public static function render_page() {
		if ( is_user_logged_in() ) {
			return '<div class="pf-split-register"><p>Bạn đã đăng nhập. <a href="' . esc_url( home_url( '/' ) ) . '">Về trang chủ →</a></p></div>';
		}

		ob_start();
		include PF_SHIELD_DIR . 'templates/split-register-page.php';

		return ob_get_clean();
	}

	public static function ajax_register_member() {
		check_ajax_referer( 'pf_split_register', 'nonce' );

		$email    = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$password = wp_unslash( $_POST['password'] ?? '' );
		$username = sanitize_text_field( wp_unslash( $_POST['username'] ?? '' ) );
		$country  = sanitize_text_field( wp_unslash( $_POST['country'] ?? '' ) );
		$lang     = sanitize_text_field( wp_unslash( $_POST['lang'] ?? 'vi' ) );
		$pets     = array_map( 'sanitize_text_field', (array) ( $_POST['pet_types'] ?? [] ) );

		$errors = self::validate_base( $email, $password );
		if ( empty( $country ) ) {
			$errors[] = 'Quốc gia là bắt buộc.';
		}
		if ( ! empty( $errors ) ) {
			wp_send_json_error( [ 'message' => implode( ' ', $errors ) ] );
		}

		if ( class_exists( 'PF_AntiSpam' ) && PF_AntiSpam::is_disposable_email( $email ) ) {
			wp_send_json_error( [ 'message' => 'Email tạm thời không được chấp nhận.' ] );
		}

		if ( empty( $username ) ) {
			$username = self::generate_username( $email );
		}

		$user_id = wp_create_user( $username, $password, $email );
		if ( is_wp_error( $user_id ) ) {
			wp_send_json_error( [ 'message' => $user_id->get_error_message() ] );
		}

		if ( ! empty( $username ) ) {
			wp_update_user( [
				'ID'           => $user_id,
				'display_name' => $username,
			] );
		}

		update_user_meta( $user_id, 'pf_user_type', 'member' );
		update_user_meta( $user_id, 'pf_preferred_lang', $lang );
		update_user_meta( $user_id, 'pf_country', $country );
		update_user_meta( $user_id, 'pf_pet_types', $pets );
		update_user_meta( $user_id, 'pf_account_active', '1' );

		if ( function_exists( 'WPF' ) ) {
			PF_User_Meta::sync_usermeta_to_wpforo( $user_id );
		}

		wp_send_json_success( [
			'message' => '✅ Đăng ký thành công! Kiểm tra email <strong>' . esc_html( $email ) . '</strong> để xác thực tài khoản.',
			'type'    => 'member',
		] );
	}

	public static function ajax_register_vet() {
		check_ajax_referer( 'pf_split_register', 'nonce' );

		$email     = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$password  = wp_unslash( $_POST['password'] ?? '' );
		$username  = sanitize_text_field( wp_unslash( $_POST['username'] ?? '' ) );
		$phone     = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
		$workplace = sanitize_text_field( wp_unslash( $_POST['workplace'] ?? '' ) );
		$specialty = sanitize_text_field( wp_unslash( $_POST['specialty'] ?? '' ) );
		$lang      = sanitize_text_field( wp_unslash( $_POST['lang'] ?? 'vi' ) );

		$errors = self::validate_base( $email, $password );
		if ( empty( $phone ) ) {
			$errors[] = 'Số điện thoại là bắt buộc.';
		}
		if ( empty( $workplace ) ) {
			$errors[] = 'Nơi công tác là bắt buộc.';
		}
		if ( empty( $username ) ) {
			$errors[] = 'Tên hiển thị là bắt buộc.';
		}
		if ( ! empty( $errors ) ) {
			wp_send_json_error( [ 'message' => implode( ' ', $errors ) ] );
		}

		if ( empty( $_FILES['certificate']['name'] ) ) {
			wp_send_json_error( [ 'message' => 'Vui lòng tải lên bằng cấp / chứng chỉ.' ] );
		}

		$file_result = self::handle_certificate_upload( $email );
		if ( is_wp_error( $file_result ) ) {
			wp_send_json_error( [ 'message' => $file_result->get_error_message() ] );
		}

		self::$registering_vet = true;
		$user_id               = wp_create_user( self::generate_username( $email, 'dr' ), $password, $email );
		self::$registering_vet = false;

		if ( is_wp_error( $user_id ) ) {
			wp_send_json_error( [ 'message' => $user_id->get_error_message() ] );
		}

		wp_update_user( [
			'ID'           => $user_id,
			'display_name' => $username,
		] );

		update_user_meta( $user_id, 'pf_user_type', 'vet_pending' );
		update_user_meta( $user_id, 'pf_preferred_lang', $lang );
		update_user_meta( $user_id, 'pf_phone', $phone );
		update_user_meta( $user_id, 'pf_workplace', $workplace );
		update_user_meta( $user_id, 'pf_specialty', $specialty );
		update_user_meta( $user_id, 'pf_certificate_file', $file_result['url'] );
		update_user_meta( $user_id, 'pf_certificate_path', $file_result['path'] );
		update_user_meta( $user_id, 'pf_vet_status', 'pending' );
		update_user_meta( $user_id, 'pf_email_verified', '1' );
		update_user_meta( $user_id, 'pf_account_active', '0' );

		self::notify_admin_new_vet( $user_id, $email, $workplace, $specialty );
		self::send_vet_pending_email( $user_id );

		wp_send_json_success( [
			'message' => 'pending',
			'type'    => 'vet',
		] );
	}

	private static function handle_certificate_upload( $email_hint = '' ) {
		$file = $_FILES['certificate'];

		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		$mime  = finfo_file( $finfo, $file['tmp_name'] );
		finfo_close( $finfo );

		$allowed = [ 'image/jpeg', 'image/png', 'image/jpg', 'application/pdf' ];
		if ( ! in_array( $mime, $allowed, true ) ) {
			return new WP_Error( 'invalid_type', 'Chỉ chấp nhận PDF, JPG, PNG.' );
		}

		if ( (int) $file['size'] > 5 * 1024 * 1024 ) {
			return new WP_Error( 'too_large', 'File quá lớn. Tối đa 5MB.' );
		}

		$upload_dir = wp_upload_dir();
		$target_dir = $upload_dir['basedir'] . '/pf-certificates/';

		if ( ! file_exists( $target_dir ) ) {
			wp_mkdir_p( $target_dir );
			file_put_contents( $target_dir . '.htaccess', "deny from all\n" );
			file_put_contents( $target_dir . 'index.php', '<?php // Silence is golden' );
		}

		$ext      = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		$filename = 'cert_' . md5( $email_hint . time() . wp_rand( 1000, 9999 ) ) . '.' . $ext;
		$filepath = $target_dir . $filename;

		if ( ! move_uploaded_file( $file['tmp_name'], $filepath ) ) {
			return new WP_Error( 'upload_failed', 'Không thể tải file lên. Vui lòng thử lại.' );
		}

		return [
			'path' => $filepath,
			'url'  => $upload_dir['baseurl'] . '/pf-certificates/' . $filename,
			'name' => $filename,
		];
	}

	public static function block_pending_vet_login( $user, $username, $password ) {
		if ( $user instanceof WP_User ) {
			$type   = get_user_meta( $user->ID, 'pf_user_type', true );
			$active = get_user_meta( $user->ID, 'pf_account_active', true );
			if ( $type === 'vet_pending' && $active === '0' ) {
				return new WP_Error(
					'pf_vet_pending',
					'Hồ sơ Bác sĩ của bạn đang chờ duyệt. Vui lòng kiểm tra email.',
					[ 'status' => 403 ]
				);
			}
		}

		return $user;
	}

	public static function register_admin_page() {
		add_menu_page(
			'Duyệt Bác sĩ Thú y',
			'🩺 Duyệt Bác sĩ',
			'manage_options',
			'pf-vet-approval',
			[ __CLASS__, 'render_approval_page' ],
			'dashicons-shield',
			4
		);
	}

	public static function render_approval_page() {
		$status_filter = sanitize_text_field( wp_unslash( $_GET['vet_status'] ?? 'pending' ) );
		$pending_vets  = get_users( [
			'meta_key'   => 'pf_vet_status',
			'meta_value' => $status_filter,
			'number'     => 50,
		] );

		$msg = get_transient( 'pf_vet_msg' );
		if ( $msg ) {
			delete_transient( 'pf_vet_msg' );
		}
		include PF_SHIELD_DIR . 'templates/admin-vet-approval.php';
	}

	public static function serve_certificate() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		$user_id = (int) ( $_GET['user_id'] ?? 0 );
		check_admin_referer( 'pf_view_cert_' . $user_id );

		$path = get_user_meta( $user_id, 'pf_certificate_path', true );
		if ( ! $path || ! file_exists( $path ) ) {
			wp_die( 'File not found.' );
		}

		$mime = wp_check_filetype( $path )['type'] ?: 'application/octet-stream';
		header( 'Content-Type: ' . $mime );
		header( 'Content-Disposition: inline; filename="' . basename( $path ) . '"' );
		readfile( $path );
		exit;
	}

	public static function handle_approve_vet() {
		$user_id = (int) ( $_POST['user_id'] ?? 0 );
		check_admin_referer( 'pf_approve_vet_' . $user_id );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		$user = new WP_User( $user_id );
		$user->set_role( self::VET_ROLE );

		update_user_meta( $user_id, 'pf_vet_status', 'approved' );
		update_user_meta( $user_id, 'pf_user_type', 'verified_vet' );
		update_user_meta( $user_id, 'pf_account_active', '1' );
		update_user_meta( $user_id, 'pf_approved_at', current_time( 'mysql' ) );
		update_user_meta( $user_id, 'pf_approved_by', get_current_user_id() );

		$group_id = self::get_vet_group_id();
		if ( $group_id && function_exists( 'WPF' ) ) {
			WPF()->init();
			WPF()->member->update_profile_fields( $user_id, [ 'groupid' => $group_id ], false );
		}

		self::send_vet_approved_email( $user_id );

		set_transient( 'pf_vet_msg', "✅ Đã duyệt và xác minh bác sĩ: {$user->display_name}", 30 );
		wp_safe_redirect( admin_url( 'admin.php?page=pf-vet-approval&vet_status=approved' ) );
		exit;
	}

	public static function handle_reject_vet() {
		$user_id = (int) ( $_POST['user_id'] ?? 0 );
		check_admin_referer( 'pf_reject_vet_' . $user_id );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		update_user_meta( $user_id, 'pf_vet_status', 'rejected' );
		update_user_meta( $user_id, 'pf_account_active', '0' );
		update_user_meta( $user_id, 'pf_rejected_at', current_time( 'mysql' ) );

		self::send_vet_rejected_email( $user_id );

		$user = get_userdata( $user_id );
		set_transient( 'pf_vet_msg', "❌ Đã từ chối hồ sơ: {$user->display_name}", 30 );
		wp_safe_redirect( admin_url( 'admin.php?page=pf-vet-approval&vet_status=rejected' ) );
		exit;
	}

	public static function add_verified_badge( $display_name, $user ) {
		$user_id = is_object( $user ) ? (int) ( $user->ID ?? 0 ) : (int) $user;
		if ( ! $user_id ) {
			return $display_name;
		}

		if ( get_user_meta( $user_id, 'pf_user_type', true ) !== 'verified_vet' ) {
			return $display_name;
		}

		return $display_name . ' <span class="pf-verified-badge" title="Bác sĩ Thú y đã xác minh">✔</span>';
	}

	public static function add_vet_column( $columns ) {
		$columns['pf_vet_status'] = '🩺 Vet Status';

		return $columns;
	}

	public static function render_vet_column( $value, $column, $user_id ) {
		if ( $column !== 'pf_vet_status' ) {
			return $value;
		}

		$status = get_user_meta( $user_id, 'pf_vet_status', true );
		$map    = [
			'pending'  => '<span style="color:#d97706">⏳ Chờ duyệt</span>',
			'approved' => '<span style="color:#15803d">✔ Verified Vet</span>',
			'rejected' => '<span style="color:#dc2626">✗ Từ chối</span>',
		];

		return $map[ $status ] ?? '—';
	}

	private static function validate_base( $email, $password ) {
		$errors = [];
		if ( ! is_email( $email ) ) {
			$errors[] = 'Email không hợp lệ.';
		}
		if ( email_exists( $email ) ) {
			$errors[] = 'Email này đã được đăng ký.';
		}
		if ( strlen( (string) $password ) < 8 ) {
			$errors[] = 'Mật khẩu tối thiểu 8 ký tự.';
		}

		return $errors;
	}

	private static function generate_username( $email, $prefix = '' ) {
		$base = $prefix . sanitize_user( current( explode( '@', $email ) ), true );
		$name = $base ?: 'user';
		$i    = 1;
		while ( username_exists( $name ) ) {
			$name = $base . '_' . $i++;
		}

		return $name;
	}

	private static function notify_admin_new_vet( $user_id, $email, $workplace, $specialty ) {
		$admin_email = get_option( 'admin_email' );
		$site_name   = get_bloginfo( 'name' );
		$review_url  = admin_url( 'admin.php?page=pf-vet-approval' );
		$user        = get_userdata( $user_id );

		$subject = "[{$site_name}] 🩺 Yêu cầu xác minh Bác sĩ Thú y mới";
		$message = "
		<div style='font-family:sans-serif;max-width:560px;margin:0 auto;padding:24px;background:#fff;border:1px solid #e2e8f0;border-radius:12px'>
		  <h2 style='color:#0369a1;margin:0 0 16px'>🩺 Yêu cầu Xác minh Bác sĩ Thú y</h2>
		  <p><strong>{$user->display_name}</strong> — {$email}</p>
		  <p>🏥 {$workplace}<br>🔬 " . ( $specialty ?: '—' ) . "</p>
		  <p><a href='{$review_url}'>Xem xét trong Admin →</a></p>
		</div>";

		wp_mail( $admin_email, $subject, $message, [ 'Content-Type: text/html; charset=UTF-8' ] );
	}

	private static function send_vet_pending_email( $user_id ) {
		$user      = get_userdata( $user_id );
		$site_name = get_bloginfo( 'name' );
		$lang      = get_user_meta( $user_id, 'pf_preferred_lang', true ) ?: 'vi';

		if ( $lang === 'vi' ) {
			$subject = "[{$site_name}] Hồ sơ của bạn đang được xem xét";
			$message = "<p>Xin chào <strong>{$user->display_name}</strong>,</p><p>Cảm ơn bạn đã đăng ký làm Bác sĩ/Chuyên gia tại {$site_name}. Ban quản trị sẽ phản hồi trong <strong>24-48 giờ</strong>.</p>";
		} else {
			$subject = "[{$site_name}] Your application is under review";
			$message = "<p>Hello <strong>{$user->display_name}</strong>,</p><p>Thank you for applying. We'll notify you within <strong>24-48 hours</strong>.</p>";
		}

		wp_mail( $user->user_email, $subject, $message, [ 'Content-Type: text/html; charset=UTF-8' ] );
	}

	private static function send_vet_approved_email( $user_id ) {
		$user      = get_userdata( $user_id );
		$site_name = get_bloginfo( 'name' );
		$login_url = wp_login_url( home_url( '/community/' ) );
		$subject   = "[{$site_name}] 🎉 Hồ sơ Bác sĩ của bạn đã được xác minh!";
		$message   = "
		<div style='font-family:sans-serif;max-width:560px;margin:0 auto;padding:32px 24px'>
		  <h2 style='color:#15803d'>🎉 Chúc mừng! Hồ sơ đã được xác minh</h2>
		  <p>Xin chào <strong>{$user->display_name}</strong>,</p>
		  <p>Tài khoản Verified Vet của bạn đã được kích hoạt.</p>
		  <p><a href='{$login_url}'>Đăng nhập và bắt đầu →</a></p>
		</div>";

		wp_mail( $user->user_email, $subject, $message, [ 'Content-Type: text/html; charset=UTF-8' ] );
	}

	private static function send_vet_rejected_email( $user_id ) {
		$user      = get_userdata( $user_id );
		$site_name = get_bloginfo( 'name' );
		$subject   = "[{$site_name}] Thông báo về hồ sơ đăng ký Bác sĩ";
		$message   = "<p>Xin chào <strong>{$user->display_name}</strong>,</p><p>Rất tiếc chúng tôi chưa thể xác minh hồ sơ của bạn lúc này. Liên hệ: " . get_option( 'admin_email' ) . '</p>';

		wp_mail( $user->user_email, $subject, $message, [ 'Content-Type: text/html; charset=UTF-8' ] );
	}
}
