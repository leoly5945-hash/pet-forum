(function () {
	'use strict';

	function loadAdUnit(ins) {
		if (!ins || ins.dataset.pfAdLoaded === '1') {
			return;
		}
		ins.dataset.pfAdLoaded = '1';
		try {
			(window.adsbygoogle = window.adsbygoogle || []).push({});
		} catch (e) {
			/* AdSense not ready */
		}
	}

	function initLazyAds() {
		var units = document.querySelectorAll('ins.pf-ad-lazy-unit:not([data-pf-ad-loaded])');
		if (!units.length) {
			return;
		}

		if (!('IntersectionObserver' in window)) {
			units.forEach(loadAdUnit);
			return;
		}

		var observer = new IntersectionObserver(
			function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						loadAdUnit(entry.target);
						observer.unobserve(entry.target);
					}
				});
			},
			{ rootMargin: '200px 0px', threshold: 0.01 }
		);

		units.forEach(function (unit) {
			observer.observe(unit);
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initLazyAds);
	} else {
		initLazyAds();
	}
})();
