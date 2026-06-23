var pfMC = (function () {
	'use strict';

	var pendingCallback = null;

	function ajax(action, data, onSuccess) {
		data.action = action;
		data._ajax_nonce = pfMC.nonce;

		fetch(pfMC.ajaxUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams(data),
		})
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (res.success) {
					showToast('✅ ' + res.data.message, 'success');
					if (onSuccess) {
						onSuccess(res);
					}
				} else {
					showToast('❌ ' + (res.data && res.data.message ? res.data.message : 'Lỗi'), 'error');
				}
			})
			.catch(function () {
				showToast('❌ Lỗi kết nối', 'error');
			});
	}

	function showToast(msg, type) {
		var t = document.createElement('div');
		t.className = 'pf-mc-toast pf-mc-toast-' + (type || 'info');
		t.textContent = msg;
		document.body.appendChild(t);
		setTimeout(function () { t.classList.add('show'); }, 10);
		setTimeout(function () {
			t.classList.remove('show');
			setTimeout(function () { t.remove(); }, 300);
		}, 3000);
	}

	function openReason(title, callback) {
		document.getElementById('pf-mc-reason-title').textContent = title;
		document.getElementById('pf-mc-reason-input').value = '';
		document.getElementById('pf-mc-reason-modal').style.display = 'block';
		document.getElementById('pf-mc-reason-input').focus();
		pendingCallback = callback;
	}

	function closeReason() {
		document.getElementById('pf-mc-reason-modal').style.display = 'none';
		pendingCallback = null;
	}

	function bindModal() {
		var confirmBtn = document.getElementById('pf-mc-reason-confirm');
		if (confirmBtn) {
			confirmBtn.addEventListener('click', function () {
				var reason = document.getElementById('pf-mc-reason-input').value.trim();
				if (!reason) {
					alert('Vui lòng nhập lý do.');
					return;
				}
				document.getElementById('pf-mc-reason-modal').style.display = 'none';
				if (typeof pendingCallback === 'function') {
					var cb = pendingCallback;
					pendingCallback = null;
					cb(reason);
				}
			});
		}

		var modal = document.getElementById('pf-mc-reason-modal');
		if (modal) {
			modal.addEventListener('click', function (e) {
				if (e.target.classList.contains('pf-mc-reason-overlay')) {
					closeReason();
				}
			});
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', bindModal);
	} else {
		bindModal();
	}

	return {
		approvePost: function (pid) {
			if (!confirm('Duyệt bài #' + pid + '?')) {
				return;
			}
			ajax('pf_mc_approve_post', { post_id: pid }, function () {
				var card = document.getElementById('post-card-' + pid);
				if (card) {
					card.style.opacity = '0';
					setTimeout(function () { card.remove(); }, 300);
				}
			});
		},

		deletePost: function (pid) {
			openReason('🗑 Lý do xóa bài', function (reason) {
				ajax('pf_mc_delete_post', { post_id: pid, reason: reason }, function () {
					var card = document.getElementById('post-card-' + pid);
					if (card) {
						card.style.opacity = '0';
						setTimeout(function () { card.remove(); }, 300);
					}
				});
			});
		},

		warnUser: function (uid, level) {
			var levelNames = { 1: 'Cảnh báo', 2: 'Cảnh cáo', 3: 'Hạn chế đăng bài', 4: 'Khóa tài khoản' };
			openReason('⚠️ Lý do — ' + (levelNames[level] || 'Cảnh báo'), function (reason) {
				ajax('pf_mc_warn_user', { user_id: uid, level: level, reason: reason }, function () {
					location.reload();
				});
			});
		},

		unpinTopic: function (tid) {
			if (!confirm('Bỏ ghim topic #' + tid + '?')) {
				return;
			}
			ajax('pf_mc_unpin_topic', { topic_id: tid }, function () {
				var card = document.getElementById('topic-card-' + tid);
				if (card) {
					var tag = card.querySelector('.pf-mc-tag-pin');
					if (tag) {
						tag.remove();
					}
				}
			});
		},

		unlockTopic: function (tid) {
			if (!confirm('Mở khóa topic #' + tid + '?')) {
				return;
			}
			ajax('pf_mc_unlock_topic', { topic_id: tid }, function () {
				var card = document.getElementById('topic-card-' + tid);
				if (card) {
					var tag = card.querySelector('.pf-mc-tag-lock');
					if (tag) {
						tag.remove();
					}
				}
			});
		},

		closeReason: closeReason,
	};
})();
