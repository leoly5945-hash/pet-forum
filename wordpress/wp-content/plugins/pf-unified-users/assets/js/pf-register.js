(function ($) {
  'use strict';

  var cfg = window.pfReg || window.pfRegSplit || {};
  var s = cfg.strings || {};

  function getLang() {
    return cfg.lang || (document.cookie.match(/pf_lang=([^;]+)/) || [])[1] || 'vi';
  }

  function applyLangVisibility() {
    var lang = getLang();
    document.querySelectorAll('.pf-lang-vi').forEach(function (el) {
      el.style.display = lang === 'en' ? 'none' : '';
    });
    document.querySelectorAll('.pf-lang-en').forEach(function (el) {
      el.style.display = lang === 'en' ? '' : 'none';
    });
  }

  function memberSubmitEnabled() {
    var cb = document.getElementById('pf_agree_terms');
    return cb && cb.checked;
  }

  function vetSubmitEnabled() {
    var general = document.getElementById('pf_agree_terms');
    var vet = document.getElementById('pf_agree_vet_terms');
    var cred = document.querySelector('input[name="vet_credential_confirm"]');
    return general && general.checked
      && vet && vet.checked
      && (!cred || cred.checked);
  }

  function updateSubmitStates() {
    var memberBtn = document.getElementById('pf-submit-member');
    var vetBtn = document.getElementById('pf-submit-vet');
    if (memberBtn) memberBtn.disabled = !memberSubmitEnabled();
    if (vetBtn) vetBtn.disabled = !vetSubmitEnabled();
  }

  $(document).ready(function () {
    applyLangVisibility();
    updateSubmitStates();

    var cred = document.querySelector('input[name="vet_credential_confirm"]');
    if (cred) cred.addEventListener('change', updateSubmitStates);
  });

  // ══ TERMS MODAL SYSTEM ══════════════════════════════════════════════
  (function initTermsModal() {
    var overlay = document.getElementById('pf-terms-modal-overlay');
    var body = document.getElementById('ptm-body');
    var agreeBtn = document.getElementById('ptm-agree-btn');
    var closeBtn = document.getElementById('ptm-close');
    var progress = document.getElementById('ptm-progress');
    var progLabel = document.getElementById('ptm-progress-label');
    var scrollHint = document.getElementById('ptm-scroll-hint');
    var readNote = document.getElementById('ptm-read-note');
    var modalTitle = document.getElementById('pf-modal-title');
    var modalIcon = document.getElementById('ptm-icon');

    if (!overlay || !body || !agreeBtn) return;

    var currentTarget = null;
    var currentIcons = {};

    var termsURLs = {
      general: cfg.termsUrl || '/terms/',
      vet: cfg.termsVetUrl || '/terms-for-vets/'
    };

    var modalMeta = {
      general: { icon: '📄', titleVi: 'Điều khoản Sử dụng', titleEn: 'Terms of Service' },
      vet: { icon: '🩺', titleVi: 'Điều khoản Bác sĩ Thú y', titleEn: 'Veterinary Expert Terms' }
    };

    function resetAgreeButton() {
      agreeBtn.classList.remove('ready');
      agreeBtn.disabled = true;
      if (readNote) {
        readNote.classList.remove('ready');
        readNote.innerHTML = getLang() === 'en'
          ? '<span>Read to the end to enable the agree button</span>'
          : '<span>Đọc đến cuối để kích hoạt nút đồng ý</span>';
      }
      if (scrollHint) scrollHint.classList.remove('hidden');
      if (progress) progress.style.setProperty('--pct', '0%');
      if (progLabel) progLabel.textContent = '0%';
    }

    function openModal(termsType, targetCheckboxId, icons) {
      var lang = getLang();
      currentTarget = targetCheckboxId;
      currentIcons = icons || {};

      var meta = modalMeta[termsType] || modalMeta.general;
      modalIcon.textContent = meta.icon;
      modalTitle.textContent = lang === 'en' ? meta.titleEn : meta.titleVi;

      body.innerHTML = '<div class="ptm-loading">⏳ ' + (lang === 'en' ? 'Loading...' : 'Đang tải...') + '</div>';
      resetAgreeButton();

      overlay.style.display = 'flex';
      document.body.style.overflow = 'hidden';

      var url = termsURLs[termsType] || termsURLs.general;
      fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.text(); })
        .then(function (html) {
          var parser = new DOMParser();
          var doc = parser.parseFromString(html, 'text/html');
          var content = doc.querySelector('.pf-terms-wrapper')
            || doc.querySelector('.entry-content')
            || doc.querySelector('main');

          if (content) {
            var clone = content.cloneNode(true);
            clone.querySelectorAll('script, style, .pf-terms-lang-switcher, .pf-terms-header, .pf-terms-footer').forEach(function (el) {
              el.remove();
            });

            var viSelectors = ['#pf-terms-vi', '#pft-vi'];
            var enSelectors = ['#pf-terms-en', '#pft-en'];

            if (lang === 'en') {
              viSelectors.forEach(function (sel) {
                var el = clone.querySelector(sel);
                if (el) el.remove();
              });
              enSelectors.forEach(function (sel) {
                var el = clone.querySelector(sel);
                if (el) el.style.display = '';
              });
            } else {
              enSelectors.forEach(function (sel) {
                var el = clone.querySelector(sel);
                if (el) el.remove();
              });
            }

            body.innerHTML = clone.innerHTML;
          } else {
            body.innerHTML = '<p style="color:#dc2626">Không tải được nội dung. <a href="' + url + '" target="_blank" rel="noopener">Mở trang mới →</a></p>';
          }

          body.scrollTop = 0;
          trackScroll();
        })
        .catch(function () {
          body.innerHTML = '<p style="color:#dc2626">Lỗi kết nối. <a href="' + url + '" target="_blank" rel="noopener">Xem trang đầy đủ →</a></p>';
        });
    }

    function enableAgree() {
      agreeBtn.disabled = false;
      agreeBtn.classList.add('ready');
      if (readNote) {
        readNote.classList.add('ready');
        readNote.innerHTML = getLang() === 'en'
          ? '<span>✅ You\'ve read the terms — click to agree</span>'
          : '<span>✅ Bạn đã đọc xong — bấm để đồng ý</span>';
      }
    }

    function onScroll() {
      var scrollTop = body.scrollTop;
      var scrollHeight = body.scrollHeight - body.clientHeight;
      if (scrollHeight <= 0) {
        enableAgree();
        return;
      }

      var pct = Math.min(100, Math.round((scrollTop / scrollHeight) * 100));
      if (progress) progress.style.setProperty('--pct', pct + '%');
      if (progLabel) progLabel.textContent = pct + '%';
      if (pct > 10 && scrollHint) scrollHint.classList.add('hidden');
      if (pct >= 90) enableAgree();
    }

    function trackScroll() {
      body.removeEventListener('scroll', onScroll);
      body.addEventListener('scroll', onScroll);
      onScroll();
    }

    function closeModal() {
      overlay.style.display = 'none';
      document.body.style.overflow = '';
      currentTarget = null;
      currentIcons = {};
      body.removeEventListener('scroll', onScroll);
    }

    agreeBtn.addEventListener('click', function () {
      if (!currentTarget || !agreeBtn.classList.contains('ready')) return;

      var lang = getLang();
      var cb = document.getElementById(currentTarget);
      if (cb) {
        cb.checked = true;
        cb.dispatchEvent(new Event('change'));
      }

      if (currentIcons.icon) {
        var iconEl = document.getElementById(currentIcons.icon);
        if (iconEl) {
          iconEl.textContent = '✅';
          iconEl.classList.remove('pf-terms-unchecked');
          iconEl.classList.add('pf-terms-checked');
        }
      }
      if (currentIcons.status) {
        var stEl = document.getElementById(currentIcons.status);
        if (stEl) {
          stEl.textContent = lang === 'vi' ? '✅ Đã đồng ý' : '✅ Agreed';
          stEl.classList.add('done');
        }
      }
      if (currentIcons.statusEn) {
        var stEnEl = document.getElementById(currentIcons.statusEn);
        if (stEnEl) {
          stEnEl.textContent = '✅ Agreed';
          stEnEl.classList.add('done');
        }
      }

      var readBtn = document.querySelector('.pf-read-terms-btn[data-target="' + currentTarget + '"]');
      if (readBtn) {
        readBtn.classList.add('done');
        readBtn.innerHTML = lang === 'en' ? '<span>✅ Agreed</span>' : '<span>✅ Đã đồng ý</span>';
        readBtn.disabled = true;
      }

      updateSubmitStates();
      closeModal();
    });

    document.querySelectorAll('.pf-read-terms-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (this.disabled || this.classList.contains('done')) return;
        openModal(this.dataset.terms, this.dataset.target, {
          icon: this.dataset.icon || null,
          status: this.dataset.status || null,
          statusEn: this.dataset.statusEn || null
        });
      });
    });

    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) {
        var modal = document.getElementById('pf-terms-modal');
        modal.style.animation = 'none';
        modal.offsetHeight;
        modal.style.animation = 'ptmShake .3s ease';
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && overlay.style.display !== 'none') {
        e.preventDefault();
        e.stopPropagation();
      }
    });
  })();

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
    if (allowed.indexOf(file.type) === -1) { alert(s.file_type || 'Invalid file type'); return; }
    if (file.size > 5 * 1024 * 1024) { alert(s.file_too_large || 'File too large'); return; }
    $('#pfFileName').text(file.name + ' (' + (file.size / 1024).toFixed(0) + 'KB)');
    $('#pfUploadZone .pf-upload-ui').hide();
    $('#pfUploadPreview').show();
    var dt = new DataTransfer();
    dt.items.add(file);
    document.getElementById('pfCertFile').files = dt.files;
  }

  $('#pfMemberForm').on('submit', function (e) {
    e.preventDefault();
    var btn = $('#pf-submit-member');
    var msg = $('#pfMemberMsg');
    var submitLabel = btn.find('.pf-lang-vi').text() || s.submit_member || btn.text();
    btn.prop('disabled', true);
    msg.hide();
    var data = new FormData(this);
    data.append('action', 'pf_register_member');
    data.append('nonce', cfg.nonce);
    $.ajax({ url: cfg.ajaxUrl, type: 'POST', data: data, processData: false, contentType: false })
      .done(function (res) {
        if (res.success) {
          $('#pfMemberForm').slideUp(300);
          $('#pfMemberSuccessMsg').html(res.data.message);
          $('#pfMemberSuccess').slideDown(300);
        } else {
          msg.html(res.data.message).removeClass('success').addClass('error').show();
          btn.prop('disabled', !memberSubmitEnabled());
        }
      })
      .fail(function () {
        msg.html(s.network_error || 'Error').addClass('error').show();
        btn.prop('disabled', !memberSubmitEnabled());
      });
  });

  $('#pfVetForm').on('submit', function (e) {
    e.preventDefault();
    var btn = $('#pf-submit-vet');
    var msg = $('#pfVetMsg');
    var submitLabel = btn.find('.pf-lang-vi').text() || s.submit_vet || btn.text();

    if (!$('#pfCertFile')[0].files.length) {
      msg.html(s.err_cert_required || 'Certificate required').addClass('error').show();
      return;
    }

    btn.prop('disabled', true);
    msg.hide();
    var data = new FormData(this);
    data.append('action', 'pf_register_vet');
    data.append('nonce', cfg.nonce);
    $.ajax({
      url: cfg.ajaxUrl, type: 'POST', data: data, processData: false, contentType: false,
      xhr: function () {
        var xhr = new XMLHttpRequest();
        xhr.upload.addEventListener('progress', function (ev) {
          if (ev.lengthComputable && s.uploading_pct) {
            btn.find('.pf-lang-vi').text(s.uploading_pct.replace('%s', Math.round(ev.loaded / ev.total * 100)));
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
        btn.prop('disabled', !vetSubmitEnabled());
      }
    }).fail(function () {
      msg.html(s.network_error || 'Error').addClass('error').show();
      btn.prop('disabled', !vetSubmitEnabled());
    });
  });

})(jQuery);
