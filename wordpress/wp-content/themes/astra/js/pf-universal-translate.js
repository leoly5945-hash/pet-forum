/**
 * Pet Forum — Universal Translation Layer (wpForo + blog).
 */
class PfUniversalTranslate {
	constructor() {
		this.API_DELAY = 300;
		this.STORAGE_KEY = 'pf_user_lang';

		this.LANGUAGES = {
			original: { flag: '📄', native: 'Gốc / Original', code: '' },
			vi: { flag: '🇻🇳', native: 'Tiếng Việt', code: 'vi' },
			en: { flag: '🇺🇸', native: 'English', code: 'en' },
			ru: { flag: '🇷🇺', native: 'Русский', code: 'ru' },
			'zh-CN': { flag: '🇨🇳', native: '简体中文', code: 'zh-CN' },
			'zh-TW': { flag: '🇹🇼', native: '繁體中文', code: 'zh-TW' },
			ja: { flag: '🇯🇵', native: '日本語', code: 'ja' },
			ko: { flag: '🇰🇷', native: '한국어', code: 'ko' },
			th: { flag: '🇹🇭', native: 'ภาษาไทย', code: 'th' },
			fr: { flag: '🇫🇷', native: 'Français', code: 'fr' },
			de: { flag: '🇩🇪', native: 'Deutsch', code: 'de' },
			es: { flag: '🇪🇸', native: 'Español', code: 'es' },
		};
	}

	getUserLang() {
		return localStorage.getItem(this.STORAGE_KEY) || 'original';
	}

	setUserLang(lang) {
		localStorage.setItem(this.STORAGE_KEY, lang);
	}

	async translate(text, targetLang) {
		if (!text || !targetLang || targetLang === 'original') {
			return text;
		}

		const url =
			'https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl=' +
			encodeURIComponent(targetLang) +
			'&dt=t&q=' +
			encodeURIComponent(text.substring(0, 4500));

		try {
			const response = await fetch(url);
			const data = await response.json();
			if (!data || !data[0]) {
				return null;
			}
			return data[0].map((item) => item[0]).join('');
		} catch (error) {
			return null;
		}
	}

	toggleMenu() {
		const menu = document.getElementById('pfLangMenu');
		if (menu) {
			menu.classList.toggle('open');
		}
	}

	closeMenuOnOutsideClick(event) {
		const wrap = document.getElementById('pfUniversalTranslate');
		if (wrap && !event.target.closest('#pfUniversalTranslate')) {
			const menu = document.getElementById('pfLangMenu');
			if (menu) {
				menu.classList.remove('open');
			}
		}
	}

	updateSwitcherUI(lang) {
		const current = this.LANGUAGES[lang] || this.LANGUAGES.original;
		const flagEl = document.getElementById('pfCurrentFlag');
		const nameEl = document.getElementById('pfCurrentName');

		if (flagEl) {
			flagEl.textContent = current.flag;
		}
		if (nameEl) {
			nameEl.textContent = current.native;
		}

		document.querySelectorAll('.pf-lang-item').forEach((el) => {
			el.classList.toggle('selected', el.dataset.lang === lang);
		});
	}

	async changeLang(lang) {
		this.setUserLang(lang);
		document.getElementById('pfLangMenu').classList.remove('open');

		const current = this.LANGUAGES[lang] || this.LANGUAGES['original'];
		document.getElementById('pfCurrentFlag').textContent = current.flag;
		document.getElementById('pfCurrentName').textContent = current.native;

		document.querySelectorAll('.pf-lang-item').forEach((el, i) => {
			el.classList.toggle('selected', Object.keys(this.LANGUAGES)[i] === lang);
		});

		// Reset tất cả bản dịch cũ
		document.querySelectorAll('.pf-post-translated').forEach((el) => el.remove());
		document.querySelectorAll('.pf-post-original-toggle').forEach((el) => el.remove());
		document.querySelectorAll('.pf-original-text').forEach((el) => el.remove());
		document.querySelectorAll('[data-pf-translated]').forEach((el) => {
			delete el.dataset.pfTranslated;
			// Khôi phục tiêu đề gốc
			if (el.dataset.pfOriginalTitle) {
				el.textContent = el.dataset.pfOriginalTitle;
				el.title = '';
			}
			// Khôi phục excerpt gốc
			if (el.dataset.pfOriginal) {
				el.innerHTML = el.dataset.pfOriginal;
			}
		});
		// Xoá mô tả chuyên mục đã dịch
		document.querySelectorAll('.pf-forum-desc-trans').forEach((el) => el.remove());

		if (lang === 'original') {
			return;
		}
		await this.translateAllPosts(lang);
	}

	async translateAllPosts(targetLang) {
		const langInfo = this.LANGUAGES[targetLang];

		// ── Tất cả selector cần dịch trong wpForo ──────────────────────────
		const SELECTORS = {
			// Nội dung bài viết (trong topic)
			postBody: [
				'.wpforo-post-content',
				'.wpf-post-body',
				'.wpfpb-post-body',
				'.wpforo-body',
				'.wpf-topic-body',
			],
			// Tiêu đề topic trong danh sách forum
			topicTitle: [
				'.wpforo-topic-title a',
				'.wpf-topic-title a',
				'.wpfpb-topic-title a',
				'h2.wpf-topic-title',
				'.wpf-topic-wrap .wpf-title a',
				'.wpforo-last-topic-title a',
				'.wpforo-forum-title a',
			],
			// Mô tả topic (excerpt trong forum list)
			topicExcerpt: [
				'.wpf-topic-excerpt',
				'.wpforo-topic-excerpt',
				'.wpf-last-post-content',
			],
			// Tên và mô tả chuyên mục (forum categories)
			forumDesc: [
				'.wpforo-forum-description',
				'.wpf-forum-description',
				'.wpf-forum-desc',
			],
			forumTitle: [
				'.wpforo-forum-title a',
				'h3.wpforo-forum-title a',
			],
		};

		// Thu thập tất cả elements (ưu tiên mô tả forum → tiêu đề → excerpt → nội dung bài)
		const priority = ['forumDesc', 'forumTitle', 'topicTitle', 'topicExcerpt', 'postBody'];
		const allElements = [];
		const seen = new Set();

		priority.forEach((type) => {
			(SELECTORS[type] || []).forEach((sel) => {
				document.querySelectorAll(sel).forEach((el) => {
					if (!seen.has(el)) {
						seen.add(el);
						el.dataset.pfType = type;
						allElements.push(el);
					}
				});
			});
		});

		if (allElements.length === 0) {
			return;
		}

		// Hiện status bar
		const status = document.getElementById('pfTranslateStatus');
		const statusText = document.getElementById('pfStatusText');
		status.classList.add('show');
		statusText.textContent = `Đang dịch ${allElements.length} nội dung → ${langInfo.flag} ${langInfo.native}...`;

		let done = 0;
		for (const el of allElements) {
			if (el.dataset.pfTranslated === targetLang) {
				done++;
				continue;
			}
			const originalText = el.innerText.trim();
			if (!originalText || originalText.length < 3) {
				done++;
				continue;
			}

			const translated = await this.translate(originalText, targetLang);
			done++;

			statusText.textContent = `${langInfo.flag} Đang dịch... ${done}/${allElements.length}`;

			if (!translated || translated === originalText) {
				continue;
			}

			const type = el.dataset.pfType;

			// ── Render khác nhau theo từng loại ──────────────────────────────

			if (type === 'postBody') {
				// Bài viết đầy đủ: hiện bản dịch nổi bật + toggle xem gốc
				const transDiv = document.createElement('div');
				transDiv.className = 'pf-post-translated';
				transDiv.innerHTML = `<span class="pf-lang-badge">${langInfo.flag} ${langInfo.native}</span><br>${translated}`;

				const toggle = document.createElement('span');
				toggle.className = 'pf-post-original-toggle';
				toggle.textContent = '📄 Xem bản gốc';
				toggle.onclick = function () {
					const orig = this.nextElementSibling;
					orig.classList.toggle('show');
					this.textContent = orig.classList.contains('show') ? '📄 Ẩn bản gốc' : '📄 Xem bản gốc';
				};

				const origDiv = document.createElement('div');
				origDiv.className = 'pf-original-text';
				origDiv.textContent = originalText;

				el.after(origDiv);
				el.after(toggle);
				el.after(transDiv);
			} else if (type === 'topicTitle' || type === 'forumTitle') {
				// Tiêu đề: giữ link, chỉ thay text + tooltip gốc
				el.dataset.pfOriginalTitle = el.textContent;
				el.textContent = translated;
				el.title = `📄 Gốc: ${originalText}`;
				el.style.cursor = 'help';
			} else if (type === 'topicExcerpt') {
				// Excerpt: thay inline, nhỏ gọn
				el.dataset.pfOriginal = el.innerHTML;
				el.innerHTML = `<span style="opacity:0.6;font-size:0.78rem">${langInfo.flag}</span> ${translated}`;
			} else if (type === 'forumDesc') {
				// Mô tả chuyên mục: thêm bản dịch nhỏ bên dưới
				if (!el.dataset.pfTranslated) {
					const descTrans = document.createElement('div');
					descTrans.className = 'pf-forum-desc-trans';
					descTrans.style.cssText = 'font-size:0.82rem;color:#64748b;margin-top:4px;';
					descTrans.innerHTML = `${langInfo.flag} ${translated}`;
					el.appendChild(descTrans);
				}
			}

			el.dataset.pfTranslated = targetLang;
			await new Promise((r) => setTimeout(r, this.API_DELAY));
		}

		statusText.textContent = `✅ Đã dịch xong → ${langInfo.flag} ${langInfo.native}`;
		setTimeout(() => status.classList.remove('show'), 2500);
	}

	// ── Bilingual Reply Toolbar ───────────────────────────────────────────
	renderReplyToolbar() {
		const replyContainerSelectors = [
			'form.wpforo-main-form',
			'form.wpforoeditor',
			'.wpf-post-create form',
			'#wpforo-reply-form',
			'.wpforo-reply-form',
			'form[id*="wpforo"]',
			'form[class*="wpforo"]',
			'#new-post',
			'.wpf-reply-form',
			'#wpforo-add-post-form',
		];

		let replyForm = null;
		for (const sel of replyContainerSelectors) {
			replyForm = document.querySelector(sel);
			if (replyForm) {
				break;
			}
		}

		if (!replyForm) {
			document.querySelectorAll('form').forEach((form) => {
				const btn = form.querySelector('button, input[type="submit"]');
				const label = (btn ? btn.textContent + (btn.value || '') : '') + form.className;
				if (btn && /reply|trả lời|submit|đăng/i.test(label)) {
					replyForm = form;
				}
			});
		}

		if (!replyForm) {
			return false;
		}

		if (replyForm.querySelector('#pfBilingualToolbar')) {
			return true;
		}

		const langOptions = Object.entries(this.LANGUAGES)
			.filter(([k]) => k !== 'original')
			.map(([code, lang]) => `<option value="${code}">${lang.flag} ${lang.native}</option>`)
			.join('');

		const toolbar = document.createElement('div');
		toolbar.id = 'pfBilingualToolbar';
		toolbar.innerHTML = `
			<label>🌐 Tự động dịch reply sang:</label>
			<select id="pfTargetLangSelect">${langOptions}</select>
			<button id="pfAddTransBtn" type="button">✨ Thêm bản dịch</button>
			<div class="pf-hint">Viết reply bình thường → nhấn nút → bản dịch tự ghép vào cuối bài</div>
			<div id="pfTransResult"></div>
		`;

		replyForm.insertBefore(toolbar, replyForm.firstChild);

		document.getElementById('pfAddTransBtn').onclick = () => this.addTranslationToReply();
		return true;
	}

	async addTranslationToReply() {
		const targetLang = document.getElementById('pfTargetLangSelect').value;
		const langInfo = this.LANGUAGES[targetLang];
		const btn = document.getElementById('pfAddTransBtn');
		const result = document.getElementById('pfTransResult');

		let content = '';
		let editorType = '';

		if (typeof tinymce !== 'undefined' && tinymce.editors && tinymce.editors.length > 0) {
			const editor = tinymce.editors.find((ed) => ed.id && ed.id.indexOf('wpforo') !== -1) || tinymce.editors[0];
			content = editor.getContent({ format: 'text' }).trim();
			editorType = 'tinymce';
		}

		if (!content) {
			const iframe = document.querySelector('.wpforo-editor iframe, #wpforo-editor iframe, iframe[id*="mce"]');
			if (iframe) {
				try {
					content = iframe.contentDocument.body.innerText.trim();
					editorType = 'iframe';
				} catch (e) {
					// Cross-origin or not ready.
				}
			}
		}

		if (!content) {
			const editable = document.querySelector('.wpforo-main-form [contenteditable="true"], [contenteditable="true"]:not([class*="title"])');
			if (editable) {
				content = editable.innerText.trim();
				editorType = 'contenteditable';
			}
		}

		if (!content) {
			const ta = document.querySelector(
				'textarea[name="post_content"], textarea[id*="content"], textarea[name*="body"], textarea.wpforo-editor-area'
			);
			if (ta) {
				content = ta.value.trim();
				editorType = 'textarea';
			}
		}

		if (!content || content === 'p') {
			result.innerHTML = '⚠️ Vui lòng viết nội dung reply trước khi dịch!';
			result.classList.add('show');
			result.style.background = '#fef2f2';
			result.style.borderColor = '#fca5a5';
			return;
		}

		btn.textContent = '⏳ Đang dịch...';
		btn.disabled = true;
		result.classList.remove('show');

		try {
			const translated = await this.translate(content, targetLang);
			if (!translated) {
				throw new Error('translate failed');
			}

			const separator = '\n\n---\n';
			const transBlock = `[${langInfo.flag} ${langInfo.native} - Auto Translation]\n${translated}`;
			const finalContent = content + separator + transBlock;

			if (editorType === 'tinymce' && typeof tinymce !== 'undefined') {
				const editor = tinymce.editors.find((ed) => ed.id && ed.id.indexOf('wpforo') !== -1) || tinymce.editors[0];
				const currentHTML = editor.getContent();
				editor.setContent(
					currentHTML +
						'<hr style="border:none;border-top:1px dashed #e2e8f0;margin:16px 0">' +
						`<p><strong>${langInfo.flag} ${langInfo.native} (Auto Translation)</strong></p>` +
						'<p>' + translated.replace(/\n/g, '</p><p>') + '</p>'
				);
			} else if (editorType === 'contenteditable') {
				const editable = document.querySelector('.wpforo-main-form [contenteditable="true"], [contenteditable="true"]:not([class*="title"])');
				editable.innerHTML +=
					`<br><hr><strong>${langInfo.flag} ${langInfo.native}</strong><br>${translated.replace(/\n/g, '<br>')}`;
			} else if (editorType === 'textarea') {
				const ta = document.querySelector('textarea[name="post_content"], textarea[id*="content"], textarea.wpforo-editor-area');
				ta.value = finalContent;
			}

			result.innerHTML = `✅ Đã thêm bản dịch <strong>${langInfo.flag} ${langInfo.native}</strong> vào cuối reply!`;
			result.style.background = '#f0fdf4';
			result.style.borderColor = '#86efac';
			result.classList.add('show');
			btn.textContent = '✅ Đã dịch!';
			btn.style.background = '#16a34a';
			setTimeout(() => {
				btn.textContent = '✨ Thêm bản dịch';
				btn.style.background = '';
				btn.disabled = false;
			}, 3000);
		} catch (e) {
			result.innerHTML = '❌ Lỗi dịch. Kiểm tra kết nối internet.';
			result.classList.add('show');
			btn.textContent = '✨ Thêm bản dịch';
			btn.disabled = false;
		}
	}

	scheduleReplyToolbarInject() {
		let attempts = 0;
		const tryInject = () => {
			const ok = this.renderReplyToolbar();
			if (!ok && attempts < 5) {
				attempts++;
				setTimeout(tryInject, 800 * attempts);
			}
		};
		setTimeout(tryInject, 500);
	}

	async init() {
		this.updateSwitcherUI(this.getUserLang());

		document.querySelectorAll('.pf-lang-item').forEach((el) => {
			el.addEventListener('click', (event) => {
				event.preventDefault();
				this.changeLang(el.dataset.lang);
			});
		});

		document.addEventListener('click', (event) => this.closeMenuOnOutsideClick(event));

		const lang = this.getUserLang();
		if (lang && lang !== 'original') {
			await this.translateAllPosts(lang);
		}

		this.scheduleReplyToolbarInject();
	}
}

window.PfTranslate = new PfUniversalTranslate();

document.addEventListener('DOMContentLoaded', () => {
	window.PfTranslate.init();
});

// wpForo AJAX navigation
document.addEventListener('wpforo_ajax_success', () => {
	const lang = window.PfTranslate.getUserLang();
	if (lang && lang !== 'original') {
		window.PfTranslate.translateAllPosts(lang);
	}
	window.PfTranslate.scheduleReplyToolbarInject();
});
