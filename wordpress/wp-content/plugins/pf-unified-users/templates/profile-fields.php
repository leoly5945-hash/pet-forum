<?php
defined( 'ABSPATH' ) || exit;

$lang         = get_user_meta( $user->ID, 'pf_preferred_lang', true ) ?: 'vi';
$country      = get_user_meta( $user->ID, 'pf_country', true );
$phone        = get_user_meta( $user->ID, 'pf_phone', true );
$city         = get_user_meta( $user->ID, 'pf_city', true );
$phone_public = get_user_meta( $user->ID, 'pf_phone_public', true );
$city_public  = get_user_meta( $user->ID, 'pf_city_public', true );
$pet_types    = get_user_meta( $user->ID, 'pf_pet_types', true ) ?: [];
$pets         = [ 'dog' => '🐕 Chó', 'cat' => '🐈 Mèo', 'bird' => '🐦 Chim', 'other' => '🐾 Khác' ];
?>

<div class="pf-register-wrapper pf-profile-wrapper">
  <h2><?php esc_html_e( 'Hồ sơ thành viên', 'pf' ); ?></h2>
  <form id="pfProfileForm" class="pf-register-form">
    <div class="pf-field">
      <label for="pf_profile_lang"><?php esc_html_e( 'Ngôn ngữ ưu tiên', 'pf' ); ?></label>
      <select id="pf_profile_lang" name="pf_preferred_lang">
        <option value="vi" <?php selected( $lang, 'vi' ); ?>>🇻🇳 Tiếng Việt</option>
        <option value="en" <?php selected( $lang, 'en' ); ?>>🇺🇸 English</option>
      </select>
    </div>

    <div class="pf-field">
      <label for="pf_profile_country"><?php esc_html_e( 'Quốc gia', 'pf' ); ?></label>
      <select id="pf_profile_country" name="pf_country">
        <option value="">— Chọn quốc gia —</option>
        <?php foreach ( PF_User_Meta::$countries as $code => $names ) : ?>
        <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $country, $code ); ?>>
          <?php echo esc_html( $names[ $lang ] ?? $names['vi'] ); ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="pf-field">
      <label for="pf_profile_phone"><?php esc_html_e( 'Số điện thoại', 'pf' ); ?></label>
      <input type="tel" id="pf_profile_phone" name="pf_phone" value="<?php echo esc_attr( $phone ); ?>">
      <label class="pf-terms">
        <input type="checkbox" name="pf_phone_public" value="1" <?php checked( $phone_public, '1' ); ?>>
        <?php esc_html_e( 'Cho thành viên khác xem', 'pf' ); ?>
      </label>
    </div>

    <div class="pf-field">
      <label for="pf_profile_city"><?php esc_html_e( 'Thành phố / Khu vực', 'pf' ); ?></label>
      <input type="text" id="pf_profile_city" name="pf_city" value="<?php echo esc_attr( $city ); ?>">
      <label class="pf-terms">
        <input type="checkbox" name="pf_city_public" value="1" <?php checked( $city_public, '1' ); ?>>
        <?php esc_html_e( 'Cho thành viên khác xem', 'pf' ); ?>
      </label>
    </div>

    <div class="pf-field">
      <label><?php esc_html_e( 'Thú cưng đang nuôi', 'pf' ); ?></label>
      <?php foreach ( $pets as $val => $label ) : ?>
      <label class="pf-terms" style="display:inline-block;margin-right:12px">
        <input type="checkbox" name="pf_pet_types[]" value="<?php echo esc_attr( $val ); ?>"
          <?php checked( in_array( $val, (array) $pet_types, true ) ); ?>>
        <?php echo esc_html( $label ); ?>
      </label>
      <?php endforeach; ?>
    </div>

    <div id="pfProfileMessage" class="pf-form-message" style="display:none"></div>
    <button type="submit" class="pf-btn-submit"><?php esc_html_e( 'Lưu hồ sơ', 'pf' ); ?></button>
  </form>
</div>

<script>
(function($) {
  $('#pfProfileForm').on('submit', function(e) {
    e.preventDefault();
    $.post('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
      action: 'pf_save_profile',
      nonce: '<?php echo esc_js( wp_create_nonce( 'pf_save_profile_' . $user->ID ) ); ?>',
      pf_profile_nonce: '<?php echo esc_js( wp_create_nonce( 'pf_save_profile_' . $user->ID ) ); ?>',
      pf_preferred_lang: $('#pf_profile_lang').val(),
      pf_country: $('#pf_profile_country').val(),
      pf_phone: $('#pf_profile_phone').val(),
      pf_city: $('#pf_profile_city').val(),
      pf_phone_public: $('input[name="pf_phone_public"]').is(':checked') ? '1' : '',
      pf_city_public: $('input[name="pf_city_public"]').is(':checked') ? '1' : '',
      pf_pet_types: $('input[name="pf_pet_types[]"]:checked').map(function() { return this.value; }).get()
    }, function(res) {
      var box = $('#pfProfileMessage');
      if (res.success) {
        box.text(res.data.message).removeClass('error').addClass('success').show();
      } else {
        box.text(res.data.message || 'Lỗi').removeClass('success').addClass('error').show();
      }
    });
  });
})(jQuery);
</script>
