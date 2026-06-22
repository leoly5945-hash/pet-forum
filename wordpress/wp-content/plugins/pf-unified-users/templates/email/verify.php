<?php defined( 'ABSPATH' ) || exit; ?>
<div style="font-family:sans-serif;max-width:560px;margin:0 auto;padding:32px 24px;background:#fff;border-radius:12px;border:1px solid #e2e8f0">
  <h2 style="color:#f97316">🐾 <?php echo esc_html( $site_name ?? get_bloginfo( 'name' ) ); ?></h2>
  <p>Xin chào <strong><?php echo esc_html( $display_name ?? '' ); ?></strong>,</p>
  <p style="text-align:center;margin:32px 0">
    <a href="<?php echo esc_url( $verify_url ?? '#' ); ?>" style="background:#f97316;color:#fff;padding:14px 32px;border-radius:25px;text-decoration:none;font-weight:700">✅ Xác thực tài khoản</a>
  </p>
</div>
