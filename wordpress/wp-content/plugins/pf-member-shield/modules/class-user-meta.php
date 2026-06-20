<?php
defined( 'ABSPATH' ) || exit;

class PF_User_Meta {

	public static $countries = [
		'VN'    => [ 'vi' => 'Việt Nam', 'en' => 'Vietnam' ],
		'US'    => [ 'vi' => 'Hoa Kỳ', 'en' => 'United States' ],
		'CN'    => [ 'vi' => 'Trung Quốc', 'en' => 'China' ],
		'JP'    => [ 'vi' => 'Nhật Bản', 'en' => 'Japan' ],
		'KR'    => [ 'vi' => 'Hàn Quốc', 'en' => 'South Korea' ],
		'TH'    => [ 'vi' => 'Thái Lan', 'en' => 'Thailand' ],
		'SG'    => [ 'vi' => 'Singapore', 'en' => 'Singapore' ],
		'AU'    => [ 'vi' => 'Úc', 'en' => 'Australia' ],
		'GB'    => [ 'vi' => 'Vương quốc Anh', 'en' => 'United Kingdom' ],
		'DE'    => [ 'vi' => 'Đức', 'en' => 'Germany' ],
		'FR'    => [ 'vi' => 'Pháp', 'en' => 'France' ],
		'CA'    => [ 'vi' => 'Canada', 'en' => 'Canada' ],
		'RU'    => [ 'vi' => 'Nga', 'en' => 'Russia' ],
		'IN'    => [ 'vi' => 'Ấn Độ', 'en' => 'India' ],
		'BR'    => [ 'vi' => 'Brazil', 'en' => 'Brazil' ],
		'PH'    => [ 'vi' => 'Philippines', 'en' => 'Philippines' ],
		'MY'    => [ 'vi' => 'Malaysia', 'en' => 'Malaysia' ],
		'ID'    => [ 'vi' => 'Indonesia', 'en' => 'Indonesia' ],
		'OTHER' => [ 'vi' => 'Khác', 'en' => 'Other' ],
	];

	public static function init() {
		add_action( 'user_register', [ __CLASS__, 'set_default_meta' ], 10 );

		add_action( 'show_user_profile', [ __CLASS__, 'render_profile_fields' ] );
		add_action( 'edit_user_profile', [ __CLASS__, 'render_profile_fields' ] );
		add_action( 'personal_options_update', [ __CLASS__, 'save_profile_fields' ] );
		add_action( 'edit_user_profile_update', [ __CLASS__, 'save_profile_fields' ] );

		add_action( 'rest_api_init', [ __CLASS__, 'register_rest_endpoints' ] );

		add_shortcode( 'pf_user_profile', [ __CLASS__, 'render_frontend_profile' ] );

		add_action( 'wp_ajax_pf_save_profile', [ __CLASS__, 'ajax_save_profile' ] );
	}

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
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pf_profile_nonce'] ?? '' ) ), 'pf_save_profile_' . $user_id ) ) {
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
		include PF_SHIELD_DIR . 'templates/profile-fields.php';
		return ob_get_clean();
	}

	public static function get_country_name( $code, $lang = 'vi' ) {
		return self::$countries[ $code ][ $lang ] ?? $code;
	}
}
