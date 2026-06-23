(function () {
	'use strict';

	var cfg = window.pfChatbotConfig || {};
	var bubble = document.getElementById('pf-chatbot-bubble');
	var panel = document.getElementById('pf-chatbot-panel');
	var messages = document.getElementById('pf-chatbot-messages');
	var input = document.getElementById('pf-chatbot-input');
	var sendBtn = document.getElementById('pf-chatbot-send');
	var typing = document.getElementById('pf-chatbot-typing');
	var convId = localStorage.getItem('pf_chat_conv_id') || '';
	var isOpen = false;

	if (!bubble || !panel || !messages || !input || !sendBtn) {
		return;
	}

	bubble.addEventListener('click', function () {
		isOpen = !isOpen;
		if (isOpen) {
			panel.hidden = false;
			panel.classList.add('pf-chatbot-opening');
			input.focus();
			scrollToBottom();
		} else {
			panel.hidden = true;
		}
	});

	var closeBtn = document.getElementById('pf-chatbot-close');
	if (closeBtn) {
		closeBtn.addEventListener('click', function () {
			isOpen = false;
			panel.hidden = true;
		});
	}

	var clearBtn = document.getElementById('pf-chatbot-clear');
	if (clearBtn) {
		clearBtn.addEventListener('click', function () {
			if (!confirm(cfg.strings && cfg.strings.clearConfirm ? cfg.strings.clearConfirm : 'Clear chat?')) {
				return;
			}
			messages.querySelectorAll('.pf-chatbot-msg-user, .pf-chatbot-msg-bot:not(:first-child)').forEach(function (el) {
				el.remove();
			});
			var quick = messages.querySelector('.pf-chatbot-quick-actions');
			if (!quick) {
				var welcome = messages.querySelector('.pf-chatbot-msg-bot');
				if (welcome && welcome.nextElementSibling) {
					// quick actions were removed earlier
				}
			}
			convId = '';
			localStorage.removeItem('pf_chat_conv_id');
		});
	}

	messages.addEventListener('click', function (e) {
		var btn = e.target.closest('.pf-chatbot-quick');
		if (!btn) {
			return;
		}
		var msg = btn.dataset.msg;
		var actions = btn.closest('.pf-chatbot-quick-actions');
		if (actions) {
			actions.remove();
		}
		sendMessage(msg);
	});

	input.addEventListener('keydown', function (e) {
		if (e.key === 'Enter' && !e.shiftKey) {
			e.preventDefault();
			send();
		}
	});

	input.addEventListener('input', function () {
		this.style.height = 'auto';
		this.style.height = Math.min(this.scrollHeight, 100) + 'px';
	});

	sendBtn.addEventListener('click', send);

	function send() {
		var msg = input.value.trim();
		if (!msg || sendBtn.disabled) {
			return;
		}
		input.value = '';
		input.style.height = 'auto';
		sendMessage(msg);
	}

	function sendMessage(msg) {
		appendMessage('user', msg);
		showTyping(true);
		sendBtn.disabled = true;

		var formData = new FormData();
		formData.append('action', 'pf_ai_chat');
		formData.append('nonce', cfg.nonce || '');
		formData.append('message', msg);
		formData.append('conversation_id', convId);
		formData.append('metadata', JSON.stringify(cfg.metadata || {}));

		fetch(cfg.ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				showTyping(false);
				sendBtn.disabled = false;

				if (!res.success) {
					var errMsg = res.data && res.data.message;
					if (errMsg === 'rate_limit_hour' || errMsg === 'rate_limit_day') {
						appendMessage('bot', (cfg.strings && cfg.strings.errorLimit) || 'Limit reached.');
					} else if (errMsg) {
						appendMessage('bot', errMsg);
					} else {
						appendMessage('bot', (cfg.strings && cfg.strings.errorNetwork) || 'Error.');
					}
					return;
				}

				var data = res.data || {};
				if (data.conversation_id) {
					convId = data.conversation_id;
					localStorage.setItem('pf_chat_conv_id', convId);
				}

				appendMessage('bot', data.answer || (cfg.strings && cfg.strings.errorEmpty) || 'Không nhận được phản hồi. Thử lại sau.');
			})
			.catch(function () {
				showTyping(false);
				sendBtn.disabled = false;
				appendMessage('bot', (cfg.strings && cfg.strings.errorNetwork) || 'Error.');
			});
	}

	function appendMessage(role, text) {
		var wrap = document.createElement('div');
		wrap.className = 'pf-chatbot-msg pf-chatbot-msg-' + role;

		var avatar = document.createElement('span');
		avatar.className = 'pf-chatbot-msg-avatar';
		avatar.textContent = role === 'bot' ? '🌿' : '🐾';

		var content = document.createElement('div');
		content.className = 'pf-chatbot-msg-content';

		var formatted = String(text)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
			.replace(/\*(.+?)\*/g, '<em>$1</em>')
			.replace(/\n/g, '<br>');
		content.innerHTML = formatted;

		wrap.appendChild(avatar);
		wrap.appendChild(content);
		messages.appendChild(wrap);
		scrollToBottom();
	}

	function showTyping(show) {
		if (!typing) {
			return;
		}
		typing.style.display = show ? 'flex' : 'none';
		if (show) {
			scrollToBottom();
		}
	}

	function scrollToBottom() {
		setTimeout(function () {
			messages.scrollTop = messages.scrollHeight;
		}, 50);
	}
})();
