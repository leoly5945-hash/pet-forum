<?php
defined( 'ABSPATH' ) || exit;
?>
<div class="pf-terms-agree-section">
  <input type="checkbox" name="agree_terms" id="pf_agree_terms" style="display:none" required>

  <div class="pf-terms-status" id="pf-terms-status-member">
    <div class="pf-terms-row">
      <span class="pf-terms-icon pf-terms-unchecked" id="pf-icon-general">📄</span>
      <div class="pf-terms-info">
        <strong class="pf-lang-vi">Điều khoản sử dụng</strong>
        <strong class="pf-lang-en" style="display:none">Terms of Service</strong>
        <p class="pf-lang-vi" id="pf-status-general">Bạn cần đọc và đồng ý trước khi đăng ký</p>
        <p class="pf-lang-en" id="pf-status-general-en" style="display:none">You must read and agree before registering</p>
      </div>
      <button type="button" class="pf-read-terms-btn"
        data-terms="general"
        data-target="pf_agree_terms"
        data-icon="pf-icon-general"
        data-status="pf-status-general"
        data-status-en="pf-status-general-en">
        <span class="pf-lang-vi">📖 Đọc &amp; Đồng ý</span>
        <span class="pf-lang-en" style="display:none">📖 Read &amp; Agree</span>
      </button>
    </div>
  </div>
</div>
