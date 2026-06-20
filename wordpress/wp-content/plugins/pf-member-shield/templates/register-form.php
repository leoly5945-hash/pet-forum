<?php defined( 'ABSPATH' ) || exit; ?>

<div class="pf-register-wrapper">

  <div class="pf-social-login">
    <a href="<?php echo esc_url( pf_google_login_url() ); ?>" class="pf-btn-google">
      <svg viewBox="0 0 24 24" width="20"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
      <?php esc_html_e( 'Tiếp tục với Google', 'pf' ); ?>
    </a>

    <a href="<?php echo esc_url( pf_apple_login_url() ); ?>" class="pf-btn-apple">
      <svg viewBox="0 0 24 24" width="20" fill="#fff"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
      <?php esc_html_e( 'Tiếp tục với Apple', 'pf' ); ?>
    </a>
  </div>

  <div class="pf-divider"><span><?php esc_html_e( 'hoặc đăng ký bằng email', 'pf' ); ?></span></div>

  <form id="pfRegisterForm" class="pf-register-form" novalidate>
    <?php wp_nonce_field( 'pf_register_nonce', 'pf_nonce_field' ); ?>

    <div class="pf-field">
      <label for="pf_email"><?php esc_html_e( 'Email *', 'pf' ); ?></label>
      <input type="email" id="pf_email" name="email" placeholder="you@example.com" required autocomplete="email">
      <span class="pf-field-error" id="pf_email_error"></span>
    </div>

    <div class="pf-field">
      <label for="pf_username"><?php esc_html_e( 'Tên hiển thị', 'pf' ); ?> <small><?php esc_html_e( '(tùy chọn)', 'pf' ); ?></small></label>
      <input type="text" id="pf_username" name="username" placeholder="Nguyen Van A" autocomplete="username">
    </div>

    <div class="pf-field">
      <label for="pf_password"><?php esc_html_e( 'Mật khẩu *', 'pf' ); ?></label>
      <div class="pf-pass-wrap">
        <input type="password" id="pf_password" name="password" placeholder="Tối thiểu 8 ký tự" required autocomplete="new-password">
        <button type="button" class="pf-toggle-pass" aria-label="Hiện mật khẩu">👁</button>
      </div>
      <div class="pf-pass-strength" id="pfPassStrength"></div>
      <span class="pf-field-error" id="pf_pass_error"></span>
    </div>

    <div class="pf-field">
      <label for="pf_lang"><?php esc_html_e( 'Ngôn ngữ ưu tiên *', 'pf' ); ?></label>
      <select id="pf_lang" name="lang">
        <option value="vi" selected>🇻🇳 Tiếng Việt</option>
        <option value="en">🇺🇸 English</option>
      </select>
    </div>

    <div class="cf-turnstile"
         data-sitekey="<?php echo esc_attr( get_option( 'pf_turnstile_site_key', '' ) ); ?>"
         data-theme="light"
         data-language="vi">
    </div>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

    <div class="pf-field pf-terms">
      <label>
        <input type="checkbox" id="pf_terms" required>
        <?php
        printf(
          wp_kses(
            __( 'Tôi đồng ý với <a href="%s" target="_blank">Điều khoản sử dụng</a> và <a href="%s" target="_blank">Chính sách bảo mật</a>', 'pf' ),
            [ 'a' => [ 'href' => [], 'target' => [] ] ]
          ),
          esc_url( home_url( '/terms/' ) ),
          esc_url( home_url( '/privacy/' ) )
        );
        ?>
      </label>
    </div>

    <div id="pfRegisterMessage" class="pf-form-message" style="display:none"></div>

    <button type="submit" id="pfRegisterBtn" class="pf-btn-submit">
      <?php esc_html_e( 'Tạo tài khoản miễn phí 🐾', 'pf' ); ?>
    </button>

    <p class="pf-login-link">
      <?php
      printf(
        wp_kses( __( 'Đã có tài khoản? <a href="%s">Đăng nhập ngay</a>', 'pf' ), [ 'a' => [ 'href' => [] ] ] ),
        esc_url( wp_login_url( home_url( '/' ) ) )
      );
      ?>
    </p>
  </form>
</div>
