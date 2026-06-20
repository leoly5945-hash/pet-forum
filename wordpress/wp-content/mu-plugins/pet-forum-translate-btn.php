<?php
/**
 * Nút "Dịch bài này" cho wpForo posts + blog single.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_footer',
	static function () {
		if ( is_admin() ) {
			return;
		}
		?>
		<style>
		.pf-translate-wrap {
			margin-top: 12px;
			padding-top: 12px;
			border-top: 1px solid #f1f5f9;
			display: flex;
			align-items: center;
			gap: 8px;
			flex-wrap: wrap;
		}
		.pf-translate-btn {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			padding: 5px 14px;
			border: 1px solid #e2e8f0;
			border-radius: 16px;
			font-size: 0.8rem;
			color: #475569;
			background: #f8fafc;
			cursor: pointer;
			transition: all 0.2s;
		}
		.pf-translate-btn:hover { border-color: #0ea5e9; color: #0ea5e9; background: #f0f9ff; }
		.pf-translate-btn.active { background: #0ea5e9; color: #fff; border-color: #0ea5e9; }
		.pf-lang-chips { display: flex; gap: 6px; flex-wrap: wrap; }
		.pf-lang-chip {
			padding: 4px 10px;
			border-radius: 12px;
			border: 1px solid #e2e8f0;
			font-size: 0.75rem;
			cursor: pointer;
			color: #64748b;
			background: #fff;
			transition: all 0.15s;
		}
		.pf-lang-chip:hover { border-color: #0ea5e9; color: #0ea5e9; }
		.pf-translated-content {
			margin-top: 12px;
			padding: 14px 16px;
			background: #f0f9ff;
			border-left: 3px solid #0ea5e9;
			border-radius: 0 8px 8px 0;
			font-size: 0.92rem;
			color: #1e293b;
			display: none;
			line-height: 1.6;
		}
		.pf-translated-content.show { display: block; }
		.pf-translate-loading { color: #94a3b8; font-style: italic; font-size: 0.85rem; }
		</style>
		<script>
		document.addEventListener('DOMContentLoaded', function() {
			var selectors = [
				'.wpforo-post-content',
				'.wpfpb-post-body',
				'.wpf-post-body',
				'.entry-content'
			];
			var posts = document.querySelectorAll(selectors.join(', '));

			posts.forEach(function(post, idx) {
				if (post.closest('.pf-translate-wrap') || post.dataset.pfTranslate) return;
				post.dataset.pfTranslate = '1';

				var postId = 'pfpost_' + idx;
				var originalText = post.innerText.trim();
				if (!originalText || originalText.length < 20) return;

				var langs = [
					{code:'vi', label:'🇻🇳 Tiếng Việt'},
					{code:'en', label:'🇺🇸 English'},
					{code:'zh-CN', label:'🇨🇳 中文'},
					{code:'ja', label:'🇯🇵 日本語'},
					{code:'ko', label:'🇰🇷 한국어'}
				];

				var wrap = document.createElement('div');
				wrap.className = 'pf-translate-wrap';

				var btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'pf-translate-btn';
				btn.innerHTML = '🌐 Dịch bài này';
				btn.setAttribute('aria-label', 'Translate this post');

				var chips = document.createElement('div');
				chips.className = 'pf-lang-chips';
				chips.style.display = 'none';

				var result = document.createElement('div');
				result.className = 'pf-translated-content';
				result.id = postId + '_result';

				langs.forEach(function(lang) {
					var chip = document.createElement('span');
					chip.className = 'pf-lang-chip';
					chip.textContent = lang.label;
					chip.onclick = function() {
						result.innerHTML = '<span class="pf-translate-loading">⏳ Đang dịch...</span>';
						result.classList.add('show');
						translateText(originalText, lang.code, function(translated) {
							result.innerHTML = '<strong>' + lang.label + ':</strong><br>' + translated;
						});
					};
					chips.appendChild(chip);
				});

				btn.onclick = function() {
					var isOpen = chips.style.display === 'flex';
					chips.style.display = isOpen ? 'none' : 'flex';
					btn.classList.toggle('active', !isOpen);
					if (isOpen) { result.classList.remove('show'); }
				};

				wrap.appendChild(btn);
				wrap.appendChild(chips);
				post.parentNode.insertBefore(result, post.nextSibling);
				post.parentNode.insertBefore(wrap, result);
			});
		});

		function translateText(text, targetLang, callback) {
			var url = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl='
				+ targetLang + '&dt=t&q=' + encodeURIComponent(text.substring(0, 500));
			fetch(url)
				.then(function(r) { return r.json(); })
				.then(function(data) {
					var translated = data[0].map(function(item) { return item[0]; }).join('');
					callback(translated);
				})
				.catch(function() {
					callback('⚠️ Không thể dịch. Vui lòng thử lại sau.');
				});
		}
		</script>
		<?php
	},
	99
);
