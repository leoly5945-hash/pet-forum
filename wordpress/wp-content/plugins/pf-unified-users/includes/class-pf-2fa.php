<?php
defined( 'ABSPATH' ) || exit;

class PF_TwoFactor {

	const OTP_LENGTH = 6;
	const OTP_EXPIRY = 600;
	const OTP_MAX_ATTEMPTS = 3;
	const OTP_LOCKOUT_SECONDS = 900;

	public static function init(): void {
		add_filter( 'authenticate', [ __CLASS__, 'intercept_login' ], 40, 3 );
		add_filter( 'login_errors', [ __CLASS__, 'style_otp_login_errors' ] );
		add_action( 'login_form', [ __CLASS__, 'maybe_show_otp_field' ] );
		add_action( 'wp_ajax_nopriv_pf_resend_otp', [ __CLASS__, 'ajax_resend_otp' ] );
		add_action( 'admin_notices', [ __CLASS__, 'nudge_2fa_setup' ] );
		add_action( 'show_user_profile', [ __CLASS__, 'render_2fa_profile_section' ] );
		add_action( 'edit_user_profile', [ __CLASS__, 'render_2fa_profile_section' ] );
	}

	private static function requires_2fa( int $user_id ): bool {
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		$role = PF_Constants::get_pf_role( $user_id );

		return $role !== null && $role !== PF_Constants::ROLE_VERIFIED_VET;
	}

	public static function intercept_login( $user, $username, $password ) {
		if ( ! ( $user instanceof WP_User ) ) {
			return $user;
		}

		if ( ! self::requires_2fa( $user->ID ) ) {
			return $user;
		}

		if ( ! empty( $_POST['pf_otp_code'] ) ) {
			$result = self::verify_otp( $user->ID, sanitize_text_field( wp_unslash( $_POST['pf_otp_code'] ) ) );
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			update_user_meta( $user->ID, PF_Constants::META_2FA_ENABLED, '1' );
			update_user_meta( $user->ID, PF_Constants::META_2FA_LAST_IP, self::get_ip() );

			PF_Logger::log(
				$user->ID,
				'2fa_success',
				'user',
				$user->ID,
				$user->user_email,
				'2FA login successful',
				[ 'ip' => self::get_ip() ]
			);

			return $user;
		}

		$lock_error = self::check_lockout( $user->ID );
		if ( is_wp_error( $lock_error ) ) {
			return $lock_error;
		}

		self::send_otp( $user->ID );

		$session_key = 'pf_2fa_pending_' . md5( $username . self::get_ip() );
		set_transient( $session_key, $user->ID, self::OTP_EXPIRY );

		return new WP_Error(
			'pf_2fa_pending',
			sprintf(
				'<div class="pf-otp-prompt">
					<p>📧 Mã xác thực đã được gửi đến <strong>%s</strong></p>
					<p style="font-size:12px;color:#64748b">Mã có hiệu lực trong 10 phút</p>
				</div>',
				esc_html( self::mask_email( $user->user_email ) )
			)
		);
	}

	public static function maybe_show_otp_field(): void {
		$username = sanitize_user( wp_unslash( $_POST['log'] ?? $_GET['log'] ?? '' ) );
		if ( ! $username ) {
			return;
		}

		$session_key = 'pf_2fa_pending_' . md5( $username . self::get_ip() );
		if ( ! get_transient( $session_key ) ) {
			return;
		}
		?>
		<div class="pf-otp-section" style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:14px;margin:12px 0">
			<label style="display:block;font-size:13px;color:#0369a1;font-weight:600;margin-bottom:8px">
				🔐 Nhập mã xác thực (6 chữ số)
			</label>
			<input type="text" name="pf_otp_code" id="pf_otp_code"
				maxlength="6" autocomplete="one-time-code" inputmode="numeric"
				style="width:100%;padding:10px;font-size:20px;letter-spacing:8px;text-align:center;border:2px solid #bae6fd;border-radius:6px"
				placeholder="• • • • • •" autofocus>
			<p style="font-size:12px;color:#64748b;margin:8px 0 0">
				Chưa nhận được? <a href="#" onclick="pfResendOtp('<?php echo esc_js( $username ); ?>');return false">Gửi lại</a>
			</p>
		</div>
		<script>
		document.addEventListener('DOMContentLoaded',function(){
			var otp = document.getElementById('pf_otp_code');
			if(otp) {
				otp.addEventListener('input',function(){
					this.value=this.value.replace(/[^0-9]/g,'');
				});
				otp.focus();
			}
		});
		function pfResendOtp(username) {
			fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
				method:'POST',
				headers:{'Content-Type':'application/x-www-form-urlencoded'},
				body:'action=pf_resend_otp&username='+encodeURIComponent(username)
			}).then(r=>r.json()).then(function(d){
				alert(d.success ? d.data.message : (d.data && d.data.message ? d.data.message : 'Lỗi gửi lại mã.'));
			});
		}
		</script>
		<?php
	}

	private static function clear_pending_session( int $user_id ): void {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$session_key = 'pf_2fa_pending_' . md5( $user->user_login . self::get_ip() );
		delete_transient( $session_key );
	}

	public static function style_otp_login_errors( string $errors ): string {
		global $wp_error;
		if ( ! ( $wp_error instanceof WP_Error ) ) {
			return $errors;
		}

		$codes = [ 'pf_2fa_wrong', 'pf_2fa_locked', 'pf_2fa_expired' ];
		foreach ( $codes as $code ) {
			$message = $wp_error->get_error_message( $code );
			if ( $message ) {
				return '<div class="pf-otp-error" style="color:#c0392b;margin:10px 0;padding:10px;background:#fdf0ef;border-radius:4px;">'
					. wp_kses_post( $message )
					. '</div>';
			}
		}

		return $errors;
	}

	private static function get_lang( int $user_id ): string {
		$lang = get_user_meta( $user_id, PF_Constants::META_PREFERRED_LANG, true );

		return in_array( $lang, [ 'vi', 'en' ], true ) ? $lang : PF_I18n::current_lang();
	}

	private static function check_lockout( int $user_id ) {
		$locked_until = (int) get_user_meta( $user_id, PF_Constants::META_2FA_LOCKED_UNTIL, true );

		if ( $locked_until > time() ) {
			$minutes = (int) ceil( ( $locked_until - time() ) / 60 );
			$lang    = self::get_lang( $user_id );

			return new WP_Error(
				'pf_2fa_locked',
				sprintf( PF_I18n::get( '2fa_locked', $lang ), $minutes )
			);
		}

		if ( $locked_until > 0 && $locked_until <= time() ) {
			delete_user_meta( $user_id, PF_Constants::META_2FA_LOCKED_UNTIL );
		}

		return true;
	}

	/**
	 * @return true|WP_Error
	 */
	private static function verify_otp( int $user_id, string $submitted_otp ) {
		$lang = self::get_lang( $user_id );

		$lock_error = self::check_lockout( $user_id );
		if ( is_wp_error( $lock_error ) ) {
			return $lock_error;
		}

		$stored   = get_user_meta( $user_id, PF_Constants::META_2FA_OTP, true );
		$expiry   = (int) get_user_meta( $user_id, PF_Constants::META_2FA_OTP_EXPIRY, true );

		if ( ! $stored || ! $expiry ) {
			delete_user_meta( $user_id, PF_Constants::META_2FA_FAIL_COUNT );

			return new WP_Error( 'pf_2fa_expired', PF_I18n::get( '2fa_expired', $lang ) );
		}

		if ( time() > $expiry ) {
			delete_user_meta( $user_id, PF_Constants::META_2FA_OTP );
			delete_user_meta( $user_id, PF_Constants::META_2FA_OTP_EXPIRY );
			delete_user_meta( $user_id, PF_Constants::META_2FA_FAIL_COUNT );

			return new WP_Error( 'pf_2fa_expired', PF_I18n::get( '2fa_expired', $lang ) );
		}

		if ( ! hash_equals( (string) $stored, $submitted_otp ) ) {
			$fail_count = (int) get_user_meta( $user_id, PF_Constants::META_2FA_FAIL_COUNT, true ) + 1;
			update_user_meta( $user_id, PF_Constants::META_2FA_FAIL_COUNT, $fail_count );

			$user_obj   = get_userdata( $user_id );
			$user_email = ( $user_obj instanceof WP_User ) ? $user_obj->user_email : '';

			PF_Logger::log(
				$user_id,
				'2fa_failed',
				'user',
				$user_id,
				$user_email,
				'Wrong OTP entered',
				[ 'ip' => self::get_ip(), 'attempt' => $fail_count ]
			);

			if ( $fail_count >= self::OTP_MAX_ATTEMPTS ) {
				$lock_until = time() + self::OTP_LOCKOUT_SECONDS;
				update_user_meta( $user_id, PF_Constants::META_2FA_LOCKED_UNTIL, $lock_until );
				delete_user_meta( $user_id, PF_Constants::META_2FA_OTP );
				delete_user_meta( $user_id, PF_Constants::META_2FA_OTP_EXPIRY );
				delete_user_meta( $user_id, PF_Constants::META_2FA_FAIL_COUNT );
				self::clear_pending_session( $user_id );

				PF_Logger::log(
					$user_id,
					'2fa_lockout',
					'user',
					$user_id,
					$user_email,
					'3 lần nhập OTP sai — khóa 15 phút',
					[ 'ip' => self::get_ip(), 'locked_until' => $lock_until ]
				);

				return new WP_Error( 'pf_2fa_locked', PF_I18n::get( '2fa_locked_now', $lang ) );
			}

			$remaining = self::OTP_MAX_ATTEMPTS - $fail_count;

			return new WP_Error(
				'pf_2fa_wrong',
				sprintf( PF_I18n::get( '2fa_wrong', $lang ), $remaining )
			);
		}

		delete_user_meta( $user_id, PF_Constants::META_2FA_OTP );
		delete_user_meta( $user_id, PF_Constants::META_2FA_OTP_EXPIRY );
		delete_user_meta( $user_id, PF_Constants::META_2FA_FAIL_COUNT );
		delete_user_meta( $user_id, PF_Constants::META_2FA_LOCKED_UNTIL );

		return true;
	}

	public static function send_otp( int $user_id ): void {
		$otp    = str_pad( (string) random_int( 0, 999999 ), self::OTP_LENGTH, '0', STR_PAD_LEFT );
		$expiry = time() + self::OTP_EXPIRY;

		update_user_meta( $user_id, PF_Constants::META_2FA_OTP, $otp );
		update_user_meta( $user_id, PF_Constants::META_2FA_OTP_EXPIRY, $expiry );

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$lang = get_user_meta( $user_id, PF_Constants::META_PREFERRED_LANG, true ) ?: 'vi';
		$site = get_bloginfo( 'name' );

		if ( $lang === 'en' ) {
			$subject = "[{$site}] 🔐 Your login verification code";
			$body    = "
			<div style='font-family:sans-serif;max-width:480px;margin:0 auto;padding:32px;background:#f8fafc;border-radius:16px'>
			  <h2 style='color:#1e293b;text-align:center'>🔐 Login Verification</h2>
			  <p>Hi <strong>{$user->display_name}</strong>,</p>
			  <p>Your one-time verification code for Pet Forum admin login:</p>
			  <div style='background:#1e1b4b;color:#fff;font-size:36px;font-weight:800;letter-spacing:12px;text-align:center;padding:20px;border-radius:12px;margin:20px 0'>
				{$otp}
			  </div>
			  <p style='color:#64748b;font-size:13px'>This code expires in <strong>10 minutes</strong>. Do not share it with anyone.</p>
			  <p style='color:#dc2626;font-size:12px'>If you did not attempt to log in, please contact the Super Admin immediately.</p>
			</div>";
		} else {
			$subject = "[{$site}] 🔐 Mã xác thực đăng nhập của bạn";
			$body    = "
			<div style='font-family:sans-serif;max-width:480px;margin:0 auto;padding:32px;background:#f8fafc;border-radius:16px'>
			  <h2 style='color:#1e293b;text-align:center'>🔐 Xác thực Đăng nhập</h2>
			  <p>Xin chào <strong>{$user->display_name}</strong>,</p>
			  <p>Mã xác thực một lần của bạn để đăng nhập trang quản trị Pet Forum:</p>
			  <div style='background:#1e1b4b;color:#fff;font-size:36px;font-weight:800;letter-spacing:12px;text-align:center;padding:20px;border-radius:12px;margin:20px 0'>
				{$otp}
			  </div>
			  <p style='color:#64748b;font-size:13px'>Mã hết hạn sau <strong>10 phút</strong>. Không chia sẻ mã này cho bất kỳ ai.</p>
			  <p style='color:#dc2626;font-size:12px'>Nếu bạn không thực hiện đăng nhập này, hãy liên hệ Super Admin ngay lập tức.</p>
			</div>";
		}

		wp_mail( $user->user_email, $subject, $body, [ 'Content-Type: text/html; charset=UTF-8' ] );
	}

	public static function ajax_resend_otp(): void {
		$username = sanitize_user( wp_unslash( $_POST['username'] ?? '' ) );
		$user     = get_user_by( 'login', $username ) ?: get_user_by( 'email', $username );

		if ( ! $user ) {
			wp_send_json_error( [ 'message' => 'Không tìm thấy tài khoản.' ] );
		}

		if ( ! self::requires_2fa( $user->ID ) ) {
			wp_send_json_error( [ 'message' => 'Tài khoản này không yêu cầu 2FA.' ] );
		}

		$lock_error = self::check_lockout( $user->ID );
		if ( is_wp_error( $lock_error ) ) {
			wp_send_json_error( [ 'message' => $lock_error->get_error_message() ] );
		}

		$expiry = (int) get_user_meta( $user->ID, PF_Constants::META_2FA_OTP_EXPIRY, true );
		if ( $expiry ) {
			$sent_at = $expiry - self::OTP_EXPIRY;
			if ( ( time() - $sent_at ) < 60 ) {
				wp_send_json_error( [ 'message' => 'Vui lòng đợi 1 phút trước khi gửi lại.' ] );
			}
		}

		self::send_otp( $user->ID );

		$session_key = 'pf_2fa_pending_' . md5( $username . self::get_ip() );
		set_transient( $session_key, $user->ID, self::OTP_EXPIRY );

		wp_send_json_success( [ 'message' => '✅ Đã gửi lại mã xác thực!' ] );
	}

	public static function nudge_2fa_setup(): void {
		$uid = get_current_user_id();
		if ( ! $uid || ! self::requires_2fa( $uid ) ) {
			return;
		}

		if ( get_user_meta( $uid, PF_Constants::META_2FA_ENABLED, true ) ) {
			return;
		}
		?>
		<div class="notice notice-warning">
			<p>🔐 <strong>Bảo mật tài khoản:</strong> Vui lòng xác nhận email của bạn đang hoạt động — hệ thống sẽ gửi mã 2FA mỗi khi đăng nhập.
			<a href="<?php echo esc_url( admin_url( 'profile.php#pf-2fa' ) ); ?>">Kiểm tra ngay →</a></p>
		</div>
		<?php
	}

	public static function render_2fa_profile_section( WP_User $user ): void {
		if ( ! self::requires_2fa( $user->ID ) ) {
			return;
		}

		$enabled = get_user_meta( $user->ID, PF_Constants::META_2FA_ENABLED, true );
		$last_ip = get_user_meta( $user->ID, PF_Constants::META_2FA_LAST_IP, true );
		?>
		<h2 id="pf-2fa">🔐 Xác thực 2 lớp (2FA)</h2>
		<table class="form-table">
			<tr>
				<th>Trạng thái</th>
				<td>
					<?php if ( $enabled ) : ?>
						<span style="color:green;font-weight:600">✅ Đang hoạt động</span>
						<p style="color:#64748b;font-size:13px;margin:4px 0">
							Mã OTP sẽ được gửi đến <strong><?php echo esc_html( $user->user_email ); ?></strong> mỗi khi đăng nhập.
						</p>
						<?php if ( $last_ip ) : ?>
						<p style="color:#94a3b8;font-size:12px">Login gần nhất từ IP: <?php echo esc_html( $last_ip ); ?></p>
						<?php endif; ?>
					<?php else : ?>
						<span style="color:#f59e0b;font-weight:600">⚠️ Chưa xác nhận</span>
						<p style="color:#64748b;font-size:13px;margin:4px 0">
							2FA sẽ tự động hoạt động khi bạn đăng nhập lần tiếp theo — OTP gửi về <?php echo esc_html( $user->user_email ); ?>
						</p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th>Email nhận OTP</th>
				<td>
					<strong><?php echo esc_html( $user->user_email ); ?></strong>
					<p class="description">Để đổi email nhận OTP, cập nhật Email ở phần trên.</p>
				</td>
			</tr>
		</table>
		<?php
	}

	private static function mask_email( string $email ): string {
		$parts = explode( '@', $email, 2 );
		if ( count( $parts ) !== 2 ) {
			return $email;
		}

		[ $local, $domain ] = $parts;

		return substr( $local, 0, 2 ) . str_repeat( '*', max( strlen( $local ) - 3, 2 ) ) . substr( $local, -1 ) . '@' . $domain;
	}

	private static function get_ip(): string {
		foreach ( [ 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ] as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				return sanitize_text_field( explode( ',', (string) $_SERVER[ $key ] )[0] );
			}
		}

		return 'unknown';
	}
}
