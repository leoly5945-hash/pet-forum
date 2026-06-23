<?php
defined( 'ABSPATH' ) || exit;
?>
<div class="pf-terms-agree-section">
  <input type="checkbox" name="agree_terms" id="pf_agree_terms" style="display:none" required>
  <input type="checkbox" name="agree_vet_terms" id="pf_agree_vet_terms" style="display:none" required>

  <div class="pf-terms-row" id="pf-row-general">
    <span class="pf-terms-icon pf-terms-unchecked" id="pf-icon-general">📄</span>
    <div class="pf-terms-info">
      <strong class="pf-lang-vi">Điều khoản sử dụng chung</strong>
      <strong class="pf-lang-en" style="display:none">General Terms of Service</strong>
      <p class="pf-lang-vi" id="pf-status-general">Chưa đọc</p>
      <p class="pf-lang-en" id="pf-status-general-en" style="display:none">Not read yet</p>
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

  <div class="pf-terms-divider"></div>

  <div class="pf-terms-row" id="pf-row-vet">
    <span class="pf-terms-icon pf-terms-unchecked" id="pf-icon-vet">📄</span>
    <div class="pf-terms-info">
      <strong class="pf-lang-vi">Điều khoản Bác sĩ Thú y</strong>
      <strong class="pf-lang-en" style="display:none">Veterinary Expert Terms</strong>
      <p class="pf-lang-vi" id="pf-status-vet">Chưa đọc</p>
      <p class="pf-lang-en" id="pf-status-vet-en" style="display:none">Not read yet</p>
    </div>
    <button type="button" class="pf-read-terms-btn"
      data-terms="vet"
      data-target="pf_agree_vet_terms"
      data-icon="pf-icon-vet"
      data-status="pf-status-vet"
      data-status-en="pf-status-vet-en">
      <span class="pf-lang-vi">📖 Đọc &amp; Đồng ý</span>
      <span class="pf-lang-en" style="display:none">📖 Read &amp; Agree</span>
    </button>
  </div>
</div>
