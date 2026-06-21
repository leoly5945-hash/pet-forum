(function ($) {
  'use strict';

  function toast(msg, type) {
    type = type || 'success';
    var el = document.getElementById('pfModToast');
    if (!el) {
      el = document.createElement('div');
      el.id = 'pfModToast';
      document.body.appendChild(el);
    }
    el.textContent = msg;
    el.className = 'show ' + type;
    setTimeout(function () { el.className = ''; }, 3000);
  }

  function modAction(action, data, btn, onSuccess) {
    btn.classList.add('loading');
    $.post(pfModData.ajaxUrl, $.extend({ action: action, nonce: pfModData.nonce }, data))
      .done(function (res) {
        if (res.success) {
          toast(res.data.message, 'success');
          if (onSuccess) onSuccess(res.data);
        } else {
          toast((res.data && res.data.message) || pfModData.strings.error, 'error');
        }
      })
      .fail(function () { toast(pfModData.strings.error, 'error'); })
      .always(function () { btn.classList.remove('loading'); });
  }

  $(document).on('click', '.pf-btn-delete', function () {
    if (!confirm(pfModData.strings.confirm_delete)) return;
    var toolbar = $(this).closest('[data-post-id]');
    modAction('pf_mod_delete_post', {
      id: toolbar.data('post-id'),
      forum_id: toolbar.data('forum-id')
    }, this, function () {
      toolbar.closest('.wpforo-post, .wpf-post, .wpforo-post-wrap').fadeOut(400, function () { $(this).remove(); });
    });
  });

  $(document).on('click', '.pf-btn-approve', function () {
    var toolbar = $(this).closest('[data-post-id]');
    var btn = this;
    modAction('pf_mod_approve_post', {
      id: toolbar.data('post-id'),
      forum_id: toolbar.data('forum-id')
    }, btn, function () {
      $(btn).remove();
    });
  });

  $(document).on('click', '.pf-btn-pin', function () {
    var toolbar = $(this).closest('[data-topic-id]');
    var btn = this;
    modAction('pf_mod_pin_topic', {
      id: toolbar.data('topic-id') || $(this).data('id'),
      forum_id: toolbar.data('forum-id'),
      pinned: $(this).data('pinned')
    }, btn, function (data) {
      var newPinned = data.pinned ? '1' : '0';
      $(btn).data('pinned', newPinned)
        .text(data.pinned ? '📌 Bỏ ghim' : '📌 Ghim')
        .toggleClass('active', data.pinned);
    });
  });

  $(document).on('click', '.pf-btn-lock', function () {
    if (!confirm(pfModData.strings.confirm_lock)) return;
    var toolbar = $(this).closest('[data-topic-id]');
    var btn = this;
    modAction('pf_mod_lock_topic', {
      id: toolbar.data('topic-id') || $(this).data('id'),
      forum_id: toolbar.data('forum-id'),
      locked: $(this).data('locked')
    }, btn, function (data) {
      $(btn).data('locked', data.locked ? '1' : '0')
        .text(data.locked ? '🔓 Mở khóa' : '🔒 Khóa')
        .toggleClass('active', data.locked);
    });
  });

  $(document).on('click', '.pf-btn-ban', function () {
    var name = $(this).data('name') || 'user này';
    if (!confirm(pfModData.strings.confirm_ban + '\nUser: ' + name)) return;
    var toolbar = $(this).closest('[data-post-id]');
    var userId = toolbar.data('user-id') || $(this).data('id');
    modAction('pf_mod_ban_user', {
      id: userId,
      forum_id: toolbar.data('forum-id')
    }, this, function () {
      $('.pf-mod-toolbar[data-user-id="' + userId + '"]').closest('.wpforo-post, .wpf-post').css('opacity', '0.4');
    });
  });

})(jQuery);
