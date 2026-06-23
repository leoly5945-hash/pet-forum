<?php
defined( 'ABSPATH' ) || exit;

include PFU_DIR . 'templates/partials/register-i18n.php';

$pet_types = [
	'dog'   => 'pet_dog',
	'cat'   => 'pet_cat',
	'bird'  => 'pet_bird',
	'other' => 'pet_other',
];
?>

<div class="pf-split-register">
  <?php include PFU_DIR . 'templates/partials/lang-switcher.php'; ?>

  <div class="pf-form-section" id="pfFormMember">
    <div class="pf-form-header">
      <a class="pf-back-btn" id="pfBackMember" href="<?php echo esc_url( $register_base ); ?>"><?php echo esc_html( $t( 'back_btn' ) ); ?></a>
      <h2><?php echo esc_html( $t( 'member_form_title' ) ); ?></h2>
    </div>

    <form id="pfMemberForm" class="pf-register-form" novalidate>
      <div class="pf-field-row">
        <div class="pf-field">
          <label><?php echo esc_html( $t( 'email_label' ) ); ?> <span class="req">*</span></label>
          <input type="email" name="email" placeholder="<?php echo esc_attr( $t( 'email_placeholder' ) ); ?>" required autocomplete="email">
        </div>
        <div class="pf-field">
          <label><?php echo esc_html( $t( 'display_name_label' ) ); ?></label>
          <input type="text" name="username" placeholder="<?php echo esc_attr( $t( 'display_name_optional' ) ); ?>">
        </div>
      </div>

      <div class="pf-field">
        <label><?php echo esc_html( $t( 'password_label' ) ); ?> <span class="req">*</span></label>
        <div class="pf-pass-wrap">
          <input type="password" name="password" placeholder="<?php echo esc_attr( $t( 'password_hint' ) ); ?>" required>
          <button type="button" class="pf-eye">👁</button>
        </div>
        <div class="pf-strength-bar"><div class="pf-strength-fill"></div></div>
      </div>

      <div class="pf-field-row">
        <div class="pf-field">
          <label><?php echo esc_html( $t( 'country_label' ) ); ?> <span class="req">*</span></label>
          <select name="country" required>
            <option value=""><?php echo esc_html( $t( 'country_select' ) ); ?></option>
            <option value="VN">🇻🇳 Việt Nam</option>
            <option value="US">🇺🇸 United States</option>
            <option value="CN">🇨🇳 China</option>
            <option value="JP">🇯🇵 Japan</option>
            <option value="KR">🇰🇷 South Korea</option>
            <option value="TH">🇹🇭 Thailand</option>
            <option value="SG">🇸🇬 Singapore</option>
            <option value="AU">🇦🇺 Australia</option>
            <option value="OTHER">🌍 <?php echo esc_html( $pf_lang === 'en' ? 'Other' : 'Khác' ); ?></option>
          </select>
        </div>
        <div class="pf-field">
          <label><?php echo esc_html( $t( 'lang_label' ) ); ?></label>
          <select name="lang">
            <option value="vi" <?php selected( $pf_lang, 'vi' ); ?>><?php echo esc_html( $t( 'lang_vi' ) ); ?></option>
            <option value="en" <?php selected( $pf_lang, 'en' ); ?>><?php echo esc_html( $t( 'lang_en' ) ); ?></option>
          </select>
        </div>
      </div>

      <div class="pf-field">
        <label><?php echo esc_html( $t( 'pet_types_label' ) ); ?></label>
        <div class="pf-pet-checks">
          <?php foreach ( $pet_types as $value => $label_key ) : ?>
          <label class="pf-check-pill">
            <input type="checkbox" name="pet_types[]" value="<?php echo esc_attr( $value ); ?>">
            <span><?php echo esc_html( $t( $label_key ) ); ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="pf-form-msg" id="pfMemberMsg" style="display:none"></div>
      <?php include PFU_DIR . 'templates/partials/terms-agree-member.php'; ?>
      <button type="submit" class="pf-submit-btn pf-btn-primary" id="pf-submit-member" disabled>
        <span class="pf-lang-vi">🐾 Tạo tài khoản</span>
        <span class="pf-lang-en" style="display:none">🐾 Create Account</span>
      </button>
      <p class="pf-login-hint"><?php echo esc_html( $t( 'have_account' ) ); ?> <a href="<?php echo esc_url( wp_login_url() ); ?>"><?php echo esc_html( $t( 'login_link' ) ); ?> →</a></p>
    </form>

    <div id="pfMemberSuccess" class="pf-success-box" style="display:none">
      <div class="pf-success-icon">📬</div>
      <h3><?php echo esc_html( $t( 'member_success_box' ) ); ?></h3>
      <p id="pfMemberSuccessMsg"></p>
    </div>
  </div>

  <?php include PFU_DIR . 'templates/partials/terms-modal.php'; ?>
</div>
