<?php
defined( 'ABSPATH' ) || exit;

class PF_Onboarding {

	public static function init() {
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );

		add_action( 'user_register', [ __CLASS__, 'on_user_register' ], 5 );
		add_action( 'init', [ __CLASS__, 'handle_email_verify' ] );
		add_action( 'wp_login', [ __CLASS__, 'check_email_verified' ], 10, 2 );

		add_action( 'wp_footer', [ __CLASS__, 'render_welcome_popup' ] );
		add_action( 'wp_ajax_pf_dismiss_popup', [ __CLASS__, 'dismiss_welcome_popup' ] );

		add_shortcode( 'pf_register_form', [ __CLASS__, 'render_register_form' ] );

		add_filter( 'login_redirect', [ __CLASS__, 'custom_login_redirect' ], 10, 3 );

		add_action( 'wp_ajax_nopriv_pf_register', [ __CLASS__, 'ajax_register' ] );
		add_action( 'wp_ajax_nopriv_pf_resend_verify', [ __CLASS__, 'ajax_resend_verify' ] );
		add_action( 'wp_ajax_pf_resend_verify', [ __CLASS__, 'ajax_resend_verify' ] );

		// Social login users: email verified by provider.
		add_action( 'nsl_register_new_user', [ __CLASS__, 'mark_social_user_verified' ], 10, 1 );
		add_action( 'nsl_login_success', [ __CLASS__, 'mark_social_user_verified_on_login' ], 10, 1 );
	}

	public static function enqueue_assets() {
		wp_enqueue_style(
			'pf-register',
			PF_SHIELD_URL . 'assets/css/pf-register.css',
			[],
			PF_SHIELD_VER
		);
		wp_enqueue_style(
			'pf-welcome-popup',
			PF_SHIELD_URL . 'assets/css/pf-welcome-popup.css',
			[],
			PF_SHIELD_VER
		);

		wp_enqueue_script(
			'pf-register',
			PF_SHIELD_URL . 'assets/js/pf-register.js',
			[ 'jquery' ],
			PF_SHIELD_VER,
			true
		);
		wp_enqueue_script(
			'pf-welcome-popup',
			PF_SHIELD_URL . 'assets/js/pf-welcome-popup.js',
			[ 'jquery' ],
			PF_SHIELD_VER,
			true
		);

		$localize = [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'pf_register_nonce' ),
			'homeUrl' => home_url( '/' ),
			'strings' => [
				'sending'      => __( 'Đang xử lý...', 'pf' ),
				'success'      => __( 'Đăng ký thành công! Kiểm tra email để xác thực.', 'pf' ),
				'error_email'  => __( 'Email không hợp lệ.', 'pf' ),
				'error_pass'   => __( 'Mật khẩu tối thiểu 8 ký tự.', 'pf' ),
				'error_exists' => __( 'Email này đã được đăng ký.', 'pf' ),
			],
		];

		wp_localize_script( 'pf-register', 'pfRegData', $localize );
		wp_localize_script( 'pf-welcome-popup', 'pfPopupData', [
			'ajaxUrl' => $localize['ajaxUrl'],
			'nonce'   => wp_create_nonce( 'pf_dismiss_popup' ),
		] );
	}

	public static function on_user_register( $user_id ) {
		if ( class_exists( 'PF_Split_Register' ) && PF_Split_Register::is_registering_vet() ) {
			update_user_meta( $user_id, 'pf_email_verified', '1' );
			return;
		}

		if ( ! get_user_meta( $user_id, 'pf_email_verified', true ) ) {
			update_user_meta( $user_id, 'pf_email_verified', '0' );
		}
		if ( ! get_user_meta( $user_id, 'pf_registered_at', true ) ) {
			update_user_meta( $user_id, 'pf_registered_at', current_time( 'mysql' ) );
		}
		self::send_verification_email( $user_id );
	}

	public static function mark_social_user_verified( $user_id ) {
		update_user_meta( $user_id, 'pf_email_verified', '1' );
		update_user_meta( $user_id, 'pf_social_login', '1' );
	}

	public static function mark_social_user_verified_on_login( $user_id ) {
		if ( $user_id ) {
			update_user_meta( $user_id, 'pf_email_verified', '1' );
		}
	}

	public static function render_register_form() {
		if ( is_user_logged_in() ) {
			return '<p>' . sprintf(
				__( 'Bạn đã đăng nhập. <a href="%s">Về trang chủ</a>', 'pf' ),
				esc_url( home_url( '/' ) )
			) . '</p>';
		}

		ob_start();
		include PF_SHIELD_DIR . 'templates/register-form.php';
		return ob_get_clean();
	}

	public static function ajax_register() {
		check_ajax_referer( 'pf_register_nonce', 'nonce' );

		$email    = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$password = wp_unslash( $_POST['password'] ?? '' );
		$username = sanitize_text_field( wp_unslash( $_POST['username'] ?? '' ) );
		$lang     = sanitize_text_field( wp_unslash( $_POST['lang'] ?? 'vi' ) );

		if ( ! is_email( $email ) ) {
			wp_send_json_error( [ 'message' => __( 'Email không hợp lệ.', 'pf' ) ] );
		}
		if ( strlen( $password ) < 8 ) {
			wp_send_json_error( [ 'message' => __( 'Mật khẩu tối thiểu 8 ký tự.', 'pf' ) ] );
		}
		if ( email_exists( $email ) ) {
			wp_send_json_error( [ 'message' => __( 'Email này đã được đăng ký.', 'pf' ) ] );
		}
		if ( PF_AntiSpam::is_disposable_email( $email ) ) {
			wp_send_json_error( [
				'message' => __( 'Email tạm thời không được chấp nhận. Vui lòng dùng Gmail, Yahoo, Outlook...', 'pf' ),
			] );
		}

		if ( empty( $username ) ) {
			$username = sanitize_user( current( explode( '@', $email ) ) );
			if ( username_exists( $username ) ) {
				$username .= '_' . wp_rand( 100, 999 );
			}
		}

		$user_id = wp_create_user( $username, $password, $email );
		if ( is_wp_error( $user_id ) ) {
			wp_send_json_error( [ 'message' => $user_id->get_error_message() ] );
		}

		update_user_meta( $user_id, 'pf_preferred_lang', $lang );

		wp_send_json_success( [
			'message' => sprintf(
				__( 'Đăng ký thành công! Vui lòng kiểm tra email %s để xác thực tài khoản.', 'pf' ),
				$email
			),
		] );
	}

	public static function ajax_resend_verify() {
		check_ajax_referer( 'pf_register_nonce', 'nonce' );

		$uid   = intval( $_POST['uid'] ?? 0 );
		$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );

		$user = $uid ? get_userdata( $uid ) : get_user_by( 'email', $email );
		if ( ! $user ) {
			wp_send_json_error( [ 'message' => __( 'Không tìm thấy tài khoản.', 'pf' ) ] );
		}

		if ( get_user_meta( $user->ID, 'pf_email_verified', true ) === '1' ) {
			wp_send_json_error( [ 'message' => __( 'Email này đã được xác thực rồi.', 'pf' ) ] );
		}

		$last_sent = (int) get_user_meta( $user->ID, 'pf_verify_last_sent', true );
		if ( $last_sent && ( time() - $last_sent ) < 120 ) {
			wp_send_json_error( [ 'message' => __( 'Vui lòng chờ 2 phút trước khi gửi lại.', 'pf' ) ] );
		}

		update_user_meta( $user->ID, 'pf_verify_last_sent', time() );
		self::send_verification_email( $user->ID );

		wp_send_json_success( [ 'message' => __( 'Đã gửi lại email xác thực!', 'pf' ) ] );
	}

	public static function send_verification_email( $user_id ) {
		if ( get_user_meta( $user_id, 'pf_social_login', true ) === '1' ) {
			return;
		}
		if ( get_user_meta( $user_id, 'pf_email_verified', true ) === '1' ) {
			return;
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$token = wp_generate_password( 32, false );
		update_user_meta( $user_id, 'pf_verify_token', $token );
		update_user_meta( $user_id, 'pf_verify_token_expiry', time() + DAY_IN_SECONDS );

		$verify_url = add_query_arg(
			[
				'pf_verify' => $token,
				'uid'       => $user_id,
			],
			home_url( '/' )
		);

		$lang      = get_user_meta( $user_id, 'pf_preferred_lang', true ) ?: 'vi';
		$site_name = get_bloginfo( 'name' );
		$subject   = $lang === 'vi'
			? "[{$site_name}] Xác thực tài khoản của bạn"
			: "[{$site_name}] Verify your account";

		$message = $lang === 'vi'
			? self::email_template_vi( $user->display_name, $verify_url, $site_name )
			: self::email_template_en( $user->display_name, $verify_url, $site_name );

		wp_mail( $user->user_email, $subject, $message, [ 'Content-Type: text/html; charset=UTF-8' ] );
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
		  <hr style='border:none;border-top:1px solid #e2e8f0;margin:24px 0'>
		  <p style='color:#94a3b8;font-size:12px;text-align:center'>© {$site} — Cộng đồng thú cưng</p>
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
		  <hr style='border:none;border-top:1px solid #e2e8f0;margin:24px 0'>
		  <p style='color:#94a3b8;font-size:12px;text-align:center'>© {$site} — Pet Community</p>
		</div>";
	}

	public static function handle_email_verify() {
		if ( empty( $_GET['pf_verify'] ) || empty( $_GET['uid'] ) ) {
			return;
		}

		$token   = sanitize_text_field( wp_unslash( $_GET['pf_verify'] ) );
		$user_id = intval( $_GET['uid'] );
		$stored  = get_user_meta( $user_id, 'pf_verify_token', true );
		$expiry  = (int) get_user_meta( $user_id, 'pf_verify_token_expiry', true );

		if ( $token !== $stored || time() > $expiry ) {
			wp_die(
				'Link xác thực không hợp lệ hoặc đã hết hạn. <a href="' . esc_url( home_url( '/register/' ) ) . '">Đăng ký lại</a>'
			);
		}

		update_user_meta( $user_id, 'pf_email_verified', '1' );
		delete_user_meta( $user_id, 'pf_verify_token' );
		delete_user_meta( $user_id, 'pf_verify_token_expiry' );

		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true );

		wp_safe_redirect( add_query_arg( 'pf_welcome', '1', home_url( '/' ) ) );
		exit;
	}

	public static function check_email_verified( $user_login, $user ) {
		if ( user_can( $user, 'manage_options' ) ) {
			return;
		}
		if ( get_user_meta( $user->ID, 'pf_user_type', true ) === 'vet_pending' ) {
			return;
		}
		if ( get_user_meta( $user->ID, 'pf_social_login', true ) === '1' ) {
			return;
		}

		$verified = get_user_meta( $user->ID, 'pf_email_verified', true );
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

	public static function render_welcome_popup() {
		if ( ! is_user_logged_in() || empty( $_GET['pf_welcome'] ) ) {
			return;
		}

		$user = wp_get_current_user();
		$lang = get_user_meta( $user->ID, 'pf_preferred_lang', true ) ?: 'vi';
		include PF_SHIELD_DIR . 'templates/welcome-popup.php';
	}

	public static function dismiss_welcome_popup() {
		check_ajax_referer( 'pf_dismiss_popup', 'nonce' );
		$user_id = get_current_user_id();
		if ( $user_id ) {
			update_user_meta( $user_id, 'pf_welcome_shown', '1' );
		}
		wp_send_json_success();
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
