<?php defined( 'ABSPATH' ) || exit; ?>

<div class="pf-split-register">

  <?php if ( ! empty( $_GET['pf_status'] ) && sanitize_text_field( wp_unslash( $_GET['pf_status'] ) ) === 'vet_pending' ) : ?>
  <div class="pf-pending-notice">
    <div class="pf-pending-icon">⏳</div>
    <h3>Hồ sơ của bạn đang chờ duyệt</h3>
    <p>Ban quản trị sẽ xem xét và phản hồi trong <strong>24-48 giờ</strong> qua email.</p>
  </div>
  <?php return; endif; ?>

  <div class="pf-type-selector" id="pfTypeSelector">
    <h2 class="pf-register-headline">Bạn muốn tham gia với tư cách nào?</h2>
    <p class="pf-register-sub">Chọn một trong hai loại tài khoản bên dưới</p>

    <div class="pf-type-cards">
      <div class="pf-type-card" id="pfCardMember" data-type="member">
        <div class="pf-type-icon">🐾</div>
        <h3>Thành viên nuôi Pet</h3>
        <p>Tham gia chia sẻ kinh nghiệm, đặt câu hỏi và kết nối cộng đồng yêu thú cưng.</p>
        <ul class="pf-type-perks">
          <li>✓ Tham gia thảo luận ngay</li>
          <li>✓ Đăng bài &amp; chia sẻ ảnh</li>
          <li>✓ Nhận tư vấn từ bác sĩ</li>
        </ul>
        <button type="button" class="pf-select-type-btn" data-target="member">Đăng ký Thành viên →</button>
      </div>

      <div class="pf-type-card pf-type-card--vet" id="pfCardVet" data-type="vet">
        <div class="pf-type-badge">Chuyên gia</div>
        <div class="pf-type-icon">🩺</div>
        <h3>Bác sĩ Thú y / Chuyên gia</h3>
        <p>Chia sẻ chuyên môn, tư vấn cho thành viên và xây dựng thương hiệu cá nhân.</p>
        <ul class="pf-type-perks">
          <li>✓ Tích xanh ✔ xác minh</li>
          <li>✓ Nhãn "Verified Vet"</li>
          <li>✓ Ưu tiên hiển thị</li>
        </ul>
        <button type="button" class="pf-select-type-btn pf-btn-vet" data-target="vet">Đăng ký Bác sĩ →</button>
      </div>
    </div>
  </div>

  <div class="pf-form-section" id="pfFormMember" style="display:none">
    <div class="pf-form-header">
      <button type="button" class="pf-back-btn" id="pfBackMember">← Quay lại</button>
      <h2>🐾 Đăng ký Thành viên</h2>
    </div>

    <form id="pfMemberForm" class="pf-register-form" novalidate>
      <div class="pf-field-row">
        <div class="pf-field">
          <label>Email <span class="req">*</span></label>
          <input type="email" name="email" placeholder="you@gmail.com" required autocomplete="email">
        </div>
        <div class="pf-field">
          <label>Tên hiển thị</label>
          <input type="text" name="username" placeholder="Tên của bạn (tùy chọn)">
        </div>
      </div>

      <div class="pf-field">
        <label>Mật khẩu <span class="req">*</span></label>
        <div class="pf-pass-wrap">
          <input type="password" name="password" placeholder="Tối thiểu 8 ký tự" required>
          <button type="button" class="pf-eye">👁</button>
        </div>
        <div class="pf-strength-bar"><div class="pf-strength-fill"></div></div>
      </div>

      <div class="pf-field-row">
        <div class="pf-field">
          <label>Quốc gia <span class="req">*</span></label>
          <select name="country" required>
            <option value="">— Chọn quốc gia —</option>
            <option value="VN">🇻🇳 Việt Nam</option>
            <option value="US">🇺🇸 United States</option>
            <option value="CN">🇨🇳 China</option>
            <option value="JP">🇯🇵 Japan</option>
            <option value="KR">🇰🇷 South Korea</option>
            <option value="TH">🇹🇭 Thailand</option>
            <option value="SG">🇸🇬 Singapore</option>
            <option value="AU">🇦🇺 Australia</option>
            <option value="OTHER">🌍 Khác</option>
          </select>
        </div>
        <div class="pf-field">
          <label>Ngôn ngữ</label>
          <select name="lang">
            <option value="vi">🇻🇳 Tiếng Việt</option>
            <option value="en">🇺🇸 English</option>
          </select>
        </div>
      </div>

      <div class="pf-field">
        <label>Loại thú cưng đang nuôi</label>
        <div class="pf-pet-checks">
          <?php foreach ( [ 'dog' => '🐕 Chó', 'cat' => '🐈 Mèo', 'bird' => '🐦 Chim', 'other' => '🐾 Khác' ] as $v => $l ) : ?>
          <label class="pf-check-pill">
            <input type="checkbox" name="pet_types[]" value="<?php echo esc_attr( $v ); ?>">
            <span><?php echo esc_html( $l ); ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="pf-form-msg" id="pfMemberMsg" style="display:none"></div>
      <button type="submit" class="pf-submit-btn" id="pfMemberSubmit">🐾 Tạo tài khoản ngay</button>
      <p class="pf-login-hint">Đã có tài khoản? <a href="<?php echo esc_url( wp_login_url() ); ?>">Đăng nhập →</a></p>
    </form>

    <div id="pfMemberSuccess" class="pf-success-box" style="display:none">
      <div class="pf-success-icon">📬</div>
      <h3>Kiểm tra hộp thư của bạn!</h3>
      <p id="pfMemberSuccessMsg"></p>
    </div>
  </div>

  <div class="pf-form-section" id="pfFormVet" style="display:none">
    <div class="pf-form-header pf-form-header--vet">
      <button type="button" class="pf-back-btn" id="pfBackVet">← Quay lại</button>
      <h2>🩺 Đăng ký Bác sĩ / Chuyên gia</h2>
      <p class="pf-vet-notice">⚠️ Tài khoản sẽ được kích hoạt sau khi Admin xác minh bằng cấp (24-48h)</p>
    </div>

    <form id="pfVetForm" class="pf-register-form" enctype="multipart/form-data" novalidate>
      <div class="pf-field-row">
        <div class="pf-field">
          <label>Email <span class="req">*</span></label>
          <input type="email" name="email" placeholder="dr.name@hospital.com" required>
        </div>
        <div class="pf-field">
          <label>Tên hiển thị <span class="req">*</span></label>
          <input type="text" name="username" placeholder="BS. Nguyễn Văn A" required>
        </div>
      </div>

      <div class="pf-field">
        <label>Mật khẩu <span class="req">*</span></label>
        <div class="pf-pass-wrap">
          <input type="password" name="password" placeholder="Tối thiểu 8 ký tự" required>
          <button type="button" class="pf-eye">👁</button>
        </div>
      </div>

      <div class="pf-field-row">
        <div class="pf-field">
          <label>Số điện thoại <span class="req">*</span></label>
          <input type="tel" name="phone" placeholder="+84 901 234 567" required>
        </div>
        <div class="pf-field">
          <label>Ngôn ngữ</label>
          <select name="lang">
            <option value="vi">🇻🇳 Tiếng Việt</option>
            <option value="en">🇺🇸 English</option>
          </select>
        </div>
      </div>

      <div class="pf-field">
        <label>Nơi công tác <span class="req">*</span></label>
        <input type="text" name="workplace" placeholder="Phòng khám / Bệnh viện thú y" required>
      </div>

      <div class="pf-field">
        <label>Chuyên khoa</label>
        <input type="text" name="specialty" placeholder="VD: Thú cưng nhỏ, Chim cảnh...">
      </div>

      <div class="pf-field pf-upload-field">
        <label>Bằng cấp / Chứng chỉ hành nghề <span class="req">*</span></label>
        <div class="pf-upload-zone" id="pfUploadZone">
          <input type="file" name="certificate" id="pfCertFile" accept=".pdf,.jpg,.jpeg,.png" required>
          <div class="pf-upload-ui">
            <div class="pf-upload-icon">📎</div>
            <p><strong>Kéo thả file vào đây</strong> hoặc <span class="pf-upload-link">chọn file</span></p>
            <p class="pf-upload-hint">PDF, JPG, PNG — Tối đa 5MB</p>
          </div>
          <div class="pf-upload-preview" id="pfUploadPreview" style="display:none">
            <span class="pf-file-icon">📄</span>
            <span id="pfFileName"></span>
            <button type="button" id="pfRemoveFile">✕</button>
          </div>
        </div>
      </div>

      <div class="pf-field pf-terms">
        <label>
          <input type="checkbox" name="terms" required>
          Tôi cam kết thông tin và bằng cấp cung cấp là <strong>chính xác và hợp lệ</strong>.
        </label>
      </div>

      <div class="pf-form-msg" id="pfVetMsg" style="display:none"></div>
      <button type="submit" class="pf-submit-btn pf-submit-btn--vet" id="pfVetSubmit">🩺 Gửi hồ sơ để xác minh</button>
    </form>

    <div id="pfVetSuccess" class="pf-pending-notice" style="display:none">
      <div class="pf-pending-icon">⏳</div>
      <h3>Hồ sơ đã được gửi thành công!</h3>
      <p>Chúng tôi sẽ xem xét và phản hồi qua email trong <strong>24-48 giờ</strong>.</p>
      <p style="color:#94a3b8;font-size:0.85rem">Trong thời gian chờ, tài khoản chưa thể đăng nhập.</p>
    </div>
  </div>

</div>
