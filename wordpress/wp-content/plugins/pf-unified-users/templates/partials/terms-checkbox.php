<?php
defined( 'ABSPATH' ) || exit;

$terms_url = home_url( '/terms/' );
?>
<div class="pf-terms-check">
  <label class="pf-checkbox-label">
    <input type="checkbox" id="pf_agree_terms" name="agree_terms" value="1">
    <span class="pf-check-text">
      <span class="pf-lang-vi">
        Tôi đã đọc và đồng ý với
        <a href="<?php echo esc_url( $terms_url ); ?>" target="_blank" rel="noopener" class="pf-terms-link">Điều khoản sử dụng</a>
        của Pet Forum.
      </span>
      <span class="pf-lang-en" style="display:none">
        I have read and agree to Pet Forum's
        <a href="<?php echo esc_url( $terms_url ); ?>" target="_blank" rel="noopener" class="pf-terms-link">Terms of Service</a>.
      </span>
    </span>
  </label>
</div>
