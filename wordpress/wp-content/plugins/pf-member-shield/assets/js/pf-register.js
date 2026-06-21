(function ($) {
  'use strict';

  $(document).on('click', '.pf-toggle-pass', function () {
    const input = $(this).siblings('input');
    const type = input.attr('type') === 'password' ? 'text' : 'password';
    input.attr('type', type);
    $(this).text(type === 'password' ? '👁' : '🙈');
  });

  $(document).on('input', '#pf_password', function () {
    const val = $(this).val();
    let score = 0;
    if (val.length >= 8)           score++;
    if (/[A-Z]/.test(val))         score++;
    if (/[0-9]/.test(val))         score++;
    if (/[^A-Za-z0-9]/.test(val))  score++;

    const colors  = ['#ef4444', '#f97316', '#eab308', '#22c55e'];
    const widths  = ['25%', '50%', '75%', '100%'];
    const labels  = ['Yếu', 'Trung bình', 'Khá', 'Mạnh'];
    const meter   = document.getElementById('pfPassStrength');
    const hint    = document.getElementById('pfPassHint');
    const idx     = Math.max(0, score - 1);

    if (val.length > 0 && meter) {
      meter.style.setProperty('--strength-width', widths[idx]);
      meter.style.setProperty('--strength-color', colors[idx]);
      if (hint) {
        hint.textContent = 'Độ mạnh: ' + labels[idx];
        hint.style.color = colors[idx];
      }
    } else if (meter) {
      meter.style.setProperty('--strength-width', '0%');
      if (hint) hint.textContent = '';
    }
  });

  $(document).on('submit', '#pfRegisterForm', function (e) {
    e.preventDefault();
    $('.pf-field-error').text('');

    const email    = $('#pf_email').val().trim();
    const password = $('#pf_password').val();
    const username = $('#pf_username').val().trim();
    const lang     = $('#pf_lang').val();
    const terms    = $('#pf_terms').is(':checked');
    let valid = true;

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      $('#pf_email_error').text(pfRegData.strings.error_email || 'Email không hợp lệ.');
      valid = false;
    }
    if (password.length < 8) {
      $('#pf_pass_error').text(pfRegData.strings.error_pass || 'Mật khẩu tối thiểu 8 ký tự.');
      valid = false;
    }
    if (!terms) {
      showMsg('Vui lòng đồng ý với điều khoản sử dụng.', 'error');
      valid = false;
    }
    if (!valid) return;

    const cfToken = document.querySelector('[name="cf-turnstile-response"]')?.value || '';
    const btn = $('#pfRegisterBtn');
    btn.text('⏳ Đang xử lý...').prop('disabled', true);
    $('#pfRegisterMessage').hide();

    $.post(pfRegData.ajaxUrl, {
      action:   'pf_register',
      nonce:    pfRegData.nonce,
      email,
      password,
      username,
      lang,
      cf_token: cfToken,
    })
    .done(function (res) {
      if (res.success) {
        $('#pfRegisterForm').slideUp(300);
        $('#pfSuccessEmail').html(
          'Chúng tôi đã gửi email xác thực đến <strong>' + email + '</strong>.<br>' +
          'Nhấn vào link trong email để kích hoạt tài khoản.'
        );
        $('#pfRegisterSuccess').slideDown(300);
        $('#pfResendBtn').data('email', email);
      } else {
        showMsg(res.data.message, 'error');
        btn.text('🐾 Tạo tài khoản miễn phí').prop('disabled', false);
        if (typeof turnstile !== 'undefined') turnstile.reset();
      }
    })
    .fail(function () {
      showMsg('Lỗi kết nối. Vui lòng thử lại.', 'error');
      btn.text('🐾 Tạo tài khoản miễn phí').prop('disabled', false);
    });
  });

  $(document).on('click', '#pfResendBtn, #pfResendVerify', function (e) {
    e.preventDefault();
    const uid   = $(this).data('uid') || 0;
    const email = $(this).data('email') || '';
    const $el   = $(this);
    $el.text('⏳ Đang gửi...');

    $.post(pfRegData.ajaxUrl, {
      action: 'pf_resend_verify',
      nonce:  pfRegData.nonce,
      uid,
      email,
    }).done(function (res) {
      alert(res.success
        ? '✅ Đã gửi lại! Kiểm tra hộp thư (kể cả Spam).'
        : '❌ ' + (res.data && res.data.message ? res.data.message : 'Lỗi gửi email.'));
      if (res.success) {
        $el.text($el.is('#pfResendBtn') ? 'gửi lại' : 'gửi lại email xác thực');
      }
    }).fail(function () {
      alert('❌ Lỗi kết nối.');
      $el.text($el.is('#pfResendBtn') ? 'gửi lại' : 'gửi lại email xác thực');
    });
  });

  function showMsg(msg, type) {
    const box = $('#pfRegisterMessage');
    box.text(msg).removeClass('success error').addClass(type).show();
    $('html,body').animate({ scrollTop: box.offset().top - 100 }, 300);
  }

})(jQuery);
