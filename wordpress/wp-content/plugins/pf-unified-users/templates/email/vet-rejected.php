<?php defined( 'ABSPATH' ) || exit; ?>
<div style="font-family:sans-serif;max-width:560px;margin:0 auto;padding:32px 24px">
  <p>Xin chào <strong><?php echo esc_html( $display_name ?? '' ); ?></strong>,</p>
  <p>Rất tiếc chúng tôi chưa thể xác minh hồ sơ Bác sĩ của bạn lúc này.</p>
  <p>Liên hệ: <?php echo esc_html( get_option( 'admin_email' ) ); ?></p>
</div>
