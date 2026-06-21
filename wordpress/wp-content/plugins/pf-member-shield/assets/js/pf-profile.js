(function ($) {
  'use strict';

  $(document).on('change', '.pf-toggle input[type="radio"]', function () {
    const group = $(this).closest('.pf-toggle-group');
    group.find('.pf-toggle').removeClass('active');
    $(this).closest('.pf-toggle').addClass('active');
  });

  $(document).on('input', 'input[name="pf_phone"], input[name="data[pf_phone]"]', function () {
    let val = $(this).val().replace(/[^\d\+\-\(\)\s]/g, '');
    if (val !== $(this).val()) $(this).val(val);

    const digits = val.replace(/\D/g, '');
    const hint = $(this).siblings('.pf-phone-hint');
    if (!hint.length) {
      $(this).after('<span class="pf-phone-hint" style="font-size:0.75rem;display:block;margin-top:4px"></span>');
    }
    const $hint = $(this).siblings('.pf-phone-hint');
    if (digits.length > 0 && (digits.length < 7 || digits.length > 15)) {
      $hint.text('⚠️ Số điện thoại phải có 7-15 chữ số').css('color', '#ef4444');
    } else if (digits.length >= 7) {
      $hint.text('✓ Hợp lệ').css('color', '#22c55e');
    } else {
      $hint.text('');
    }
  });

  $(document).ready(function () {
    $('input[name="pf_phone"], input[name="data[pf_phone]"]').each(function () {
      if (!$(this).siblings('.pf-phone-hint').length) {
        $(this).after('<span class="pf-phone-hint" style="font-size:0.75rem;display:block;margin-top:4px"></span>');
      }
    });
  });

  $(document).on('change', 'select[name="pf_country"], select[name="data[pf_country]"]', function () {
    if ($(this).val()) {
      $(this).css('border-color', '#f97316');
      setTimeout(() => $(this).css('border-color', ''), 1500);
    }
  });

})(jQuery);
