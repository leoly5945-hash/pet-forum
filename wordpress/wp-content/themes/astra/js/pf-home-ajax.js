(function ($) {
	'use strict';

	function loadSidebar() {
		const $sidebar = $('#pfCommunitySidebar');
		if (!$sidebar.length || $sidebar.data('loaded') === true) {
			return;
		}

		$.post(pfHomeData.ajaxUrl, {
			action: 'pf_load_sidebar',
			nonce: pfHomeData.nonce,
		}).done(function (res) {
			if (res.success && res.data.html) {
				$sidebar.html(res.data.html).attr('data-loaded', 'true');
			}
		});
	}

	$(document).ready(function () {
		setTimeout(loadSidebar, 500);

		$('#pfLoadMore').on('click', function () {
			const $btn = $(this);
			const page = parseInt($btn.data('page'), 10) || 2;

			$btn.text('⏳ Đang tải...').prop('disabled', true);

			$.post(pfHomeData.ajaxUrl, {
				action: 'pf_load_more_news',
				nonce: pfHomeData.nonce,
				page: page,
			}).done(function (res) {
				if (res.success && res.data.html) {
					$('#pfNewsGrid').append(res.data.html);
					$btn.data('page', page + 1).text('⬇ Xem thêm tin tức').prop('disabled', false);
					if (!res.data.has_more) {
						$btn.hide();
					}
				} else {
					$btn.text('Hết rồi 🎉').prop('disabled', true);
				}
			}).fail(function () {
				$btn.text('⬇ Xem thêm tin tức').prop('disabled', false);
			});
		});
	});
})(jQuery);
