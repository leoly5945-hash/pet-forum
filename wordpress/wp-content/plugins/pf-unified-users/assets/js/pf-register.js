(function ($) {
  'use strict';

  $(document).on('click', '.pf-select-type-btn', function () {
    var target = $(this).data('target');
    $('#pfTypeSelector').slideUp(250, function () {
      $('#pfForm' + (target === 'member' ? 'Member' : 'Vet')).slideDown(300);
    });
  });

  $(document).on('click', '#pfBackMember, #pfBackVet', function () {
    $('.pf-form-section').slideUp(200, function () {
      $('#pfTypeSelector').slideDown(300);
    });
  });

  $(document).on('click', '.pf-eye', function () {
    var input = $(this).siblings('input');
    var type = input.attr('type') === 'password' ? 'text' : 'password';
    input.attr('type', type);
    $(this).text(type === 'password' ? '👁' : '🙈');
  });

  $(document).on('input', 'input[name="password"]', function () {
    var val = $(this).val();
    var score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    var colors = ['#ef4444', '#f97316', '#eab308', '#22c55e'];
    var widths = ['25%', '50%', '75%', '100%'];
    var fill = $(this).closest('.pf-field').find('.pf-strength-fill');
    fill.css({ width: val.length ? (widths[score - 1] || '25%') : '0%', background: colors[score - 1] || '#ef4444' });
  });

  var zone = document.getElementById('pfUploadZone');
  if (zone) {
    zone.addEventListener('dragover', function (e) { e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', function () { zone.classList.remove('dragover'); });
    zone.addEventListener('drop', function (e) {
      e.preventDefault();
      zone.classList.remove('dragover');
      if (e.dataTransfer.files[0]) handleFileSelect(e.dataTransfer.files[0]);
    });
  }

  $(document).on('change', '#pfCertFile', function () {
    if (this.files[0]) handleFileSelect(this.files[0]);
  });

  $(document).on('click', '#pfRemoveFile', function (e) {
    e.stopPropagation();
    $('#pfCertFile').val('');
    $('#pfUploadPreview').hide();
    $('#pfUploadZone .pf-upload-ui').show();
  });

  function handleFileSelect(file) {
    var allowed = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
    if (allowed.indexOf(file.type) === -1) { alert(pfRegSplit.strings.file_type); return; }
    if (file.size > 5 * 1024 * 1024) { alert(pfRegSplit.strings.file_too_large); return; }
    $('#pfFileName').text(file.name + ' (' + (file.size / 1024).toFixed(0) + 'KB)');
    $('#pfUploadZone .pf-upload-ui').hide();
    $('#pfUploadPreview').show();
    var dt = new DataTransfer();
    dt.items.add(file);
    document.getElementById('pfCertFile').files = dt.files;
  }

  $('#pfMemberForm').on('submit', function (e) {
    e.preventDefault();
    var btn = $('#pfMemberSubmit');
    var msg = $('#pfMemberMsg');
    btn.text(pfRegSplit.strings.registering).prop('disabled', true);
    msg.hide();
    var data = new FormData(this);
    data.append('action', 'pf_register_member');
    data.append('nonce', pfRegSplit.nonce);
    $.ajax({ url: pfRegSplit.ajaxUrl, type: 'POST', data: data, processData: false, contentType: false })
      .done(function (res) {
        if (res.success) {
          $('#pfMemberForm').slideUp(300);
          $('#pfMemberSuccessMsg').html(res.data.message);
          $('#pfMemberSuccess').slideDown(300);
        } else {
          msg.html(res.data.message).removeClass('success').addClass('error').show();
          btn.text('🐾 Tạo tài khoản ngay').prop('disabled', false);
        }
      })
      .fail(function () {
        msg.html('Lỗi kết nối. Vui lòng thử lại.').addClass('error').show();
        btn.text('🐾 Tạo tài khoản ngay').prop('disabled', false);
      });
  });

  $('#pfVetForm').on('submit', function (e) {
    e.preventDefault();
    if (!$('#pfCertFile')[0].files.length) {
      $('#pfVetMsg').html('Vui lòng tải lên bằng cấp / chứng chỉ.').addClass('error').show();
      return;
    }
    var btn = $('#pfVetSubmit');
    var msg = $('#pfVetMsg');
    btn.text(pfRegSplit.strings.uploading).prop('disabled', true);
    msg.hide();
    var data = new FormData(this);
    data.append('action', 'pf_register_vet');
    data.append('nonce', pfRegSplit.nonce);
    $.ajax({
      url: pfRegSplit.ajaxUrl, type: 'POST', data: data, processData: false, contentType: false,
      xhr: function () {
        var xhr = new XMLHttpRequest();
        xhr.upload.addEventListener('progress', function (ev) {
          if (ev.lengthComputable) {
            btn.text('⏳ Đang tải lên... ' + Math.round(ev.loaded / ev.total * 100) + '%');
          }
        });
        return xhr;
      }
    }).done(function (res) {
      if (res.success) {
        $('#pfVetForm').slideUp(300);
        $('#pfVetSuccess').slideDown(300);
      } else {
        msg.html(res.data.message).removeClass('success').addClass('error').show();
        btn.text('🩺 Gửi hồ sơ để xác minh').prop('disabled', false);
      }
    }).fail(function () {
      msg.html('Lỗi kết nối. Vui lòng thử lại.').addClass('error').show();
      btn.text('🩺 Gửi hồ sơ để xác minh').prop('disabled', false);
    });
  });

})(jQuery);
