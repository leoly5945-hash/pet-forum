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

	const SECTION_FORUM_SLUGS = [
		'pf_dog_admin'    => [ 'cho-canh', 'hoi-cuong-cho', 'dogs', 'dog-lovers' ],
		'pf_cat_admin'    => [ 'meo-canh', 'hoi-cuong-meo', 'cats', 'cat-lovers', 'test-cat-care', 'test-cat-care-discussions' ],
		'pf_bird_admin'   => [ 'chim-canh', 'hoi-cuong-chim', 'birds', 'bird-lovers' ],
		'pf_market_admin' => [ 'goc-mua-ban', 'marketplace', 'mua-ban', 'buy-sell' ],
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

	public static function get_vet_group_id() {
		$id = (int) get_option( 'pf_verified_vet_group_id', 0 );
		if ( $id ) {
			return $id;
		}

		return (int) apply_filters( 'pf_verified_vet_group_id', self::WPFORO_GROUP_VERIFIED_VET );
	}
}
