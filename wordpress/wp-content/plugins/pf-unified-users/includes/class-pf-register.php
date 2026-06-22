<?php
defined( 'ABSPATH' ) || exit;

class PF_Register_V2 {

	private static $registering_vet = false;

	public static function init() {
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );

		add_shortcode( 'pf_register', [ __CLASS__, 'render_split_register' ] );
		add_shortcode( 'pf_split_register', [ __CLASS__, 'render_split_register' ] );
		add_shortcode( 'pf_register_form', [ __CLASS__, 'render_split_register' ] );

		add_action( 'wp_ajax_nopriv_pf_register_member', [ __CLASS__, 'ajax_register_member' ] );
		add_action( 'wp_ajax_nopriv_pf_register_vet', [ __CLASS__, 'ajax_register_vet' ] );
		add_action( 'wp_ajax_nopriv_pf_resend_verify', [ __CLASS__, 'ajax_resend_verify' ] );
		add_action( 'wp_ajax_pf_resend_verify', [ __CLASS__, 'ajax_resend_verify' ] );
		add_action( 'wp_ajax_pf_dismiss_welcome', [ __CLASS__, 'dismiss_welcome_popup' ] );
		add_action( 'wp_ajax_pf_dismiss_popup', [ __CLASS__, 'dismiss_welcome_popup' ] );

		add_action( 'user_register', [ __CLASS__, 'on_user_register' ], 5 );
		add_action( 'init', [ __CLASS__, 'handle_email_verify' ] );

		add_filter( 'authenticate', [ __CLASS__, 'gate_login' ], 25, 3 );
		add_filter( 'authenticate', [ __CLASS__, 'block_pending_vet_login' ], 30, 3 );
		add_action( 'wp_login', [ __CLASS__, 'check_email_verified' ], 10, 2 );

		add_action( 'wp_footer', [ __CLASS__, 'render_welcome_popup' ] );
		add_filter( 'login_redirect', [ __CLASS__, 'custom_login_redirect' ], 10, 3 );

		add_action( 'admin_post_pf_view_cert', [ __CLASS__, 'serve_certificate' ] );

		add_action( 'nsl_register_new_user', [ __CLASS__, 'mark_social_user_verified' ], 10, 1 );
		add_action( 'nsl_login_success', [ __CLASS__, 'mark_social_user_verified_on_login' ], 10, 1 );
	}

	public static function is_registering_vet() {
		return self::$registering_vet;
	}

	public static function enqueue_assets() {
		if ( ! self::is_register_page() ) {
			return;
		}

		wp_enqueue_style( 'pf-register', PFU_URL . 'assets/css/pf-register.css', [], PFU_VERSION );
		wp_enqueue_style( 'pf-welcome-popup', PFU_URL . 'assets/css/pf-welcome-popup.css', [], PFU_VERSION );

		wp_enqueue_script( 'pf-register', PFU_URL . 'assets/js/pf-register.js', [ 'jquery' ], PFU_VERSION, true );
		wp_enqueue_script( 'pf-welcome-popup', PFU_URL . 'assets/js/pf-welcome-popup.js', [ 'jquery' ], PFU_VERSION, true );

		wp_localize_script(
			'pf-register',
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

		wp_localize_script(
			'pf-welcome-popup',
			'pfPopupData',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'pf_dismiss_popup' ),
			]
		);
	}

	private static function is_register_page() {
		if ( is_page( 'register' ) ) {
			return true;
		}

		global $post;

		if ( ! $post ) {
			return false;
		}

		foreach ( [ 'pf_register', 'pf_split_register', 'pf_register_form' ] as $shortcode ) {
			if ( has_shortcode( $post->post_content, $shortcode ) ) {
				return true;
			}
		}

		return false;
	}

	public static function render_split_register() {
		if ( is_user_logged_in() ) {
			return '<div class="pf-split-register"><p>Bạn đã đăng nhập. <a href="' . esc_url( home_url( '/' ) ) . '">Về trang chủ →</a></p></div>';
		}

		ob_start();
		include PFU_DIR . 'templates/split-register.php';

		return ob_get_clean();
	}

	/* ── Registration AJAX ── */

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

		if ( PF_AntiSpam_V2::is_disposable_email( $email ) ) {
			wp_send_json_error( [ 'message' => 'Email tạm thời không được chấp nhận.' ] );
		}

		if ( empty( $username ) ) {
			$username = self::generate_username( $email );
		}

		$user_id = wp_create_user( $username, $password, $email );
		if ( is_wp_error( $user_id ) ) {
			wp_send_json_error( [ 'message' => $user_id->get_error_message() ] );
		}

		wp_update_user( [
			'ID'           => $user_id,
			'display_name' => $username,
		] );

		update_user_meta( $user_id, PF_Constants::META_USER_TYPE, PF_Constants::TYPE_MEMBER );
		update_user_meta( $user_id, PF_Constants::META_PREFERRED_LANG, $lang );
		update_user_meta( $user_id, PF_Constants::META_COUNTRY, $country );
		update_user_meta( $user_id, PF_Constants::META_PET_TYPES, $pets );
		update_user_meta( $user_id, PF_Constants::META_ACCOUNT_ACTIVE, '1' );

		if ( function_exists( 'WPF' ) ) {
			PF_User_Meta_V2::sync_usermeta_to_wpforo( $user_id );
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

		update_user_meta( $user_id, PF_Constants::META_USER_TYPE, PF_Constants::TYPE_VET_PENDING );
		update_user_meta( $user_id, PF_Constants::META_PREFERRED_LANG, $lang );
		update_user_meta( $user_id, PF_Constants::META_PHONE, $phone );
		update_user_meta( $user_id, PF_Constants::META_WORKPLACE, $workplace );
		update_user_meta( $user_id, PF_Constants::META_SPECIALTY, $specialty );
		update_user_meta( $user_id, PF_Constants::META_CERT_FILE, $file_result['url'] );
		update_user_meta( $user_id, PF_Constants::META_CERT_PATH, $file_result['path'] );
		update_user_meta( $user_id, PF_Constants::META_VET_STATUS, 'pending' );
		update_user_meta( $user_id, PF_Constants::META_EMAIL_VERIFIED, '1' );
		update_user_meta( $user_id, PF_Constants::META_ACCOUNT_ACTIVE, '0' );

		self::notify_admin_new_vet( $user_id, $email, $workplace, $specialty );
		PF_Roles_V2::send_vet_pending_email( $user_id );

		wp_send_json_success( [
			'message' => 'pending',
			'type'    => 'vet',
		] );
	}

	public static function handle_certificate_upload( $email_hint = '' ) {
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

	public static function serve_certificate() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		$user_id = (int) ( $_GET['user_id'] ?? 0 );
		check_admin_referer( 'pf_view_cert_' . $user_id );

		$path = get_user_meta( $user_id, PF_Constants::META_CERT_PATH, true );
		if ( ! $path || ! file_exists( $path ) ) {
			wp_die( 'File not found.' );
		}

		$mime = wp_check_filetype( $path )['type'] ?: 'application/octet-stream';
		header( 'Content-Type: ' . $mime );
		header( 'Content-Disposition: inline; filename="' . basename( $path ) . '"' );
		readfile( $path );
		exit;
	}

	/* ── Email verification ── */

	public static function on_user_register( $user_id ) {
		if ( self::is_registering_vet() ) {
			update_user_meta( $user_id, PF_Constants::META_EMAIL_VERIFIED, '1' );
			return;
		}

		if ( ! get_user_meta( $user_id, PF_Constants::META_EMAIL_VERIFIED, true ) ) {
			update_user_meta( $user_id, PF_Constants::META_EMAIL_VERIFIED, '0' );
		}
		if ( ! get_user_meta( $user_id, PF_Constants::META_REGISTERED_AT, true ) ) {
			update_user_meta( $user_id, PF_Constants::META_REGISTERED_AT, current_time( 'mysql' ) );
		}

		self::send_verification_email( $user_id );
	}

	public static function send_verification_email( $user_id ) {
		if ( get_user_meta( $user_id, PF_Constants::META_SOCIAL_LOGIN, true ) === '1' ) {
			return;
		}
		if ( get_user_meta( $user_id, PF_Constants::META_EMAIL_VERIFIED, true ) === '1' ) {
			return;
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$token = wp_generate_password( 32, false );
		update_user_meta( $user_id, PF_Constants::META_VERIFY_TOKEN, $token );
		update_user_meta( $user_id, PF_Constants::META_VERIFY_EXPIRY, time() + DAY_IN_SECONDS );

		$verify_url = add_query_arg(
			[
				'pf_verify' => $token,
				'uid'       => $user_id,
			],
			home_url( '/' )
		);

		$lang      = get_user_meta( $user_id, PF_Constants::META_PREFERRED_LANG, true ) ?: 'vi';
		$site_name = get_bloginfo( 'name' );
		$subject   = $lang === 'vi'
			? "[{$site_name}] Xác thực tài khoản của bạn"
			: "[{$site_name}] Verify your account";

		$message = self::load_email_template(
			$lang === 'vi' ? 'verify-vi' : 'verify-en',
			[
				'name'       => $user->display_name,
				'verify_url' => $verify_url,
				'site_name'  => $site_name,
			],
			$lang === 'vi'
				? self::email_template_vi( $user->display_name, $verify_url, $site_name )
				: self::email_template_en( $user->display_name, $verify_url, $site_name )
		);

		wp_mail( $user->user_email, $subject, $message, [ 'Content-Type: text/html; charset=UTF-8' ] );
	}

	public static function ajax_resend_verify() {
		check_ajax_referer( 'pf_split_register', 'nonce' );

		$uid   = (int) ( $_POST['uid'] ?? 0 );
		$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );

		$user = $uid ? get_userdata( $uid ) : get_user_by( 'email', $email );
		if ( ! $user ) {
			wp_send_json_error( [ 'message' => 'Không tìm thấy tài khoản.' ] );
		}

		if ( get_user_meta( $user->ID, PF_Constants::META_EMAIL_VERIFIED, true ) === '1' ) {
			wp_send_json_error( [ 'message' => 'Email này đã được xác thực rồi.' ] );
		}

		$last_sent = (int) get_user_meta( $user->ID, PF_Constants::META_VERIFY_LAST_SENT, true );
		if ( $last_sent && ( time() - $last_sent ) < 120 ) {
			wp_send_json_error( [ 'message' => 'Vui lòng chờ 2 phút trước khi gửi lại.' ] );
		}

		update_user_meta( $user->ID, PF_Constants::META_VERIFY_LAST_SENT, time() );
		self::send_verification_email( $user->ID );

		wp_send_json_success( [ 'message' => 'Đã gửi lại email xác thực!' ] );
	}

	public static function handle_email_verify() {
		if ( empty( $_GET['pf_verify'] ) || empty( $_GET['uid'] ) ) {
			return;
		}

		$token   = sanitize_text_field( wp_unslash( $_GET['pf_verify'] ) );
		$user_id = (int) $_GET['uid'];
		$stored  = get_user_meta( $user_id, PF_Constants::META_VERIFY_TOKEN, true );
		$expiry  = (int) get_user_meta( $user_id, PF_Constants::META_VERIFY_EXPIRY, true );

		if ( $token !== $stored || time() > $expiry ) {
			wp_die(
				'Link xác thực không hợp lệ hoặc đã hết hạn. <a href="' . esc_url( home_url( '/register/' ) ) . '">Đăng ký lại</a>'
			);
		}

		update_user_meta( $user_id, PF_Constants::META_EMAIL_VERIFIED, '1' );
		delete_user_meta( $user_id, PF_Constants::META_VERIFY_TOKEN );
		delete_user_meta( $user_id, PF_Constants::META_VERIFY_EXPIRY );

		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true );

		wp_safe_redirect( add_query_arg( 'pf_welcome', '1', home_url( '/' ) ) );
		exit;
	}

	/* ── Login gates ── */

	public static function gate_login( $user, $username, $password ) {
		unset( $username, $password );

		if ( ! ( $user instanceof WP_User ) ) {
			return $user;
		}

		if ( user_can( $user, 'manage_options' ) ) {
			return $user;
		}

		if ( get_user_meta( $user->ID, PF_Constants::META_USER_TYPE, true ) === PF_Constants::TYPE_VET_PENDING ) {
			return $user;
		}

		if ( get_user_meta( $user->ID, PF_Constants::META_SOCIAL_LOGIN, true ) === '1' ) {
			return $user;
		}

		$verified = get_user_meta( $user->ID, PF_Constants::META_EMAIL_VERIFIED, true );
		if ( $verified === '0' && get_option( 'pf_email_verify_required', 1 ) ) {
			return new WP_Error(
				'pf_email_unverified',
				'Email chưa được xác thực. Vui lòng kiểm tra hộp thư hoặc <a href="' . esc_url( home_url( '/register/' ) ) . '">đăng ký lại</a>.',
				[ 'status' => 403 ]
			);
		}

		return $user;
	}

	public static function block_pending_vet_login( $user, $username, $password ) {
		unset( $username, $password );

		if ( $user instanceof WP_User ) {
			$type   = get_user_meta( $user->ID, PF_Constants::META_USER_TYPE, true );
			$active = get_user_meta( $user->ID, PF_Constants::META_ACCOUNT_ACTIVE, true );
			if ( $type === PF_Constants::TYPE_VET_PENDING && $active === '0' ) {
				return new WP_Error(
					'pf_vet_pending',
					'Hồ sơ Bác sĩ của bạn đang chờ duyệt. Vui lòng kiểm tra email.',
					[ 'status' => 403 ]
				);
			}
		}

		return $user;
	}

	public static function check_email_verified( $user_login, $user ) {
		unset( $user_login );

		if ( user_can( $user, 'manage_options' ) ) {
			return;
		}
		if ( get_user_meta( $user->ID, PF_Constants::META_USER_TYPE, true ) === PF_Constants::TYPE_VET_PENDING ) {
			return;
		}
		if ( get_user_meta( $user->ID, PF_Constants::META_SOCIAL_LOGIN, true ) === '1' ) {
			return;
		}

		$verified = get_user_meta( $user->ID, PF_Constants::META_EMAIL_VERIFIED, true );
		if ( $verified === '0' && get_option( 'pf_email_verify_required', 1 ) ) {
			wp_logout();
			wp_safe_redirect( add_query_arg(
				[
					'pf_error' => 'unverified',
					'pf_uid'   => $user->ID,
				],
				home_url( '/register/' )
			) );
			exit;
		}
	}

	public static function custom_login_redirect( $redirect_to, $request, $user ) {
		if ( is_wp_error( $user ) || ! isset( $user->roles ) ) {
			return $redirect_to;
		}
		if ( ! in_array( 'administrator', $user->roles, true ) ) {
			return home_url( '/' );
		}

		return $redirect_to;
	}

	/* ── Social login ── */

	public static function mark_social_user_verified( $user_id ) {
		update_user_meta( $user_id, PF_Constants::META_EMAIL_VERIFIED, '1' );
		update_user_meta( $user_id, PF_Constants::META_SOCIAL_LOGIN, '1' );
	}

	public static function mark_social_user_verified_on_login( $user_id ) {
		if ( $user_id ) {
			update_user_meta( $user_id, PF_Constants::META_EMAIL_VERIFIED, '1' );
		}
	}

	/* ── Welcome popup ── */

	public static function render_welcome_popup() {
		if ( ! is_user_logged_in() || empty( $_GET['pf_welcome'] ) ) {
			return;
		}

		$user = wp_get_current_user();
		$lang = get_user_meta( $user->ID, PF_Constants::META_PREFERRED_LANG, true ) ?: 'vi';
		include PFU_DIR . 'templates/welcome-popup.php';
	}

	public static function dismiss_welcome_popup() {
		check_ajax_referer( 'pf_dismiss_popup', 'nonce' );
		$user_id = get_current_user_id();
		if ( $user_id ) {
			update_user_meta( $user_id, PF_Constants::META_WELCOME_SHOWN, '1' );
		}
		wp_send_json_success();
	}

	/* ── Helpers ── */

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
		$review_url  = admin_url( 'admin.php?page=pfu-vet-approval' );
		$user        = get_userdata( $user_id );

		$subject = "[{$site_name}] 🩺 Yêu cầu xác minh Bác sĩ Thú y mới";
		$message = self::load_email_template(
			'admin-new-vet',
			[
				'display_name' => $user->display_name,
				'email'        => $email,
				'workplace'    => $workplace,
				'specialty'    => $specialty ?: '—',
				'review_url'   => $review_url,
				'site_name'    => $site_name,
			],
			"<div style='font-family:sans-serif;max-width:560px;margin:0 auto;padding:24px;background:#fff;border:1px solid #e2e8f0;border-radius:12px'>
			  <h2 style='color:#0369a1;margin:0 0 16px'>🩺 Yêu cầu Xác minh Bác sĩ Thú y</h2>
			  <p><strong>{$user->display_name}</strong> — {$email}</p>
			  <p>🏥 {$workplace}<br>🔬 " . ( $specialty ?: '—' ) . "</p>
			  <p><a href='{$review_url}'>Xem xét trong Admin →</a></p>
			</div>"
		);

		wp_mail( $admin_email, $subject, $message, [ 'Content-Type: text/html; charset=UTF-8' ] );
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

	private static function email_template_vi( $name, $url, $site ) {
		return "
		<div style='font-family:sans-serif;max-width:560px;margin:0 auto;padding:32px 24px;background:#fff;border-radius:12px;border:1px solid #e2e8f0'>
		  <div style='text-align:center;margin-bottom:24px'>
		    <h2 style='color:#f97316;margin:0'>🐾 {$site}</h2>
		  </div>
		  <p style='color:#1e293b;font-size:16px'>Xin chào <strong>{$name}</strong>,</p>
		  <p style='color:#475569;line-height:1.7'>
		    Cảm ơn bạn đã tham gia cộng đồng thú cưng! Vui lòng nhấn nút bên dưới để xác thực địa chỉ email và kích hoạt tài khoản.
		  </p>
		  <div style='text-align:center;margin:32px 0'>
		    <a href='{$url}' style='background:#f97316;color:#fff;padding:14px 32px;border-radius:25px;text-decoration:none;font-weight:700;font-size:16px'>
		      ✅ Xác thực tài khoản
		    </a>
		  </div>
		  <p style='color:#94a3b8;font-size:13px'>Link có hiệu lực trong 24 giờ. Nếu bạn không đăng ký, hãy bỏ qua email này.</p>
		</div>";
	}

	private static function email_template_en( $name, $url, $site ) {
		return "
		<div style='font-family:sans-serif;max-width:560px;margin:0 auto;padding:32px 24px;background:#fff;border-radius:12px;border:1px solid #e2e8f0'>
		  <div style='text-align:center;margin-bottom:24px'>
		    <h2 style='color:#f97316;margin:0'>🐾 {$site}</h2>
		  </div>
		  <p style='color:#1e293b;font-size:16px'>Hello <strong>{$name}</strong>,</p>
		  <p style='color:#475569;line-height:1.7'>
		    Thank you for joining our pet community! Please click the button below to verify your email address and activate your account.
		  </p>
		  <div style='text-align:center;margin:32px 0'>
		    <a href='{$url}' style='background:#f97316;color:#fff;padding:14px 32px;border-radius:25px;text-decoration:none;font-weight:700;font-size:16px'>
		      ✅ Verify my account
		    </a>
		  </div>
		  <p style='color:#94a3b8;font-size:13px'>Link expires in 24 hours. If you did not sign up, please ignore this email.</p>
		</div>";
	}
}

function pf_google_login_url() {
	if ( ! class_exists( 'NextendSocialLogin' ) ) {
		return '#install-nextend-social-login';
	}

	$provider = NextendSocialLogin::getProviderByProviderID( 'google' );
	if ( $provider ) {
		return $provider->getLoginUrl();
	}

	$settings = (array) maybe_unserialize( get_option( 'nsl_google' ) );
	if ( ! empty( $settings['client_id'] ) ) {
		return add_query_arg( 'loginSocial', 'google', NextendSocialLogin::getLoginUrl() );
	}

	return '#google-not-configured';
}
