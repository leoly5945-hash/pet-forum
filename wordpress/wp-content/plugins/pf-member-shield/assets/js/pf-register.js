(function($) {
  'use strict';

  $('.pf-toggle-pass').on('click', function() {
    const input = $(this).siblings('input');
    const type  = input.attr('type') === 'password' ? 'text' : 'password';
    input.attr('type', type);
    $(this).text(type === 'password' ? '👁' : '🙈');
  });

  $('#pf_password').on('input', function() {
    const val = $(this).val();
    let score = 0;
    if (val.length >= 8)  score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const colors = ['#ef4444', '#f97316', '#eab308', '#22c55e'];
    const widths = ['25%', '50%', '75%', '100%'];
    const meter  = $('#pfPassStrength')[0];
    if (meter && val.length > 0) {
      meter.style.setProperty('--strength-width', widths[score - 1] || '25%');
      meter.style.setProperty('--strength-color', colors[score - 1] || '#ef4444');
    } else if (meter) {
      meter.style.setProperty('--strength-width', '0%');
    }
  });

  $('#pfRegisterForm').on('submit', function(e) {
    e.preventDefault();

    const email    = $('#pf_email').val().trim();
    const password = $('#pf_password').val();
    const username = $('#pf_username').val().trim();
    const lang     = $('#pf_lang').val();
    const terms    = $('#pf_terms').is(':checked');
    let valid = true;

    $('.pf-field-error').text('');

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      $('#pf_email_error').text(pfRegData.strings.error_email);
      valid = false;
    }

    if (password.length < 8) {
      $('#pf_pass_error').text(pfRegData.strings.error_pass);
      valid = false;
    }

    if (!terms) {
      showMessage('Vui lòng đồng ý với điều khoản sử dụng.', 'error');
      valid = false;
    }

    if (!valid) return;

    const turnstileToken = document.querySelector('[name="cf-turnstile-response"]')?.value || '';
    const btn = $('#pfRegisterBtn');
    btn.text(pfRegData.strings.sending).prop('disabled', true);

    $.post(pfRegData.ajaxUrl, {
      action:   'pf_register',
      nonce:    pfRegData.nonce,
      email:    email,
      password: password,
      username: username,
      lang:     lang,
      cf_token: turnstileToken,
    }, function(res) {
      if (res.success) {
        showMessage(res.data.message, 'success');
        $('#pfRegisterForm')[0].reset();
        btn.text('✅ Kiểm tra email của bạn').prop('disabled', true);
      } else {
        showMessage(res.data.message, 'error');
        btn.text('Tạo tài khoản miễn phí 🐾').prop('disabled', false);
        if (typeof turnstile !== 'undefined') turnstile.reset();
      }
    }).fail(function() {
      showMessage('Lỗi kết nối. Vui lòng thử lại.', 'error');
      btn.text('Tạo tài khoản miễn phí 🐾').prop('disabled', false);
    });
  });

  function showMessage(msg, type) {
    const box = $('#pfRegisterMessage');
    box.text(msg).removeClass('success error').addClass(type).show();
    $('html,body').animate({ scrollTop: box.offset().top - 80 }, 300);
  }

})(jQuery);
