<?php
defined( 'ABSPATH' ) || exit;

$vet_terms_url = home_url( '/terms-for-vets/' );
?>
<div class="pf-terms-check pf-terms-check--vet">
  <label class="pf-checkbox-label">
    <input type="checkbox" id="pf_agree_vet_terms" name="agree_vet_terms" value="1">
    <span class="pf-check-text">
      <span class="pf-lang-vi">
        Tôi đã đọc và <strong>cam kết tuân thủ</strong>
        <a href="<?php echo esc_url( $vet_terms_url ); ?>" target="_blank" rel="noopener" class="pf-terms-link pf-vet-terms-link">
          Điều khoản dành cho Chuyên gia Thú y
        </a>
        — bao gồm trách nhiệm về tính chính xác y tế và đạo đức nghề nghiệp.
      </span>
      <span class="pf-lang-en" style="display:none">
        I have read and <strong>commit to complying with</strong> the
        <a href="<?php echo esc_url( $vet_terms_url ); ?>" target="_blank" rel="noopener" class="pf-terms-link pf-vet-terms-link">
          Terms for Veterinary Experts
        </a>
        — including responsibilities for medical accuracy and professional ethics.
      </span>
    </span>
  </label>
</div>
