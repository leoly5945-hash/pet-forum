<?php
defined( 'ABSPATH' ) || exit;

include PFU_DIR . 'templates/partials/register-i18n.php';
?>

<div class="pf-split-register">
  <?php include PFU_DIR . 'templates/partials/lang-switcher.php'; ?>

  <div class="pf-form-section" id="pfFormVet">
    <div class="pf-form-header pf-form-header--vet">
      <a class="pf-back-btn" id="pfBackVet" href="<?php echo esc_url( $register_base ); ?>"><?php echo esc_html( $t( 'back_btn' ) ); ?></a>
      <h2><?php echo esc_html( $t( 'vet_form_title' ) ); ?></h2>
      <p class="pf-vet-notice"><?php echo esc_html( $t( 'vet_notice' ) ); ?></p>
    </div>

    <form id="pfVetForm" class="pf-register-form" enctype="multipart/form-data" novalidate>
      <div class="pf-field-row">
        <div class="pf-field">
          <label><?php echo esc_html( $t( 'email_label' ) ); ?> <span class="req">*</span></label>
          <input type="email" name="email" placeholder="<?php echo esc_attr( $t( 'email_placeholder_vet' ) ); ?>" required autocomplete="email">
        </div>
        <div class="pf-field">
          <label><?php echo esc_html( $t( 'display_name_label' ) ); ?> <span class="req">*</span></label>
          <input type="text" name="username" placeholder="<?php echo esc_attr( $t( 'display_name_ph_vet' ) ); ?>" required>
        </div>
      </div>

      <div class="pf-field">
        <label><?php echo esc_html( $t( 'password_label' ) ); ?> <span class="req">*</span></label>
        <div class="pf-pass-wrap">
          <input type="password" name="password" placeholder="<?php echo esc_attr( $t( 'password_hint' ) ); ?>" required>
          <button type="button" class="pf-eye">👁</button>
        </div>
      </div>

      <div class="pf-field-row">
        <div class="pf-field">
          <label><?php echo esc_html( $t( 'phone_label' ) ); ?> <span class="req">*</span></label>
          <input type="tel" name="phone" placeholder="<?php echo esc_attr( $t( 'phone_placeholder' ) ); ?>" required>
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
        <label><?php echo esc_html( $t( 'workplace_label' ) ); ?> <span class="req">*</span></label>
        <input type="text" name="workplace" placeholder="<?php echo esc_attr( $t( 'workplace_ph' ) ); ?>" required>
      </div>

      <div class="pf-field">
        <label><?php echo esc_html( $t( 'specialty_label' ) ); ?></label>
        <input type="text" name="specialty" placeholder="<?php echo esc_attr( $t( 'specialty_ph' ) ); ?>">
      </div>

      <div class="pf-field pf-upload-field">
        <label><?php echo esc_html( $t( 'cert_label' ) ); ?> <span class="req">*</span></label>
        <div class="pf-upload-zone" id="pfUploadZone">
          <input type="file" name="certificate" id="pfCertFile" accept=".pdf,.jpg,.jpeg,.png" required>
          <div class="pf-upload-ui">
            <div class="pf-upload-icon">📎</div>
            <p><strong><?php echo esc_html( $t( 'cert_drag' ) ); ?></strong> <span class="pf-upload-link"><?php echo esc_html( $t( 'cert_browse' ) ); ?></span></p>
            <p class="pf-upload-hint"><?php echo esc_html( $t( 'cert_hint' ) ); ?></p>
          </div>
          <div class="pf-upload-preview" id="pfUploadPreview" style="display:none">
            <span class="pf-file-icon">📄</span>
            <span id="pfFileName"></span>
            <button type="button" id="pfRemoveFile">✕</button>
          </div>
        </div>
      </div>

      <div class="pf-field pf-terms pf-terms-vet-cred">
        <label class="pf-checkbox-label">
          <input type="checkbox" name="vet_credential_confirm" value="1">
          <span class="pf-check-text"><?php echo wp_kses_post( $t( 'terms_vet' ) ); ?></span>
        </label>
      </div>

      <?php include PFU_DIR . 'templates/partials/terms-agree-vet.php'; ?>

      <div class="pf-form-msg" id="pfVetMsg" style="display:none"></div>
      <button type="submit" class="pf-submit-btn pf-submit-btn--vet pf-btn-primary" id="pf-submit-vet" disabled>
        <span class="pf-lang-vi">🩺 Gửi hồ sơ xét duyệt</span>
        <span class="pf-lang-en" style="display:none">🩺 Submit for Review</span>
      </button>
    </form>

    <div id="pfVetSuccess" class="pf-pending-notice" style="display:none">
      <div class="pf-pending-icon">⏳</div>
      <h3><?php echo esc_html( $t( 'vet_success_title' ) ); ?></h3>
      <p><?php echo esc_html( $t( 'vet_success_msg' ) ); ?></p>
      <p style="color:#94a3b8;font-size:0.85rem"><?php echo esc_html( $t( 'vet_success_wait' ) ); ?></p>
    </div>
  </div>

  <?php include PFU_DIR . 'templates/partials/terms-modal.php'; ?>
</div>
