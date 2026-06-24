<?php
defined( 'ABSPATH' ) || exit;

class PF_Constants {

	const TYPE_MEMBER       = 'member';
	const TYPE_VET_PENDING  = 'vet_pending';
	const TYPE_VERIFIED_VET = 'verified_vet';

	const ROLE_VERIFIED_VET = 'pf_verified_vet';
	const ROLE_GLOBAL_ADMIN = 'pf_global_admin';
	const ROLE_DOG_MOD      = 'pf_dog_admin';
	const ROLE_CAT_MOD      = 'pf_cat_admin';
	const ROLE_BIRD_MOD     = 'pf_bird_admin';
	const ROLE_MARKET_MOD   = 'pf_market_admin';

	const SECTION_MOD_ROLES = [
		'pf_dog_admin',
		'pf_cat_admin',
		'pf_bird_admin',
		'pf_market_admin',
	];

	const ALL_PF_ROLES = [
		'pf_global_admin',
		'pf_verified_vet',
		'pf_dog_admin',
		'pf_cat_admin',
		'pf_bird_admin',
		'pf_market_admin',
	];

	const WPFORO_GROUP_MEMBER       = 3;
	const WPFORO_GROUP_VERIFIED_VET = 4;
	const WPFORO_GROUP_BANNED       = 5;

	const META_USER_TYPE        = 'pf_user_type';
	const META_ACCOUNT_ACTIVE   = 'pf_account_active';
	const META_REGISTERED_AT    = 'pf_registered_at';
	const META_EMAIL_VERIFIED   = 'pf_email_verified';
	const META_VERIFY_TOKEN     = 'pf_verify_token';
	const META_VERIFY_EXPIRY    = 'pf_verify_token_expiry';
	const META_VERIFY_LAST_SENT = 'pf_verify_last_sent';
	const META_WELCOME_SHOWN    = 'pf_welcome_shown';
	const META_SOCIAL_LOGIN     = 'pf_social_login';
	const META_PREFERRED_LANG   = 'pf_preferred_lang';
	const META_COUNTRY          = 'pf_country';
	const META_PHONE            = 'pf_phone';
	const META_CITY             = 'pf_city';
	const META_PET_TYPES        = 'pf_pet_types';
	const META_PHONE_PUBLIC     = 'pf_phone_public';
	const META_CITY_PUBLIC      = 'pf_city_public';
	const META_VET_STATUS       = 'pf_vet_status';
	const META_WORKPLACE        = 'pf_workplace';
	const META_SPECIALTY        = 'pf_specialty';
	const META_CERT_FILE        = 'pf_certificate_file';
	const META_CERT_PATH        = 'pf_certificate_path';
	const META_APPROVED_AT      = 'pf_approved_at';
	const META_APPROVED_BY      = 'pf_approved_by';
	const META_REJECTED_AT      = 'pf_rejected_at';
	const META_BANNED           = 'pf_banned';
	const META_BANNED_BY        = 'pf_banned_by';
	const META_BANNED_AT        = 'pf_banned_at';
	const META_WPFORO_ROLE      = 'pf_wpforo_role';
	const META_MOD_FORUM_IDS    = 'pf_mod_forum_ids';

	/* ── Terms acceptance tracking ── */
	const META_VET_TERMS_ACCEPTED      = 'pf_vet_terms_accepted';       // timestamp when vet accepted
	const META_SUBADMIN_TERMS_ACCEPTED = 'pf_subadmin_terms_accepted'; // timestamp when sub-admin accepted
	const META_SUBADMIN_TERMS_VERSION  = 'pf_subadmin_terms_version';  // accepted version
	const SUBADMIN_TERMS_VERSION       = '1.0';                        // current version

	// 2FA
	const META_2FA_ENABLED    = 'pf_2fa_enabled';
	const META_2FA_OTP        = 'pf_2fa_otp';
	const META_2FA_OTP_EXPIRY = 'pf_2fa_otp_expiry';
	const META_2FA_FAIL_COUNT = 'pf_2fa_fail_count';
	const META_2FA_LOCKED_UNTIL = 'pf_2fa_locked_until';
	const META_2FA_LAST_IP    = 'pf_2fa_last_ip';

	// Handover
	const META_HANDOVER_DONE = 'pf_handover_done';
	const META_HANDOVER_AT   = 'pf_handover_at';
	const META_HANDOVER_BY   = 'pf_handover_by';

	const WARN_NONE       = 0;
	const WARN_WARNING    = 1;
	const WARN_CAUTION    = 2;
	const WARN_RESTRICTED = 3;
	const WARN_BANNED     = 4;

	const META_WARN_LEVEL   = 'pf_warn_level';
	const META_WARN_COUNT   = 'pf_warn_count';
	const META_WARN_REASON  = 'pf_warn_reason';
	const META_WARN_BY      = 'pf_warn_by';
	const META_WARN_AT      = 'pf_warn_at';
	const META_WARN_HISTORY = 'pf_warn_history';
	const META_RESTRICTED   = 'pf_restricted';

	const SECTION_FORUM_SLUGS = [
		'pf_dog_admin'    => [ 'cho-canh', 'hoi-cuong-cho', 'dogs', 'dog-lovers-community' ],
		'pf_cat_admin'    => [ 'meo-canh', 'hoi-cuong-meo', 'cats', 'cat-lovers-community', 'test-cat-care', 'test-cat-care-discussions' ],
		'pf_bird_admin'   => [ 'chim-canh', 'hoi-cuong-chim', 'pet-birds', 'bird-lovers-community' ],
		'pf_market_admin' => [ 'goc-mua-ban', 'marketplace', 'mua-ban', 'buy-sell' ],
	];

	/** Vet consultation section — wpForo multiboard URLs use board slug prefix */
	const VET_FORUM_SLUG_VI = 'bac-si-tu-van';
	const VET_FORUM_SLUG_EN = 'vet-consultation';
	const VET_BOARD_SLUG_VI = 'muc-tieng-viet';
	const VET_BOARD_SLUG_EN = 'english-section';
	const VET_BOARD_ID_VI     = 3;
	const VET_BOARD_ID_EN     = 2;

	const VET_FORUM_SLUGS = [
		'bac-si-tu-van',
		'bac-si-thu-y',      // legacy slug (setup-forum-v3.php)
		'vet-consultation',  // English board
	];

	const COUNTRIES = [
		''      => [ 'vi' => '— Chọn quốc gia —', 'en' => '— Select country —' ],
		'VN'    => [ 'vi' => '🇻🇳 Việt Nam', 'en' => '🇻🇳 Vietnam' ],
		'US'    => [ 'vi' => '🇺🇸 Hoa Kỳ', 'en' => '🇺🇸 United States' ],
		'CN'    => [ 'vi' => '🇨🇳 Trung Quốc', 'en' => '🇨🇳 China' ],
		'JP'    => [ 'vi' => '🇯🇵 Nhật Bản', 'en' => '🇯🇵 Japan' ],
		'KR'    => [ 'vi' => '🇰🇷 Hàn Quốc', 'en' => '🇰🇷 South Korea' ],
		'TH'    => [ 'vi' => '🇹🇭 Thái Lan', 'en' => '🇹🇭 Thailand' ],
		'SG'    => [ 'vi' => '🇸🇬 Singapore', 'en' => '🇸🇬 Singapore' ],
		'MY'    => [ 'vi' => '🇲🇾 Malaysia', 'en' => '🇲🇾 Malaysia' ],
		'PH'    => [ 'vi' => '🇵🇭 Philippines', 'en' => '🇵🇭 Philippines' ],
		'ID'    => [ 'vi' => '🇮🇩 Indonesia', 'en' => '🇮🇩 Indonesia' ],
		'AU'    => [ 'vi' => '🇦🇺 Úc', 'en' => '🇦🇺 Australia' ],
		'GB'    => [ 'vi' => '🇬🇧 Anh', 'en' => '🇬🇧 United Kingdom' ],
		'DE'    => [ 'vi' => '🇩🇪 Đức', 'en' => '🇩🇪 Germany' ],
		'FR'    => [ 'vi' => '🇫🇷 Pháp', 'en' => '🇫🇷 France' ],
		'RU'    => [ 'vi' => '🇷🇺 Nga', 'en' => '🇷🇺 Russia' ],
		'CA'    => [ 'vi' => '🇨🇦 Canada', 'en' => '🇨🇦 Canada' ],
		'IN'    => [ 'vi' => '🇮🇳 Ấn Độ', 'en' => '🇮🇳 India' ],
		'BR'    => [ 'vi' => '🇧🇷 Brazil', 'en' => '🇧🇷 Brazil' ],
		'OTHER' => [ 'vi' => '🌍 Quốc gia khác', 'en' => '🌍 Other' ],
	];

	const PET_TYPES = [
		'dog'   => [ 'vi' => '🐕 Chó', 'en' => '🐕 Dog' ],
		'cat'   => [ 'vi' => '🐈 Mèo', 'en' => '🐈 Cat' ],
		'bird'  => [ 'vi' => '🐦 Chim', 'en' => '🐦 Bird' ],
		'other' => [ 'vi' => '🐾 Khác', 'en' => '🐾 Other' ],
	];

	const DISPOSABLE_DOMAINS = [
		'mailinator.com', 'guerrillamail.com', 'tempmail.com', 'yopmail.com',
		'throwam.com', 'sharklasers.com', 'spam4.me', 'trashmail.com',
		'dispostable.com', 'maildrop.cc', 'fakeinbox.com', 'tempr.email',
		'discard.email', '10minutemail.com', 'temp-mail.org', 'tempemail.net',
		'mailtemp.info', 'spamgob.com', 'example.com', 'trbvm.com',
	];

	const DEFAULT_BANNED_KEYWORDS = [
		'casino', 'baccarat', 'xổ số', 'cá độ', 'cá cược', 'poker', 'nổ hũ',
		'jackpot', 'keno', 'lô đề', 'cho vay', 'vay tiền nhanh', 'forex',
		'bitcoin', 'crypto đầu tư', 'gấp vốn', 'làm giàu nhanh',
		'liên hệ ngay', 'hotline:', 'zalo:', 'click here', 'limited offer',
	];

	/* ── Upload limits ── */
	const UPLOAD_MAX_IMAGE_SIZE = 5242880;   // 5MB.
	const UPLOAD_MAX_DOC_SIZE   = 10485760;  // 10MB.
	const UPLOAD_USER_QUOTA     = 104857600; // 100MB per user.
	const UPLOAD_NEW_USER_DAYS  = 7;
	const UPLOAD_NEW_USER_MAX   = 2097152;   // 2MB for new accounts.

	const ALLOWED_IMAGE_MIMES = [
		'image/jpeg' => [ 'jpg', 'jpeg' ],
		'image/png'  => [ 'png' ],
		'image/gif'  => [ 'gif' ],
		'image/webp' => [ 'webp' ],
	];

	const ALLOWED_DOC_MIMES = [
		'application/pdf' => [ 'pdf' ],
	];

	const BLOCKED_EXTENSIONS = [
		'php', 'php3', 'php4', 'php5', 'phtml',
		'exe', 'bat', 'sh', 'py', 'rb', 'pl',
		'js', 'jsx', 'ts', 'vue',
		'html', 'htm', 'xml',
		'zip', 'rar', '7z', 'tar', 'gz',
		'svg',
	];

	const IMAGE_MAX_WIDTH    = 1200;
	const IMAGE_MAX_HEIGHT   = 1200;
	const IMAGE_QUALITY_JPEG = 82;
	const IMAGE_QUALITY_PNG  = 6;
	const IMAGE_QUALITY_WEBP = 80;

	const VIDEO_ALLOWED_DOMAINS = [
		'youtube.com', 'youtu.be',
		'tiktok.com',
		'facebook.com', 'fb.watch',
		'vimeo.com',
	];

	public static function get_allowed_all_mimes(): array {
		return array_merge( self::ALLOWED_IMAGE_MIMES, self::ALLOWED_DOC_MIMES );
	}

	public static function get_country_label( $code, $lang = 'vi' ) {
		return self::COUNTRIES[ $code ][ $lang ] ?? self::COUNTRIES[ $code ]['vi'] ?? $code;
	}

	public static function get_country_select_options( $lang = 'vi', $selected = '' ) {
		$html = '';
		foreach ( self::COUNTRIES as $code => $labels ) {
			$html .= sprintf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $code ),
				selected( $selected, $code, false ),
				esc_html( $labels[ $lang ] ?? $labels['vi'] )
			);
		}

		return $html;
	}

	public static function is_pf_role( $role ) {
		return in_array( $role, self::ALL_PF_ROLES, true );
	}

	public static function get_pf_role( $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return null;
		}
		foreach ( self::ALL_PF_ROLES as $role ) {
			if ( in_array( $role, (array) $user->roles, true ) ) {
				return $role;
			}
		}

		return null;
	}

	public static function get_user_type( $user_id ) {
		return get_user_meta( $user_id, self::META_USER_TYPE, true ) ?: self::TYPE_MEMBER;
	}

	public static function is_verified_vet( $user_id ) {
		return self::get_user_type( $user_id ) === self::TYPE_VERIFIED_VET;
	}

	public static function is_account_active( $user_id ) {
		$active = get_user_meta( $user_id, self::META_ACCOUNT_ACTIVE, true );

		return $active !== '0';
	}

	public static function get_warn_level( $user_id ) {
		return (int) get_user_meta( $user_id, self::META_WARN_LEVEL, true );
	}

	public static function is_user_banned( $user_id ) {
		if ( get_user_meta( $user_id, self::META_BANNED, true ) === '1' ) {
			return true;
		}

		return self::get_warn_level( $user_id ) >= self::WARN_BANNED;
	}

	public static function is_user_restricted( $user_id ) {
		if ( get_user_meta( $user_id, self::META_RESTRICTED, true ) === '1' ) {
			return true;
		}

		return self::get_warn_level( $user_id ) >= self::WARN_RESTRICTED;
	}

	public static function get_vet_group_id() {
		$id = (int) get_option( 'pf_verified_vet_group_id', 0 );
		if ( $id ) {
			return $id;
		}

		return (int) apply_filters( 'pf_verified_vet_group_id', self::WPFORO_GROUP_VERIFIED_VET );
	}

	public static function get_vet_forum_url( $lang = '' ) {
		if ( ! $lang ) {
			$lang = function_exists( 'PF_I18n' ) ? PF_I18n::current_lang() : 'vi';
		}

		$is_en     = ( 'en' === $lang );
		$board_id  = $is_en ? self::VET_BOARD_ID_EN : self::VET_BOARD_ID_VI;
		$board_slug = $is_en ? self::VET_BOARD_SLUG_EN : self::VET_BOARD_SLUG_VI;
		$forum_slug = $is_en ? self::VET_FORUM_SLUG_EN : self::VET_FORUM_SLUG_VI;

		if ( function_exists( 'WPF' ) && WPF()->forum ) {
			WPF()->change_board( $board_id );
			WPF()->forum->reset();
			$forum = WPF()->forum->get_forum( $forum_slug );
			if ( ! empty( $forum['forumid'] ) ) {
				return trailingslashit( WPF()->forum->get_forum_url( $forum ) );
			}
		}

		return home_url( '/' . $board_slug . '/' . $forum_slug . '/' );
	}
}
