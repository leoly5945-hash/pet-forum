<?php
defined( 'ABSPATH' ) || exit;

class PF_I18n {

	public static function init() {
		add_action( 'init', [ __CLASS__, 'start_session' ], 1 );
	}

	public static function start_session() {
		if ( ! session_id() && ! headers_sent() ) {
			session_start();
		}
	}

	public static function current_lang(): string {
		if ( ! empty( $_GET['lang'] ) ) {
			$lang = sanitize_key( wp_unslash( $_GET['lang'] ) );
			if ( in_array( $lang, [ 'vi', 'en' ], true ) ) {
				self::persist_lang( $lang );

				return $lang;
			}
		}

		self::start_session();

		if ( ! empty( $_SESSION['pf_lang'] ) && in_array( $_SESSION['pf_lang'], [ 'vi', 'en' ], true ) ) {
			return sanitize_key( $_SESSION['pf_lang'] );
		}

		if ( ! empty( $_COOKIE['pf_lang'] ) ) {
			$cookie = sanitize_key( wp_unslash( $_COOKIE['pf_lang'] ) );
			if ( in_array( $cookie, [ 'vi', 'en' ], true ) ) {
				return $cookie;
			}
		}

		return 'vi';
	}

	public static function persist_lang( string $lang ): void {
		if ( ! in_array( $lang, [ 'vi', 'en' ], true ) ) {
			return;
		}

		self::start_session();
		$_SESSION['pf_lang'] = $lang;

		if ( ! headers_sent() ) {
			setcookie( 'pf_lang', $lang, time() + DAY_IN_SECONDS * 30, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true );
		}
	}

	public static function get( string $key, string $lang = 'vi' ): string {
		return self::strings()[ $key ][ $lang ] ?? self::strings()[ $key ]['vi'] ?? $key;
	}

	public static function register_page_url( string $type = '', string $lang = '' ): string {
		$args = [];

		if ( in_array( $type, [ 'member', 'vet' ], true ) ) {
			$args['type'] = $type;
		}

		if ( $lang === '' ) {
			$lang = self::current_lang();
		}

		if ( in_array( $lang, [ 'vi', 'en' ], true ) ) {
			$args['lang'] = $lang;
		}

		$base = home_url( '/register/' );

		return empty( $args ) ? $base : add_query_arg( $args, $base );
	}

	public static function js_strings( string $lang ): array {
		return [
			'processing'       => $lang === 'en' ? '⏳ Processing...' : '⏳ Đang xử lý...',
			'registering'      => $lang === 'en' ? '⏳ Processing...' : '⏳ Đang đăng ký...',
			'uploading'        => self::get( 'uploading', $lang ),
			'uploading_pct'    => $lang === 'en' ? '⏳ Uploading... %s%%' : '⏳ Đang tải lên... %s%%',
			'file_too_large'   => self::get( 'err_file_size', $lang ),
			'file_type'        => self::get( 'err_file_type', $lang ),
			'err_cert_required'=> self::get( 'err_cert_required', $lang ),
			'err_email_exists' => self::get( 'err_email_exists', $lang ),
			'err_pass_short'   => self::get( 'err_pass_short', $lang ),
			'submit_member'    => self::get( 'submit_member', $lang ),
			'submit_vet'       => self::get( 'submit_vet', $lang ),
			'network_error'    => $lang === 'en' ? 'Connection error. Please try again.' : 'Lỗi kết nối. Vui lòng thử lại.',
		];
	}

	public static function strings(): array {
		return [
			'split_heading'        => [ 'vi' => 'Bạn muốn tham gia với tư cách nào?', 'en' => 'How would you like to join?' ],
			'split_subheading'     => [ 'vi' => 'Chọn một trong hai loại tài khoản bên dưới', 'en' => 'Choose one of the account types below' ],
			'member_title'         => [ 'vi' => 'Thành viên nuôi Pet', 'en' => 'Pet Owner Member' ],
			'member_desc'          => [ 'vi' => 'Tham gia chia sẻ kinh nghiệm, đặt câu hỏi và kết nối cộng đồng yêu thú cưng.', 'en' => 'Join to share experiences, ask questions and connect with the pet lover community.' ],
			'member_feat_1'        => [ 'vi' => 'Tham gia thảo luận ngay', 'en' => 'Join discussions immediately' ],
			'member_feat_2'        => [ 'vi' => 'Đăng bài & chia sẻ ảnh', 'en' => 'Post & share photos' ],
			'member_feat_3'        => [ 'vi' => 'Nhận tư vấn từ bác sĩ', 'en' => 'Get advice from vets' ],
			'member_btn'           => [ 'vi' => 'Đăng ký Thành viên →', 'en' => 'Register as Member →' ],
			'vet_title'            => [ 'vi' => 'Bác sĩ Thú y / Chuyên gia', 'en' => 'Veterinarian / Expert' ],
			'vet_desc'             => [ 'vi' => 'Chia sẻ chuyên môn, tư vấn cho thành viên và xây dựng thương hiệu cá nhân.', 'en' => 'Share expertise, advise members and build your personal brand.' ],
			'vet_feat_1'           => [ 'vi' => 'Tích xanh ✔ xác minh', 'en' => 'Blue ✔ verification badge' ],
			'vet_feat_2'           => [ 'vi' => 'Nhãn "Verified Vet"', 'en' => '"Verified Vet" label' ],
			'vet_feat_3'           => [ 'vi' => 'Ưu tiên hiển thị', 'en' => 'Priority display' ],
			'vet_btn'              => [ 'vi' => 'Đăng ký Bác sĩ →', 'en' => 'Register as Vet →' ],
			'vet_badge'            => [ 'vi' => 'Chuyên gia', 'en' => 'Expert' ],
			'back_btn'             => [ 'vi' => '← Quay lại', 'en' => '← Back' ],
			'email_label'          => [ 'vi' => 'Email', 'en' => 'Email' ],
			'email_placeholder'    => [ 'vi' => 'ban@example.com', 'en' => 'you@example.com' ],
			'email_placeholder_vet'=> [ 'vi' => 'dr.name@hospital.com', 'en' => 'dr.name@hospital.com' ],
			'display_name_label'   => [ 'vi' => 'Tên hiển thị', 'en' => 'Display name' ],
			'display_name_optional'=> [ 'vi' => 'Tên của bạn (tùy chọn)', 'en' => 'Your name (optional)' ],
			'display_name_ph_vet'  => [ 'vi' => 'BS. Nguyễn Văn A', 'en' => 'Dr. John Doe' ],
			'password_label'       => [ 'vi' => 'Mật khẩu', 'en' => 'Password' ],
			'password_hint'        => [ 'vi' => 'Tối thiểu 8 ký tự', 'en' => 'Minimum 8 characters' ],
			'lang_label'           => [ 'vi' => 'Ngôn ngữ', 'en' => 'Language' ],
			'lang_vi'              => [ 'vi' => '🇻🇳 Tiếng Việt', 'en' => '🇻🇳 Vietnamese' ],
			'lang_en'              => [ 'vi' => '🇺🇸 Tiếng Anh', 'en' => '🇺🇸 English' ],
			'country_label'        => [ 'vi' => 'Quốc gia', 'en' => 'Country' ],
			'country_select'       => [ 'vi' => '— Chọn quốc gia —', 'en' => '— Select country —' ],
			'pet_types_label'      => [ 'vi' => 'Loại thú cưng đang nuôi', 'en' => 'Pet type(s) you own' ],
			'pet_dog'              => [ 'vi' => '🐕 Chó', 'en' => '🐕 Dog' ],
			'pet_cat'              => [ 'vi' => '🐈 Mèo', 'en' => '🐈 Cat' ],
			'pet_bird'             => [ 'vi' => '🐦 Chim', 'en' => '🐦 Bird' ],
			'pet_other'            => [ 'vi' => '🐾 Khác', 'en' => '🐾 Other' ],
			'submit_member'        => [ 'vi' => '🐾 Tạo tài khoản', 'en' => '🐾 Create account' ],
			'have_account'         => [ 'vi' => 'Đã có tài khoản?', 'en' => 'Already have an account?' ],
			'login_link'           => [ 'vi' => 'Đăng nhập', 'en' => 'Log in' ],
			'member_form_title'    => [ 'vi' => '🐾 Đăng ký Thành viên nuôi Pet', 'en' => '🐾 Register as Pet Owner' ],
			'vet_form_title'       => [ 'vi' => '🩺 Đăng ký Bác sĩ / Chuyên gia', 'en' => '🩺 Register as Veterinarian / Expert' ],
			'vet_notice'           => [ 'vi' => '⚠️ Tài khoản sẽ được kích hoạt sau khi Admin xác minh bằng cấp (24-48h)', 'en' => '⚠️ Account will be activated after Admin verifies your credentials (24-48h)' ],
			'phone_label'          => [ 'vi' => 'Số điện thoại', 'en' => 'Phone number' ],
			'phone_placeholder'    => [ 'vi' => '+84 901 234 567', 'en' => '+1 555 000 1234' ],
			'workplace_label'      => [ 'vi' => 'Nơi công tác', 'en' => 'Workplace' ],
			'workplace_ph'         => [ 'vi' => 'Phòng khám / Bệnh viện thú y', 'en' => 'Clinic / Veterinary hospital' ],
			'specialty_label'      => [ 'vi' => 'Chuyên khoa', 'en' => 'Specialty' ],
			'specialty_ph'         => [ 'vi' => 'Nội khoa, Ngoại khoa, Da liễu...', 'en' => 'Internal medicine, Surgery, Dermatology...' ],
			'cert_label'           => [ 'vi' => 'Bằng cấp / Chứng chỉ', 'en' => 'Degree / Certificate' ],
			'cert_hint'            => [ 'vi' => 'PDF, JPG, PNG — tối đa 5MB', 'en' => 'PDF, JPG, PNG — max 5MB' ],
			'cert_drag'            => [ 'vi' => 'Kéo thả file vào đây hoặc', 'en' => 'Drag & drop file here or' ],
			'cert_browse'          => [ 'vi' => 'chọn file', 'en' => 'browse' ],
			'terms_vet'            => [ 'vi' => 'Tôi cam kết thông tin và bằng cấp cung cấp là <strong>chính xác và hợp lệ</strong>.', 'en' => 'I confirm the information and credentials provided are <strong>accurate and valid</strong>.' ],
			'submit_vet'           => [ 'vi' => '🩺 Gửi hồ sơ xét duyệt', 'en' => '🩺 Submit for review' ],
			'uploading'            => [ 'vi' => '⏳ Đang tải lên...', 'en' => '⏳ Uploading...' ],
			'member_success_title' => [ 'vi' => '✅ Đăng ký thành công!', 'en' => '✅ Registration successful!' ],
			'member_success_msg'   => [ 'vi' => 'Chúng tôi đã gửi email xác thực đến <strong>%s</strong>. Vui lòng kiểm tra hộp thư (kể cả spam) và click link xác thực.', 'en' => 'We sent a verification email to <strong>%s</strong>. Please check your inbox (including spam) and click the verification link.' ],
			'member_success_box'   => [ 'vi' => 'Kiểm tra hộp thư của bạn!', 'en' => 'Check your inbox!' ],
			'vet_success_title'    => [ 'vi' => '⏳ Hồ sơ đã được gửi!', 'en' => '⏳ Application submitted!' ],
			'vet_success_msg'      => [ 'vi' => 'Chúng tôi sẽ xem xét và phản hồi trong vòng 24-48 giờ qua email.', 'en' => 'We will review and respond within 24-48 hours via email.' ],
			'vet_success_wait'     => [ 'vi' => 'Trong thời gian chờ, tài khoản chưa thể đăng nhập.', 'en' => 'Your account cannot log in while pending review.' ],
			'vet_pending_title'    => [ 'vi' => 'Hồ sơ của bạn đang chờ duyệt', 'en' => 'Your application is pending review' ],
			'vet_pending_msg'      => [ 'vi' => 'Ban quản trị sẽ xem xét và phản hồi trong <strong>24-48 giờ</strong> qua email.', 'en' => 'Admins will review and respond within <strong>24-48 hours</strong> via email.' ],
			'resend_verify'        => [ 'vi' => 'Gửi lại email xác thực', 'en' => 'Resend verification email' ],
			'err_email_invalid'    => [ 'vi' => 'Email không hợp lệ.', 'en' => 'Invalid email address.' ],
			'err_email_exists'     => [ 'vi' => 'Email này đã được đăng ký.', 'en' => 'This email is already registered.' ],
			'err_pass_short'       => [ 'vi' => 'Mật khẩu tối thiểu 8 ký tự.', 'en' => 'Password must be at least 8 characters.' ],
			'err_phone_required'   => [ 'vi' => 'Số điện thoại là bắt buộc.', 'en' => 'Phone number is required.' ],
			'err_workplace_req'    => [ 'vi' => 'Nơi công tác là bắt buộc.', 'en' => 'Workplace is required.' ],
			'err_display_name_req' => [ 'vi' => 'Tên hiển thị là bắt buộc.', 'en' => 'Display name is required.' ],
			'err_country_required' => [ 'vi' => 'Quốc gia là bắt buộc.', 'en' => 'Country is required.' ],
			'err_terms_required'   => [ 'vi' => 'Bạn cần đồng ý với Điều khoản sử dụng để đăng ký.', 'en' => 'You must agree to the Terms of Service to register.' ],
			'err_vet_credential'   => [ 'vi' => 'Vui lòng xác nhận thông tin và bằng cấp là chính xác.', 'en' => 'Please confirm your credentials are accurate and valid.' ],
			'err_vet_terms_required' => [ 'vi' => 'Bạn cần đồng ý với Điều khoản Chuyên gia Thú y để đăng ký.', 'en' => 'You must agree to the Terms for Veterinary Experts to register.' ],
			'err_cert_required'    => [ 'vi' => 'Vui lòng tải lên bằng cấp.', 'en' => 'Please upload your certificate.' ],
			'err_file_type'        => [ 'vi' => 'Chỉ chấp nhận PDF, JPG, PNG.', 'en' => 'Only PDF, JPG, PNG accepted.' ],
			'err_file_size'        => [ 'vi' => 'File tối đa 5MB.', 'en' => 'File max 5MB.' ],
			'err_disposable'       => [ 'vi' => 'Email tạm thời không được chấp nhận.', 'en' => 'Disposable email addresses are not accepted.' ],
			'logged_in_msg'        => [ 'vi' => 'Bạn đã đăng nhập.', 'en' => 'You are already logged in.' ],
			'home_link'            => [ 'vi' => 'Về trang chủ →', 'en' => 'Back to home →' ],
			'menu_account'         => [ 'vi' => 'Tài khoản', 'en' => 'Account' ],
			'menu_section_register'=> [ 'vi' => 'ĐĂNG KÝ', 'en' => 'REGISTER' ],
			'menu_btn_member'      => [ 'vi' => '🐾 Đăng ký thành viên', 'en' => '🐾 Register as Member' ],
			'menu_btn_vet'         => [ 'vi' => '🩺 Đăng ký bác sĩ', 'en' => '🩺 Register as Vet' ],
			'menu_btn_login'       => [ 'vi' => '🔑 Đăng nhập', 'en' => '🔑 Log in' ],
			'menu_community'       => [ 'vi' => '💬 Diễn đàn', 'en' => '💬 Community' ],
			'menu_profile'         => [ 'vi' => '👤 Hồ sơ', 'en' => '👤 Profile' ],
			'menu_logout'          => [ 'vi' => '🚪 Đăng xuất', 'en' => '🚪 Log out' ],
			'nav_home'             => [ 'vi' => '🏠 Trang chủ', 'en' => '🏠 Home' ],
			'nav_community'        => [ 'vi' => '🐾 Diễn đàn', 'en' => '🐾 Community' ],
			'nav_english'          => [ 'vi' => '🇺🇸 Diễn đàn Anh', 'en' => '🇺🇸 English Forum' ],
			'nav_vietnamese'       => [ 'vi' => '🇻🇳 Mục Tiếng Việt', 'en' => '🇻🇳 Vietnamese' ],
			'nav_members'          => [ 'vi' => '👥 Thành viên', 'en' => '👥 Members' ],
			'nav_blog'             => [ 'vi' => '✍️ Blog', 'en' => '✍️ Blog' ],
			'nav_world'            => [ 'vi' => '🌐 Đa ngôn ngữ', 'en' => '🌐 World' ],
			'nav_terms'            => [ 'vi' => '📄 Điều khoản', 'en' => '📄 Terms' ],
			'nav_feedback'         => [ 'vi' => '💬 Góp ý', 'en' => '💬 Feedback' ],
			'2fa_wrong'            => [ 'vi' => 'Mã OTP không đúng. Còn %d lần thử.', 'en' => 'Incorrect OTP. %d attempts remaining.' ],
			'2fa_locked'           => [ 'vi' => 'Tài khoản tạm khóa do nhập sai OTP nhiều lần. Thử lại sau %d phút.', 'en' => 'Account temporarily locked after too many wrong OTP attempts. Try again in %d minutes.' ],
			'2fa_locked_now'       => [ 'vi' => 'Nhập sai OTP 3 lần — tài khoản bị khóa 15 phút. Một mã OTP mới sẽ được gửi khi hết thời gian khóa.', 'en' => 'Too many wrong OTP attempts — account locked for 15 minutes. A new OTP will be available after the lockout.' ],
			'2fa_expired'          => [ 'vi' => 'Mã OTP đã hết hạn. Vui lòng đăng nhập lại để nhận mã mới.', 'en' => 'OTP has expired. Please log in again to receive a new code.' ],
		];
	}

	public static function menu_lang(): string {
		return self::current_lang();
	}

	public static function lang_switch_url( string $lang ): string {
		return add_query_arg( 'lang', $lang, remove_query_arg( 'lang' ) );
	}
}
