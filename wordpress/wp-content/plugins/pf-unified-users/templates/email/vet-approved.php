<?php defined( 'ABSPATH' ) || exit; ?>
<div style="font-family:sans-serif;max-width:560px;margin:0 auto;padding:32px 24px">
  <h2 style="color:#15803d">🎉 Hồ sơ đã được xác minh!</h2>
  <p>Xin chào <strong><?php echo esc_html( $display_name ?? '' ); ?></strong>,</p>
  <p>Tài khoản Verified Vet của bạn đã được kích hoạt.</p>
  <p><a href="<?php echo esc_url( $login_url ?? wp_login_url() ); ?>">Đăng nhập →</a></p>
</div>
