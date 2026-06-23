function pfPlayVideo(previewEl) {
	var wrap = previewEl.parentElement;
	var embedUrl = wrap.dataset.embed;
	if (!embedUrl) {
		return;
	}

	var iframeWrap = document.createElement('div');
	iframeWrap.className = 'pf-video-iframe-wrap';

	var iframe = document.createElement('iframe');
	iframe.src = embedUrl + (embedUrl.indexOf('?') >= 0 ? '&' : '?') + 'autoplay=1';
	iframe.setAttribute('allow', 'autoplay; fullscreen; picture-in-picture');
	iframe.loading = 'lazy';

	iframeWrap.appendChild(iframe);
	previewEl.replaceWith(iframeWrap);
}

(function ($) {
	'use strict';

	var timer;

	function isAllowedVideoUrl(url) {
		var allowed = (typeof pfVideo !== 'undefined' && pfVideo.allowedDomains) ? pfVideo.allowedDomains : [];
		try {
			var host = new URL(url).hostname.replace(/^www\./, '');
			return allowed.some(function (d) {
				return host === d || host.slice(-(d.length + 1)) === '.' + d;
			});
		} catch (e) {
			return false;
		}
	}

	function detectVideoInput(textarea) {
		textarea.addEventListener('input', function () {
			clearTimeout(timer);
			timer = setTimeout(function () {
				if (typeof pfVideo === 'undefined') {
					return;
				}

				var val = textarea.value;
				var lines = val.split('\n');
				var last = lines[lines.length - 1].trim();

				if (!last || last.indexOf('http') !== 0) {
					return;
				}

				if (!isAllowedVideoUrl(last)) {
					return;
				}

				$.post(pfVideo.ajaxUrl, {
					action: 'pf_preview_video',
					nonce: pfVideo.nonce,
					url: last,
				}).done(function (res) {
					if (!res.success || !res.data.html) {
						return;
					}

					var existing = document.getElementById('pf-video-preview-box');
					if (existing) {
						existing.remove();
					}

					var box = document.createElement('div');
					box.id = 'pf-video-preview-box';
					box.innerHTML = '<p style="font-size:12px;color:#6b7c6e;margin-bottom:8px">'
						+ (pfVideo.strings.preview || 'Xem trước video') + ':</p>' + res.data.html;
					textarea.parentElement.appendChild(box);
				});
			}, 600);
		});
	}

	$(document).ready(function () {
		document.querySelectorAll(
			'#wpforo-post-content, .wpforo-textarea, textarea[name="body"]'
		).forEach(detectVideoInput);
	});
})(jQuery);
