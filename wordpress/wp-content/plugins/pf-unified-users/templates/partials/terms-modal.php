<?php
defined( 'ABSPATH' ) || exit;
?>
<div id="pf-terms-modal-overlay" style="display:none" role="dialog" aria-modal="true" aria-labelledby="pf-modal-title">
  <div id="pf-terms-modal">
    <div class="ptm-header">
      <div>
        <span id="ptm-icon">📄</span>
        <h2 id="pf-modal-title">Điều khoản sử dụng</h2>
      </div>
      <button type="button" id="ptm-close" aria-label="Đóng">✕</button>
    </div>

    <div class="ptm-scroll-hint" id="ptm-scroll-hint">
      <span class="pf-lang-vi">👇 Cuộn xuống để đọc toàn bộ điều khoản</span>
      <span class="pf-lang-en" style="display:none">👇 Scroll down to read all terms</span>
    </div>

    <div class="ptm-body" id="ptm-body">
      <div class="ptm-loading">⏳ Đang tải...</div>
    </div>

    <div class="ptm-progress-wrap">
      <div class="ptm-progress-bar" id="ptm-progress"></div>
      <span class="ptm-progress-label" id="ptm-progress-label">0%</span>
    </div>

    <div class="ptm-footer">
      <p class="ptm-read-note" id="ptm-read-note">
        <span class="pf-lang-vi">Đọc đến cuối để kích hoạt nút đồng ý</span>
        <span class="pf-lang-en" style="display:none">Read to the end to enable the agree button</span>
      </p>
      <button type="button" id="ptm-agree-btn" disabled>
        <span class="pf-lang-vi">✅ Tôi đã đọc và đồng ý</span>
        <span class="pf-lang-en" style="display:none">✅ I have read and agree</span>
      </button>
    </div>
  </div>
</div>
