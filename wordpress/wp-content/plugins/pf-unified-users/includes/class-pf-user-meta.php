<?php
defined( 'ABSPATH' ) || exit;

class PF_User_Meta_V2 {

	private static $wpforo_hooks_registered = false;

	public static $countries = [
		'VN'    => [ 'vi' => 'Việt Nam', 'en' => 'Vietnam' ],
		'US'    => [ 'vi' => 'Hoa Kỳ', 'en' => 'United States' ],
		'CN'    => [ 'vi' => 'Trung Quốc', 'en' => 'China' ],
		'JP'    => [ 'vi' => 'Nhật Bản', 'en' => 'Japan' ],
		'KR'    => [ 'vi' => 'Hàn Quốc', 'en' => 'South Korea' ],
		'TH'    => [ 'vi' => 'Thái Lan', 'en' => 'Thailand' ],
		'SG'    => [ 'vi' => 'Singapore', 'en' => 'Singapore' ],
		'MY'    => [ 'vi' => 'Malaysia', 'en' => 'Malaysia' ],
		'PH'    => [ 'vi' => 'Philippines', 'en' => 'Philippines' ],
		'ID'    => [ 'vi' => 'Indonesia', 'en' => 'Indonesia' ],
		'AU'    => [ 'vi' => 'Úc', 'en' => 'Australia' ],
		'GB'    => [ 'vi' => 'Vương quốc Anh', 'en' => 'United Kingdom' ],
		'DE'    => [ 'vi' => 'Đức', 'en' => 'Germany' ],
		'FR'    => [ 'vi' => 'Pháp', 'en' => 'France' ],
		'CA'    => [ 'vi' => 'Canada', 'en' => 'Canada' ],
		'RU'    => [ 'vi' => 'Nga', 'en' => 'Russia' ],
		'IN'    => [ 'vi' => 'Ấn Độ', 'en' => 'India' ],
		'BR'    => [ 'vi' => 'Brazil', 'en' => 'Brazil' ],
		'OTHER' => [ 'vi' => 'Khác', 'en' => 'Other' ],
	];

	public static function set_default_options() {
		if ( ! get_option( 'pf_banned_keywords' ) ) {
			update_option( 'pf_banned_keywords', PF_Constants::DEFAULT_BANNED_KEYWORDS );
		}
	}

	public static function init() {
		add_action( 'user_register', [ __CLASS__, 'set_default_meta' ], 10 );

		add_action( 'show_user_profile', [ __CLASS__, 'render_profile_fields' ] );
		add_action( 'edit_user_profile', [ __CLASS__, 'render_profile_fields' ] );
		add_action( 'personal_options_update', [ __CLASS__, 'save_profile_fields' ] );
		add_action( 'edit_user_profile_update', [ __CLASS__, 'save_profile_fields' ] );

		add_action( 'rest_api_init', [ __CLASS__, 'register_rest_endpoints' ] );

		add_shortcode( 'pf_user_profile', [ __CLASS__, 'render_frontend_profile' ] );

		add_action( 'wp_ajax_pf_save_profile', [ __CLASS__, 'ajax_save_profile' ] );

		add_action( 'init', [ __CLASS__, 'register_wpforo_custom_fields' ], 20 );

		add_action( 'wpforo_create_user_after', [ __CLASS__, 'on_wpforo_create_user' ], 10, 1 );
		add_action( 'wpforo_update_profile_after', [ __CLASS__, 'on_wpforo_update_profile' ], 10, 1 );
		add_action( 'wpforo_update_profile_fields', [ __CLASS__, 'on_wpforo_update_profile_fields' ], 10, 3 );
		add_action( 'profile_update', [ __CLASS__, 'sync_on_wp_profile_update' ], 10, 2 );

		add_filter( 'wpforo_create_profile', [ __CLASS__, 'validate_phone_on_create' ], 20 );
		add_filter( 'wpforo_update_profile', [ __CLASS__, 'validate_phone_on_update' ], 20 );

		add_action( 'wpforo_profile_account_bottom', [ __CLASS__, 'render_privacy_toggles' ] );

		add_action( 'wpforo_profile_head_right', [ __CLASS__, 'render_member_card_fields' ], 10, 1 );
		add_action( 'wpforo_profile_after_statbox', [ __CLASS__, 'render_member_card_fields' ], 10, 1 );

		add_filter( 'wpforo_get_member', [ __CLASS__, 'merge_member_meta' ] );

		add_filter( 'manage_users_columns', [ __CLASS__, 'add_user_columns' ] );
		add_filter( 'manage_users_custom_column', [ __CLASS__, 'render_user_columns' ], 10, 3 );
		add_filter( 'manage_users_sortable_columns', [ __CLASS__, 'sortable_user_columns' ] );

		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
	}

	/* ── wpForo field registration (wpForo 3.x — filter-based, no userfields table) ── */

	public static function register_wpforo_custom_fields() {
		if ( self::$wpforo_hooks_registered || ! function_exists( 'wpforo_get_option' ) ) {
			return;
		}

		add_filter( 'wpforo_get_fields', [ __CLASS__, 'inject_wpforo_field_definitions' ] );
		add_filter( 'wpforo_get_register_fields', [ __CLASS__, 'append_register_fields' ] );
		add_filter( 'wpforo_get_account_fields', [ __CLASS__, 'append_account_fields' ] );

		self::$wpforo_hooks_registered = true;
	}

	public static function inject_wpforo_field_definitions( $fields ) {
		if ( ! function_exists( 'WPF' ) ) {
			return $fields;
		}

		$usergroupids_can_edit = WPF()->usergroup->get_groupids_by_can( 'em' );
		$usergroupids          = array_column( WPF()->usergroup->get_usergroups(), 'groupid' );
		$country_values        = self::get_country_select_values();

		$fields['pf_country'] = [
			'fieldKey'       => 'pf_country',
			'type'           => 'select',
			'isDefault'      => 0,
			'isRemovable'    => 0,
			'isRequired'     => 1,
			'isEditable'     => 1,
			'label'          => 'Country / Quốc gia',
			'title'          => 'Country / Quốc gia',
			'placeholder'    => '— Chọn quốc gia / Select country —',
			'description'    => 'Quốc gia / Country (required)',
			'faIcon'         => 'fas fa-globe',
			'values'         => $country_values,
			'name'           => 'pf_country',
			'cantBeInactive' => [ 'register' ],
			'canEdit'        => $usergroupids_can_edit,
			'canView'        => $usergroupids,
			'can'            => '',
			'isSearchable'   => 1,
		];

		$fields['pf_phone'] = [
			'fieldKey'       => 'pf_phone',
			'type'           => 'text',
			'isDefault'      => 0,
			'isRemovable'    => 0,
			'isRequired'     => 0,
			'isEditable'     => 1,
			'label'          => 'Phone / Số điện thoại',
			'title'          => 'Phone / Số điện thoại',
			'placeholder'    => '+84 90 123 4567',
			'description'    => 'Số điện thoại (tùy chọn)',
			'faIcon'         => 'fas fa-phone',
			'name'           => 'pf_phone',
			'cantBeInactive' => [],
			'canEdit'        => $usergroupids_can_edit,
			'canView'        => $usergroupids,
			'can'            => '',
			'isSearchable'   => 0,
		];

		$fields['pf_city'] = [
			'fieldKey'       => 'pf_city',
			'type'           => 'text',
			'isDefault'      => 0,
			'isRemovable'    => 0,
			'isRequired'     => 0,
			'isEditable'     => 1,
			'label'          => 'City / Thành phố',
			'title'          => 'City / Thành phố',
			'placeholder'    => 'TP. Hồ Chí Minh',
			'description'    => 'Thành phố / khu vực (tùy chọn)',
			'faIcon'         => 'fas fa-map-marker-alt',
			'name'           => 'pf_city',
			'cantBeInactive' => [],
			'canEdit'        => $usergroupids_can_edit,
			'canView'        => $usergroupids,
			'can'            => '',
			'isSearchable'   => 1,
		];

		return $fields;
	}

	public static function append_register_fields( $fields ) {
		if ( empty( $fields[0][0] ) || ! is_array( $fields[0][0] ) ) {
			return $fields;
		}
		$fields[0][0][] = 'pf_country';
		$fields[0][0][] = 'pf_phone';

		return $fields;
	}

	public static function append_account_fields( $fields ) {
		if ( empty( $fields[2][0] ) || ! is_array( $fields[2][0] ) ) {
			return $fields;
		}
		array_unshift( $fields[2][0], 'pf_country', 'pf_phone', 'pf_city' );

		return $fields;
	}

	public static function get_country_select_values() {
		$rows = [
			''      => '— Chọn quốc gia / Select country —',
			'VN'    => '🇻🇳 Việt Nam',
			'US'    => '🇺🇸 Hoa Kỳ / United States',
			'CN'    => '🇨🇳 Trung Quốc / China',
			'JP'    => '🇯🇵 Nhật Bản / Japan',
			'KR'    => '🇰🇷 Hàn Quốc / South Korea',
			'TH'    => '🇹🇭 Thái Lan / Thailand',
			'SG'    => '🇸🇬 Singapore',
			'MY'    => '🇲🇾 Malaysia',
			'PH'    => '🇵🇭 Philippines',
			'ID'    => '🇮🇩 Indonesia',
			'AU'    => '🇦🇺 Úc / Australia',
			'GB'    => '🇬🇧 Anh / United Kingdom',
			'DE'    => '🇩🇪 Đức / Germany',
			'FR'    => '🇫🇷 Pháp / France',
			'CA'    => '🇨🇦 Canada',
			'RU'    => '🇷🇺 Nga / Russia',
			'IN'    => '🇮🇳 Ấn Độ / India',
			'BR'    => '🇧🇷 Brazil',
			'OTHER' => '🌍 Quốc gia khác / Other',
		];

		$options = [];
		foreach ( $rows as $code => $label ) {
			$options[] = ( $code ? $code : '' ) . '=>' . $label;
		}

		return $options;
	}

	public static function set_country_options() {
		// wpForo 3.x: country options live in field definition via get_country_select_values().
	}

	/* ── Sync wpForo ↔ wp_usermeta ── */

	public static function on_wpforo_create_user( $data ) {
		$user_id = get_current_user_id();
		if ( ! $user_id && ! empty( $data['wpfreg']['user_login'] ) ) {
			$user = get_user_by( 'login', sanitize_user( $data['wpfreg']['user_login'] ) );
			$user_id = $user ? (int) $user->ID : 0;
		}
		if ( ! $user_id && ! empty( $data['user_login'] ) ) {
			$user = get_user_by( 'login', sanitize_user( $data['user_login'] ) );
			$user_id = $user ? (int) $user->ID : 0;
		}
		self::sync_fields_to_usermeta( $user_id, $data );
	}

	public static function on_wpforo_update_profile( $user ) {
		$user_id = (int) ( $user['userid'] ?? get_current_user_id() );
		self::sync_fields_to_usermeta( $user_id, $user );
	}

	public static function on_wpforo_update_profile_fields( $userid, $data, $result ) {
		if ( $userid ) {
			self::sync_fields_to_usermeta( (int) $userid, is_array( $data ) ? $data : [] );
		}
	}

	public static function sync_on_wp_profile_update( $user_id, $old_data ) {
		$keys = [ 'pf_country', 'pf_phone', 'pf_city', 'pf_phone_public', 'pf_city_public' ];
		$has  = false;
		foreach ( $keys as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				$has = true;
				break;
			}
		}
		if ( $has ) {
			self::sync_usermeta_to_wpforo( $user_id );
		}
	}

	public static function sync_fields_to_usermeta( $user_id, $data = [] ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		if ( ! $user_id ) {
			return;
		}

		$values = self::extract_pf_fields( is_array( $data ) ? $data : [] );

		if ( function_exists( 'WPF' ) ) {
			$wpforo_data = WPF()->member->get_custom_fields( $user_id );
			foreach ( [ 'pf_country', 'pf_phone', 'pf_city' ] as $key ) {
				if ( empty( $values[ $key ] ) && ! empty( $wpforo_data[ $key ] ) ) {
					$values[ $key ] = sanitize_text_field( $wpforo_data[ $key ] );
				}
			}
		}

		foreach ( $values as $key => $value ) {
			if ( $value !== '' ) {
				update_user_meta( $user_id, $key, $value );
			}
		}

		if ( isset( $_POST['pf_phone_public'] ) ) {
			update_user_meta( $user_id, 'pf_phone_public', (int) $_POST['pf_phone_public'] );
		}
		if ( isset( $_POST['pf_city_public'] ) ) {
			update_user_meta( $user_id, 'pf_city_public', (int) $_POST['pf_city_public'] );
		}
	}

	public static function sync_usermeta_to_wpforo( $user_id ) {
		if ( ! $user_id || ! function_exists( 'WPF' ) ) {
			return;
		}

		$custom = [];
		foreach ( [ 'pf_country', 'pf_phone', 'pf_city' ] as $key ) {
			$val = get_user_meta( $user_id, $key, true );
			if ( $val !== '' ) {
				$custom[ $key ] = $val;
			}
		}
		if ( ! empty( $custom ) ) {
			WPF()->member->update_custom_fields( $user_id, $custom, false );
		}
	}

	private static function extract_pf_fields( $data = [] ) {
		$out  = [];
		$keys = [ 'pf_country', 'pf_phone', 'pf_city' ];

		foreach ( $keys as $key ) {
			if ( ! empty( $data[ $key ] ) ) {
				$out[ $key ] = sanitize_text_field( $data[ $key ] );
				continue;
			}
			if ( ! empty( $data['data'][ $key ] ) ) {
				$out[ $key ] = sanitize_text_field( $data['data'][ $key ] );
				continue;
			}
			if ( ! empty( $_POST['data'][ $key ] ) ) {
				$out[ $key ] = sanitize_text_field( wp_unslash( $_POST['data'][ $key ] ) );
				continue;
			}
			if ( ! empty( $_POST[ $key ] ) ) {
				$out[ $key ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
			}
		}

		return $out;
	}

	public static function merge_member_meta( $member ) {
		if ( empty( $member['userid'] ) ) {
			return $member;
		}
		$user_id = (int) $member['userid'];
		foreach ( [ 'pf_country', 'pf_phone', 'pf_city' ] as $key ) {
			$val = get_user_meta( $user_id, $key, true );
			if ( $val !== '' ) {
				$member[ $key ] = $val;
			}
		}

		return $member;
	}

	/* ── Validation ── */

	public static function validate_phone_on_create( $user_fields ) {
		$phone   = self::get_phone_from_context( $user_fields );
		$group_id = self::get_group_id_from_context( $user_fields );

		$error = self::phone_validation_error( $phone, $group_id );
		if ( $error ) {
			$user_fields['error'] = $error;
		}

		return $user_fields;
	}

	public static function validate_phone_on_update( $user ) {
		$phone    = self::get_phone_from_context( $user );
		$group_id = self::get_group_id_from_context( $user );

		$error = self::phone_validation_error( $phone, $group_id );
		if ( $error ) {
			$user['error']         = 1;
			$user['error_message'] = $error;
		}

		return $user;
	}

	private static function get_phone_from_context( $data ) {
		$extracted = self::extract_pf_fields( is_array( $data ) ? $data : [] );

		return trim( $extracted['pf_phone'] ?? '' );
	}

	private static function get_group_id_from_context( $data ) {
		if ( ! empty( $data['groupid'] ) ) {
			return (int) $data['groupid'];
		}

		$user_id = (int) ( $data['userid'] ?? get_current_user_id() );
		if ( $user_id && function_exists( 'WPF' ) ) {
			$member = WPF()->member->get_member( $user_id );

			return (int) ( $member['groupid'] ?? 0 );
		}

		return 0;
	}

	private static function phone_validation_error( $phone, $group_id ) {
		$required_groups = apply_filters( 'pf_required_phone_groups', [ 3, 4 ] );

		if ( ! in_array( $group_id, $required_groups, true ) ) {
			return '';
		}

		if ( $phone === '' ) {
			return 'Số điện thoại là bắt buộc đối với tài khoản Bác sĩ thú y / Cửa hàng.';
		}

		if ( ! preg_match( '/^[+\d\s\-\(\)]{7,20}$/', $phone ) ) {
			return 'Số điện thoại không hợp lệ.';
		}

		return '';
	}

	/* ── Privacy UI & helpers ── */

	public static function render_privacy_toggles() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$user_id   = get_current_user_id();
		$phone_pub = (int) get_user_meta( $user_id, 'pf_phone_public', true );
		$city_pub  = (int) get_user_meta( $user_id, 'pf_city_public', true );
		?>
		<div class="pf-privacy-section">
			<h4 class="pf-privacy-title">🔒 Quyền riêng tư / Privacy Settings</h4>
			<p class="pf-privacy-desc">
				Chọn thông tin nào hiển thị với các thành viên khác.
				Admin và Mod luôn có thể xem toàn bộ.
			</p>

			<div class="pf-privacy-row">
				<label class="pf-privacy-label">📞 Số điện thoại</label>
				<div class="pf-toggle-group">
					<label class="pf-toggle <?php echo $phone_pub ? 'active' : ''; ?>">
						<input type="radio" name="pf_phone_public" value="1" <?php checked( $phone_pub, 1 ); ?>>
						<span>👥 Hiện với mọi người</span>
					</label>
					<label class="pf-toggle <?php echo ! $phone_pub ? 'active' : ''; ?>">
						<input type="radio" name="pf_phone_public" value="0" <?php checked( $phone_pub, 0 ); ?>>
						<span>🔒 Chỉ mình tôi &amp; Admin</span>
					</label>
				</div>
			</div>

			<div class="pf-privacy-row">
				<label class="pf-privacy-label">📍 Thành phố / Khu vực</label>
				<div class="pf-toggle-group">
					<label class="pf-toggle <?php echo $city_pub ? 'active' : ''; ?>">
						<input type="radio" name="pf_city_public" value="1" <?php checked( $city_pub, 1 ); ?>>
						<span>👥 Hiện với mọi người</span>
					</label>
					<label class="pf-toggle <?php echo ! $city_pub ? 'active' : ''; ?>">
						<input type="radio" name="pf_city_public" value="0" <?php checked( $city_pub, 0 ); ?>>
						<span>🔒 Chỉ mình tôi &amp; Admin</span>
					</label>
				</div>
			</div>
		</div>
		<?php
	}

	public static function can_view_field( $target_user_id, $field, $viewer_id = 0 ) {
		if ( ! $viewer_id ) {
			$viewer_id = get_current_user_id();
		}

		if ( $viewer_id && (int) $viewer_id === (int) $target_user_id ) {
			return true;
		}

		if ( self::viewer_is_privileged( $viewer_id ) ) {
			return true;
		}

		$meta_key  = 'pf_' . $field . '_public';
		$is_public = (int) get_user_meta( $target_user_id, $meta_key, true );

		return $is_public === 1;
	}

	public static function get_user_field( $target_user_id, $field, $viewer_id = 0 ) {
		if ( ! self::can_view_field( $target_user_id, $field, $viewer_id ) ) {
			return null;
		}

		return get_user_meta( $target_user_id, 'pf_' . $field, true );
	}

	private static function viewer_is_privileged( $viewer_id ) {
		if ( ! $viewer_id ) {
			return false;
		}

		if ( user_can( $viewer_id, 'manage_options' ) ) {
			return true;
		}

		if ( user_can( $viewer_id, 'moderate_comments' ) ) {
			return true;
		}

		if ( function_exists( 'WPF' ) ) {
			$member = WPF()->member->get_member( $viewer_id );
			$gid    = (int) ( $member['groupid'] ?? 0 );
			if ( in_array( $gid, [ 1, 2, 4 ], true ) ) {
				return true;
			}
		}

		return false;
	}

	/* ── Profile card display ── */

	public static function render_member_card_fields( $user ) {
		$user_id = is_array( $user ) ? (int) ( $user['userid'] ?? 0 ) : (int) $user;
		if ( ! $user_id ) {
			return;
		}

		$viewer_id    = get_current_user_id();
		$country_code = get_user_meta( $user_id, 'pf_country', true );
		$country_name = self::get_country_display( $country_code );
		$phone        = self::get_user_field( $user_id, 'phone', $viewer_id );
		$city         = self::get_user_field( $user_id, 'city', $viewer_id );

		if ( ! $country_name && $phone === null && $city === null ) {
			return;
		}
		?>
		<div class="pf-member-extra-info">
			<?php if ( $country_name ) : ?>
			<div class="pf-info-row">
				<span class="pf-info-icon">🌍</span>
				<span class="pf-info-value"><?php echo esc_html( $country_name ); ?></span>
			</div>
			<?php endif; ?>

			<?php if ( $phone !== null && $phone !== '' ) : ?>
			<div class="pf-info-row">
				<span class="pf-info-icon">📞</span>
				<span class="pf-info-value"><?php echo esc_html( $phone ); ?></span>
			</div>
			<?php elseif ( self::viewer_is_privileged( $viewer_id ) ) : ?>
				<?php $hidden_phone = get_user_meta( $user_id, 'pf_phone', true ); ?>
				<?php if ( $hidden_phone ) : ?>
				<div class="pf-info-row pf-info-hidden">
					<span class="pf-info-icon">📞</span>
					<span class="pf-info-value pf-private"><?php echo esc_html( $hidden_phone ); ?> <em>(ẩn)</em></span>
				</div>
				<?php endif; ?>
			<?php endif; ?>

			<?php if ( $city !== null && $city !== '' ) : ?>
			<div class="pf-info-row">
				<span class="pf-info-icon">📍</span>
				<span class="pf-info-value"><?php echo esc_html( $city ); ?></span>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function get_country_display( $code ) {
		$map = [
			'VN'    => '🇻🇳 Việt Nam',
			'US'    => '🇺🇸 United States',
			'CN'    => '🇨🇳 China',
			'JP'    => '🇯🇵 Japan',
			'KR'    => '🇰🇷 South Korea',
			'TH'    => '🇹🇭 Thailand',
			'SG'    => '🇸🇬 Singapore',
			'MY'    => '🇲🇾 Malaysia',
			'PH'    => '🇵🇭 Philippines',
			'ID'    => '🇮🇩 Indonesia',
			'AU'    => '🇦🇺 Australia',
			'GB'    => '🇬🇧 United Kingdom',
			'DE'    => '🇩🇪 Germany',
			'FR'    => '🇫🇷 France',
			'CA'    => '🇨🇦 Canada',
			'RU'    => '🇷🇺 Russia',
			'IN'    => '🇮🇳 India',
			'BR'    => '🇧🇷 Brazil',
			'OTHER' => '🌍 Other',
		];

		return $map[ $code ] ?? ( $code ?: '' );
	}

	/* ── Admin user list columns ── */

	public static function add_user_columns( $columns ) {
		$columns['pf_country'] = '🌍 Country';
		$columns['pf_phone']   = '📞 Phone';
		$columns['pf_city']    = '📍 City';

		return $columns;
	}

	public static function render_user_columns( $value, $column, $user_id ) {
		switch ( $column ) {
			case 'pf_country':
				$code = get_user_meta( $user_id, 'pf_country', true );

				return $code ? esc_html( self::get_country_display( $code ) ) : '—';
			case 'pf_phone':
				$phone = get_user_meta( $user_id, 'pf_phone', true );
				$pub   = get_user_meta( $user_id, 'pf_phone_public', true ) ? '' : ' <em style="color:#94a3b8">(ẩn)</em>';

				return $phone ? esc_html( $phone ) . $pub : '—';
			case 'pf_city':
				$city = get_user_meta( $user_id, 'pf_city', true );
				$pub  = get_user_meta( $user_id, 'pf_city_public', true ) ? '' : ' <em style="color:#94a3b8">(ẩn)</em>';

				return $city ? esc_html( $city ) . $pub : '—';
		}

		return $value;
	}

	public static function sortable_user_columns( $columns ) {
		$columns['pf_country'] = 'pf_country';

		return $columns;
	}

	/* ── Assets ── */

	public static function enqueue_assets() {
		if ( ! function_exists( 'wpforo_get_option' ) && ! is_user_logged_in() ) {
			return;
		}

		wp_enqueue_style( 'pf-profile', PFU_URL . 'assets/css/pf-profile.css', [], PFU_VERSION );
		wp_enqueue_script( 'pf-profile', PFU_URL . 'assets/js/pf-profile.js', [ 'jquery' ], PFU_VERSION, true );
	}

	public static function enqueue_admin_assets( $hook ) {
		if ( ! in_array( $hook, [ 'profile.php', 'user-edit.php', 'users.php' ], true ) ) {
			return;
		}

		wp_enqueue_style( 'pf-profile', PFU_URL . 'assets/css/pf-profile.css', [], PFU_VERSION );
		wp_enqueue_script( 'pf-profile', PFU_URL . 'assets/js/pf-profile.js', [ 'jquery' ], PFU_VERSION, true );
	}

	/* ── Existing WP admin / REST / shortcode (unchanged behavior) ── */

	public static function set_default_meta( $user_id ) {
		$defaults = [
			'pf_preferred_lang' => 'vi',
			'pf_country'        => 'VN',
			'pf_phone'          => '',
			'pf_city'           => '',
			'pf_phone_public'   => '0',
			'pf_city_public'    => '1',
			'pf_pet_types'      => [],
		];

		foreach ( $defaults as $key => $value ) {
			if ( ! metadata_exists( 'user', $user_id, $key ) ) {
				update_user_meta( $user_id, $key, $value );
			}
		}
	}

	public static function render_profile_fields( $user ) {
		$lang         = get_user_meta( $user->ID, 'pf_preferred_lang', true );
		$country      = get_user_meta( $user->ID, 'pf_country', true );
		$phone        = get_user_meta( $user->ID, 'pf_phone', true );
		$city         = get_user_meta( $user->ID, 'pf_city', true );
		$phone_public = get_user_meta( $user->ID, 'pf_phone_public', true );
		$city_public  = get_user_meta( $user->ID, 'pf_city_public', true );
		$pet_types    = get_user_meta( $user->ID, 'pf_pet_types', true ) ?: [];
		$verified     = get_user_meta( $user->ID, 'pf_email_verified', true );
		?>
		<h2>🐾 PetForum — Thông tin thành viên</h2>
		<table class="form-table">
			<tr>
				<th>Trạng thái Email</th>
				<td><?php echo $verified === '1' ? '<span style="color:green">✅ Đã xác thực</span>' : '<span style="color:red">⚠️ Chưa xác thực</span>'; ?></td>
			</tr>
			<tr>
				<th><label for="pf_preferred_lang">Ngôn ngữ ưu tiên *</label></th>
				<td>
					<select name="pf_preferred_lang" id="pf_preferred_lang">
						<option value="vi" <?php selected( $lang, 'vi' ); ?>>🇻🇳 Tiếng Việt</option>
						<option value="en" <?php selected( $lang, 'en' ); ?>>🇺🇸 English</option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="pf_country">Quốc gia *</label></th>
				<td>
					<select name="pf_country" id="pf_country">
						<option value="">— Chọn quốc gia —</option>
						<?php foreach ( self::$countries as $code => $names ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $country, $code ); ?>>
							<?php echo esc_html( $names['vi'] ); ?> / <?php echo esc_html( $names['en'] ); ?>
						</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="pf_phone">Số điện thoại</label></th>
				<td>
					<input type="tel" name="pf_phone" id="pf_phone" value="<?php echo esc_attr( $phone ); ?>" class="regular-text">
					<label style="margin-left:12px">
						<input type="checkbox" name="pf_phone_public" value="1" <?php checked( $phone_public, '1' ); ?>>
						Cho thành viên khác xem
					</label>
				</td>
			</tr>
			<tr>
				<th><label for="pf_city">Thành phố / Khu vực</label></th>
				<td>
					<input type="text" name="pf_city" id="pf_city" value="<?php echo esc_attr( $city ); ?>" class="regular-text" placeholder="TP. Hồ Chí Minh">
					<label style="margin-left:12px">
						<input type="checkbox" name="pf_city_public" value="1" <?php checked( $city_public, '1' ); ?>>
						Cho thành viên khác xem
					</label>
				</td>
			</tr>
			<tr>
				<th>Thú cưng đang nuôi</th>
				<td>
					<?php
					$pets = [ 'dog' => '🐕 Chó', 'cat' => '🐈 Mèo', 'bird' => '🐦 Chim', 'other' => '🐾 Khác' ];
					foreach ( $pets as $val => $label ) :
						?>
					<label style="margin-right:16px">
						<input type="checkbox" name="pf_pet_types[]" value="<?php echo esc_attr( $val ); ?>"
							<?php checked( in_array( $val, (array) $pet_types, true ) ); ?>>
						<?php echo esc_html( $label ); ?>
					</label>
					<?php endforeach; ?>
				</td>
			</tr>
		</table>
		<?php wp_nonce_field( 'pf_save_profile_' . $user->ID, 'pf_profile_nonce' ); ?>
		<?php
	}

	public static function save_profile_fields( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}
		if ( isset( $_POST['pf_profile_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pf_profile_nonce'] ) ), 'pf_save_profile_' . $user_id ) ) {
			return;
		}

		update_user_meta( $user_id, 'pf_preferred_lang', sanitize_text_field( wp_unslash( $_POST['pf_preferred_lang'] ?? 'vi' ) ) );
		update_user_meta( $user_id, 'pf_country', sanitize_text_field( wp_unslash( $_POST['pf_country'] ?? '' ) ) );
		update_user_meta( $user_id, 'pf_phone', sanitize_text_field( wp_unslash( $_POST['pf_phone'] ?? '' ) ) );
		update_user_meta( $user_id, 'pf_city', sanitize_text_field( wp_unslash( $_POST['pf_city'] ?? '' ) ) );
		update_user_meta( $user_id, 'pf_phone_public', isset( $_POST['pf_phone_public'] ) ? '1' : '0' );
		update_user_meta( $user_id, 'pf_city_public', isset( $_POST['pf_city_public'] ) ? '1' : '0' );

		$pet_types = array_map( 'sanitize_text_field', (array) ( $_POST['pf_pet_types'] ?? [] ) );
		update_user_meta( $user_id, 'pf_pet_types', $pet_types );

		self::sync_usermeta_to_wpforo( $user_id );
	}

	public static function ajax_save_profile() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( [ 'message' => 'Bạn chưa đăng nhập.' ] );
		}

		check_ajax_referer( 'pf_save_profile_' . $user_id, 'nonce' );

		$_POST['pf_profile_nonce'] = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		self::save_profile_fields( $user_id );

		wp_send_json_success( [ 'message' => 'Đã lưu thông tin thành công!' ] );
	}

	public static function register_rest_endpoints() {
		register_rest_route( 'pf/v1', '/profile', [
			'methods'             => 'GET',
			'callback'            => [ __CLASS__, 'rest_get_profile' ],
			'permission_callback' => 'is_user_logged_in',
		] );

		register_rest_route( 'pf/v1', '/profile', [
			'methods'             => 'POST',
			'callback'            => [ __CLASS__, 'rest_update_profile' ],
			'permission_callback' => 'is_user_logged_in',
		] );
	}

	public static function rest_get_profile( $request ) {
		$user_id = get_current_user_id();

		return rest_ensure_response( [
			'pf_preferred_lang' => get_user_meta( $user_id, 'pf_preferred_lang', true ),
			'pf_country'        => get_user_meta( $user_id, 'pf_country', true ),
			'pf_phone'          => get_user_meta( $user_id, 'pf_phone', true ),
			'pf_city'           => get_user_meta( $user_id, 'pf_city', true ),
			'pf_phone_public'   => get_user_meta( $user_id, 'pf_phone_public', true ),
			'pf_city_public'    => get_user_meta( $user_id, 'pf_city_public', true ),
			'pf_pet_types'      => get_user_meta( $user_id, 'pf_pet_types', true ) ?: [],
		] );
	}

	public static function rest_update_profile( $request ) {
		$user_id = get_current_user_id();
		$params  = $request->get_json_params();

		if ( isset( $params['pf_preferred_lang'] ) ) {
			update_user_meta( $user_id, 'pf_preferred_lang', sanitize_text_field( $params['pf_preferred_lang'] ) );
		}
		if ( isset( $params['pf_country'] ) ) {
			update_user_meta( $user_id, 'pf_country', sanitize_text_field( $params['pf_country'] ) );
		}
		if ( isset( $params['pf_phone'] ) ) {
			update_user_meta( $user_id, 'pf_phone', sanitize_text_field( $params['pf_phone'] ) );
		}
		if ( isset( $params['pf_city'] ) ) {
			update_user_meta( $user_id, 'pf_city', sanitize_text_field( $params['pf_city'] ) );
		}
		if ( isset( $params['pf_pet_types'] ) ) {
			update_user_meta( $user_id, 'pf_pet_types', array_map( 'sanitize_text_field', (array) $params['pf_pet_types'] ) );
		}

		self::sync_usermeta_to_wpforo( $user_id );

		return rest_ensure_response( [ 'success' => true ] );
	}

	public static function render_frontend_profile() {
		if ( ! is_user_logged_in() ) {
			return '<p>' . sprintf(
				__( 'Vui lòng <a href="%s">đăng nhập</a> để xem hồ sơ.', 'pf' ),
				esc_url( wp_login_url( get_permalink() ) )
			) . '</p>';
		}

		$user = wp_get_current_user();
		ob_start();
		include PFU_DIR . 'templates/profile-fields.php';

		return ob_get_clean();
	}

	public static function get_country_name( $code, $lang = 'vi' ) {
		return self::$countries[ $code ][ $lang ] ?? $code;
	}
}
