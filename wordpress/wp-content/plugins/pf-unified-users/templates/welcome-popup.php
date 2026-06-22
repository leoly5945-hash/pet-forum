<?php defined( 'ABSPATH' ) || exit;
$name = $user->display_name;
?>
<div id="pfWelcomePopup" class="pf-popup-overlay">
  <div class="pf-popup-box">
    <div class="pf-popup-confetti">🎉</div>
    <?php if ( $lang === 'vi' ) : ?>
      <h2>Chào mừng đến với cộng đồng! 🐾</h2>
      <p>Xin chào <strong><?php echo esc_html( $name ); ?></strong>! Tài khoản của bạn đã được xác thực thành công.</p>
      <p>Bắt đầu khám phá diễn đàn thú cưng ngay nào!</p>
      <div class="pf-popup-actions">
        <a href="<?php echo esc_url( home_url( '/community/' ) ); ?>" class="pf-popup-btn primary">Vào Diễn Đàn →</a>
        <button class="pf-popup-btn secondary" id="pfPopupClose">Để sau</button>
      </div>
    <?php else : ?>
      <h2>Welcome to the community! 🐾</h2>
      <p>Hello <strong><?php echo esc_html( $name ); ?></strong>! Your account has been verified successfully.</p>
      <p>Start exploring the pet forum now!</p>
      <div class="pf-popup-actions">
        <a href="<?php echo esc_url( home_url( '/community/' ) ); ?>" class="pf-popup-btn primary">Go to Forum →</a>
        <button class="pf-popup-btn secondary" id="pfPopupClose">Maybe later</button>
      </div>
    <?php endif; ?>
  </div>
</div>
